(async () => {
  try {
    const response = await fetch('auth.php?action=session', { credentials: 'same-origin' });
    const session = await response.json();

    if (!session.authenticated) {
      window.location.replace('auth.php');
    }
  } catch (error) {
    window.location.replace('auth.php');
  }
})();