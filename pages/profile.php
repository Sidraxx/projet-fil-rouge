<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('../');

$user = current_user('../');

$collectionStatement = database()->prepare(
    'SELECT
        m.id,
        m.title,
        m.poster_link AS poster,
        m.released_year AS year,
        m.genre,
        m.rarity_level AS rarity,
        uc.quantity
    FROM user_cards uc
    INNER JOIN movies m ON m.id = uc.movie_id
    WHERE uc.user_id = :user_id
    ORDER BY m.rarity_score DESC, m.title ASC'
);
$collectionStatement->execute(['user_id' => $_SESSION['user_id']]);
$collectionCards = $collectionStatement->fetchAll();
foreach ($collectionCards as &$collectionCard) {
    $collectionCard['poster'] = movie_poster_url($collectionCard['poster']);
}
unset($collectionCard);
$uniqueCards = count($collectionCards);

$showcaseStatement = database()->prepare(
    'SELECT
        sc.slot_position,
        m.id,
        m.title,
        m.poster_link AS poster,
        m.released_year AS year,
        m.rarity_level AS rarity,
        uc.quantity
    FROM profile_showcase_cards sc
    INNER JOIN user_cards uc
        ON uc.user_id = sc.user_id AND uc.movie_id = sc.movie_id
    INNER JOIN movies m ON m.id = sc.movie_id
    WHERE sc.user_id = :user_id
    ORDER BY sc.slot_position ASC'
);
$showcaseStatement->execute(['user_id' => $_SESSION['user_id']]);
$showcaseCards = [];

foreach ($showcaseStatement->fetchAll() as $showcaseCard) {
    $showcaseCard['poster'] = movie_poster_url($showcaseCard['poster']);
    $showcaseCards[(int) $showcaseCard['slot_position']] = $showcaseCard;
}

$rarityBorders = [
    'Commune' => 'border-stone-500/45',
    'Peu commune' => 'border-emerald-500/50',
    'Rare' => 'border-sky-500/55',
    'Épique' => 'border-violet-500/55',
    'Légendaire' => 'border-amber-400/60',
];
$initials = mb_strtoupper(mb_substr((string) $user['username'], 0, 2));

render_start('Profil', 'profile', '../');
?>
  <main class="flex flex-1 flex-col px-4 pb-16 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-2xl space-y-4">
      <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Mon Profil</h1>

      <section class="flex items-center gap-4 rounded-2xl border border-white/10 bg-panel p-5">
        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-[#13281f] text-lg font-semibold text-mint" aria-hidden="true"><?= e($initials) ?></div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-lg font-semibold"><?= e($user['username']) ?></p>
          <p class="text-sm text-stone-500"><?= $uniqueCards ?> carte<?= $uniqueCards > 1 ? 's' : '' ?> · Depuis <?= e(french_date($user['created_at'])) ?></p>
        </div>
        <div class="flex flex-col items-end gap-2">
          <span class="text-xs text-stone-500" id="visibility-label">Visible de tous</span>
          <span role="switch" aria-checked="true" aria-disabled="true" aria-labelledby="visibility-label" title="Bientôt disponible" class="relative inline-flex h-6 w-11 cursor-not-allowed items-center rounded-full bg-mint opacity-60">
            <span class="inline-block h-5 w-5 translate-x-[22px] rounded-full bg-white"></span>
          </span>
        </div>
      </section>

      <section class="rounded-2xl border border-white/10 bg-panel p-6 text-center">
        <p class="text-3xl font-semibold text-mint"><?= $uniqueCards ?></p>
        <p class="mt-1 text-sm text-stone-500">Cartes uniques</p>
      </section>

      <section class="rounded-2xl border border-white/10 bg-panel p-5 sm:p-6" aria-labelledby="showcase-title">
        <h2 id="showcase-title" class="flex items-center gap-2 text-base font-semibold">
          <svg class="h-5 w-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4z"></path><path d="M7 6H4v1a3 3 0 0 0 3 3M17 6h3v1a3 3 0 0 1-3 3"></path></svg>
          Vitrine
        </h2>
        <p class="mt-2 text-sm text-stone-500">Choisissez jusqu'à quatre cartes de votre collection à exposer.</p>
        <p id="showcase-message" class="mt-3 min-h-5 text-sm text-stone-400" role="status" aria-live="polite"></p>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <?php for ($slot = 1; $slot <= 4; $slot++): ?>
            <?php $showcaseCard = $showcaseCards[$slot] ?? null; ?>
            <?php if ($showcaseCard): ?>
              <?php $border = $rarityBorders[$showcaseCard['rarity']] ?? 'border-white/15'; ?>
              <div class="group relative aspect-[2/3] overflow-hidden rounded-xl border bg-stone-900 <?= $border ?>">
                <button type="button" data-showcase-slot="<?= $slot ?>" class="absolute inset-0 z-10 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-mint" aria-label="Modifier l'emplacement <?= $slot ?>, actuellement <?= e($showcaseCard['title']) ?>"></button>
                <img src="<?= e($showcaseCard['poster']) ?>" alt="Affiche du film <?= e($showcaseCard['title']) ?>" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/85 to-transparent px-3 pb-3 pt-12">
                  <p class="truncate text-xs font-semibold text-white"><?= e($showcaseCard['title']) ?></p>
                  <p class="mt-1 text-[10px] text-stone-400"><?= e($showcaseCard['rarity']) ?> · x<?= (int) $showcaseCard['quantity'] ?></p>
                </div>
                <button type="button" data-showcase-remove="<?= $slot ?>" class="absolute right-2 top-2 z-20 rounded-full border border-white/15 bg-black/70 px-2 py-1 text-[10px] font-medium text-stone-300 opacity-0 backdrop-blur transition hover:text-white focus:opacity-100 group-hover:opacity-100" aria-label="Retirer <?= e($showcaseCard['title']) ?> de la vitrine">Retirer</button>
              </div>
            <?php else: ?>
              <button type="button" data-showcase-slot="<?= $slot ?>" class="flex aspect-[2/3] flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-white/15 bg-black/10 text-stone-500 transition hover:border-mint/40 hover:bg-mint/[0.04] hover:text-mint focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mint">
                <span class="text-2xl leading-none" aria-hidden="true">+</span>
                <span class="text-xs">Ajouter</span>
              </button>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
      </section>

      <div class="grid gap-3 sm:grid-cols-2">
        <a href="collection.php" class="flex flex-col items-center gap-3 rounded-2xl border border-white/10 bg-panel px-6 py-6 text-stone-400 transition hover:border-mint/30 hover:text-mint">
          <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"></path><path d="M4 5.5A2.5 2.5 0 0 1 6.5 8H20"></path></svg>
          <span class="text-sm font-medium">Ma Collection</span>
        </a>
        <span class="flex cursor-not-allowed flex-col items-center gap-3 rounded-2xl border border-white/10 bg-panel px-6 py-6 text-stone-600" aria-disabled="true" title="Bientôt disponible">
          <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="9" cy="8" r="3.2"></circle><path d="M3 19.2a6 6 0 0 1 12 0"></path><path d="M16 4.8a3.2 3.2 0 0 1 0 6.2M18 13.4a6 6 0 0 1 3 5.8"></path></svg>
          <span class="text-sm font-medium">Mes Amis</span>
        </span>
      </div>
    </div>
  </main>

  <dialog id="showcase-dialog" aria-labelledby="showcase-dialog-title" class="m-auto w-[min(94vw,48rem)] max-h-[86vh] overflow-hidden rounded-2xl border border-white/10 bg-[#121413] p-0 text-stone-100 shadow-2xl backdrop:bg-black/80 backdrop:backdrop-blur-sm">
    <div class="flex max-h-[86vh] flex-col">
      <header class="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-5 sm:px-6">
        <div>
          <p class="font-mono text-[9px] uppercase tracking-[0.2em] text-mint">Vitrine du profil</p>
          <h2 id="showcase-dialog-title" class="mt-2 text-xl font-semibold">Choisir une carte</h2>
          <p id="showcase-dialog-subtitle" class="mt-1 text-sm text-stone-500"></p>
        </div>
        <button type="button" data-showcase-close class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-white/10 text-lg text-stone-400 transition hover:border-white/25 hover:text-white" aria-label="Fermer">&times;</button>
      </header>

      <div class="overflow-y-auto p-5 sm:p-6">
        <?php if ($collectionCards): ?>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            <?php foreach ($collectionCards as $card): ?>
              <?php $border = $rarityBorders[$card['rarity']] ?? 'border-white/15'; ?>
              <button type="button" data-showcase-choice="<?= (int) $card['id'] ?>" class="group overflow-hidden rounded-xl border bg-[#0d0f0e] text-left transition hover:-translate-y-0.5 hover:border-white/35 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mint <?= $border ?>">
                <div class="aspect-[2/3] overflow-hidden bg-stone-900">
                  <img src="<?= e($card['poster']) ?>" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                </div>
                <div class="p-3">
                  <p class="truncate text-xs font-semibold text-white"><?= e($card['title']) ?></p>
                  <p class="mt-1 truncate text-[10px] text-stone-500"><?= e($card['rarity']) ?> · x<?= (int) $card['quantity'] ?></p>
                </div>
              </button>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="rounded-xl border border-dashed border-white/10 px-6 py-12 text-center">
            <p class="font-medium text-stone-300">Votre collection est vide</p>
            <p class="mt-2 text-sm text-stone-500">Obtenez une carte avant de remplir votre vitrine.</p>
            <a href="collection.php" class="mt-5 inline-flex rounded-full border border-mint/30 px-4 py-2 text-sm font-medium text-mint transition hover:bg-mint/10">Voir ma collection</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </dialog>
<?php
render_end('../', ['assets/js/profile.js']);
