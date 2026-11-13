<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/decks.php';

require_login('../');

$db = database();
$userId = (int) $_SESSION['user_id'];
$activeDeckId = get_or_create_deck($db, $userId);
$decks = load_user_decks($db, $userId);
$activeDeck = $decks[0] ?? ['name' => 'Mon deck', 'cards' => 0];
$cards = load_deck_cards($db, $activeDeckId);
$hasEnoughCards = count($cards) >= 4;
$enemyPreviewStatement = $db->query(
    'SELECT id, title, poster_link AS poster, genre, rarity_level AS rarity
     FROM movies
     WHERE genre IS NOT NULL
     ORDER BY RAND()
     LIMIT 10'
);
$enemyPreview = $enemyPreviewStatement->fetchAll();
$_SESSION['combat_preview_enemy_ids'] = array_map(
    static fn (array $card): int => (int) $card['id'],
    $enemyPreview
);

render_start('Combat', 'battle', '../');
?>
  <main class="combat-page flex flex-1 flex-col px-4 pb-8 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-7xl">
      <header class="combat-lobby-header flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-mint">Lobby · Combat contre le système</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Préparez votre escouade</h1>
          <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-500">Choisissez 4 cartes parmi celles de votre deck actif avant de rejoindre le terrain.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="rounded-full border border-mint/30 bg-mint/10 px-4 py-2 text-sm text-mint"><?= e((string) $activeDeck['name']) ?></span>
          <div id="team-counter" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-stone-400">0 / 4 cartes</div>
        </div>
      </header>

      <section id="team-builder" class="mt-8">
        <?php if (!$hasEnoughCards): ?>
          <div class="rounded-2xl border border-dashed border-white/10 px-6 py-16 text-center">
            <p class="text-lg font-semibold">Votre deck ne contient pas assez de cartes</p>
            <p class="mt-2 text-sm text-stone-500">Ajoutez au moins 4 cartes dans votre deck actif pour lancer un combat.</p>
            <a href="deck.php" class="mt-5 inline-flex rounded-full bg-mint px-4 py-2 text-sm font-semibold text-black transition hover:bg-emerald-300">Modifier mon deck</a>
          </div>
        <?php else: ?>
          <div class="combat-lobby-panel">
            <div class="combat-lobby-panel__top">
              <div>
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-stone-500">Deck actif</p>
                <h2 class="mt-1 text-xl font-semibold"><?= e((string) $activeDeck['name']) ?></h2>
              </div>
              <a href="deck.php" class="rounded-full border border-white/10 px-3 py-2 text-xs text-stone-300 transition hover:border-mint/40 hover:text-mint">Gérer le deck</a>
            </div>
            <div id="team-grid" class="combat-lobby-grid" aria-label="Cartes du deck actif">
            <?php foreach ($cards as $card): ?>
              <button
                type="button"
                data-movie-id="<?= (int) $card['id'] ?>"
                class="combat-card group overflow-hidden rounded-2xl border border-white/10 bg-panel text-left transition hover:-translate-y-1 hover:border-mint/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mint"
                aria-pressed="false"
              >
                <div class="relative aspect-[2/3] overflow-hidden bg-stone-900">
                  <?php if ($card['poster']): ?>
                    <img data-poster-fallback src="<?= e($card['poster']) ?>" alt="Affiche de <?= e($card['title']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                  <?php else: ?>
                    <div class="grid h-full place-items-center text-3xl font-semibold text-mint"><?= e(mb_strtoupper(mb_substr($card['title'], 0, 2))) ?></div>
                  <?php endif; ?>
                  <span class="absolute left-2 top-2 rounded-full border border-white/15 bg-black/70 px-2 py-1 text-[10px] font-semibold text-stone-200"><?= e($card['rarity'] ?: 'Carte') ?></span>
                  <span class="absolute bottom-2 right-2 rounded-full border border-white/15 bg-black/70 px-2 py-1 text-[10px] text-stone-300">x<?= (int) $card['quantity'] ?></span>
                </div>
                <div class="p-3">
                  <h2 class="truncate text-sm font-semibold text-stone-100"><?= e(display_text($card['title'], 'Film sans titre')) ?></h2>
                  <p class="mt-1 truncate text-xs text-stone-500"><?= e(display_text($card['genre'], 'Genre inconnu')) ?></p>
                </div>
              </button>
            <?php endforeach; ?>
            </div>
          </div>
          <div class="combat-opponent-preview">
            <div class="combat-lobby-panel__top">
              <div>
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-red-300/70">Aperçu adverse</p>
                <h2 class="mt-1 text-xl font-semibold">Le deck du système</h2>
              </div>
              <span class="rounded-full border border-red-300/20 bg-red-300/10 px-3 py-2 text-xs text-red-200">10 cartes · 4 seront jouées</span>
            </div>
            <p class="mb-3 text-xs text-stone-500">Observez les types adverses, puis choisissez les 4 cartes les plus adaptées à ce duel.</p>
            <div class="combat-opponent-grid" aria-label="Aperçu des dix cartes adverses">
              <?php foreach ($enemyPreview as $card): ?>
                <div class="combat-opponent-card">
                  <?php if ($card['poster']): ?>
                    <img data-poster-fallback src="<?= e($card['poster']) ?>" alt="" loading="lazy">
                  <?php else: ?>
                    <span><?= e(mb_strtoupper(mb_substr($card['title'], 0, 2))) ?></span>
                  <?php endif; ?>
                  <strong><?= e(display_text($card['title'], 'Film sans titre')) ?></strong>
                  <small><?= e(display_text($card['genre'], 'Genre inconnu')) ?></small>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="mt-5 flex flex-col items-center gap-3">
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
          <button id="combat-rules-button" type="button" class="combat-rules-button" aria-haspopup="dialog">?</button>
        </div>
        <div id="combat-message" class="mt-4 min-h-6 text-sm text-stone-400" role="status"></div>
        <div class="combat-help-bar" role="note">
          <span class="combat-help-bar__step">1</span>
          <span>Cliquez sur une carte pour choisir une cible.</span>
          <span class="combat-help-bar__arrow">→</span>
          <span class="combat-help-bar__step">2</span>
          <span>Utilisez une attaque visible sous la carte de votre choix.</span>
          <span id="combat-target-summary" class="combat-target-summary">Cible adverse : sélectionnez une carte</span>
        </div>
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
        <div id="combat-result" class="combat-result hidden" role="dialog" aria-modal="true" aria-labelledby="combat-result-title">
          <div class="combat-result__box">
            <span id="combat-result-icon" class="combat-result__icon" aria-hidden="true">★</span>
            <p id="combat-result-label" class="combat-result__label">Résultat du duel</p>
            <h2 id="combat-result-title" class="combat-result__title"></h2>
            <p id="combat-result-text" class="combat-result__text"></p>
            <button id="new-combat" type="button" class="combat-result__button">Rejouer</button>
          </div>
        </div>
      </section>
    </div>
  </main>
  <dialog id="combat-rules" class="combat-rules-dialog">
    <div class="combat-rules-dialog__content">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-mint">Aide du duel</p>
          <h2 class="mt-1 text-xl font-semibold">Règles du combat</h2>
        </div>
        <button id="combat-rules-close" type="button" class="rounded-full border border-white/10 px-3 py-1 text-sm text-stone-300" aria-label="Fermer">×</button>
      </div>
      <ul class="mt-5 space-y-3 text-sm leading-5 text-stone-300">
        <li><strong class="text-mint">Énergie :</strong> vous commencez avec 5 points, récupérez 3 points au début de chaque tour et ne pouvez pas dépasser 12.</li>
        <li><strong class="text-mint">Actions :</strong> chaque attaque ou effet consomme l'énergie affichée sur son bouton.</li>
        <li><strong class="text-mint">Concentration :</strong> récupère 2 points d'énergie une fois par tour.</li>
        <li><strong class="text-mint">Cibles :</strong> cliquez sur une carte adverse pour l'attaquer ou sur une carte alliée pour la soigner ou la protéger.</li>
        <li><strong class="text-mint">Types :</strong> les forces infligent plus de dégâts et les faiblesses en réduisent les dégâts.</li>
        <li><strong class="text-mint">Victoire :</strong> éliminez les 4 cartes du système avant que votre équipe ne soit vaincue.</li>
      </ul>
    </div>
  </dialog>
<?php
render_end('../', ['assets/js/combat.js']);
