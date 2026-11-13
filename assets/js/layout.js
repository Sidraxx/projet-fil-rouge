const sidebar = document.querySelector('#sidebar');
const backdrop = document.querySelector('#sidebar-backdrop');
const menuButton = document.querySelector('#menu-button');
const sidebarClose = document.querySelector('#sidebar-close');

function setSidebar(open) {
  sidebar.classList.toggle('-translate-x-full', !open);
  backdrop.classList.toggle('hidden', !open);
  menuButton.setAttribute('aria-expanded', String(open));
}

menuButton.addEventListener('click', () => setSidebar(true));
sidebarClose.addEventListener('click', () => setSidebar(false));
backdrop.addEventListener('click', () => setSidebar(false));

const logoutDialog = document.querySelector('#logout-dialog');
const logoutForm = document.querySelector('#logout-form');

document.querySelectorAll('[data-logout-open]').forEach((button) => {
  button.addEventListener('click', () => logoutDialog.showModal());
});
document.querySelector('#logout-cancel').addEventListener('click', () => logoutDialog.close());
document.querySelector('#logout-confirm').addEventListener('click', () => logoutForm.submit());
logoutDialog.addEventListener('click', (event) => {
  if (event.target === logoutDialog) logoutDialog.close();
});

function isPosterImage(target) {
  return target instanceof HTMLImageElement && target.hasAttribute('data-poster-fallback');
}

function showPosterFallback(image) {
  image.style.display = 'none';
  if (image.nextElementSibling?.classList.contains('poster-fallback')) return;
  const fallback = document.createElement('div');
  fallback.className = image.classList.contains('absolute') ? 'poster-fallback poster-fallback--absolute' : 'poster-fallback';
  fallback.setAttribute('aria-hidden', 'true');
  fallback.textContent = 'AC';
  image.after(fallback);
}

function hidePosterFallback(image) {
  image.style.display = '';
  if (image.nextElementSibling?.classList.contains('poster-fallback')) image.nextElementSibling.remove();
}

document.addEventListener('error', (event) => {
  if (isPosterImage(event.target)) showPosterFallback(event.target);
}, true);

document.addEventListener('load', (event) => {
  if (isPosterImage(event.target)) hidePosterFallback(event.target);
}, true);

document.querySelectorAll('img[data-poster-fallback]').forEach((image) => {
  if (!image.getAttribute('src') || (image.complete && image.naturalWidth === 0 && image.currentSrc)) {
    showPosterFallback(image);
  }
});
