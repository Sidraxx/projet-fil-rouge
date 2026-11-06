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
