const catalogSort = document.querySelector('[data-catalog-sort]');
const catalogCards = [...document.querySelectorAll('[data-catalog-card]')];
const catalogDialog = document.querySelector('#catalog-dialog');
const catalogDialogClose = catalogDialog.querySelector('[data-catalog-dialog-close]');

function formatCatalogNumber(value) {
  return new Intl.NumberFormat('fr-FR').format(value);
}

function openCatalogDialog(card) {
  const movie = JSON.parse(card.dataset.movieDetails);
  const votes = movie.votes ? `${formatCatalogNumber(movie.votes)} votes` : null;
  const gross = movie.gross
    ? `${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(movie.gross)} de recettes`
    : null;

  const poster = document.querySelector('#catalog-dialog-poster');
  poster.src = movie.poster || '';
  poster.alt = `Affiche du film ${movie.title || 'Film sans titre'}`;
  document.querySelector('#catalog-dialog-rarity').textContent = movie.rarity || 'Rareté inconnue';
  document.querySelector('#catalog-dialog-eyebrow').textContent = [movie.year, movie.genre].filter(Boolean).join(' · ') || '—';
  document.querySelector('#catalog-dialog-title').textContent = movie.title || 'Film sans titre';
  document.querySelector('#catalog-dialog-imdb').textContent = movie.imdbRating ? `${movie.imdbRating}/10` : '—';
  document.querySelector('#catalog-dialog-meta').textContent = movie.metaScore ? `${Math.round(movie.metaScore)}/100` : '—';
  document.querySelector('#catalog-dialog-runtime').textContent = movie.runtime ? `${movie.runtime} min` : '—';
  document.querySelector('#catalog-dialog-overview').textContent = movie.overview || 'Aucun synopsis disponible.';
  document.querySelector('#catalog-dialog-director').textContent = movie.director || 'Non renseigné';
  document.querySelector('#catalog-dialog-cast').textContent = movie.cast.length > 0 ? movie.cast.join(', ') : 'Non renseignée';
  document.querySelector('#catalog-dialog-popularity').textContent = [votes, gross].filter(Boolean).join(' · ') || 'Non renseignée';

  const owned = document.querySelector('#catalog-dialog-owned');
  const quantity = Number(movie.ownedQuantity);
  owned.textContent = quantity > 0 ? `Dans ta collection · ${quantity} exemplaire${quantity > 1 ? 's' : ''}` : 'Pas encore dans ta collection';
  owned.classList.toggle('border-mint/30', quantity > 0);
  owned.classList.toggle('bg-mint/[0.06]', quantity > 0);
  owned.classList.toggle('text-mint', quantity > 0);

  catalogDialog.showModal();
  document.body.classList.add('overflow-hidden');
}

catalogSort.addEventListener('change', () => catalogSort.form.submit());

catalogCards.forEach((card) => {
  card.addEventListener('click', () => openCatalogDialog(card));
  card.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    card.click();
  });
});

catalogDialogClose.addEventListener('click', () => catalogDialog.close());
catalogDialog.addEventListener('click', (event) => {
  if (event.target === catalogDialog) catalogDialog.close();
});
catalogDialog.addEventListener('close', () => document.body.classList.remove('overflow-hidden'));
