<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('../');

$collectionStatement = database()->prepare(
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
        uc.quantity,
        uc.first_obtained_at AS added_at,
        CASE WHEN uf.movie_id IS NULL THEN 0 ELSE 1 END AS favorite
    FROM user_cards uc
    INNER JOIN movies m
        ON m.id = uc.movie_id
    LEFT JOIN user_favorites uf
        ON uf.user_id = uc.user_id
        AND uf.movie_id = uc.movie_id
    WHERE uc.user_id = :user_id
    ORDER BY uc.first_obtained_at DESC, m.title ASC'
);
$collectionStatement->execute([
    'user_id' => (int) $_SESSION['user_id'],
]);
$movies = $collectionStatement->fetchAll();

$rarityStyles = [
    'Commune' => ['badge' => 'border-stone-500/40 bg-stone-500/15 text-stone-200', 'frame' => 'border-stone-500/30'],
    'Peu commune' => ['badge' => 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300', 'frame' => 'border-emerald-500/30'],
    'Rare' => ['badge' => 'border-sky-500/40 bg-sky-500/15 text-sky-300', 'frame' => 'border-sky-500/30'],
    'Épique' => ['badge' => 'border-violet-500/40 bg-violet-500/15 text-violet-300', 'frame' => 'border-violet-500/30'],
    'Légendaire' => ['badge' => 'border-amber-400/40 bg-amber-400/15 text-amber-300', 'frame' => 'border-amber-400/40'],
];

render_start('Collection', 'collection', '../');
?>
  <main class="flex flex-1 flex-col px-4 pb-16 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-[1600px]">
      <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-mint">Cartes obtenues</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Ma collection</h1>
          <p class="mt-2 text-sm text-stone-500"><span id="result-count"><?= count($movies) ?></span> films affichés</p>
        </div>
        <button id="selection-button" type="button" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-stone-300 transition hover:border-white/20 hover:bg-white/10">
          Sélectionner des cartes
        </button>
      </header>

      <section class="mt-8 space-y-4" aria-label="Filtres de collection">
        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_11rem]">
          <label class="relative block">
            <span class="sr-only">Rechercher dans la collection</span>
            <input id="collection-search" type="search" placeholder="Rechercher par titre ou genre..." class="w-full rounded-xl border border-white/10 bg-[#171918] px-4 py-3 text-sm text-stone-100 outline-none transition placeholder:text-stone-600 focus:border-mint/60 focus:ring-2 focus:ring-mint/10">
          </label>
          <label>
            <span class="sr-only">Classer la collection</span>
            <select id="collection-sort" class="w-full rounded-xl border border-mint/40 bg-[#171918] px-4 py-3 text-sm font-semibold text-stone-200 outline-none transition hover:border-mint/60 focus:border-mint focus:ring-2 focus:ring-mint/15">
              <option class="bg-[#0c0d0d]" value="rarity">Rareté</option>
              <option class="bg-[#0c0d0d]" value="title">Nom</option>
              <option class="bg-[#0c0d0d]" value="favorites">Favoris</option>
              <option class="bg-[#0c0d0d]" value="newest">Date d'ajout</option>
            </select>
          </label>
        </div>

        <div id="rarity-filters" class="flex flex-wrap gap-2" aria-label="Filtrer par rareté">
          <button type="button" data-rarity-filter="all" data-active="true" class="rounded-full border border-white/10 px-3 py-1.5 text-xs font-medium text-stone-400 transition hover:border-white/20 hover:text-white data-[active=true]:border-mint/50 data-[active=true]:bg-mint/10 data-[active=true]:text-mint">Toutes</button>
          <?php foreach ($rarityStyles as $rarity => $style): ?>
            <button type="button" data-rarity-filter="<?= e($rarity) ?>" data-active="false" class="rounded-full border border-white/10 px-3 py-1.5 text-xs font-medium text-stone-400 transition hover:border-white/20 hover:text-white data-[active=true]:border-mint/50 data-[active=true]:bg-mint/10 data-[active=true]:text-mint"><?= e($rarity) ?></button>
          <?php endforeach; ?>
        </div>
      </section>

      <section id="collection-grid" class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6" aria-live="polite">
        <?php foreach ($movies as $movie): ?>
          <?php
          $style = $rarityStyles[$movie['rarity']];
          $poster = movie_poster_url($movie['poster']);
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
              'cast' => array_values(array_filter([
                  $movie['star1'],
                  $movie['star2'],
                  $movie['star3'],
                  $movie['star4'],
              ])),
              'votes' => $movie['number_of_votes'],
              'gross' => $movie['gross'],
              'quantity' => $movie['quantity'],
              'addedAt' => $movie['added_at'],
          ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          ?>
          <article
            data-card
            data-movie-details="<?= e($movieDetails ?: '{}') ?>"
            data-title="<?= e($movie['title']) ?>"
            data-search="<?= e($movie['title'] . ' ' . $movie['genre']) ?>"
            data-rarity="<?= e($movie['rarity']) ?>"
            data-favorite="<?= $movie['favorite'] ? 'true' : 'false' ?>"
            data-added="<?= e($movie['added_at']) ?>"
            data-selected="false"
            tabindex="0"
            class="group relative overflow-hidden rounded-2xl border bg-[#111312] outline-none transition duration-200 hover:-translate-y-1 hover:border-white/25 hover:shadow-2xl focus-visible:ring-2 focus-visible:ring-mint/70 data-[selected=true]:border-mint data-[selected=true]:ring-2 data-[selected=true]:ring-mint/30 <?= $style['frame'] ?>"
          >
            <div class="relative aspect-[2/3] overflow-hidden bg-stone-900">
              <img src="<?= e($poster) ?>" alt="Affiche du film <?= e($movie['title']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
              <div class="absolute left-2 top-2 rounded-full border px-2 py-1 text-[10px] font-semibold backdrop-blur <?= $style['badge'] ?>"><?= e($movie['rarity']) ?></div>
              <?php if ($movie['quantity'] > 1): ?>
                <div class="absolute bottom-2 right-2 rounded-full border border-white/15 bg-black/75 px-2 py-1 text-[10px] font-semibold text-white">x<?= $movie['quantity'] ?></div>
              <?php endif; ?>
            </div>
            <div class="p-3 sm:p-4">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <h2 class="truncate text-sm font-semibold text-stone-100" title="<?= e($movie['title']) ?>"><?= e($movie['title']) ?></h2>
                  <p class="mt-1 truncate text-xs text-stone-500"><?= e($movie['genre']) ?> · <?= $movie['year'] ?></p>
                </div>
                <button type="button" data-favorite-toggle aria-pressed="<?= $movie['favorite'] ? 'true' : 'false' ?>" class="shrink-0 rounded-full border border-white/10 px-2 py-1 text-[10px] font-medium text-stone-500 transition hover:border-white/25 hover:text-stone-200 aria-pressed:border-amber-400/40 aria-pressed:bg-amber-400/10 aria-pressed:text-amber-300">
                  <?= $movie['favorite'] ? 'Favori' : 'Ajouter' ?>
                </button>
              </div>
              <p class="mt-3 font-mono text-[9px] uppercase tracking-wider text-stone-600">Obtenue le <?= e(date('d/m/Y', strtotime($movie['added_at']))) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <section id="collection-empty" class="mt-8 hidden rounded-2xl border border-dashed border-white/10 px-6 py-16 text-center">
        <h2 class="text-lg font-semibold">Aucune carte trouvée</h2>
        <p class="mt-2 text-sm text-stone-500">Modifie la recherche ou les filtres pour afficher d'autres films.</p>
      </section>
    </div>
  </main>

  <dialog id="movie-dialog" aria-labelledby="movie-dialog-title" class="m-auto w-[min(94vw,58rem)] max-h-[90vh] overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#101211] p-0 text-stone-100 shadow-[0_32px_100px_rgba(0,0,0,0.75)] backdrop:bg-black/80 backdrop:backdrop-blur-sm">
    <div class="relative max-h-[90vh] overflow-y-auto">
      <button type="button" data-movie-dialog-close aria-label="Fermer la fiche du film" class="absolute right-4 top-4 z-20 grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-black/60 text-xl leading-none text-stone-300 backdrop-blur transition hover:border-white/30 hover:bg-black/80 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mint">
        &times;
      </button>

      <div class="grid md:grid-cols-[19rem_minmax(0,1fr)]">
        <div class="relative min-h-72 overflow-hidden bg-stone-900 md:min-h-full">
          <img id="movie-dialog-poster" src="" alt="" class="absolute inset-0 h-full w-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-[#101211] via-transparent to-black/20 md:bg-gradient-to-r md:from-transparent md:to-[#101211]/35"></div>
          <div id="movie-dialog-rarity" class="absolute left-5 top-5 rounded-full border border-white/20 bg-black/65 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white backdrop-blur"></div>
        </div>

        <div class="relative p-6 sm:p-8 md:p-10">
          <p id="movie-dialog-eyebrow" class="pr-12 font-mono text-[10px] uppercase tracking-[0.2em] text-mint"></p>
          <h2 id="movie-dialog-title" class="mt-3 max-w-xl text-3xl font-semibold leading-tight tracking-tight text-white sm:text-4xl"></h2>

          <div class="mt-6 grid grid-cols-3 gap-2" aria-label="Informations principales">
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3">
              <p class="font-mono text-[9px] uppercase tracking-wider text-stone-500">IMDb</p>
              <p id="movie-dialog-imdb" class="mt-1 text-lg font-semibold text-amber-300"></p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3">
              <p class="font-mono text-[9px] uppercase tracking-wider text-stone-500">Metascore</p>
              <p id="movie-dialog-meta" class="mt-1 text-lg font-semibold text-mint"></p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3">
              <p class="font-mono text-[9px] uppercase tracking-wider text-stone-500">Durée</p>
              <p id="movie-dialog-runtime" class="mt-1 text-lg font-semibold text-stone-200"></p>
            </div>
          </div>

          <div class="mt-7">
            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Synopsis</p>
            <p id="movie-dialog-overview" class="mt-2 text-sm leading-6 text-stone-300"></p>
          </div>

          <dl class="mt-7 grid gap-4 border-t border-white/10 pt-6 sm:grid-cols-2">
            <div>
              <dt class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Réalisation</dt>
              <dd id="movie-dialog-director" class="mt-1 text-sm text-stone-200"></dd>
            </div>
            <div>
              <dt class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Distribution</dt>
              <dd id="movie-dialog-cast" class="mt-1 text-sm leading-5 text-stone-200"></dd>
            </div>
            <div>
              <dt class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Popularité</dt>
              <dd id="movie-dialog-popularity" class="mt-1 text-sm text-stone-200"></dd>
            </div>
            <div>
              <dt class="font-mono text-[9px] uppercase tracking-[0.18em] text-stone-500">Dans ta collection</dt>
              <dd id="movie-dialog-collection" class="mt-1 text-sm text-stone-200"></dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </dialog>
<?php
render_end('../', ['assets/js/collection.js']);
