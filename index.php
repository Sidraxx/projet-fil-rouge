<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$username = htmlspecialchars((string) ($_SESSION['username'] ?? 'Joueur'), ENT_QUOTES, 'UTF-8');
$csrfToken = htmlspecialchars((string) $_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
$packsAvailable = 10;
$packsLimit = 10;

$navigation = [
    ['id' => 'packs', 'label' => 'Paquets', 'current' => true],
    ['id' => 'collection', 'label' => 'Collection', 'current' => false],
    ['id' => 'catalog', 'label' => 'Toutes les cartes', 'current' => false],
    ['id' => 'deck', 'label' => 'Deck', 'current' => false],
    ['id' => 'battle', 'label' => 'Combat', 'current' => false],
    ['id' => 'profile', 'label' => 'Profil', 'current' => false],
    ['id' => 'settings', 'label' => 'Paramètres', 'current' => false],
];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Ouvrez un paquet et découvrez 5 cartes de films Absolute Cinema.">
  <title>Absolute Cinema</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Manrope', 'sans-serif'],
            mono: ['"DM Mono"', 'monospace'],
          },
          colors: {
            ink: '#0a0a0a',
            panel: '#141414',
            mint: '#3ddc97',
          },
        },
      },
    };
  </script>
  <style>
    #how-it-works::backdrop { background: rgba(0, 0, 0, .72); }
    #pack.is-opening { animation: pack-open .72s ease; }
    @keyframes pack-open {
      0% { transform: translateY(0) rotate(0) scale(1); }
      18% { transform: translateY(-8px) rotate(-2.5deg) scale(1.02); }
      36% { transform: translateY(-4px) rotate(2.5deg) scale(1.02); }
      58% { transform: translateY(-22px) rotate(0) scale(1.06); }
      100% { transform: translateY(0) rotate(0) scale(1); }
    }
  </style>
</head>
<body class="min-h-screen bg-ink font-sans text-stone-100">
  <div class="flex min-h-screen">
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/60 lg:hidden"></div>
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-white/5 bg-panel transition-transform duration-200 lg:static lg:translate-x-0" aria-label="Navigation principale">
      <div class="flex items-center justify-between px-5 pb-2 pt-6">
        <a class="text-lg font-semibold tracking-tight" href="index.php">Absolute<span class="font-bold">Cinema</span></a>
        <button id="sidebar-close" class="grid h-8 w-8 place-items-center rounded-lg text-stone-400 hover:bg-white/5 hover:text-white lg:hidden" type="button" aria-label="Fermer le menu">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
        </button>
      </div>
      <nav class="mt-6 flex flex-1 flex-col gap-1 px-3">
        <?php foreach ($navigation as $item): ?>
          <a
            class="<?= $item['current']
              ? 'flex items-center gap-3 rounded-xl bg-[#13281f] px-3 py-2.5 text-sm font-medium text-mint'
              : 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-300 hover:bg-white/5 hover:text-white' ?>"
            href="<?= $item['current'] ? 'index.php' : '#' ?>"
            <?= $item['current'] ? 'aria-current="page"' : 'data-soon' ?>
          >
            <span class="grid h-5 w-5 place-items-center" aria-hidden="true">
              <?php if ($item['id'] === 'packs'): ?>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"></path><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"></path></svg>
              <?php elseif ($item['id'] === 'collection'): ?>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"></path><path d="M4 5.5A2.5 2.5 0 0 1 6.5 8H20"></path></svg>
              <?php elseif ($item['id'] === 'catalog'): ?>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"></path></svg>
              <?php elseif ($item['id'] === 'deck'): ?>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="7" y="3" width="12" height="16" rx="1.5"></rect><path d="M5 7v13.5A1.5 1.5 0 0 0 6.5 22H16"></path></svg>
              <?php elseif ($item['id'] === 'battle'): ?>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14.5 3.5l6 6-8 8-6-6 8-8z"></path><path d="M4 20l5.5-5.5M14 10l2 2"></path></svg>
              <?php elseif ($item['id'] === 'profile'): ?>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="3.2"></circle><path d="M5 19.2a7 7 0 0 1 14 0"></path></svg>
              <?php else: ?>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="3"></circle><path d="M12 3.5v2.2M12 18.3v2.2M3.5 12h2.2M18.3 12h2.2M6 6l1.6 1.6M16.4 16.4L18 18M18 6l-1.6 1.6M7.6 16.4L6 18"></path></svg>
              <?php endif; ?>
            </span>
            <span class="flex-1"><?= $item['label'] ?></span>
            <?php if ($item['current']): ?>
              <span class="h-1.5 w-1.5 rounded-full bg-mint"></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <div class="flex min-h-screen min-w-0 flex-1 flex-col">
      <header class="flex h-16 items-center justify-between gap-4 px-4 sm:px-8">
        <button id="menu-button" class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 text-stone-300 lg:hidden" type="button" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
        </button>
        <div class="ml-auto flex items-center gap-3">
          <p class="hidden text-sm text-stone-400 sm:block">Connecté en tant que <span class="font-medium text-stone-200"><?= $username ?></span></p>
          <form id="logout-form" method="post" action="auth.php">
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <button id="logout-open" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs font-medium text-stone-200 transition hover:border-white/20 hover:bg-white/10" type="button">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2"></path><path d="M4 12h11M12 8l4 4-4 4"></path></svg>
              Déconnexion
            </button>
          </form>
        </div>
      </header>

      <main class="flex flex-1 flex-col items-center px-4 pb-16 pt-4 sm:px-8">
        <h1 class="text-center text-4xl font-semibold tracking-tight sm:text-5xl">Ouvrir un paquet</h1>
        <p class="mt-3 text-center text-sm text-stone-400">Découvrez 5 nouvelles cartes de cinéma</p>
        <button id="how-link" class="mt-3 text-sm font-medium text-mint hover:text-emerald-300" type="button">Comment ça marche ?</button>

        <button id="open-pack" class="group relative mt-12 border-0 bg-transparent p-0" type="button" aria-label="Ouvrir le paquet">
          <span class="pointer-events-none absolute left-1/2 top-1/2 h-56 w-44 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-200/25 blur-3xl transition group-hover:bg-amber-100/40"></span>
          <span id="pack" class="relative block transition duration-300 group-hover:-translate-y-1">
            <svg class="h-auto w-52 drop-shadow-[0_18px_40px_rgba(0,0,0,.55)] sm:w-60" viewBox="0 0 220 300" role="img" aria-hidden="true">
              <defs>
                <linearGradient id="pack-body" x1="40" y1="16" x2="180" y2="280" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#ffffff"></stop>
                  <stop offset="1" stop-color="#f3f1ea"></stop>
                </linearGradient>
              </defs>
              <path fill="url(#pack-body)" d="M36 28h148a10 10 0 0 1 10 10v196l-14 12-14-12-14 12-14-12-14 12-14-12-14 12-14-12-14 12-14-12-14 12-14-12V38a10 10 0 0 1 10-10z"></path>
              <path stroke="#e7e2d6" stroke-width="2" d="M48 48h124"></path>
              <rect x="58" y="70" width="28" height="36" rx="4" fill="#c4b5fd" transform="rotate(-12 72 88)"></rect>
              <rect x="132" y="62" width="28" height="36" rx="4" fill="#f9a8d4" transform="rotate(10 146 80)"></rect>
              <rect x="46" y="132" width="28" height="36" rx="4" fill="#86efac" transform="rotate(8 60 150)"></rect>
              <rect x="146" y="128" width="28" height="36" rx="4" fill="#fde68a" transform="rotate(-8 160 146)"></rect>
              <rect x="70" y="176" width="26" height="32" rx="4" fill="#93c5fd" transform="rotate(-6 83 192)"></rect>
              <rect x="126" y="174" width="26" height="32" rx="4" fill="#fdba74" transform="rotate(7 139 190)"></rect>
              <g transform="translate(78 112)">
                <rect width="64" height="64" rx="8" fill="#111111"></rect>
                <text x="32" y="41" text-anchor="middle" fill="#ffffff" font-family="Manrope, sans-serif" font-size="26" font-weight="700">AC</text>
              </g>
            </svg>
          </span>
          <span class="mt-8 block text-center text-lg font-semibold text-mint">Ouvrir</span>
        </button>

        <p id="pack-status" class="mt-6 min-h-5 text-center text-sm text-stone-500" role="status" aria-live="polite"></p>

        <div class="mt-2 w-full max-w-xs rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-4 text-center">
          <p class="text-lg font-semibold text-mint"><?= $packsAvailable ?> / <?= $packsLimit ?></p>
          <p class="mt-0.5 text-xs text-stone-500">paquets disponibles</p>
        </div>
      </main>
    </div>
  </div>

  <dialog id="how-it-works" class="w-[min(100%-2rem,28rem)] rounded-2xl border border-white/10 bg-[#161616] p-0 text-stone-100 shadow-2xl backdrop:bg-black/70">
    <div class="p-6">
      <h2 class="text-lg font-semibold">Comment ça marche ?</h2>
      <p class="mt-3 text-sm leading-6 text-stone-400">Chaque paquet contient 5 cartes. Chaque carte est un film, classé de commune à légendaire. Ouvrez un paquet pour les ajouter à votre collection.</p>
      <button id="how-close" class="mt-6 rounded-full bg-mint px-4 py-2 text-sm font-semibold text-black" type="button">Fermer</button>
    </div>
  </dialog>
  <dialog id="logout-dialog" class="w-[min(100%-2rem,24rem)] rounded-2xl border border-white/10 bg-[#161616] p-0 text-stone-100 shadow-2xl backdrop:bg-black/70">
    <div class="p-6">
      <h2 class="text-lg font-semibold">Se déconnecter ?</h2>
      <p class="mt-3 text-sm leading-6 text-stone-400">Votre session sera fermée et vous serez redirigé vers la page de connexion.</p>
      <div class="mt-6 flex justify-end gap-2">
        <button id="logout-cancel" class="rounded-full border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 hover:bg-white/5" type="button">Annuler</button>
        <button id="logout-confirm" class="rounded-full bg-mint px-4 py-2 text-sm font-semibold text-black" type="button">Se déconnecter</button>
      </div>
    </div>
  </dialog>
  <script src="assets/js/home.js"></script>
</body>
</html>
