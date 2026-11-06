<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/decks.php';

require_login('../');

$db = database();
$userId = (int) $_SESSION['user_id'];

$deckId = get_or_create_deck($db, $userId);
$status = deck_status($db, $deckId);
$available = load_deck_available_cards($db, $userId, $deckId);
$deckCards = load_deck_cards($db, $deckId);

$rarityStyles = [
    'Commune' => ['badge' => 'border-stone-500/40 bg-stone-500/15 text-stone-200', 'frame' => 'border-stone-500/30', 'label' => 'text-stone-400'],
    'Peu commune' => ['badge' => 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300', 'frame' => 'border-emerald-500/30', 'label' => 'text-emerald-300'],
    'Rare' => ['badge' => 'border-sky-500/40 bg-sky-500/15 text-sky-300', 'frame' => 'border-sky-500/30', 'label' => 'text-sky-300'],
    'Épique' => ['badge' => 'border-violet-500/40 bg-violet-500/15 text-violet-300', 'frame' => 'border-violet-500/30', 'label' => 'text-violet-300'],
    'Légendaire' => ['badge' => 'border-amber-400/40 bg-amber-400/15 text-amber-300', 'frame' => 'border-amber-400/40', 'label' => 'text-amber-300'],
];

render_start('Deck', 'deck', '../');
?>
  <main class="flex flex-1 flex-col px-4 pb-16 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-[1600px]">
      <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-mint">Constructeur</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Mon deck</h1>
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-500">
            <span>Choisissez les cartes de votre collection qui composeront votre deck.</span>
            <?php foreach (deck_rules() as $label => $value): ?>
              <span class="rounded-full border border-white/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-stone-500"><?= $label === 'size' ? 'Deck max' : 'Doublons max' ?> : <?= $value ?></span>
            <?php endforeach; ?>
          </p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-4">
          <p class="text-center text-lg font-semibold text-mint"><span id="deck-count" data-size="<?= $status['size'] ?>"><?= $status['count'] ?></span> / <?= $status['size'] ?></p>
          <p class="mt-0.5 text-center text-xs text-stone-500">cartes dans le deck</p>
        </div>
      </header>

      <section class="mt-6" aria-label="État du deck">
        <p id="deck-validity" role="status" aria-live="polite" data-valid="<?= $status['valid'] ? 'true' : 'false' ?>"
           class="inline-flex rounded-full border px-4 py-2 text-sm font-medium data-[valid=false]:border-amber-400/40 data-[valid=false]:bg-amber-400/10 data-[valid=false]:text-amber-300 data-[valid=true]:border-mint/50 data-[valid=true]:bg-mint/10 data-[valid=true]:text-mint"></p>
        <p id="deck-feedback" class="mt-2 hidden text-sm text-amber-300" role="status" aria-live="assertive"></p>
      </section>

      <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-label="Cartes disponibles">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold">Cartes disponibles</h2>
            <label>
              <span class="sr-only">Rechercher une carte</span>
              <input id="deck-search" type="search" placeholder="Rechercher par titre ou genre..." class="w-full rounded-xl border border-white/10 bg-[#171918] px-4 py-2.5 text-sm text-stone-100 outline-none transition placeholder:text-stone-600 focus:border-mint/60 focus:ring-2 focus:ring-mint/10 sm:w-80">
            </label>
          </div>

          <div id="available-grid" class="mt-4 grid grid-cols-3 gap-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-7">
            <?php foreach ($available as $card): ?>
              <?php $style = $rarityStyles[$card['rarity']] ?? $rarityStyles['Commune']; $title = (string) $card['title']; ?>
              <article
                data-available-card
                data-movie-id="<?= $card['id'] ?>"
                data-owned="<?= $card['owned'] ?>"
                data-title="<?= e($title) ?>"
                data-search="<?= e($title . ' ' . $card['genre']) ?>"
                class="flex flex-col"
              >
                <div class="group relative overflow-hidden rounded-2xl border bg-[#111312] transition duration-200 hover:-translate-y-1 hover:border-white/25 hover:shadow-2xl <?= $style['frame'] ?>">
                  <div class="relative aspect-[2/3] overflow-hidden bg-stone-900">
                    <img src="<?= e((string) $card['poster']) ?>" alt="Affiche du film <?= e($title) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                    <div class="absolute left-2 top-2 rounded-full border px-2 py-1 text-[10px] font-semibold backdrop-blur <?= $style['badge'] ?>"><?= e((string) $card['rarity']) ?></div>
                    <?php if ($card['owned'] > 1): ?>
                      <div class="absolute bottom-2 right-2 rounded-full border border-white/15 bg-black/75 px-2 py-1 text-[10px] font-semibold text-white">x<?= $card['owned'] ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="p-2">
                    <h3 class="truncate text-xs font-semibold text-stone-100" title="<?= e($title) ?>"><?= e($title) ?></h3>
                  </div>
                </div>
                <button
                  type="button"
                  data-add-deck
                  data-remaining="<?= max(0, $card['owned'] - $card['in_deck']) ?>"
                  class="mt-1.5 rounded-lg border border-mint/40 bg-mint/10 px-2 py-1.5 text-xs font-semibold text-mint transition hover:border-mint hover:bg-mint/20 disabled:cursor-not-allowed disabled:border-white/10 disabled:bg-white/5 disabled:text-stone-600"
                >Ajouter</button>
              </article>
            <?php endforeach; ?>
          </div>

          <section id="available-empty" class="mt-4 hidden rounded-2xl border border-dashed border-white/10 px-6 py-10 text-center">
            <p class="text-sm text-stone-500">Aucune carte disponible. Ouvrez des paquets pour enrichir votre collection.</p>
          </section>
        </section>

        <aside class="lg:sticky lg:top-4 lg:self-start">
          <div class="rounded-2xl border border-white/10 bg-[#101211] p-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold">Composition</h2>
            </div>

            <ul id="deck-list" data-empty-message="Le deck est vide. Ajoutez des cartes depuis votre collection." class="mt-3 space-y-2">
              <?php foreach ($deckCards as $card): ?>
                <li data-deck-card data-movie-id="<?= $card['id'] ?>" data-qty="<?= $card['quantity'] ?>" data-title="<?= e((string) $card['title']) ?>" data-poster="<?= e((string) $card['poster']) ?>" data-rarity="<?= e((string) $card['rarity']) ?>"
                    class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-2">
                  <img src="<?= e((string) $card['poster']) ?>" alt="" loading="lazy" class="h-12 w-9 shrink-0 rounded-md object-cover">
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-stone-100" title="<?= e((string) $card['title']) ?>"><?= e((string) $card['title']) ?></p>
                    <p class="text-[11px] font-semibold <?= $rarityStyles[$card['rarity']]['label'] ?? 'text-stone-500' ?>"><?= e((string) $card['rarity']) ?></p>
                  </div>
                  <span class="shrink-0 font-mono text-xs font-semibold text-stone-300">x<?= $card['quantity'] ?></span>
                  <button type="button" data-remove-deck class="shrink-0 rounded-full border border-white/10 px-2.5 py-1 text-[11px] font-medium text-stone-400 transition hover:border-amber-400/50 hover:bg-amber-400/10 hover:text-amber-300">Retirer</button>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </aside>
      </div>
    </div>
  </main>
<?php
render_end('../', ['../assets/js/deck.js']);
