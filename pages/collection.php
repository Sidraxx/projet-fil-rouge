<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('../');

// Données temporaires pour construire et tester l'interface.
// Cette liste sera remplacée par la requête user_cards + movies.
$movies = [
    ['title' => 'The Godfather', 'year' => 1972, 'genre' => 'Crime', 'rarity' => 'Légendaire', 'quantity' => 1, 'favorite' => true, 'added_at' => '2026-10-15', 'poster' => 'https://m.media-amazon.com/images/M/MV5BM2MyNjYxNmUtYTAwNi00MTYxLWJmNWYtYzZlODY3ZTk3OTFlXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'The Shawshank Redemption', 'year' => 1994, 'genre' => 'Drame', 'rarity' => 'Légendaire', 'quantity' => 2, 'favorite' => true, 'added_at' => '2026-10-13', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMDFkYTc0MGEtZmNhMC00ZDIzLWFmNTEtODM1ZmRlYWMwMWFmXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'The Dark Knight', 'year' => 2008, 'genre' => 'Action', 'rarity' => 'Légendaire', 'quantity' => 1, 'favorite' => false, 'added_at' => '2026-10-11', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX600_.jpg'],
    ['title' => "One Flew Over the Cuckoo's Nest", 'year' => 1975, 'genre' => 'Drame', 'rarity' => 'Épique', 'quantity' => 1, 'favorite' => false, 'added_at' => '2026-10-09', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZjA0OWVhOTAtYWQxNi00YzNhLWI4ZjYtNjFjZTEyYjJlNDVlL2ltYWdlL2ltYWdlXkEyXkFqcGdeQXVyMTQxNzMzNDI@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'The Lion King', 'year' => 1994, 'genre' => 'Animation', 'rarity' => 'Épique', 'quantity' => 3, 'favorite' => true, 'added_at' => '2026-10-07', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYTYxNGMyZTYtMjE3MS00MzNjLWFjNmYtMDk3N2FmM2JiM2M1XkEyXkFqcGdeQXVyNjY5NDU4NzI@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Back to the Future', 'year' => 1985, 'genre' => 'Aventure', 'rarity' => 'Épique', 'quantity' => 1, 'favorite' => false, 'added_at' => '2026-10-05', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZmU0M2Y1OGUtZjIxNi00ZjBkLTg1MjgtOWIyNThiZWIwYjRiXkEyXkFqcGdeQXVyMTQxNzMzNDI@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Avatar', 'year' => 2009, 'genre' => 'Action', 'rarity' => 'Rare', 'quantity' => 2, 'favorite' => false, 'added_at' => '2026-10-03', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMTYwOTEwNjAzMl5BMl5BanBnXkFtZTcwODc5MTUwMw@@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Reservoir Dogs', 'year' => 1992, 'genre' => 'Crime', 'rarity' => 'Rare', 'quantity' => 1, 'favorite' => true, 'added_at' => '2026-10-01', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZmExNmEwYWItYmQzOS00YjA5LTk2MjktZjEyZDE1Y2QxNjA1XkEyXkFqcGdeQXVyMTQxNzMzNDI@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'The Lives of Others', 'year' => 2006, 'genre' => 'Drame', 'rarity' => 'Rare', 'quantity' => 1, 'favorite' => false, 'added_at' => '2026-09-29', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOThkM2EzYmMtNDE3NS00NjlhLTg4YzktYTdhNzgyOWY3ZDYzXkEyXkFqcGdeQXVyNzQzNzQxNzI@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Birdman', 'year' => 2014, 'genre' => 'Comédie', 'rarity' => 'Peu commune', 'quantity' => 1, 'favorite' => false, 'added_at' => '2026-09-27', 'poster' => 'https://m.media-amazon.com/images/M/MV5BODAzNDMxMzAxOV5BMl5BanBnXkFtZTgwMDMxMjA4MjE@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Nightcrawler', 'year' => 2014, 'genre' => 'Crime', 'rarity' => 'Peu commune', 'quantity' => 4, 'favorite' => true, 'added_at' => '2026-09-25', 'poster' => 'https://m.media-amazon.com/images/M/MV5BN2U1YzdhYWMtZWUzMi00OWI1LWFkM2ItNWVjM2YxMGQ2MmNhXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Before Sunset', 'year' => 2004, 'genre' => 'Drame', 'rarity' => 'Peu commune', 'quantity' => 1, 'favorite' => false, 'added_at' => '2026-09-23', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMTQ1MjAwNTM5Ml5BMl5BanBnXkFtZTYwNDM0MTc3._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Glory', 'year' => 1989, 'genre' => 'Biographie', 'rarity' => 'Commune', 'quantity' => 2, 'favorite' => false, 'added_at' => '2026-09-21', 'poster' => 'https://m.media-amazon.com/images/M/MV5BODhlNjA5MDEtZDVhNS00ZmM3LTg1YzAtZGRjNjhjNTAzNzVkXkEyXkFqcGdeQXVyNjUwMzI2NzU@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Arsenic and Old Lace', 'year' => 1942, 'genre' => 'Comédie', 'rarity' => 'Commune', 'quantity' => 1, 'favorite' => false, 'added_at' => '2026-09-19', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZDVlNTBjMjctNjAzNS00ZGJhLTg2NzMtNzIwYTIzYTBiMDkyXkEyXkFqcGdeQXVyNTA4NzY1MzY@._V1_FMjpg_UX600_.jpg'],
    ['title' => 'Nosferatu', 'year' => 1922, 'genre' => 'Fantastique', 'rarity' => 'Commune', 'quantity' => 3, 'favorite' => true, 'added_at' => '2026-09-17', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMTAxYjEyMTctZTg3Ni00MGZmLWIxMmMtOGM2NTFiY2U3MmExXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX600_.jpg'],
];

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
          <?php $style = $rarityStyles[$movie['rarity']]; ?>
          <article
            data-card
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
              <img src="<?= e($movie['poster']) ?>" alt="Affiche du film <?= e($movie['title']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
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
<?php
render_end('../', ['assets/js/collection.js']);
