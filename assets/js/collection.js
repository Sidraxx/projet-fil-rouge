const searchInput = document.querySelector('#collection-search');
const sortSelect = document.querySelector('#collection-sort');
const rarityButtons = [...document.querySelectorAll('[data-rarity-filter]')];
const grid = document.querySelector('#collection-grid');
const cards = [...document.querySelectorAll('[data-card]')];
const resultCount = document.querySelector('#result-count');
const emptyState = document.querySelector('#collection-empty');
const selectionButton = document.querySelector('#selection-button');
const collectionMessage = document.querySelector('#collection-message');
const movieDialog = document.querySelector('#movie-dialog');
const movieDialogClose = movieDialog.querySelector('[data-movie-dialog-close]');
const movieDialogPoster = document.querySelector('#movie-dialog-poster');
const movieDialogRarity = document.querySelector('#movie-dialog-rarity');
const movieDialogEyebrow = document.querySelector('#movie-dialog-eyebrow');
const movieDialogTitle = document.querySelector('#movie-dialog-title');
const movieDialogImdb = document.querySelector('#movie-dialog-imdb');
const movieDialogMeta = document.querySelector('#movie-dialog-meta');
const movieDialogRuntime = document.querySelector('#movie-dialog-runtime');
const movieDialogOverview = document.querySelector('#movie-dialog-overview');
const movieDialogDirector = document.querySelector('#movie-dialog-director');
const movieDialogCast = document.querySelector('#movie-dialog-cast');
const movieDialogPopularity = document.querySelector('#movie-dialog-popularity');
const movieDialogCollection = document.querySelector('#movie-dialog-collection');

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

function formatDate(value) {
  if (!value) return 'Date inconnue';

  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(new Date(value.replace(' ', 'T')));
}

function openMovieDialog(card) {
  const movie = JSON.parse(card.dataset.movieDetails);
  const votes = movie.votes ? new Intl.NumberFormat('fr-FR').format(movie.votes) : null;
  const gross = movie.gross
    ? new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'USD',
      maximumFractionDigits: 0,
    }).format(movie.gross)
    : null;

  movieDialogPoster.src = movie.poster || '';
  movieDialogPoster.alt = `Affiche du film ${movie.title || 'Film sans titre'}`;
  movieDialogRarity.textContent = movie.rarity || 'Rareté inconnue';
  movieDialogEyebrow.textContent = [movie.year, movie.genre].filter(Boolean).join(' · ') || '—';
  movieDialogTitle.textContent = movie.title || 'Film sans titre';
  movieDialogImdb.textContent = movie.imdbRating ? `${movie.imdbRating}/10` : '—';
  movieDialogMeta.textContent = movie.metaScore ? `${Math.round(movie.metaScore)}/100` : '—';
  movieDialogRuntime.textContent = movie.runtime ? `${movie.runtime} min` : '—';
  movieDialogOverview.textContent = movie.overview || 'Aucun synopsis disponible.';
  movieDialogDirector.textContent = movie.director || 'Non renseigné';
  movieDialogCast.textContent = movie.cast.length > 0 ? movie.cast.join(', ') : 'Non renseignée';
  movieDialogPopularity.textContent = [votes && `${votes} votes`, gross && `${gross} de recettes`]
    .filter(Boolean)
    .join(' · ') || 'Non renseignée';
  movieDialogCollection.textContent = `${movie.quantity} exemplaire${Number(movie.quantity) > 1 ? 's' : ''} · obtenu le ${formatDate(movie.addedAt)}`;

  movieDialog.showModal();
  document.body.classList.add('overflow-hidden');
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

  favoriteButton.addEventListener('click', async (event) => {
    event.stopPropagation();
    const favorite = card.dataset.favorite !== 'true';
    const previousLabel = favoriteButton.textContent;

    favoriteButton.disabled = true;
    collectionMessage.textContent = 'Enregistrement du favori...';
    collectionMessage.className = 'min-h-5 text-sm text-stone-500';

    try {
      const response = await fetch('collection.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
          action: 'set_favorite',
          movie_id: Number(card.dataset.movieId),
          favorite,
        }),
      });
      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Impossible de modifier le favori.');
      }

      card.dataset.favorite = String(result.favorite);
      favoriteButton.setAttribute('aria-pressed', String(result.favorite));
      favoriteButton.textContent = result.favorite ? 'Favori' : 'Ajouter';
      collectionMessage.textContent = result.message;
      collectionMessage.className = 'min-h-5 text-sm text-mint';
      renderCollection();
    } catch (error) {
      favoriteButton.textContent = previousLabel;
      collectionMessage.textContent = error.message || 'Impossible de modifier le favori.';
      collectionMessage.className = 'min-h-5 text-sm text-red-400';
    } finally {
      favoriteButton.disabled = false;
    }
  });

  card.addEventListener('click', () => {
    if (selectionMode) {
      card.dataset.selected = String(card.dataset.selected !== 'true');
      renderSelectionButton();
      return;
    }

    openMovieDialog(card);
  });

  card.addEventListener('keydown', (event) => {
    if (event.target.closest('[data-favorite-toggle]') || (event.key !== 'Enter' && event.key !== ' ')) return;
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

movieDialogClose.addEventListener('click', () => movieDialog.close());

movieDialog.addEventListener('click', (event) => {
  if (event.target === movieDialog) movieDialog.close();
});

movieDialog.addEventListener('close', () => {
  document.body.classList.remove('overflow-hidden');
});

renderCollection();
