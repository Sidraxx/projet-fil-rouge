<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/decks.php';

require_login('../');

$db = database();
$userId = (int) $_SESSION['user_id'];

// La page reste utilisable même si la base est incomplète : le détail
// technique part dans les logs et la console, l'utilisateur voit un bandeau.
$deckError = null;
$decks = [];

try {
    // Session obsolète (base réimportée, joueur supprimé) : retour à la connexion
    // au lieu de laisser l'insertion du deck échouer sur la clé étrangère.
    current_user('../');

    get_or_create_deck($db, $userId);
    $decks = load_user_decks($db, $userId);
} catch (Throwable $error) {
    app_log_exception($error, 'Chargement des decks impossible (joueur ' . $userId . ')');
    $deckError = app_error_message($error);
}

// Vue détail : uniquement si ?deck=N désigne un deck du joueur, sinon la liste des decks.
$requestedDeckId = (int) ($_GET['deck'] ?? 0);
$deck = null;
foreach ($decks as $deckRow) {
    if ($deckRow['id'] === $requestedDeckId) {
        $deck = $deckRow;
        break;
    }
}

$rarityStyles = [
    'Commune' => ['badge' => 'border-stone-500/40 bg-stone-500/15 text-stone-200', 'frame' => 'border-stone-500/30'],
    'Peu commune' => ['badge' => 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300', 'frame' => 'border-emerald-500/30'],
    'Rare' => ['badge' => 'border-sky-500/40 bg-sky-500/15 text-sky-300', 'frame' => 'border-sky-500/30'],
    'Épique' => ['badge' => 'border-violet-500/40 bg-violet-500/15 text-violet-300', 'frame' => 'border-violet-500/30'],
    'Légendaire' => ['badge' => 'border-amber-400/40 bg-amber-400/15 text-amber-300', 'frame' => 'border-amber-400/40'],
];

if ($deck !== null) {
    $deckId = (int) $deck['id'];

    try {
        $status = deck_status($db, $deckId);
        $available = load_deck_available_cards($db, $userId, $deckId);
        $deckCards = load_deck_cards($db, $deckId);
    } catch (Throwable $error) {
        app_log_exception($error, 'Chargement du deck ' . $deckId . ' impossible (joueur ' . $userId . ')');
        $deckError = app_error_message($error);
        $deck = null;
    }
}

render_start('Deck', 'deck', '../');
?>
  <main class="flex flex-1 flex-col px-4 pb-16 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-[1600px]">
<?php if ($deckError !== null): ?>
      <div class="rounded-2xl border border-amber-400/30 bg-amber-400/10 p-6 text-sm text-amber-200" role="alert">
        <p class="font-semibold">Le constructeur de deck est momentanément indisponible.</p>
        <p class="mt-2 leading-6 text-amber-200/80"><?= e($deckError) ?></p>
        <p class="mt-3 font-mono text-[11px] uppercase tracking-wider text-amber-200/50"><?= app_debug_enabled() ? 'Détails techniques : console du navigateur (F12) et logs PHP' : 'Détails techniques : logs PHP du serveur' ?></p>
      </div>
<?php elseif ($deck === null): ?>
      <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-mint">Constructeur</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Mes decks</h1>
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-500">
            <span>Cliquez sur un deck pour composer ses cartes. Le deck actif est celui utilisé au combat.</span>
            <span class="rounded-full border border-white/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-stone-500">Deck max : <?= DECK_MAX_SIZE ?></span>
            <span class="rounded-full border border-white/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-stone-500">Doublons max : <?= DECK_MAX_COPIES ?></span>
            <span class="rounded-full border border-white/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-stone-500">Decks : <?= count($decks) ?>/<?= DECK_MAX_COUNT ?></span>
          </p>
        </div>
        <?php if (count($decks) < DECK_MAX_COUNT): ?>
          <button type="button" data-deck-create class="self-start rounded-xl border border-mint/40 bg-mint/10 px-4 py-2.5 text-sm font-semibold text-mint transition hover:border-mint hover:bg-mint/20 lg:self-auto">Nouveau deck</button>
        <?php endif; ?>
      </header>

      <p id="deck-feedback" class="mt-4 hidden text-sm text-amber-300" role="status" aria-live="assertive"></p>

      <div id="deck-tiles" class="mt-8 flex gap-4 overflow-x-auto pb-4" aria-label="Liste des decks">
        <?php foreach ($decks as $deckRow): ?>
          <?php $initial = mb_strtoupper(mb_substr((string) $deckRow['name'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
          <a href="deck.php?deck=<?= $deckRow['id'] ?>" class="group relative aspect-[2/3] w-40 shrink-0 overflow-hidden rounded-2xl border bg-[#111312] transition duration-200 hover:-translate-y-1 hover:border-white/25 hover:shadow-2xl <?= $deckRow['is_active'] ? 'border-mint/60' : 'border-white/10' ?>">
            <?php if ($deckRow['cover'] !== ''): ?>
              <img src="<?= e($deckRow['cover']) ?>" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-80 transition duration-300 group-hover:scale-[1.03] group-hover:opacity-100">
            <?php else: ?>
              <span class="absolute inset-0 flex items-center justify-center text-6xl font-semibold text-white/10"><?= e($initial) ?></span>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent"></div>
            <?php if ($deckRow['is_active']): ?>
              <span class="absolute left-2 top-2 rounded-full border border-mint/50 bg-mint/15 px-2 py-1 font-mono text-[10px] uppercase tracking-wider text-mint backdrop-blur">Actif</span>
            <?php endif; ?>
            <div class="absolute inset-x-0 bottom-0 p-3">
              <p class="truncate text-sm font-semibold text-white"><?= e((string) $deckRow['name']) ?></p>
              <p class="mt-0.5 font-mono text-[10px] uppercase tracking-wider text-stone-400"><?= $deckRow['cards'] ?>/<?= DECK_MAX_SIZE ?> cartes</p>
            </div>
          </a>
        <?php endforeach; ?>

        <?php if (count($decks) < DECK_MAX_COUNT): ?>
          <button type="button" data-deck-create class="flex aspect-[2/3] w-40 shrink-0 flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-mint/40 bg-mint/5 text-mint transition hover:border-mint hover:bg-mint/10">
            <span class="text-4xl leading-none">+</span>
            <span class="text-sm font-semibold">Nouveau deck</span>
          </button>
        <?php endif; ?>
      </div>
<?php else: ?>
      <div id="deck-detail" data-deck-id="<?= $deckId ?>" data-deck-count="<?= count($decks) ?>">
        <a href="deck.php" class="inline-flex items-center gap-2 text-xs font-medium text-stone-400 transition hover:text-mint">&larr; Tous mes decks</a>

        <header class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-mint">Deck</p>
            <h1 class="mt-2 flex flex-wrap items-center gap-3 text-3xl font-semibold tracking-tight sm:text-4xl">
              <span id="deck-title" data-name="<?= e((string) $deck['name']) ?>"><?= e((string) $deck['name']) ?></span>
              <?php if ($deck['is_active']): ?>
                <span class="rounded-full border border-mint/50 bg-mint/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-mint">Actif</span>
              <?php endif; ?>
            </h1>
            <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-500">
              <span class="rounded-full border border-white/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-stone-500">Deck max : <?= DECK_MAX_SIZE ?></span>
              <span class="rounded-full border border-white/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-stone-500">Doublons max : <?= DECK_MAX_COPIES ?></span>
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button id="deck-rename" type="button" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-medium text-stone-300 transition hover:border-white/20 hover:bg-white/10">Renommer</button>
            <button id="deck-activate" type="button" <?= $deck['is_active'] ? 'disabled' : '' ?> class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-medium text-stone-300 transition hover:border-mint/50 hover:text-mint disabled:cursor-default disabled:opacity-40"><?= $deck['is_active'] ? 'Deck actif' : 'Utiliser au combat' ?></button>
            <button id="deck-delete" type="button" class="rounded-full border border-red-500/30 bg-red-500/10 px-4 py-2 text-xs font-medium text-red-300 transition hover:border-red-500/50 hover:bg-red-500/20">Supprimer</button>
          </div>
        </header>

        <section class="mt-8 rounded-2xl border border-white/10 bg-[#101211] p-5" aria-label="Composition du deck">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-baseline gap-3">
              <h2 class="text-lg font-semibold">Composition</h2>
              <p class="font-mono text-sm font-semibold text-mint"><span id="deck-count" data-size="<?= $status['size'] ?>"><?= $status['count'] ?></span> / <?= $status['size'] ?></p>
            </div>
            <p id="deck-validity" role="status" aria-live="polite" data-valid="<?= $status['valid'] ? 'true' : 'false' ?>"
               class="inline-flex w-fit rounded-full border px-4 py-1.5 text-sm font-medium data-[valid=false]:border-amber-400/40 data-[valid=false]:bg-amber-400/10 data-[valid=false]:text-amber-300 data-[valid=true]:border-mint/50 data-[valid=true]:bg-mint/10 data-[valid=true]:text-mint"></p>
          </div>
          <p id="deck-feedback" class="mt-3 hidden text-sm text-amber-300" role="status" aria-live="assertive"></p>

          <ul id="deck-list" data-empty-message="Le deck est vide. Ajoutez des cartes depuis votre collection." class="mt-4 flex gap-4 overflow-x-auto pb-2">
            <?php foreach ($deckCards as $card): ?>
              <li data-deck-card data-movie-id="<?= $card['id'] ?>" data-qty="<?= $card['quantity'] ?>" data-title="<?= e((string) $card['title']) ?>" data-poster="<?= e((string) $card['poster']) ?>" data-rarity="<?= e((string) $card['rarity']) ?>"
                  class="flex w-28 shrink-0 flex-col gap-2">
                <div class="relative aspect-[2/3] overflow-hidden rounded-xl border border-white/10 bg-stone-900">
                  <img src="<?= e((string) $card['poster']) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
                  <span class="absolute bottom-1.5 right-1.5 rounded-full border border-white/15 bg-black/75 px-2 py-0.5 text-[10px] font-semibold text-white">x<?= $card['quantity'] ?></span>
                </div>
                <div class="min-w-0">
                  <p class="truncate text-xs font-semibold text-stone-100" title="<?= e((string) $card['title']) ?>"><?= e((string) $card['title']) ?></p>
                  <p class="text-[11px] font-semibold text-stone-400"><?= e((string) $card['rarity']) ?></p>
                </div>
                <button type="button" data-remove-deck class="w-full rounded-lg border border-white/10 px-2 py-1.5 text-xs font-medium text-stone-400 transition hover:border-amber-400/50 hover:bg-amber-400/10 hover:text-amber-300">Retirer</button>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="mt-8" aria-label="Cartes disponibles">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold">Ma collection</h2>
            <label>
              <span class="sr-only">Rechercher une carte</span>
              <input id="deck-search" type="search" placeholder="Rechercher par titre ou genre..." class="w-full rounded-xl border border-white/10 bg-[#171918] px-4 py-2.5 text-sm text-stone-100 outline-none transition placeholder:text-stone-600 focus:border-mint/60 focus:ring-2 focus:ring-mint/10 sm:w-80">
            </label>
          </div>

          <div id="available-grid" class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-8">
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
      </div>
<?php endif; ?>
    </div>
  </main>

  <dialog id="deck-name-dialog" class="w-[min(100%-2rem,24rem)] rounded-2xl border border-white/10 bg-[#161616] p-0 text-stone-100 shadow-2xl backdrop:bg-black/70">
    <form id="deck-name-form" class="p-6">
      <h2 id="deck-name-title" class="text-lg font-semibold">Nouveau deck</h2>
      <label class="mt-4 block">
        <span class="font-mono text-[9px] uppercase tracking-wider text-stone-500">Nom du deck (60 caractères max.)</span>
        <input id="deck-name-input" type="text" maxlength="60" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#171918] px-4 py-2.5 text-sm text-stone-100 outline-none transition placeholder:text-stone-600 focus:border-mint/60 focus:ring-2 focus:ring-mint/10" placeholder="Mon deck de Drama">
      </label>
      <p class="mt-1.5 hidden text-xs text-red-400" id="deck-name-error" role="alert"></p>
      <div class="mt-6 flex justify-end gap-2">
        <button type="button" id="deck-name-cancel" class="rounded-full border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 hover:bg-white/5">Annuler</button>
        <button type="submit" id="deck-name-submit" class="rounded-full bg-mint px-4 py-2 text-sm font-semibold text-black">Créer</button>
      </div>
    </form>
  </dialog>

  <dialog id="deck-delete-dialog" class="w-[min(100%-2rem,24rem)] rounded-2xl border border-white/10 bg-[#161616] p-0 text-stone-100 shadow-2xl backdrop:bg-black/70">
    <div class="p-6">
      <h2 class="text-lg font-semibold">Supprimer ce deck ?</h2>
      <p class="mt-3 text-sm leading-6 text-stone-400">« <span id="deck-delete-name"></span> » et toutes ses cartes seront supprimés. Cette action est définitive.</p>
      <p id="deck-delete-error" class="mt-2 hidden text-xs text-red-400" role="alert"></p>
      <div class="mt-6 flex justify-end gap-2">
        <button type="button" id="deck-delete-cancel" class="rounded-full border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 hover:bg-white/5">Annuler</button>
        <button type="button" id="deck-delete-confirm" class="rounded-full bg-red-500 px-4 py-2 text-sm font-semibold text-white">Supprimer</button>
      </div>
    </div>
  </dialog>
<?php
render_end('../', ['assets/js/deck.js']);
