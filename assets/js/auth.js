const tabs = document.querySelectorAll('.tab');
const forms = document.querySelectorAll('.form');
const message = document.querySelector('#message');
const panelTitle = document.querySelector('#panel-title');
const panelSubtitle = document.querySelector('#panel-subtitle');

function setMode(mode) {
  const isLogin = mode === 'login';

  tabs.forEach((tab) => {
    const active = tab.id === `${mode}-tab`;
    tab.classList.toggle('active', active);
    tab.setAttribute('aria-selected', String(active));
  });

  forms.forEach((form) => {
    form.classList.toggle('active', form.dataset.action === mode);
  });

  panelTitle.textContent = isLogin ? 'Ravi de vous revoir' : 'Entrez dans la partie';
  panelSubtitle.textContent = isLogin
    ? 'Connectez-vous pour retrouver votre deck.'
    : 'Créez votre compte et commencez votre collection.';
  message.textContent = '';
  message.className = 'message';
}

tabs.forEach((tab) => {
  tab.addEventListener('click', () => setMode(tab.id.replace('-tab', '')));
});

forms.forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submit = form.querySelector('.submit');
    const data = Object.fromEntries(new FormData(form));

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    if (form.dataset.action === 'register' && data.password !== data.password_confirmation) {
      message.textContent = 'Les mots de passe ne correspondent pas.';
      message.className = 'message error';
      return;
    }

    submit.disabled = true;
    message.textContent = 'Chargement de votre collection...';
    message.className = 'message';

    try {
      const response = await fetch('auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ action: form.dataset.action, ...data }),
      });
      const result = await response.json();

      if (!result.success) {
        console.error('[auth] action refusée :', {
          status: response.status,
          message: result.message,
        });
      }

      message.textContent = result.message || 'Une erreur est survenue.';
      message.className = `message ${result.success ? 'success' : 'error'}`;

      if (result.success && result.redirect) {
        window.location.assign(result.redirect);
      }
    } catch (error) {
      console.error('[auth] requête impossible :', error);
      message.textContent = 'Le serveur est indisponible. Vérifiez que WAMP est démarré.';
      message.className = 'message error';
    } finally {
      submit.disabled = false;
    }
  });
});
