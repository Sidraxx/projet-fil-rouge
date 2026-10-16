const pack = document.querySelector('#pack');
const openPack = document.querySelector('#open-pack');
const status = document.querySelector('#pack-status');
const dialog = document.querySelector('#how-it-works');
const howLink = document.querySelector('#how-link');
const howClose = document.querySelector('#how-close');
const packStage = document.querySelector('#pack-stage');
const revealStage = document.querySelector('#reveal-stage');
const cardRow = document.querySelector('#card-row');
const revealSummary = document.querySelector('#reveal-summary');
const revealClose = document.querySelector('#reveal-close');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

const RARITY_KEYS = new Set(['commune', 'peu-commune', 'rare', 'epique', 'legendaire']);
const RARITY_ORDER = ['Légendaire', 'Épique', 'Rare', 'Peu commune', 'Commune'];
const MARKS = {
  commune: '<circle cx="8" cy="8" r="3.2" fill="currentColor"></circle>',
  'peu-commune': '<path d="M8 1.4 13.2 8 8 14.6 2.8 8Z" fill="currentColor"></path>',
  rare: '<path d="M8 1.2 13.4 4.3v6.2L8 13.6 2.6 10.5V4.3Z" fill="currentColor"></path>',
  epique: '<path d="m8 1.1 1.55 4.15 4.45.18-3.5 2.75 1.2 4.28L8 9.95 4.3 12.46l1.2-4.28-3.5-2.75 4.45-.18Z" fill="currentColor"></path>',
  legendaire: '<path d="M8 1.1 9.7 5.4l4.7.3-3.6 3 1.2 4.5L8 10.8 4 13.2l1.2-4.5-3.6-3 4.7-.3Z" fill="currentColor"></path><circle cx="8" cy="8" r="6.6" fill="none" stroke="currentColor" stroke-width="1"></circle>',
};

let opening = false;
let revealTimers = [];
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const packCount = document.querySelector('#pack-count');
const packNext = document.querySelector('#pack-next');
const PACK_LIMIT = Number(packCount.dataset.limit);
const PACK_REFILL_MS = 5 * 60 * 1000;
let packsAvailable = Number(packCount.dataset.available);
let nextPackAt = packCount.dataset.nextIn !== '' ? Date.now() + Number(packCount.dataset.nextIn) * 1000 : null;

function formatRemaining(ms) {
  const seconds = Math.max(0, Math.ceil(ms / 1000));
  return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

function renderPacks() {
  packCount.textContent = `${packsAvailable} / ${PACK_LIMIT}`;
  openPack.disabled = !opening && packsAvailable <= 0;
  if (nextPackAt === null || packsAvailable >= PACK_LIMIT) {
    packNext.textContent = '';
  } else {
    packNext.textContent = `Prochain paquet dans ${formatRemaining(nextPackAt - Date.now())}`;
  }
}

function setPackState(available, nextIn) {
  packsAvailable = available;
  nextPackAt = typeof nextIn === 'number' ? Date.now() + nextIn * 1000 : null;
  renderPacks();
}

function tickPacks() {
  if (nextPackAt !== null && packsAvailable < PACK_LIMIT && Date.now() >= nextPackAt) {
    packsAvailable += 1;
    nextPackAt = packsAvailable < PACK_LIMIT ? nextPackAt + PACK_REFILL_MS : null;
  }
  renderPacks();
}

renderPacks();
window.setInterval(tickPacks, 1000);

function wait(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

function plural(level, count) {
  if (count === 1) return level;
  if (level === 'Peu commune') return 'Peu communes';
  return `${level}s`;
}

function summarize(cards) {
  const counts = {};
  cards.forEach((card) => {
    counts[card.rarity] = (counts[card.rarity] || 0) + 1;
  });

  return RARITY_ORDER
    .filter((level) => counts[level])
    .map((level) => `${counts[level]} ${plural(level, counts[level])}`)
    .join(', ');
}

function clearRevealTimers() {
  revealTimers.forEach((id) => window.clearTimeout(id));
  revealTimers = [];
}

function later(fn, delay) {
  revealTimers.push(window.setTimeout(fn, delay));
}

function markMissing(poster) {
  poster.querySelector('img')?.remove();
  if (poster.querySelector('.film-card__placeholder')) return;
  poster.classList.add('is-missing');
  const label = document.createElement('span');
  label.className = 'film-card__placeholder';
  label.textContent = 'AC';
  poster.prepend(label);
}

function createCard(card) {
  const key = RARITY_KEYS.has(card.rarityKey) ? card.rarityKey : 'commune';
  const article = document.createElement('article');
  article.className = `film-card film-card--${key}`;
  article.style.setProperty('--rarity', card.color || '');
  article.setAttribute('aria-label', `${card.title}, ${card.rarity}`);

  const inner = document.createElement('div');
  inner.className = 'film-card__inner';

  const back = document.createElement('div');
  back.className = 'film-card__back';
  back.setAttribute('aria-hidden', 'true');
  const logo = document.createElement('div');
  logo.className = 'film-card__logo';
  logo.textContent = 'AC';
  const backLabel = document.createElement('span');
  backLabel.className = 'film-card__back-label';
  backLabel.textContent = 'Absolute Cinema';
  back.append(logo, backLabel);

  const front = document.createElement('div');
  front.className = 'film-card__front';

  const header = document.createElement('div');
  header.className = 'film-card__header';

  const badge = document.createElement('span');
  badge.className = 'film-card__badge';
  const mark = document.createElement('span');
  mark.className = 'film-card__mark';
  mark.innerHTML = `<svg viewBox="0 0 16 16" aria-hidden="true">${MARKS[key]}</svg>`;
  const name = document.createElement('span');
  name.className = 'film-card__name';
  name.textContent = card.rarity || 'Commune';
  badge.append(mark, name);

  const rating = document.createElement('span');
  rating.className = 'film-card__rating';
  const ratingLabel = document.createElement('span');
  ratingLabel.textContent = 'IMDb';
  rating.append(ratingLabel, document.createTextNode(card.imdb || '—'));
  header.append(badge, rating);

  const poster = document.createElement('div');
  poster.className = 'film-card__poster';
  if (typeof card.poster === 'string' && /^https?:\/\//i.test(card.poster)) {
    const image = document.createElement('img');
    image.alt = '';
    image.decoding = 'async';
    image.src = card.poster;
    image.addEventListener('error', () => markMissing(poster));
    poster.append(image);
  } else {
    markMissing(poster);
  }
  const foil = document.createElement('span');
  foil.className = 'film-card__foil';
  foil.setAttribute('aria-hidden', 'true');
  poster.append(foil);

  const footer = document.createElement('div');
  footer.className = 'film-card__footer';
  const title = document.createElement('h2');
  title.className = 'film-card__title';
  title.textContent = card.title || 'Film';
  const meta = document.createElement('p');
  meta.className = 'film-card__meta';
  meta.textContent = [card.year, card.genre].filter(Boolean).join(' · ') || '\u00a0';
  footer.append(title, meta);

  const bar = document.createElement('span');
  bar.className = 'film-card__bar';
  bar.setAttribute('aria-hidden', 'true');

  front.append(header, poster, footer, bar);
  inner.append(back, front);
  article.append(inner);
  return article;
}

function showCards(cards) {
  clearRevealTimers();
  cardRow.replaceChildren();
  revealSummary.textContent = summarize(cards);
  packStage.classList.add('hidden');
  revealStage.classList.remove('hidden');

  cards.forEach((card, index) => {
    const node = createCard(card);
    cardRow.append(node);
    const enterAt = reduceMotion ? 0 : index * 80;
    const flipAt = reduceMotion ? 0 : 360 + index * 340;
    later(() => node.classList.add('is-in'), enterAt);
    later(() => node.classList.add('is-revealed'), flipAt);
  });
}

function closeReveal() {
  clearRevealTimers();
  revealStage.classList.add('hidden');
  packStage.classList.remove('hidden');
  cardRow.replaceChildren();
  revealSummary.textContent = '';
  status.textContent = '';
  pack.classList.remove('is-opening');
}

async function playOpen() {
  if (opening) return;
  opening = true;
  openPack.disabled = true;
  status.textContent = 'Ouverture du paquet…';
  pack.classList.remove('is-opening');
  void pack.offsetWidth;
  pack.classList.add('is-opening');

  try {
    const [response] = await Promise.all([
      fetch('open-pack.php', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
      }),
      wait(reduceMotion ? 0 : 720),
    ]);
    const result = await response.json();
    if (typeof result.packsAvailable === 'number') {
      setPackState(result.packsAvailable, result.nextIn ?? null);
    }
    if (!response.ok || !result.success || !Array.isArray(result.cards)) {
      status.textContent = result.message || 'Le tirage a échoué.';
      return;
    }
    status.textContent = '';
    showCards(result.cards);
  } catch {
    status.textContent = 'Le serveur est indisponible. Vérifiez que WAMP est démarré.';
  } finally {
    opening = false;
    openPack.disabled = packsAvailable <= 0;
  }
}

openPack.addEventListener('click', playOpen);
revealClose.addEventListener('click', closeReveal);
howLink.addEventListener('click', () => dialog.showModal());
howClose.addEventListener('click', () => dialog.close());
dialog.addEventListener('click', (event) => {
  if (event.target === dialog) dialog.close();
});
