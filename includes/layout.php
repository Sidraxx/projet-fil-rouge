<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function app_navigation(): array
{
    return [
        ['key' => 'packs', 'label' => 'Paquets', 'file' => 'index.php'],
        ['key' => 'collection', 'label' => 'Collection', 'file' => null],
        ['key' => 'catalog', 'label' => 'Toutes les cartes', 'file' => null],
        ['key' => 'deck', 'label' => 'Deck', 'file' => null],
        ['key' => 'battle', 'label' => 'Combat', 'file' => null],
        ['key' => 'profile', 'label' => 'Profil', 'file' => 'profile.php'],
        ['key' => 'settings', 'label' => 'Paramètres', 'file' => 'settings.php'],
    ];
}

function app_icon(string $key): string
{
    $paths = match ($key) {
        'packs' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"></path><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"></path>',
        'collection' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"></path><path d="M4 5.5A2.5 2.5 0 0 1 6.5 8H20"></path>',
        'catalog' => '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"></path>',
        'deck' => '<rect x="7" y="3" width="12" height="16" rx="1.5"></rect><path d="M5 7v13.5A1.5 1.5 0 0 0 6.5 22H16"></path>',
        'battle' => '<path d="M14.5 3.5l6 6-8 8-6-6 8-8z"></path><path d="M4 20l5.5-5.5M14 10l2 2"></path>',
        'profile' => '<circle cx="12" cy="8" r="3.2"></circle><path d="M5 19.2a7 7 0 0 1 14 0"></path>',
        default => '<circle cx="12" cy="12" r="3"></circle><path d="M12 3.5v2.2M12 18.3v2.2M3.5 12h2.2M18.3 12h2.2M6 6l1.6 1.6M16.4 16.4L18 18M18 6l-1.6 1.6M7.6 16.4L6 18"></path>',
    };

    return '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">' . $paths . '</svg>';
}

function render_start(string $title, string $active, string $root = ''): void
{
    $csrf = e(ensure_csrf_token());
    $username = e((string) ($_SESSION['username'] ?? 'Joueur'));
    $pagesPrefix = $root === '' ? 'pages/' : '';
    ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= $csrf ?>">
  <title><?= e($title) ?> | Absolute Cinema</title>
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
</head>
<body class="min-h-screen bg-ink font-sans text-stone-100">
  <div class="flex min-h-screen">
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/60 lg:hidden"></div>
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-white/5 bg-panel transition-transform duration-200 lg:static lg:translate-x-0">
      <div class="flex items-center justify-between px-5 pb-2 pt-6">
        <a class="text-lg font-semibold tracking-tight" href="<?= e($root) ?>index.php">Absolute<span class="font-bold">Cinema</span></a>
        <button id="sidebar-close" class="grid h-8 w-8 place-items-center rounded-lg text-stone-400 hover:bg-white/5 hover:text-white lg:hidden" type="button" aria-label="Fermer le menu">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
        </button>
      </div>
      <nav class="mt-6 flex flex-1 flex-col gap-1 px-3" aria-label="Navigation principale">
        <?php foreach (app_navigation() as $item): ?>
          <?php if ($item['file'] === null): ?>
            <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-600" aria-disabled="true" title="Bientôt disponible">
              <span class="grid h-5 w-5 place-items-center" aria-hidden="true"><?= app_icon($item['key']) ?></span>
              <span class="flex-1"><?= e($item['label']) ?></span>
            </span>
          <?php else: ?>
            <?php
              $isActive = $item['key'] === $active;
              $href = $item['file'] === 'index.php' ? $root . 'index.php' : $pagesPrefix . $item['file'];
            ?>
            <a
              class="<?= $isActive
                ? 'flex items-center gap-3 rounded-xl bg-[#13281f] px-3 py-2.5 text-sm font-medium text-mint'
                : 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-300 hover:bg-white/5 hover:text-white' ?>"
              href="<?= e($href) ?>"
              <?= $isActive ? 'aria-current="page"' : '' ?>
            >
              <span class="grid h-5 w-5 place-items-center" aria-hidden="true"><?= app_icon($item['key']) ?></span>
              <span class="flex-1"><?= e($item['label']) ?></span>
              <?php if ($isActive): ?>
                <span class="h-1.5 w-1.5 rounded-full bg-mint"></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    </aside>

    <div class="flex min-h-screen min-w-0 flex-1 flex-col">
      <header class="flex h-16 items-center justify-between gap-4 px-4 sm:px-8">
        <button id="menu-button" class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 text-stone-300 lg:hidden" type="button" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
        </button>
        <div class="ml-auto flex items-center gap-3">
          <p class="hidden text-sm text-stone-400 sm:block">Connecté en tant que <span id="current-username" class="font-medium text-stone-200"><?= $username ?></span></p>
          <button data-logout-open class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs font-medium text-stone-200 transition hover:border-white/20 hover:bg-white/10" type="button">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2"></path><path d="M4 12h11M12 8l4 4-4 4"></path></svg>
            Déconnexion
          </button>
        </div>
      </header>

      <form id="logout-form" method="post" action="<?= e($root) ?>auth.php" class="hidden">
        <input type="hidden" name="action" value="logout">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      </form>
<?php
}

function render_end(string $root = '', array $scripts = []): void
{
    ?>
    </div>
  </div>

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
  <script src="<?= e($root) ?>assets/js/layout.js" defer></script>
<?php foreach ($scripts as $script): ?>
  <script src="<?= e($root . $script) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php
}
