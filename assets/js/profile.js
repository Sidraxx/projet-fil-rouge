const showcaseDialog = document.querySelector('#showcase-dialog');
const showcaseDialogSubtitle = document.querySelector('#showcase-dialog-subtitle');
const showcaseMessage = document.querySelector('#showcase-message');
const showcaseSlots = [...document.querySelectorAll('[data-showcase-slot]')];
const showcaseChoices = [...document.querySelectorAll('[data-showcase-choice]')];
const showcaseRemoveButtons = [...document.querySelectorAll('[data-showcase-remove]')];
const showcaseCloseButton = document.querySelector('[data-showcase-close]');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let activeShowcaseSlot = null;
let showcaseBusy = false;

function setShowcaseMessage(message, type = 'info') {
  showcaseMessage.textContent = message;
  showcaseMessage.className = `mt-3 min-h-5 text-sm ${type === 'error' ? 'text-red-400' : type === 'success' ? 'text-mint' : 'text-stone-400'}`;
}

function setShowcaseBusy(busy) {
  showcaseBusy = busy;
  [...showcaseChoices, ...showcaseRemoveButtons].forEach((button) => {
    button.disabled = busy;
  });
}

async function updateShowcase(payload) {
  if (showcaseBusy) return;

  setShowcaseBusy(true);
  setShowcaseMessage('Enregistrement...');

  try {
    const response = await fetch('profile.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify(payload),
    });
    const result = await response.json();

    if (!response.ok || !result.success) {
      setShowcaseMessage(result.message || 'Impossible de modifier la vitrine.', 'error');
      return;
    }

    setShowcaseMessage(result.message, 'success');
    window.location.reload();
  } catch (error) {
    setShowcaseMessage('Le serveur est indisponible. Vérifiez que WAMP est démarré.', 'error');
  } finally {
    setShowcaseBusy(false);
  }
}

showcaseSlots.forEach((button) => {
  button.addEventListener('click', () => {
    activeShowcaseSlot = Number(button.dataset.showcaseSlot);
    showcaseDialogSubtitle.textContent = `Emplacement ${activeShowcaseSlot} sur 4`;
    showcaseDialog.showModal();
    document.body.classList.add('overflow-hidden');
  });
});

showcaseChoices.forEach((button) => {
  button.addEventListener('click', () => {
    if (!activeShowcaseSlot) return;

    updateShowcase({
      action: 'set_showcase_card',
      slot: activeShowcaseSlot,
      movie_id: Number(button.dataset.showcaseChoice),
    });
  });
});

showcaseRemoveButtons.forEach((button) => {
  button.addEventListener('click', () => {
    updateShowcase({
      action: 'remove_showcase_card',
      slot: Number(button.dataset.showcaseRemove),
    });
  });
});

showcaseCloseButton.addEventListener('click', () => showcaseDialog.close());

showcaseDialog.addEventListener('click', (event) => {
  if (event.target === showcaseDialog) showcaseDialog.close();
});

showcaseDialog.addEventListener('close', () => {
  activeShowcaseSlot = null;
  document.body.classList.remove('overflow-hidden');
});
