const DEBUG = true;
const log = (...args) => { if (DEBUG) console.log('[deck.js]', ...args); };
const warn = (...args) => { if (DEBUG) console.warn('[deck.js]', ...args); };

window.__deckJsLoaded = true;
log('script chargé', { url: window.location.href, readyState: document.readyState });

const detailRoot = document.querySelector('#deck-detail');
const searchInput = document.querySelector('#deck-search');
const availableGrid = document.querySelector('#available-grid');
const availableEmpty = document.querySelector('#available-empty');
const countEl = document.querySelector('#deck-count');
const validityEl = document.querySelector('#deck-validity');
const feedbackEl = document.querySelector('#deck-feedback');
const deckList = document.querySelector('#deck-list');
const deckTitle = document.querySelector('#deck-title');

const createButtons = document.querySelectorAll('[data-deck-create]');
const deckRenameButton = document.querySelector('#deck-rename');
const deckActivateButton = document.querySelector('#deck-activate');
const deckDeleteButton = document.querySelector('#deck-delete');

const deckNameDialog = document.querySelector('#deck-name-dialog');
const deckNameForm = document.querySelector('#deck-name-form');
const deckNameTitle = document.querySelector('#deck-name-title');
const deckNameInput = document.querySelector('#deck-name-input');
const deckNameError = document.querySelector('#deck-name-error');
const deckNameSubmit = document.querySelector('#deck-name-submit');

const deckDeleteDialog = document.querySelector('#deck-delete-dialog');
const deckDeleteName = document.querySelector('#deck-delete-name');
const deckDeleteError = document.querySelector('#deck-delete-error');
const deckDeleteConfirm = document.querySelector('#deck-delete-confirm');

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (!csrfMeta) {
  warn('meta[name="csrf-token"] introuvable : les appels à deck-api.php échoueront (403).');
}
const csrfToken = csrfMeta?.content ?? '';

const currentDeckId = Number(detailRoot?.dataset.deckId || 0);
const currentDeckName = deckTitle?.dataset.name || '';
const deckCountTotal = Number(detailRoot?.dataset.deckCount || 0);

log('état initial', {
  mode: detailRoot ? 'détail' : 'liste',
  deckId: currentDeckId,
  cartesDansLeDeck: deckList ? deckList.querySelectorAll('[data-deck-card]').length : 0,
  cartesDisponibles: availableGrid ? availableGrid.querySelectorAll('[data-available-card]').length : 0,
  boutonCreate: createButtons.length,
  boutonRename: Boolean(deckRenameButton),
  boutonActivate: Boolean(deckActivateButton),
  boutonDelete: Boolean(deckDeleteButton),
  dialogNom: Boolean(deckNameDialog),
  dialogSuppression: Boolean(deckDeleteDialog),
  deckApi: new URL('../deck-api.php', window.location.href).href,
});

const rules = {
  size: Number(countEl?.dataset.size || 10),
};

// État local : cartes possédées (movieId -> exemplaires) et composition du deck.
const owned = new Map();
const deck = new Map(); // movieId -> { title, poster, rarity, quantity }

function normalize(value) {
  return value
    .toLocaleLowerCase('fr')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

function deckCount() {
  let total = 0;
  deck.forEach((entry) => {
    total += entry.quantity;
  });
  return total;
}

function deckPage(deckId = null) {
  const target = new URL(window.location.href);
  if (deckId) {
    target.searchParams.set('deck', String(deckId));
  } else {
    target.searchParams.delete('deck');
  }
  return target.href;
}

function navigate(deckId = null) {
  window.location.href = deckPage(deckId);
}

function renderValidity() {
  const count = deckCount();
  const valid = count === rules.size;

  countEl.textContent = String(count);
  validityEl.dataset.valid = String(valid);

  if (valid) {
    validityEl.textContent = 'Deck prêt au combat';
  } else {
    const missing = rules.size - count;
    validityEl.textContent = count === 0
      ? `Deck vide — il manque ${missing} carte${missing > 1 ? 's' : ''}`
      : `Deck incomplet — ${missing} carte${missing > 1 ? 's' : ''} manquante${missing > 1 ? 's' : ''}`;
  }
}

function renderDeckList() {
  deckList.textContent = '';

  if (deck.size === 0) {
    const empty = document.createElement('li');
    empty.className = 'w-full rounded-xl border border-dashed border-white/10 px-6 py-10 text-center text-sm text-stone-500';
    empty.textContent = deckList.dataset.emptyMessage;
    deckList.append(empty);
    return;
  }

  deck.forEach((entry, movieId) => {
    const item = document.createElement('li');
    item.className = 'flex w-28 shrink-0 flex-col gap-2';

    const card = document.createElement('div');
    card.className = 'relative aspect-[2/3] overflow-hidden rounded-xl border border-white/10 bg-stone-900';

    const poster = document.createElement('img');
    poster.src = entry.poster;
    poster.alt = '';
    poster.loading = 'lazy';
    poster.className = 'h-full w-full object-cover';

    const quantity = document.createElement('span');
    quantity.className = 'absolute bottom-1.5 right-1.5 rounded-full border border-white/15 bg-black/75 px-2 py-0.5 text-[10px] font-semibold text-white';
    quantity.textContent = `x${entry.quantity}`;

    card.append(poster, quantity);

    const info = document.createElement('div');
    info.className = 'min-w-0';

    const title = document.createElement('p');
    title.className = 'truncate text-xs font-semibold text-stone-100';
    title.title = entry.title;
    title.textContent = entry.title;

    const rarity = document.createElement('p');
    rarity.className = 'text-[11px] font-semibold text-stone-400';
    rarity.textContent = entry.rarity;

    info.append(title, rarity);

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'w-full rounded-lg border border-white/10 px-2 py-1.5 text-xs font-medium text-stone-400 transition hover:border-amber-400/50 hover:bg-amber-400/10 hover:text-amber-300';
    removeButton.textContent = 'Retirer';
    removeButton.addEventListener('click', () => sendCardAction('remove', Number(movieId)));

    item.append(card, info, removeButton);
    deckList.append(item);
  });

  log('liste du deck rendue', {
    cartes: deck.size,
    boutonsRetirer: deckList.querySelectorAll('[data-remove-deck], button').length,
    total: deckCount(),
    taille: rules.size,
  });
}

function renderAvailable() {
  let visibleCount = 0;
  const query = searchInput ? searchInput.value.trim() : '';

  availableGrid.querySelectorAll('[data-available-card]').forEach((el) => {
    const movieId = Number(el.dataset.movieId);
    const inDeck = deck.get(movieId)?.quantity ?? 0;
    const remaining = Math.max(0, Number(el.dataset.owned) - inDeck);
    const button = el.querySelector('[data-add-deck]');

    const matchesSearch = query === '' || normalize(el.dataset.search).includes(normalize(query));
    el.classList.toggle('hidden', !matchesSearch);
    if (matchesSearch) visibleCount += 1;

    button.dataset.remaining = String(remaining);
    button.disabled = remaining === 0 || deckCount() >= rules.size;
    button.textContent = remaining === 0 ? 'Épuisé' : 'Ajouter';
  });

  availableGrid.classList.toggle('hidden', visibleCount === 0);
  availableEmpty.classList.toggle('hidden', visibleCount !== 0);

  log('rendu collection', {
    visibles: visibleCount,
    boutonsAjouter: availableGrid.querySelectorAll('[data-add-deck]').length,
    boutonsDesactives: availableGrid.querySelectorAll('[data-add-deck]:disabled').length,
    cartesDansLeDeck: deckCount(),
    tailleDeck: rules.size,
  });
}

async function postDeckApi(payload) {
  const url = new URL('../deck-api.php', window.location.href).href;
  log('→ POST', url, payload);

  let response;
  try {
    response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify(payload),
    });
  } catch (error) {
    warn('échec réseau / requête bloquée', { url, error });
    showFeedback('Connexion impossible. Vérifiez le serveur.');
    return null;
  }

  log('← réponse', { status: response.status, ok: response.ok, url: response.url, redirected: response.redirected });

  const raw = await response.text();
  let result = null;
  try {
    result = JSON.parse(raw);
  } catch (error) {
    warn('réponse non JSON (redirection vers login ? erreur PHP ?)', { status: response.status, body: raw.slice(0, 500), error });
  }

  if (!response.ok || !result?.success) {
    warn('action refusée', { status: response.status, payload, result, body: result ? undefined : raw.slice(0, 500) });
    showFeedback(result?.message || 'L\'action a échoué.');
    return null;
  }

  log('✔ action réussie', { payload, result });
  hideFeedback();
  return result;
}

async function sendCardAction(action, movieId) {
  log('carte →', action, { movieId, deckId: currentDeckId });
  const result = await postDeckApi({ action, movieId, deckId: currentDeckId });
  if (!result) return;

  deck.clear();
  (result.cards || []).forEach((card) => {
    deck.set(Number(card.id), {
      title: card.title,
      poster: card.poster,
      rarity: card.rarity,
      quantity: Number(card.quantity),
    });
  });

  if (typeof result.size === 'number') rules.size = result.size;

  renderDeckList();
  renderAvailable();
  renderValidity();
}

function showFeedback(message) {
  feedbackEl.textContent = message;
  feedbackEl.classList.remove('hidden');
}

function hideFeedback() {
  feedbackEl.classList.add('hidden');
}

function openNameDialog(action) {
  const isCreate = action === 'create';
  deckNameTitle.textContent = isCreate ? 'Nouveau deck' : 'Renommer le deck';
  deckNameSubmit.textContent = isCreate ? 'Créer' : 'Renommer';
  deckNameError.classList.add('hidden');
  deckNameForm.dataset.action = action;
  deckNameInput.value = isCreate ? '' : currentDeckName;
  deckNameDialog.showModal();
  if (isCreate) {
    deckNameInput.focus();
  } else {
    deckNameInput.select();
  }
}

// Création depuis la liste des decks (boutons "Nouveau").
createButtons.forEach((button) => {
  button.addEventListener('click', () => {
    log('clic bouton "Nouveau deck"');
    openNameDialog('create');
  });
});

deckNameForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const name = deckNameInput.value.trim();
  log('soumission formulaire deck', { name, action: deckNameForm.dataset.action, deckId: currentDeckId });

  if (name === '') {
    warn('nom vide, envoi annulé');
    deckNameError.textContent = 'Donnez un nom à votre deck (60 caractères maximum).';
    deckNameError.classList.remove('hidden');
    return;
  }

  deckNameSubmit.disabled = true;
  const action = deckNameForm.dataset.action;
  const result = await postDeckApi({
    action,
    deckId: currentDeckId,
    name,
  });
  deckNameSubmit.disabled = false;

  if (result) {
    deckNameDialog.close();
    log('redirection après', action, { deckId: result.deckId ?? currentDeckId });
    navigate(action === 'create' ? result.deckId : currentDeckId);
  }
});

['deck-name-cancel', 'deck-delete-cancel'].forEach((id) => {
  document.querySelector(`#${id}`).addEventListener('click', () => {
    document.querySelector(id === 'deck-name-cancel' ? '#deck-name-dialog' : '#deck-delete-dialog').close();
  });
});

[deckNameDialog, deckDeleteDialog].forEach((dialog) => {
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });
});

if (detailRoot) {
  deckList.querySelectorAll('[data-deck-card]').forEach((el) => {
    deck.set(Number(el.dataset.movieId), {
      title: el.dataset.title,
      poster: el.dataset.poster,
      rarity: el.dataset.rarity,
      quantity: Number(el.dataset.qty),
    });
  });

  availableGrid.querySelectorAll('[data-available-card]').forEach((el) => {
    owned.set(Number(el.dataset.movieId), Number(el.dataset.owned));
  });

  deckRenameButton.addEventListener('click', () => {
    log('clic "Renommer"');
    openNameDialog('rename');
  });

  deckActivateButton.addEventListener('click', async () => {
    log('clic "Utiliser au combat"', { deckId: currentDeckId, disabled: deckActivateButton.disabled });
    const result = await postDeckApi({ action: 'switch', deckId: currentDeckId });
    if (result) navigate(currentDeckId);
  });

  deckDeleteButton.addEventListener('click', () => {
    log('clic "Supprimer"', { deckId: currentDeckId, nom: currentDeckName, totalDecks: deckCountTotal });
    deckDeleteName.textContent = currentDeckName;
    deckDeleteError.classList.add('hidden');
    deckDeleteDialog.showModal();
  });

  deckDeleteConfirm.addEventListener('click', async () => {
    log('confirmation suppression', { deckId: currentDeckId, totalDecks: deckCountTotal });
    if (deckCountTotal <= 1) {
      warn('suppression refusée localement : dernier deck');
      deckDeleteError.textContent = 'Impossible de supprimer votre dernier deck.';
      deckDeleteError.classList.remove('hidden');
      return;
    }
    const result = await postDeckApi({ action: 'delete', deckId: currentDeckId });
    if (result) navigate(null);
  });

  searchInput.addEventListener('input', () => {
    log('recherche', { valeur: searchInput.value });
    renderAvailable();
  });

  availableGrid.querySelectorAll('[data-add-deck]').forEach((button) => {
    button.addEventListener('click', () => {
      const host = button.closest('[data-available-card]');
      log('clic "Ajouter"', {
        movieId: host?.dataset.movieId,
        remaining: button.dataset.remaining,
        disabled: button.disabled,
      });
      if (!host) {
        warn('bouton "Ajouter" sans [data-available-card] parent');
        return;
      }
      sendCardAction('add', Number(host.dataset.movieId));
    });
  });

  log('listeners du détail attachés ✔');

  renderDeckList();
  renderAvailable();
  renderValidity();
}
