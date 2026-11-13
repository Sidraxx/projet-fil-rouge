const THEME_KEY = 'ac-theme';
const themeToggle = document.querySelector('#theme-toggle');
const themeLabel = document.querySelector('#theme-label');
const themeDescription = document.querySelector('#theme-description');
const accountForm = document.querySelector('#account-form');
const usernameInput = document.querySelector('#account-username');
const accountMessage = document.querySelector('#account-message');
const submitButton = accountForm.querySelector('button[type="submit"]');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function renderSwitch(toggle, checked) {
  toggle.setAttribute('aria-checked', String(checked));
  toggle.classList.toggle('bg-mint', checked);
  toggle.classList.toggle('bg-white/10', !checked);
  toggle.firstElementChild.classList.toggle('translate-x-[22px]', checked);
  toggle.firstElementChild.classList.toggle('translate-x-0.5', !checked);
}

function renderTheme(isLight) {
  document.documentElement.dataset.theme = isLight ? 'light' : 'dark';
  themeToggle.setAttribute('aria-checked', String(isLight));
  renderSwitch(themeToggle, isLight);
  themeLabel.textContent = isLight ? 'Mode clair' : 'Mode sombre';
  themeDescription.textContent = isLight
    ? 'Utiliser un fond sombre pour l\'interface'
    : 'Utiliser un fond clair pour l\'interface';
}

renderTheme(localStorage.getItem(THEME_KEY) === 'light');

themeToggle.addEventListener('click', () => {
  const next = themeToggle.getAttribute('aria-checked') !== 'true';
  localStorage.setItem(THEME_KEY, next ? 'light' : 'dark');
  renderTheme(next);
});

function setMessage(text, type) {
  accountMessage.textContent = text;
  accountMessage.className = `min-h-5 text-sm ${type === 'error' ? 'text-red-400' : type === 'success' ? 'text-mint' : 'text-stone-400'}`;
}

accountForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (!accountForm.checkValidity()) {
    accountForm.reportValidity();
    return;
  }

  submitButton.disabled = true;
  setMessage('Enregistrement...', 'info');

  try {
    const response = await fetch('../settings-api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({ action: 'update_username', username: usernameInput.value.trim() }),
    });
    const result = await response.json();

    setMessage(result.message, result.success ? 'success' : 'error');

    if (result.success) {
      usernameInput.value = result.username;
      document.querySelector('#current-username').textContent = result.username;
    }
  } catch (error) {
    setMessage('Le serveur est indisponible. Vérifiez que WAMP est démarré.', 'error');
  } finally {
    submitButton.disabled = false;
  }
});

const deleteDialog = document.querySelector('#delete-account-dialog');
const deleteForm = document.querySelector('#delete-account-form');
const deleteConfirmation = document.querySelector('#delete-account-confirmation');
const deleteMessage = document.querySelector('#delete-account-message');
const deleteSubmit = document.querySelector('#delete-account-submit');

document.querySelector('#delete-account-open').addEventListener('click', () => {
  deleteForm.reset();
  deleteMessage.textContent = '';
  deleteDialog.showModal();
  deleteConfirmation.focus();
});

document.querySelector('#delete-account-cancel').addEventListener('click', () => deleteDialog.close());

deleteDialog.addEventListener('click', (event) => {
  if (event.target === deleteDialog) deleteDialog.close();
});

deleteForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (deleteConfirmation.value.trim() !== 'SUPPRIMER') {
    deleteMessage.textContent = 'Saisissez SUPPRIMER pour confirmer.';
    return;
  }

  deleteSubmit.disabled = true;
  deleteMessage.textContent = 'Suppression en cours...';

  try {
    const response = await fetch('../settings-api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({ action: 'delete_account', confirmation: 'SUPPRIMER' }),
    });
    const result = await response.json();

    if (!result.success) {
      deleteMessage.textContent = result.message || 'Impossible de supprimer le compte.';
      return;
    }

    window.location.assign(result.redirect || '../auth.php');
  } catch (error) {
    deleteMessage.textContent = 'Le serveur est indisponible. Vérifiez que WAMP est démarré.';
  } finally {
    deleteSubmit.disabled = false;
  }
});
