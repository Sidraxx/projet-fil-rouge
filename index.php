<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_login();
require_once __DIR__ . '/includes/packs.php';

$packState = refresh_pack_state(database(), (int) $_SESSION['user_id']);
$packsAvailable = $packState['available'];
$packsLimit = PACK_LIMIT;
$nextIn = $packState['next_in'];

render_start('Ouvrir un paquet', 'packs');
?>
  <link rel="stylesheet" href="assets/css/cards.css">
  <style>
    #pack.is-opening { animation: pack-open .72s ease; }
    @keyframes pack-open {
      0% { transform: translateY(0) rotate(0) scale(1); }
      18% { transform: translateY(-8px) rotate(-2.5deg) scale(1.02); }
      36% { transform: translateY(-4px) rotate(2.5deg) scale(1.02); }
      58% { transform: translateY(-22px) rotate(0) scale(1.06); }
      100% { transform: translateY(0) rotate(0) scale(1); }
    }
  </style>

  <main class="flex flex-1 flex-col items-center px-4 pb-16 pt-4 sm:px-8">
    <h1 class="text-center text-4xl font-semibold tracking-tight sm:text-5xl">Ouvrir un paquet</h1>
    <p class="mt-3 text-center text-sm text-stone-400">Découvrez 5 nouvelles cartes de cinéma</p>
    <button id="how-link" class="mt-3 text-sm font-medium text-mint hover:text-emerald-300" type="button">Comment ça marche ?</button>

    <div id="pack-stage" class="flex w-full flex-col items-center">
    <button id="open-pack" class="group relative mt-12 border-0 bg-transparent p-0 disabled:cursor-wait" type="button" aria-label="Ouvrir le paquet">
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
      <p id="pack-count" class="text-lg font-semibold text-mint" data-available="<?= $packsAvailable ?>" data-limit="<?= $packsLimit ?>" data-next-in="<?= $nextIn ?? '' ?>"><?= $packsAvailable ?> / <?= $packsLimit ?></p>
      <p class="mt-0.5 text-xs text-stone-500">paquets disponibles</p>
      <p id="pack-next" class="mt-1 text-xs text-stone-500"></p>
    </div>
    </div>

    <section id="reveal-stage" class="mt-10 hidden w-full max-w-6xl" aria-live="polite">
      <p id="reveal-summary" class="text-center text-sm text-stone-400"></p>
      <div id="card-row" class="card-row"></div>
      <div class="text-center">
        <button id="reveal-close" class="rounded-full border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 hover:bg-white/5" type="button">Retour</button>
      </div>
    </section>
  </main>

  <dialog id="how-it-works" class="w-[min(100%-2rem,28rem)] rounded-2xl border border-white/10 bg-[#161616] p-0 text-stone-100 shadow-2xl backdrop:bg-black/70">
    <div class="p-6">
      <h2 class="text-lg font-semibold">Comment ça marche ?</h2>
      <p class="mt-3 text-sm leading-6 text-stone-400">Chaque paquet contient 5 cartes. Chaque carte est un film, classé de commune à légendaire. Ouvrez un paquet pour les ajouter à votre collection.</p>
      <button id="how-close" class="mt-6 rounded-full bg-mint px-4 py-2 text-sm font-semibold text-black" type="button">Fermer</button>
    </div>
  </dialog>
<?php
render_end('', ['assets/js/home.js']);
