const SOUND_KEY = 'ac-sound';
const soundToggle = document.querySelector('#sound-toggle');
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

renderSwitch(soundToggle, localStorage.getItem(SOUND_KEY) !== 'off');

soundToggle.addEventListener('click', () => {
  const next = soundToggle.getAttribute('aria-checked') !== 'true';
  localStorage.setItem(SOUND_KEY, next ? 'on' : 'off');
  renderSwitch(soundToggle, next);
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
