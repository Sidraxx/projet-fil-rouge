const sidebar = document.querySelector('#sidebar');
const backdrop = document.querySelector('#sidebar-backdrop');
const menuButton = document.querySelector('#menu-button');
const sidebarClose = document.querySelector('#sidebar-close');
const pack = document.querySelector('#pack');
const openPack = document.querySelector('#open-pack');
const status = document.querySelector('#pack-status');
const dialog = document.querySelector('#how-it-works');
const howLink = document.querySelector('#how-link');
const howClose = document.querySelector('#how-close');

function setSidebar(open) {
  sidebar.classList.toggle('-translate-x-full', !open);
  backdrop.classList.toggle('hidden', !open);
  menuButton.setAttribute('aria-expanded', String(open));
}

menuButton.addEventListener('click', () => setSidebar(true));
sidebarClose.addEventListener('click', () => setSidebar(false));
backdrop.addEventListener('click', () => setSidebar(false));

document.querySelectorAll('[data-soon]').forEach((link) => {
  link.addEventListener('click', (event) => event.preventDefault());
});

function playOpen() {
  pack.classList.remove('is-opening');
  void pack.offsetWidth;
  pack.classList.add('is-opening');
  status.textContent = 'Le tirage des cartes arrive bientôt.';
}

openPack.addEventListener('click', playOpen);

howLink.addEventListener('click', () => dialog.showModal());
howClose.addEventListener('click', () => dialog.close());
dialog.addEventListener('click', (event) => {
  if (event.target === dialog) dialog.close();
});

const logoutDialog = document.querySelector('#logout-dialog');
const logoutForm = document.querySelector('#logout-form');

document.querySelector('#logout-open').addEventListener('click', () => logoutDialog.showModal());
document.querySelector('#logout-cancel').addEventListener('click', () => logoutDialog.close());
document.querySelector('#logout-confirm').addEventListener('click', () => logoutForm.submit());
logoutDialog.addEventListener('click', (event) => {
  if (event.target === logoutDialog) logoutDialog.close();
});
