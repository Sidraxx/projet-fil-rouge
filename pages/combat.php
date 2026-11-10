<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('../');

$statement = database()->prepare(
    'SELECT m.id, m.title, m.poster_link AS poster, m.genre, m.rarity_level AS rarity, uc.quantity
     FROM user_cards uc
     INNER JOIN movies m ON m.id = uc.movie_id
     WHERE uc.user_id = :user_id
     ORDER BY m.title ASC'
);
$statement->execute(['user_id' => (int) $_SESSION['user_id']]);
$cards = $statement->fetchAll();
$hasEnoughCards = count($cards) >= 4;

render_start('Combat', 'battle', '../');
?>
  <main class="flex flex-1 flex-col px-4 pb-16 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-7xl">
      <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-mint">Combat contre le système</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Préparez votre équipe</h1>
          <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-500">Sélectionnez 4 cartes. Leur genre, leurs attaques et leurs passifs seront utilisés pendant le combat.</p>
        </div>
        <div id="team-counter" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-stone-400">0 / 4 cartes</div>
      </header>

      <section id="team-builder" class="mt-8">
        <?php if (!$hasEnoughCards): ?>
          <div class="rounded-2xl border border-dashed border-white/10 px-6 py-16 text-center">
            <p class="text-lg font-semibold">Votre équipe n'est pas encore complète</p>
            <p class="mt-2 text-sm text-stone-500">Obtenez au moins 4 cartes dans un paquet avant de lancer un combat.</p>
            <a href="../index.php" class="mt-5 inline-flex rounded-full bg-mint px-4 py-2 text-sm font-semibold text-black transition hover:bg-emerald-300">Ouvrir un paquet</a>
          </div>
        <?php else: ?>
          <div id="team-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5" aria-label="Cartes disponibles">
            <?php foreach ($cards as $card): ?>
              <button
                type="button"
                data-movie-id="<?= (int) $card['id'] ?>"
                class="combat-card group overflow-hidden rounded-2xl border border-white/10 bg-panel text-left transition hover:-translate-y-1 hover:border-mint/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mint"
                aria-pressed="false"
              >
                <div class="relative aspect-[2/3] overflow-hidden bg-stone-900">
                  <?php if ($card['poster']): ?>
                    <img src="<?= e($card['poster']) ?>" alt="Affiche de <?= e($card['title']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                  <?php else: ?>
                    <div class="grid h-full place-items-center text-3xl font-semibold text-mint"><?= e(mb_strtoupper(mb_substr($card['title'], 0, 2))) ?></div>
                  <?php endif; ?>
                  <span class="absolute left-2 top-2 rounded-full border border-white/15 bg-black/70 px-2 py-1 text-[10px] font-semibold text-stone-200"><?= e($card['rarity'] ?: 'Carte') ?></span>
                  <span class="absolute bottom-2 right-2 rounded-full border border-white/15 bg-black/70 px-2 py-1 text-[10px] text-stone-300">x<?= (int) $card['quantity'] ?></span>
                </div>
                <div class="p-3">
                  <h2 class="truncate text-sm font-semibold text-stone-100"><?= e($card['title']) ?></h2>
                  <p class="mt-1 truncate text-xs text-stone-500"><?= e($card['genre'] ?? 'Drama') ?></p>
                </div>
              </button>
            <?php endforeach; ?>
          </div>
          <div class="mt-6 flex flex-col items-center gap-3">
            <p id="team-message" class="min-h-5 text-center text-sm text-stone-500" role="status" aria-live="polite"></p>
            <button id="start-combat" type="button" disabled class="rounded-full bg-mint px-5 py-3 text-sm font-semibold text-black transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-40">Lancer le combat</button>
          </div>
        <?php endif; ?>
      </section>

      <section id="combat-board" class="hidden" aria-live="polite">
        <div class="combat-hud">
          <div class="combat-hud__resources">
            <div class="combat-resource combat-resource--energy">
              <span class="combat-resource__icon">✦</span>
              <span id="combat-energy">5 / 12</span>
              <small>ÉNERGIE</small>
            </div>
            <div class="combat-resource combat-resource--focus">
              <span class="combat-resource__icon">✚</span>
              <span>+2</span>
              <small>CONCENTRATION</small>
            </div>
          </div>
          <div class="combat-hud__title">
            <p>Absolute Cinema</p>
            <strong>DUEL DES CARTES</strong>
          </div>
          <div class="combat-turn">
            <span class="combat-turn__pulse"></span>
            <small>TOUR EN COURS</small>
            <strong id="combat-turn-label">VOTRE TOUR</strong>
          </div>
        </div>
        <div id="combat-message" class="mt-4 min-h-6 text-sm text-stone-400" role="status"></div>
        <div class="combat-field mt-6 rounded-3xl border border-white/10 p-3 sm:p-5">
          <section class="combat-side combat-side--enemy" aria-labelledby="enemy-team-title">
            <div class="combat-side__header">
              <h3 id="enemy-team-title" class="font-semibold">Adversaire</h3>
              <span class="combat-side__badge">SYSTÈME · 4 CARTES</span>
            </div>
            <div id="enemy-team" class="combat-side__cards"></div>
          </section>
          <div class="combat-divider" aria-hidden="true">
            <span>VS</span>
          </div>
          <section class="combat-side combat-side--player" aria-labelledby="player-team-title">
            <div class="combat-side__header">
              <h3 id="player-team-title" class="font-semibold">Votre équipe</h3>
              <div class="ml-auto flex items-center gap-2">
                <span class="combat-side__badge">JOUEUR · 4 CARTES</span>
                <button id="focus-energy" type="button" class="rounded-full border border-violet-400/30 bg-violet-400/10 px-3 py-2 text-xs font-medium text-violet-200 transition hover:bg-violet-400/20">Concentration +2</button>
                <button id="end-turn" type="button" class="rounded-full border border-white/10 px-4 py-2 text-xs font-medium text-stone-300 transition hover:bg-white/5">Terminer le tour</button>
              </div>
            </div>
            <div id="player-team" class="combat-side__cards"></div>
          </section>
        </div>
        <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_20rem]">
          <section class="rounded-2xl border border-white/10 bg-panel p-4 sm:p-5">
            <h3 class="font-semibold">Journal du combat</h3>
            <ol id="combat-log" class="mt-4 max-h-72 space-y-2 overflow-y-auto text-xs leading-5 text-stone-400"></ol>
          </section>
        </div>
        <div id="combat-result" class="mt-6 hidden rounded-2xl border border-mint/30 bg-mint/10 p-6 text-center">
          <h2 id="combat-result-title" class="text-2xl font-semibold text-mint"></h2>
          <p id="combat-result-text" class="mt-2 text-sm text-stone-400"></p>
          <button id="new-combat" type="button" class="mt-5 rounded-full bg-mint px-5 py-3 text-sm font-semibold text-black transition hover:bg-emerald-300">Préparer un nouveau combat</button>
        </div>
      </section>
    </div>
  </main>
<?php
render_end('../', ['assets/js/combat.js']);
