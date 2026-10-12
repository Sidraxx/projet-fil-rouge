<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('../');
$user = current_user('../');

// La table de collection n'existe pas encore : le compteur reste à 0.
$uniqueCards = 0;
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
          <p class="text-sm text-stone-500"><?= $uniqueCards ?> cartes · Depuis <?= e(french_date($user['created_at'])) ?></p>
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
        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <?php for ($slot = 0; $slot < 4; $slot++): ?>
            <div class="flex aspect-[2/3] cursor-not-allowed flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-white/10 text-stone-600">
              <span class="text-2xl leading-none" aria-hidden="true">+</span>
              <span class="text-xs">Ajouter</span>
            </div>
          <?php endfor; ?>
        </div>
      </section>

      <div class="grid gap-3 sm:grid-cols-2">
        <span class="flex cursor-not-allowed flex-col items-center gap-3 rounded-2xl border border-white/10 bg-panel px-6 py-6 text-stone-600" aria-disabled="true" title="Bientôt disponible">
          <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"></path><path d="M4 5.5A2.5 2.5 0 0 1 6.5 8H20"></path></svg>
          <span class="text-sm font-medium">Ma Collection</span>
        </span>
        <span class="flex cursor-not-allowed flex-col items-center gap-3 rounded-2xl border border-white/10 bg-panel px-6 py-6 text-stone-600" aria-disabled="true" title="Bientôt disponible">
          <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="9" cy="8" r="3.2"></circle><path d="M3 19.2a6 6 0 0 1 12 0"></path><path d="M16 4.8a3.2 3.2 0 0 1 0 6.2M18 13.4a6 6 0 0 1 3 5.8"></path></svg>
          <span class="text-sm font-medium">Mes Amis</span>
        </span>
      </div>
    </div>
  </main>
<?php
render_end('../');
