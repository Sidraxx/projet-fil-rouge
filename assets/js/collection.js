const searchInput = document.querySelector('#collection-search');
const sortSelect = document.querySelector('#collection-sort');
const rarityButtons = [...document.querySelectorAll('[data-rarity-filter]')];
const grid = document.querySelector('#collection-grid');
const cards = [...document.querySelectorAll('[data-card]')];
const resultCount = document.querySelector('#result-count');
const emptyState = document.querySelector('#collection-empty');
const selectionButton = document.querySelector('#selection-button');

let activeRarity = 'all';
let selectionMode = false;

function normalize(value) {
  return value
    .toLocaleLowerCase('fr')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

function matchesFilters(card) {
  const query = normalize(searchInput.value.trim());
  const matchesSearch = query === '' || normalize(card.dataset.search).includes(query);
  const matchesRarity = activeRarity === 'all' || card.dataset.rarity === activeRarity;

  return matchesSearch && matchesRarity;
}

function sortedCards() {
  const rarityOrder = {
    'Légendaire': 5,
    'Épique': 4,
    'Rare': 3,
    'Peu commune': 2,
    'Commune': 1,
  };

  return [...cards].sort((first, second) => {
    if (sortSelect.value === 'title') {
      return first.dataset.title.localeCompare(second.dataset.title, 'fr');
    }

    if (sortSelect.value === 'favorites') {
      const favoriteDifference = Number(second.dataset.favorite === 'true') - Number(first.dataset.favorite === 'true');
      return favoriteDifference || first.dataset.title.localeCompare(second.dataset.title, 'fr');
    }

    if (sortSelect.value === 'newest') {
      return second.dataset.added.localeCompare(first.dataset.added);
    }

    const rarityDifference = rarityOrder[second.dataset.rarity] - rarityOrder[first.dataset.rarity];
    return rarityDifference || first.dataset.title.localeCompare(second.dataset.title, 'fr');
  });
}

function renderCollection() {
  let visibleCount = 0;

  sortedCards().forEach((card) => {
    const visible = matchesFilters(card);
    card.classList.toggle('hidden', !visible);
    grid.append(card);
    if (visible) visibleCount += 1;
  });

  resultCount.textContent = String(visibleCount);
  grid.classList.toggle('hidden', visibleCount === 0);
  emptyState.classList.toggle('hidden', visibleCount !== 0);
}

function renderSelectionButton() {
  const selectedCount = cards.filter((card) => card.dataset.selected === 'true').length;

  if (!selectionMode) {
    selectionButton.textContent = 'Sélectionner des cartes';
    return;
  }

  selectionButton.textContent = selectedCount > 0
    ? `${selectedCount} carte${selectedCount > 1 ? 's' : ''} sélectionnée${selectedCount > 1 ? 's' : ''}`
    : 'Terminer la sélection';
}

searchInput.addEventListener('input', renderCollection);
sortSelect.addEventListener('change', renderCollection);

rarityButtons.forEach((button) => {
  button.addEventListener('click', () => {
    activeRarity = button.dataset.rarityFilter;
    rarityButtons.forEach((item) => {
      item.dataset.active = String(item === button);
    });
    renderCollection();
  });
});

cards.forEach((card) => {
  const favoriteButton = card.querySelector('[data-favorite-toggle]');

  favoriteButton.addEventListener('click', (event) => {
    event.stopPropagation();
    const favorite = card.dataset.favorite !== 'true';
    card.dataset.favorite = String(favorite);
    favoriteButton.setAttribute('aria-pressed', String(favorite));
    favoriteButton.textContent = favorite ? 'Favori' : 'Ajouter';
    renderCollection();
  });

  card.addEventListener('click', () => {
    if (!selectionMode) return;
    card.dataset.selected = String(card.dataset.selected !== 'true');
    renderSelectionButton();
  });

  card.addEventListener('keydown', (event) => {
    if (!selectionMode || (event.key !== 'Enter' && event.key !== ' ')) return;
    event.preventDefault();
    card.click();
  });
});

selectionButton.addEventListener('click', () => {
  selectionMode = !selectionMode;

  if (!selectionMode) {
    cards.forEach((card) => {
      card.dataset.selected = 'false';
    });
  }

  renderSelectionButton();
});

renderCollection();
