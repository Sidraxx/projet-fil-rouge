const searchInput = document.querySelector('#deck-search');
const availableGrid = document.querySelector('#available-grid');
const availableEmpty = document.querySelector('#available-empty');
const countEl = document.querySelector('#deck-count');
const validityEl = document.querySelector('#deck-validity');
const feedbackEl = document.querySelector('#deck-feedback');
const deckList = document.querySelector('#deck-list');
const deckSelect = document.querySelector('#deck-select');
const deckTitle = document.querySelector('#deck-title');
const deckActiveBadge = document.querySelector('#deck-active-badge');

const deckNewButton = document.querySelector('#deck-new');
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

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

const rules = {
  size: Number(countEl.dataset.size || 10),
  copies: 2,
};

const currentDeckId = Number(deckSelect.dataset.current);
const currentDeckName = deckTitle.dataset.name || '';

// État local : cartes possédées (movieId -> exemplaires) et composition du deck.
const owned = new Map();
const deck = new Map(); // movieId -> { title, poster, rarity, quantity }

availableGrid.querySelectorAll('[data-available-card]').forEach((el) => {
  owned.set(Number(el.dataset.movieId), Number(el.dataset.owned));
});

deckList.querySelectorAll('[data-deck-card]').forEach((el) => {
  deck.set(Number(el.dataset.movieId), {
    title: el.dataset.title,
    poster: el.dataset.poster,
    rarity: el.dataset.rarity,
    quantity: Number(el.dataset.qty),
  });
});

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
    empty.className = 'rounded-xl border border-dashed border-white/10 px-3 py-6 text-center text-xs text-stone-500';
    empty.textContent = deckList.dataset.emptyMessage;
    deckList.append(empty);
    return;
  }

  deck.forEach((entry, movieId) => {
    const item = document.createElement('li');
    item.className = 'flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-2';

    const poster = document.createElement('img');
    poster.src = entry.poster;
    poster.alt = '';
    poster.loading = 'lazy';
    poster.className = 'h-12 w-9 shrink-0 rounded-md object-cover';

    const info = document.createElement('div');
    info.className = 'min-w-0 flex-1';

    const title = document.createElement('p');
    title.className = 'truncate text-sm font-medium text-stone-100';
    title.title = entry.title;
    title.textContent = entry.title;

    const rarity = document.createElement('p');
    rarity.className = 'text-[11px] font-semibold text-stone-400';
    rarity.textContent = entry.rarity;

    info.append(title, rarity);

    const quantity = document.createElement('span');
    quantity.className = 'shrink-0 font-mono text-xs font-semibold text-stone-300';
    quantity.textContent = `x${entry.quantity}`;

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'shrink-0 rounded-full border border-white/10 px-2.5 py-1 text-[11px] font-medium text-stone-400 transition hover:border-amber-400/50 hover:bg-amber-400/10 hover:text-amber-300';
    removeButton.textContent = 'Retirer';
    removeButton.addEventListener('click', () => sendCardAction('remove', Number(movieId)));

    item.append(poster, info, quantity, removeButton);
    deckList.append(item);
  });
}

function updateDeckCounts(count) {
  // Met à jour l'entrée du deck courant dans le sélecteur (6/10 -> 7/10).
  const option = deckSelect.querySelector(`option[value="${currentDeckId}"]`);
  if (option) {
    option.textContent = option.textContent.replace(/\(\d+\/\d+\)/, `(${count}/${rules.size})`);
  }
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
}

async function postDeckApi(payload) {
  let response;
  try {
    response = await fetch('../deck-api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify(payload),
    });
  } catch {
    showFeedback('Connexion impossible. Vérifiez le serveur.');
    return null;
  }

  const result = await response.json().catch(() => null);

  if (!response.ok || !result?.success) {
    showFeedback(result?.message || 'L\'action a échoué.');
    return null;
  }

  hideFeedback();
  return result;
}

async function sendCardAction(action, movieId) {
  const result = await postDeckApi({ action, movieId, deckId: currentDeckId });

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

  updateDeckCounts(result.count);
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

// Gestion des decks : bascule, création, renommage et suppression.
deckSelect.addEventListener('change', () => {
  navigate(Number(deckSelect.value));
});

deckNewButton.addEventListener('click', () => {
  deckNameTitle.textContent = 'Nouveau deck';
  deckNameSubmit.textContent = 'Créer';
  deckNameError.classList.add('hidden');
  deckNameForm.dataset.action = 'create';
  deckNameInput.value = '';
  deckNameDialog.showModal();
  deckNameInput.focus();
});

deckRenameButton.addEventListener('click', () => {
  deckNameTitle.textContent = 'Renommer le deck';
  deckNameSubmit.textContent = 'Renommer';
  deckNameError.classList.add('hidden');
  deckNameForm.dataset.action = 'rename';
  deckNameInput.value = currentDeckName;
  deckNameDialog.showModal();
  deckNameInput.select();
});

deckActivateButton.addEventListener('click', async () => {
  const result = await postDeckApi({ action: 'switch', deckId: currentDeckId });
  if (result) navigate(currentDeckId);
});

deckDeleteButton.addEventListener('click', () => {
  deckDeleteName.textContent = currentDeckName;
  deckDeleteError.classList.add('hidden');
  deckDeleteDialog.showModal();
});

deckDeleteConfirm.addEventListener('click', async () => {
  if (deckSelect.options.length <= 1) {
    deckDeleteError.textContent = 'Impossible de supprimer votre dernier deck.';
    deckDeleteError.classList.remove('hidden');
    return;
  }
  const result = await postDeckApi({ action: 'delete', deckId: currentDeckId });
  if (result) navigate(null);
});

deckNameForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const name = deckNameInput.value.trim();
  if (name === '') {
    deckNameError.textContent = 'Donnez un nom à votre deck (60 caractères maximum).';
    deckNameError.classList.remove('hidden');
    return;
  }

  deckNameSubmit.disabled = true;
  const result = await postDeckApi({
    action: deckNameForm.dataset.action,
    deckId: currentDeckId,
    name,
  });
  deckNameSubmit.disabled = false;

  if (result) {
    deckNameDialog.close();
    navigate(deckNameForm.dataset.action === 'create' ? result.deckId : currentDeckId);
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

searchInput.addEventListener('input', renderAvailable);

availableGrid.querySelectorAll('[data-add-deck]').forEach((button) => {
  button.addEventListener('click', () => {
    const host = button.closest('[data-available-card]');
    sendCardAction('add', Number(host.dataset.movieId));
  });
});

renderDeckList();
renderAvailable();
renderValidity();
