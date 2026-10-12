const pack = document.querySelector('#pack');
const openPack = document.querySelector('#open-pack');
const status = document.querySelector('#pack-status');
const dialog = document.querySelector('#how-it-works');
const howLink = document.querySelector('#how-link');
const howClose = document.querySelector('#how-close');

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
