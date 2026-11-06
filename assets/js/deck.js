const searchInput = document.querySelector('#deck-search');
const availableGrid = document.querySelector('#available-grid');
const availableEmpty = document.querySelector('#available-empty');
const countEl = document.querySelector('#deck-count');
const validityEl = document.querySelector('#deck-validity');
const feedbackEl = document.querySelector('#deck-feedback');
const deckList = document.querySelector('#deck-list');
const deckEmptyMessage = deckList.dataset.emptyMessage;

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

const rules = {
  size: Number(countEl.dataset.size || 10),
  copies: 2,
};

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
    empty.textContent = deckEmptyMessage;
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
    removeButton.dataset.removeDeck = '';
    removeButton.className = 'shrink-0 rounded-full border border-white/10 px-2.5 py-1 text-[11px] font-medium text-stone-400 transition hover:border-amber-400/50 hover:bg-amber-400/10 hover:text-amber-300';
    removeButton.textContent = 'Retirer';

    removeButton.addEventListener('click', () => sendAction('remove', Number(movieId)));

    item.append(poster, info, quantity, removeButton);
    deckList.append(item);
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
}

async function sendAction(action, movieId) {
  let response;
  try {
    response = await fetch('../deck-api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify({ action, movieId }),
    });
  } catch {
    showFeedback('Connexion impossible. Vérifiez le serveur.');
    return;
  }

  const result = await response.json();

  if (!response.ok || !result.success) {
    showFeedback(result.message || 'L\'action a échoué.');
    return;
  }

  hideFeedback();

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

searchInput.addEventListener('input', renderAvailable);

availableGrid.querySelectorAll('[data-add-deck]').forEach((button) => {
  button.addEventListener('click', () => {
    const host = button.closest('[data-available-card]');
    sendAction('add', Number(host.dataset.movieId));
  });
});

renderDeckList();
renderAvailable();
renderValidity();
