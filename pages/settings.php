<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('../');

$user = current_user('../');

render_start('Paramètres', 'settings', '../');
?>
  <main class="flex flex-1 flex-col px-4 pb-16 pt-4 sm:px-8">
    <div class="mx-auto w-full max-w-2xl space-y-4">
      <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Paramètres</h1>

      <section class="rounded-2xl border border-white/10 bg-panel p-5 sm:p-6" aria-labelledby="account-title">
        <h2 id="account-title" class="text-sm font-semibold text-stone-400">Informations du compte</h2>
        <form id="account-form" class="mt-5 space-y-5" novalidate>
          <div>
            <label for="account-email" class="mb-2 block font-mono text-[10px] uppercase tracking-wider text-stone-500">Adresse courriel</label>
            <input id="account-email" type="email" value="<?= e($user['email']) ?>" disabled class="w-full cursor-not-allowed rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-sm text-stone-500">
          </div>
          <div>
            <label for="account-username" class="mb-2 block font-mono text-[10px] uppercase tracking-wider text-stone-500">Nom d'utilisateur</label>
            <input id="account-username" name="username" type="text" value="<?= e($user['username']) ?>" required minlength="3" maxlength="50" class="w-full rounded-xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-sm text-stone-100 outline-none transition focus:border-mint/60">
          </div>
          <p id="account-message" class="min-h-5 text-sm text-stone-400" role="status" aria-live="polite"></p>
          <button type="submit" class="w-full rounded-xl bg-mint px-4 py-3 text-sm font-semibold text-black transition hover:bg-emerald-300 disabled:opacity-50">Sauvegarder</button>
        </form>
      </section>

      <section class="rounded-2xl border border-white/10 bg-panel p-5 sm:p-6" aria-labelledby="membership-title">
        <h2 id="membership-title" class="text-sm font-semibold text-stone-400">Compte</h2>
        <p class="mt-3 text-sm text-stone-500">Membre depuis <?= e(french_date($user['created_at'])) ?></p>
      </section>

      <section class="rounded-2xl border border-white/10 bg-panel p-5 sm:p-6" aria-labelledby="appearance-title">
        <h2 id="appearance-title" class="text-sm font-semibold text-stone-400">Apparence</h2>
        <div class="mt-4 flex items-center justify-between gap-4">
          <div>
            <p id="theme-label" class="font-medium">Mode clair</p>
            <p id="theme-description" class="mt-0.5 text-sm text-stone-500">Utiliser un fond clair pour l'interface</p>
          </div>
          <button id="theme-toggle" type="button" role="switch" aria-checked="false" aria-labelledby="theme-label" class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-white/10 transition">
            <span class="inline-block h-5 w-5 translate-x-0.5 rounded-full bg-white transition"></span>
          </button>
        </div>
      </section>

      <button data-logout-open class="w-full rounded-2xl border border-red-900/60 bg-red-950/40 px-4 py-3.5 text-sm font-semibold text-red-300 transition hover:bg-red-950/70" type="button">Déconnexion</button>

      <section class="rounded-2xl border border-red-900/40 bg-panel p-5 sm:p-6" aria-labelledby="danger-title">
        <h2 id="danger-title" class="text-sm font-semibold text-red-400">Zone dangereuse</h2>
        <p class="mt-2 text-sm text-stone-500">Supprimer définitivement votre compte et retirer toutes vos cartes du jeu.</p>
        <button id="delete-account-open" type="button" class="mt-4 w-full rounded-xl border border-red-900/50 bg-red-950/30 px-4 py-3 text-sm font-semibold text-red-300 transition hover:bg-red-950/70">Supprimer mon compte</button>
      </section>
    </div>
  </main>
  <dialog id="delete-account-dialog" class="w-[min(100%-2rem,28rem)] rounded-2xl border border-red-900/50 bg-[#161616] p-0 text-stone-100 shadow-2xl backdrop:bg-black/70">
    <form id="delete-account-form" class="p-6">
      <h2 class="text-lg font-semibold">Supprimer définitivement le compte ?</h2>
      <p class="mt-3 text-sm leading-6 text-stone-400">Cette action supprimera votre compte, vos cartes, vos paquets et vos decks. Elle est irréversible.</p>
      <label for="delete-account-confirmation" class="mt-5 block text-sm font-medium">Tapez <strong>SUPPRIMER</strong> pour confirmer</label>
      <input id="delete-account-confirmation" type="text" autocomplete="off" class="mt-2 w-full rounded-xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-sm text-stone-100 outline-none transition focus:border-red-400/60" required>
      <p id="delete-account-message" class="mt-3 min-h-5 text-sm text-red-400" role="alert"></p>
      <div class="mt-5 flex justify-end gap-2">
        <button id="delete-account-cancel" type="button" class="rounded-full border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 hover:bg-white/5">Annuler</button>
        <button id="delete-account-submit" type="submit" class="rounded-full bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400 disabled:opacity-50">Supprimer</button>
      </div>
    </form>
  </dialog>
<?php
render_end('../', ['assets/js/settings.js?v=2']);
