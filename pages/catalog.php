<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('../');

function catalog_poster_url(?string $url): string
{
    $source = (string) $url;
    $highResolution = preg_replace('/\._V1_.*\.jpg$/', '._V1_FMjpg_UX600_.jpg', $source);

    return $highResolution ?: $source;
}

$rarityStyles = [
    'Légendaire' => ['badge' => 'border-amber-400/50 bg-amber-400/15 text-amber-300', 'frame' => 'border-amber-400/45', 'dot' => 'bg-amber-300'],
    'Épique' => ['badge' => 'border-violet-500/45 bg-violet-500/15 text-violet-300', 'frame' => 'border-violet-500/40', 'dot' => 'bg-violet-400'],
    'Rare' => ['badge' => 'border-sky-500/45 bg-sky-500/15 text-sky-300', 'frame' => 'border-sky-500/40', 'dot' => 'bg-sky-400'],
    'Peu commune' => ['badge' => 'border-emerald-500/45 bg-emerald-500/15 text-emerald-300', 'frame' => 'border-emerald-500/35', 'dot' => 'bg-emerald-300'],
    'Commune' => ['badge' => 'border-stone-500/40 bg-stone-500/15 text-stone-200', 'frame' => 'border-stone-500/30', 'dot' => 'bg-stone-300'],
];

$sortOptions = [
    'rarity' => 'Rareté',
    'title' => 'Nom',
    'rating' => 'Note IMDb',
    'newest' => 'Année récente',
];

$search = trim((string) ($_GET['q'] ?? ''));
$rarity = (string) ($_GET['rarity'] ?? '');
$sort = (string) ($_GET['sort'] ?? 'rarity');
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = 48;

if (!array_key_exists($rarity, $rarityStyles)) {
    $rarity = '';
}

if (!array_key_exists($sort, $sortOptions)) {
    $sort = 'rarity';
}

$where = [];
$parameters = [];

if ($search !== '') {
    $where[] = '(m.title LIKE :title_search OR m.genre LIKE :genre_search OR m.director LIKE :director_search)';
    $searchPattern = '%' . $search . '%';
    $parameters['title_search'] = $searchPattern;
    $parameters['genre_search'] = $searchPattern;
    $parameters['director_search'] = $searchPattern;
}

if ($rarity !== '') {
    $where[] = 'm.rarity_level = :rarity';
    $parameters['rarity'] = $rarity;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$orderSql = match ($sort) {
    'title' => 'm.title ASC',
    'rating' => 'm.imdb_rating DESC, m.title ASC',
    'newest' => 'm.released_year DESC, m.title ASC',
    default => "FIELD(m.rarity_level, 'Légendaire', 'Épique', 'Rare', 'Peu commune', 'Commune'), m.rarity_score DESC, m.title ASC",
};

$countStatement = database()->prepare('SELECT COUNT(*) FROM movies m' . $whereSql);
$countStatement->execute($parameters);
$filteredTotal = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($filteredTotal / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;

$catalogStatement = database()->prepare(
    'SELECT
        m.id,
        m.title,
        m.poster_link AS poster,
        m.released_year AS year,
        m.genre,
        m.rarity_level AS rarity,
        m.runtime_minutes,
        m.imdb_rating,
        m.meta_score,
        m.overview,
        m.director,
        m.star1,
        m.star2,
        m.star3,
        m.star4,
        m.number_of_votes,
        m.gross,
        COALESCE(uc.quantity, 0) AS owned_quantity
    FROM movies m
    LEFT JOIN user_cards uc
        ON uc.movie_id = m.id AND uc.user_id = :user_id'
    . $whereSql
    . ' ORDER BY ' . $orderSql
    . ' LIMIT :page_size OFFSET :offset'
);
$catalogStatement->bindValue(':user_id', (int) $_SESSION['user_id'], PDO::PARAM_INT);

foreach ($parameters as $name => $value) {
    $catalogStatement->bindValue(':' . $name, $value);
}

$catalogStatement->bindValue(':page_size', $pageSize, PDO::PARAM_INT);
$catalogStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$catalogStatement->execute();
$movies = $catalogStatement->fetchAll();

$globalTotal = (int) database()->query('SELECT COUNT(*) FROM movies')->fetchColumn();
$rarityCounts = array_fill_keys(array_keys($rarityStyles), 0);
$rarityCountStatement = database()->query(
    'SELECT rarity_level, COUNT(*) AS total
    FROM movies
    WHERE rarity_level IS NOT NULL
    GROUP BY rarity_level'
);

foreach ($rarityCountStatement->fetchAll() as $row) {
    $rarityCounts[$row['rarity_level']] = (int) $row['total'];
}

$catalogUrl = static function (array $overrides = []) use ($search, $rarity, $sort, $page): string {
    $parameters = array_merge([
        'q' => $search,
        'rarity' => $rarity,
        'sort' => $sort,
        'page' => $page,
    ], $overrides);
    $parameters = array_filter($parameters, static fn ($value): bool => $value !== '' && $value !== null && $value !== 1);
    $query = http_build_query($parameters);

    return 'catalog.php' . ($query !== '' ? '?' . $query : '');
};

render_start('Toutes les cartes', 'catalog', '../');
?>
  <main class="flex flex-1 flex-col px-4 pb-16 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-[1600px]">
      <header>
        <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-mint">Catalogue général</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Toutes les cartes</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-500">Explore les <?= number_format($globalTotal, 0, ',', ' ') ?> films disponibles et repère ceux qu'il te manque.</p>
      </header>

      <section class="mt-8 rounded-2xl border border-white/10 bg-panel p-5 sm:p-6" aria-label="Répartition des raretés">
        <div class="flex flex-wrap gap-x-6 gap-y-3">
          <?php foreach ($rarityStyles as $rarityName => $style): ?>
            <div class="flex items-center gap-2">
              <span class="h-2.5 w-2.5 rounded-sm <?= $style['dot'] ?>" aria-hidden="true"></span>
              <span class="text-sm text-stone-500"><?= e($rarityName) ?></span>
              <strong class="text-sm text-stone-200"><?= number_format($rarityCounts[$rarityName], 0, ',', ' ') ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="mt-5 font-mono text-[10px] uppercase tracking-[0.16em] text-stone-600"><?= number_format($globalTotal, 0, ',', ' ') ?> cartes au total</p>
      </section>

      <form method="get" action="catalog.php" class="mt-8 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_12rem]" role="search">
        <label>
          <span class="sr-only">Rechercher toutes les cartes</span>
          <input name="q" type="search" value="<?= e($search) ?>" placeholder="Rechercher par titre, genre ou réalisateur..." class="w-full rounded-xl border border-white/10 bg-[#171918] px-4 py-3 text-sm text-stone-100 outline-none transition placeholder:text-stone-600 focus:border-mint/60 focus:ring-2 focus:ring-mint/10">
        </label>
        <input type="hidden" name="rarity" value="<?= e($rarity) ?>">
        <button type="submit" class="rounded-xl bg-mint px-5 py-3 text-sm font-semibold text-black transition hover:bg-emerald-300">Rechercher</button>
        <label>
          <span class="sr-only">Trier toutes les cartes</span>
          <select name="sort" data-catalog-sort class="w-full rounded-xl border border-mint/35 bg-[#171918] px-4 py-3 text-sm font-semibold text-stone-200 outline-none transition hover:border-mint/60 focus:border-mint focus:ring-2 focus:ring-mint/15">
            <?php foreach ($sortOptions as $value => $label): ?>
              <option class="bg-[#0c0d0d]" value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </form>

      <nav class="mt-4 flex flex-wrap gap-2" aria-label="Filtrer par rareté">
        <a href="<?= e($catalogUrl(['rarity' => '', 'page' => 1])) ?>" class="rounded-full border px-3 py-1.5 text-xs font-medium transition <?= $rarity === '' ? 'border-mint/50 bg-mint/10 text-mint' : 'border-white/10 text-stone-400 hover:border-white/20 hover:text-white' ?>">Toutes</a>
        <?php foreach ($rarityStyles as $rarityName => $style): ?>
          <a href="<?= e($catalogUrl(['rarity' => $rarityName, 'page' => 1])) ?>" class="rounded-full border px-3 py-1.5 text-xs font-medium transition <?= $rarity === $rarityName ? $style['badge'] : 'border-white/10 text-stone-400 hover:border-white/20 hover:text-white' ?>"><?= e($rarityName) ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="mt-7 flex items-center justify-between gap-4 border-b border-white/5 pb-4">
        <p class="text-sm text-stone-500"><strong class="font-semibold text-stone-200"><?= number_format($filteredTotal, 0, ',', ' ') ?></strong> résultat<?= $filteredTotal > 1 ? 's' : '' ?></p>
        <?php if ($totalPages > 1): ?>
          <p class="font-mono text-[10px] uppercase tracking-wider text-stone-600">Page <?= $page ?> / <?= $totalPages ?></p>
        <?php endif; ?>
      </div>

      <?php if ($movies): ?>
        <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6" aria-label="Catalogue des cartes">
          <?php foreach ($movies as $movie): ?>
            <?php
            $style = $rarityStyles[$movie['rarity']] ?? $rarityStyles['Commune'];
            $poster = catalog_poster_url($movie['poster']);
            $movieDetails = json_encode([
                'title' => $movie['title'],
                'poster' => $poster,
                'year' => $movie['year'],
                'genre' => $movie['genre'],
                'rarity' => $movie['rarity'],
                'runtime' => $movie['runtime_minutes'],
                'imdbRating' => $movie['imdb_rating'],
                'metaScore' => $movie['meta_score'],
                'overview' => $movie['overview'],
                'director' => $movie['director'],
                'cast' => array_values(array_filter([$movie['star1'], $movie['star2'], $movie['star3'], $movie['star4']])),
                'votes' => $movie['number_of_votes'],
                'gross' => $movie['gross'],
                'ownedQuantity' => $movie['owned_quantity'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ?>
            <article
              data-catalog-card
              data-movie-details="<?= e($movieDetails ?: '{}') ?>"
              tabindex="0"
              class="group relative cursor-pointer overflow-hidden rounded-2xl border bg-[#111312] outline-none transition duration-200 hover:-translate-y-1 hover:border-white/25 hover:shadow-2xl focus-visible:ring-2 focus-visible:ring-mint/70 <?= $style['frame'] ?>"
            >
              <div class="relative aspect-[2/3] overflow-hidden bg-stone-900">
                <img src="<?= e($poster) ?>" alt="Affiche du film <?= e($movie['title']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                <div class="absolute left-2 top-2 rounded-full border px-2 py-1 text-[10px] font-semibold backdrop-blur <?= $style['badge'] ?>"><?= e($movie['rarity']) ?></div>
                <?php if ((int) $movie['owned_quantity'] > 0): ?>
                  <div class="absolute bottom-2 right-2 rounded-full border border-mint/30 bg-black/80 px-2 py-1 text-[10px] font-semibold text-mint backdrop-blur">Possédée · x<?= (int) $movie['owned_quantity'] ?></div>
                <?php endif; ?>
              </div>
              <div class="p-3 sm:p-4">
                <h2 class="truncate text-sm font-semibold text-stone-100" title="<?= e($movie['title']) ?>"><?= e($movie['title']) ?></h2>
                <p class="mt-1 truncate text-xs text-stone-500"><?= e($movie['genre']) ?> · <?= e((string) $movie['year']) ?></p>
                <div class="mt-3 flex items-center justify-between gap-2 font-mono text-[9px] uppercase tracking-wider text-stone-600">
                  <span>IMDb <?= e((string) $movie['imdb_rating']) ?></span>
                  <span><?= (int) $movie['runtime_minutes'] ?> min</span>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </section>
      <?php else: ?>
        <section class="mt-8 rounded-2xl border border-dashed border-white/10 px-6 py-16 text-center">
          <h2 class="text-lg font-semibold">Aucune carte trouvée</h2>
          <p class="mt-2 text-sm text-stone-500">Essaie un autre titre, genre ou niveau de rareté.</p>
          <a href="catalog.php" class="mt-5 inline-flex rounded-full border border-mint/30 px-4 py-2 text-sm font-medium text-mint transition hover:bg-mint/10">Réinitialiser les filtres</a>
        </section>
      <?php endif; ?>

      <?php if ($totalPages > 1): ?>
        <nav class="mt-10 flex items-center justify-center gap-3" aria-label="Pagination du catalogue">
          <?php if ($page > 1): ?>
            <a href="<?= e($catalogUrl(['page' => $page - 1])) ?>" class="rounded-full border border-white/10 px-4 py-2 text-sm text-stone-300 transition hover:border-white/25 hover:text-white">← Précédent</a>
          <?php endif; ?>
          <span class="px-2 text-sm text-stone-500"><?= $page ?> / <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="<?= e($catalogUrl(['page' => $page + 1])) ?>" class="rounded-full border border-white/10 px-4 py-2 text-sm text-stone-300 transition hover:border-white/25 hover:text-white">Suivant →</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </div>
  </main>

  <dialog id="catalog-dialog" aria-labelledby="catalog-dialog-title" class="m-auto w-[min(94vw,58rem)] max-h-[90vh] overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#101211] p-0 text-stone-100 shadow-[0_32px_100px_rgba(0,0,0,0.75)] backdrop:bg-black/80 backdrop:backdrop-blur-sm">
    <div class="relative max-h-[90vh] overflow-y-auto">
      <button type="button" data-catalog-dialog-close aria-label="Fermer la fiche du film" class="absolute right-4 top-4 z-20 grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-black/60 text-xl leading-none text-stone-300 backdrop-blur transition hover:border-white/30 hover:bg-black/80 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mint">&times;</button>
      <div class="grid md:grid-cols-[19rem_minmax(0,1fr)]">
        <div class="relative min-h-72 overflow-hidden bg-stone-900 md:min-h-full">
          <img id="catalog-dialog-poster" src="" alt="" class="absolute inset-0 h-full w-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-[#101211] via-transparent to-black/20 md:bg-gradient-to-r md:from-transparent md:to-[#101211]/35"></div>
          <div id="catalog-dialog-rarity" class="absolute left-5 top-5 rounded-full border border-white/20 bg-black/65 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white backdrop-blur"></div>
        </div>
        <div class="relative p-6 sm:p-8 md:p-10">
          <p id="catalog-dialog-eyebrow" class="pr-12 font-mono text-[10px] uppercase tracking-[0.2em] text-mint"></p>
          <h2 id="catalog-dialog-title" class="mt-3 max-w-xl text-3xl font-semibold leading-tight tracking-tight text-white sm:text-4xl"></h2>
          <p id="catalog-dialog-owned" class="mt-4 inline-flex rounded-full border border-white/10 px-3 py-1.5 text-xs text-stone-400"></p>
          <div class="mt-6 grid grid-cols-3 gap-2">
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3"><p class="font-mono text-[9px] uppercase tracking-wider text-stone-500">IMDb</p><p id="catalog-dialog-imdb" class="mt-1 text-lg font-semibold text-amber-300"></p></div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3"><p class="font-mono text-[9px] uppercase tracking-wider text-stone-500">Metascore</p><p id="catalog-dialog-meta" class="mt-1 text-lg font-semibold text-mint"></p></div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3"><p class="font-mono text-[9px] uppercase tracking-wider text-stone-500">Durée</p><p id="catalog-dialog-runtime" class="mt-1 text-lg font-semibold text-stone-200"></p></div>
          </div>
          <div class="mt-7"><p class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Synopsis</p><p id="catalog-dialog-overview" class="mt-2 text-sm leading-6 text-stone-300"></p></div>
          <dl class="mt-7 grid gap-4 border-t border-white/10 pt-6 sm:grid-cols-2">
            <div><dt class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Réalisation</dt><dd id="catalog-dialog-director" class="mt-1 text-sm text-stone-200"></dd></div>
            <div><dt class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Distribution</dt><dd id="catalog-dialog-cast" class="mt-1 text-sm leading-5 text-stone-200"></dd></div>
            <div class="sm:col-span-2"><dt class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Popularité</dt><dd id="catalog-dialog-popularity" class="mt-1 text-sm text-stone-200"></dd></div>
          </dl>
        </div>
      </div>
    </div>
  </dialog>
<?php
render_end('../', ['assets/js/catalog.js']);
