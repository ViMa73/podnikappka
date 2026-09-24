<?php
    use Core\Auth;
    use Core\Nav;
    use Core\AppRelease;
    use Core\WhatsNew;
    use Core\DB;

    $appVersion = \Core\AppRelease::current();
    $appReleases = \Core\AppRelease::all();

    $shouldAutoOpenWhatsNew = \Core\WhatsNew::shouldAutoOpen();
?>

<!DOCTYPE html>
<html lang="cs" class="<?= \Core\Auth::theme() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard' ?></title>
    <link rel="manifest" href="/uploads/site.webmanifest">
    <meta name="theme-color" content="#1E3A5F">
    <link rel="icon" type="image/svg+xml" href="/uploads/podnikappka_icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/uploads/podnikappka_icon_32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/uploads_podnikappka_icon_192.png">
    <link rel="apple-touch-icon" href="/uploads/podnikappka_icon_180.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <style>
      /* default (light) */
      html.light {
        --bg: #f3f4f6;          /* gray-100 */
        --card: #ffffff;
        --text: #111827;        /* gray-900 */
        --muted: #6b7280;       /* gray-500 */
        --border: #e5e7eb;      /* gray-200 */
        --primary: #2563eb;     /* blue-600 */
        --primary-hover: #1d4ed8;
        --primary-text: #ffffff;

        --success: #16a34a;
        --error: #dc2626;

        --closed: #d97706;
        --closed-today: #b45309;

        --free: #dc2626;
        --free-today: #b91c1c;

        --taken: #16a34a;
        --taken-today: #15803d;

        --vacation: #2563eb;
        --vacation-today: #1d4ed8;

        --note: #7c3aed;
        --note-today: #6d28d9;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #f59e0b;
        --attendance-sick: #ef4444;
        --attendance-holiday: #eab308; /* amber-500 */
      }

      /* dark */
      html.dark {
        --bg: #111827;          /* gray-900 */
        --card: #1f2937;        /* gray-800 */
        --text: #f9fafb;        /* gray-50 */
        --muted: #cbd5e1;       /* slate-300 */
        --border: #374151;      /* gray-700 */
        --primary: #60a5fa;     /* blue-400 */
        --primary-hover: #3b82f6;
        --primary-text: #0f172a;

        --success: #4ade80;
        --error: #f87171;

        --closed: #f59e0b;
        --closed-today: #fbbf24;

        --free: #f87171;
        --free-today: #fca5a5;

        --taken: #4ade80;
        --taken-today: #86efac;

        --vacation: #60a5fa;
        --vacation-today: #93c5fd;

        --note: #a78bfa;
        --note-today: #c4b5fd;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #fbbf24;
        --attendance-sick: #f87171;
        --attendance-holiday: #facc15; /* amber-400 */
      }

      /* coffee */
      html.coffee {
        --bg: #f6f0e7;
        --card: #fffaf2;
        --text: #2b2118;
        --muted: #6b5748;
        --border: #eadfce;
        --primary: #8b5e3c;
        --primary-hover: #754e31;
        --primary-text: #ffffff;

        --success: #5f8a3b;
        --error: #b64a3a;

        --closed: #b7791f;
        --closed-today: #975a16;

        --free: #c05621;
        --free-today: #9c4221;

        --taken: #5f8a3b;
        --taken-today: #4a6b2f;

        --vacation: #3b82a0;
        --vacation-today: #2c6c88;

        --note: #8b5cf6;
        --note-today: #7c3aed;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #b7791f;
        --attendance-sick: #c05621;
        --attendance-holiday: #d4a373;
      }

      /* olive */
      html.olive {
        --bg: #f2f5ee;
        --card: #fbfdf8;
        --text: #1f2a1f;
        --muted: #556155;
        --border: #dbe4d2;
        --primary: #4f7a3a;
        --primary-hover: #3f642f;
        --primary-text: #ffffff;

        --success: #4d7c0f;
        --error: #b91c1c;

        --closed: #a16207;
        --closed-today: #854d0e;

        --free: #b91c1c;
        --free-today: #991b1b;

        --taken: #4f7a3a;
        --taken-today: #3f642f;

        --vacation: #2563eb;
        --vacation-today: #1d4ed8;

        --note: #7c3aed;
        --note-today: #6d28d9;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #a16207;
        --attendance-sick: #b91c1c;
        --attendance-holiday: #ca8a04;
      }

      html.slate {
        --bg: #f1f5f9;          /* slate-100 */
        --card: #ffffff;
        --text: #0f172a;        /* slate-900 */
        --muted: #475569;       /* slate-600 */
        --border: #e2e8f0;      /* slate-200 */
        --primary: #0284c7;     /* sky-600 */
        --primary-hover: #0369a1;
        --primary-text: #ffffff;

        --success: #16a34a;
        --error: #dc2626;

        --closed: #d97706;
        --closed-today: #b45309;

        --free: #dc2626;
        --free-today: #b91c1c;

        --taken: #16a34a;
        --taken-today: #15803d;

        --vacation: #0284c7;
        --vacation-today: #0369a1;

        --note: #7c3aed;
        --note-today: #6d28d9;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #d97706;
        --attendance-sick: #dc2626;
        --attendance-holiday: #eab308;
      }

      html.forest {
        --bg: #f0fdf4;          /* green-50 */
        --card: #ffffff;
        --text: #14532d;        /* green-900 */
        --muted: #4d7c0f;       /* lime-700 */
        --border: #dcfce7;      /* green-100 */
        --primary: #16a34a;     /* green-600 */
        --primary-hover: #15803d;
        --primary-text: #ffffff;

        --success: #15803d;
        --error: #dc2626;

        --closed: #ca8a04;
        --closed-today: #a16207;

        --free: #dc2626;
        --free-today: #b91c1c;

        --taken: #16a34a;
        --taken-today: #15803d;

        --vacation: #2563eb;
        --vacation-today: #1d4ed8;

        --note: #7c3aed;
        --note-today: #6d28d9;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #ca8a04;
        --attendance-sick: #dc2626;
        --attendance-holiday: #eab308;
      }

      html.midnight {
        --bg: #0f172a;           /* slate-900 */
        --card: #1e293b;         /* slate-800 */
        --text: #e2e8f0;         /* slate-200 */
        --muted: #94a3b8;        /* slate-400 */
        --border: #334155;       /* slate-700 */
        --primary: #6366f1;      /* indigo-500 */
        --primary-hover: #4f46e5;
        --primary-text: #ffffff;

        --success: #22c55e;
        --error: #f87171;

        --closed: #f59e0b;
        --closed-today: #fbbf24;

        --free: #f87171;
        --free-today: #fca5a5;

        --taken: #22c55e;
        --taken-today: #4ade80;

        --vacation: #60a5fa;
        --vacation-today: #93c5fd;

        --note: #a78bfa;
        --note-today: #c4b5fd;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #f59e0b;
        --attendance-sick: #f87171;
        --attendance-holiday: #facc15;
      }

      html.midnight .rounded-2xl {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
      }

      /* CRAZY THEME */
      html.crazy {
        --bg: #14002b;
        --card: #1f003d;
        --text: #f5e9ff;
        --muted: #b38bd6;
        --border: #3a0072;

        --primary: #00f5d4;
        --primary-hover: #00e0c2;
        --primary-text: #14002b;

        --success: #b9ff00;
        --error: #ff2e63;

        --closed: #ff9f1c;
        --closed-today: #ffbf69;

        --free: #ff2e63;
        --free-today: #ff5c8a;

        --taken: #b9ff00;
        --taken-today: #d8ff4d;

        --vacation: #00bbf9;
        --vacation-today: #66d9ff;

        --note: #9b5de5;
        --note-today: #c77dff;

        --sidebar-bg: #0f001f;
        --sidebar-text: #e8d4ff;

        --attendance-vacation: var(--vacation);
        --attendance-ocr: #ff9f1c;
        --attendance-sick: #ff2e63;
        --attendance-holiday: #fee440; /* neon žlutá */
      }

      .theme-crazy .btn-primary {
        box-shadow: 0 0 12px rgba(0, 245, 212, 0.5);
      }

      .theme-crazy .btn-primary:hover {
        box-shadow: 0 0 18px rgba(0, 245, 212, 0.8);
      }

      .inactive-menu {
        background: var(--bg);
        color: var(--text);
        transition: background-color 150ms ease, box-shadow 150ms ease;
      }

      .inactive-menu:hover {
        background: var(--primary);
      }

      .active-menu {
        background: var(--primary);
        color: var(--primary-text);
      }

      .btn-primary {
        background: var(--primary);
        color: var(--primary-text);
        transition: background-color 150ms ease, box-shadow 150ms ease;
      }

      .btn-primary:hover {
        background: var(--primary-hover);
      }

      .btn-primary:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 40%, transparent);
      }

      .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }

      .btn-secondary {
        background: transparent;
        color: var(--primary);
        border: 1px solid var(--border, #e5e7eb);
        transition: background-color 150ms ease, color 150ms ease;
      }

      .btn-secondary:hover {
        background: color-mix(in srgb, var(--primary) 8%, transparent);
      }

      .hover-menu{
        background: var(--bg);
      }

      .hover-menu:hover{
        background: var(--card);
      }

      .dropdown-menu{
        background: var(--card);
      }

      .dropdown-menu:hover{
        background: var(--bg);
      }

      .toast {
        max-width: 420px;
        transform: translateY(0);
        opacity: 1;
        transition: opacity 200ms ease, transform 200ms ease;
      }
      .toast.toast-hide {
        opacity: 0;
        transform: translateY(-6px);
      }

      /* default: desktop – scroll jen v <main> */
      #appContent { overflow-y: auto; overflow-x: hidden; }

      /* MOBILE: vrať scroll na body, aby fungoval pull-to-refresh */
      @media (max-width: 768px) {
        body { height: auto !important; overflow: auto !important; }
        #appShell { height: auto !important; min-height: 100vh; }
        #appContent { overflow: visible !important; }
      }
      </style>
</head>

<body class="h-screen overflow-hidden" style="background: var(--bg); color: var(--text);">

<div id="toasts" class="fixed top-20 right-4 z-50 space-y-3 w-[calc(100%-2rem)] sm:w-auto">
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="toast flex items-start justify-between gap-4 rounded-xl px-4 py-3 shadow-lg"
         style="background: color-mix(in srgb, var(--error) 16%, var(--card)); border: 1px solid var(--border); color: var(--text);">
      <div class="text-sm">
        <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
      </div>
      <button type="button" class="toast-close text-xl leading-none opacity-80 hover:opacity-100">&times;</button>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="toast flex items-start justify-between gap-4 rounded-xl px-4 py-3 shadow-lg"
         style="background: color-mix(in srgb, var(--success) 16%, var(--card)); border: 1px solid var(--border); color: var(--text);">
      <div class="text-sm">
        <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
      </div>
      <button type="button" class="toast-close text-xl leading-none opacity-80 hover:opacity-100">&times;</button>
    </div>
  <?php endif; ?>
</div>

<div id="appShell" class="flex h-full min-h-0">

    <!-- Mobile overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden" onclick="closeMobileMenu()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-50 md:z-auto w-[80vw] max-w-[22rem] md:w-64 shadow-lg flex flex-col transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out border-r overflow-y-auto overscroll-contain" style="background: var(--bg); border-color: var(--border);">

        <div class="p-6 text-2xl font-bold border-b flex justify-between items-center" style="border-color: var(--border);">
            <img src="/uploads/podnikappka_logotyp.png">
          <button class="md:hidden" onclick="closeMobileMenu()">✕</button>
        </div>

        <nav class="p-4 space-y-2 text-sm">

            <?php if (\Core\Edition::isSelfHosted() && \Core\Auth::isOwner()): ?>
              <a href="/admin/system" class="<?= \Core\Nav::linkClass('/admin/system') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 13V21M12 21L15.5 17.5M12 21L8.5 17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path d="M8 17H6.5C4.01472 17 2 14.9853 2 12.5C2 10.0147 4.01472 8 6.5 8C6.67633 8 6.85029 8.01013 7.02135 8.02984C7.62694 5.69654 9.74659 4 12.25 4C15.2335 4 17.6669 6.36654 17.7745 9.32372C20.1748 9.45463 22 11.4412 22 13.875C22 16.3933 19.9558 18.4375 17.4375 18.4375H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                </svg>
                Systém / Aktualizace
              </a>
              <hr>
            <?php endif; ?>

            <?php if (\Core\Auth::isSuperAdmin()): ?>
              <a href="/admin" class="<?= \Core\Nav::linkClass('/admin') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M2 18C2 17.0681 2 16.6022 2.15224 16.2346C2.35523 15.7446 2.74458 15.3552 3.23463 15.1522C3.60218 15 4.06812 15 5 15H19C19.9319 15 20.3978 15 20.7654 15.1522C21.2554 15.3552 21.6448 15.7446 21.8478 16.2346C22 16.6022 22 17.0681 22 18C22 18.9319 22 19.3978 21.8478 19.7654C21.6448 20.2554 21.2554 20.6448 20.7654 20.8478C20.3978 21 19.9319 21 19 21H5C4.06812 21 3.60218 21 3.23463 20.8478C2.74458 20.6448 2.35523 20.2554 2.15224 19.7654C2 19.3978 2 18.9319 2 18Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M2 12C2 11.0681 2 10.6022 2.15224 10.2346C2.35523 9.74458 2.74458 9.35523 3.23463 9.15224C3.60218 9 4.06812 9 5 9H19C19.9319 9 20.3978 9 20.7654 9.15224C21.2554 9.35523 21.6448 9.74458 21.8478 10.2346C22 10.6022 22 11.0681 22 12C22 12.9319 22 13.3978 21.8478 13.7654C21.6448 14.2554 21.2554 14.6448 20.7654 14.8478C20.3978 15 19.9319 15 19 15H5C4.06812 15 3.60218 15 3.23463 14.8478C2.74458 14.6448 2.35523 14.2554 2.15224 13.7654C2 13.3978 2 12.9319 2 12Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M2 6C2 5.06812 2 4.60218 2.15224 4.23463C2.35523 3.74458 2.74458 3.35523 3.23463 3.15224C3.60218 3 4.06812 3 5 3H19C19.9319 3 20.3978 3 20.7654 3.15224C21.2554 3.35523 21.6448 3.74458 21.8478 4.23463C22 4.60218 22 5.06812 22 6C22 6.93188 22 7.39782 21.8478 7.76537C21.6448 8.25542 21.2554 8.64477 20.7654 8.84776C20.3978 9 19.9319 9 19 9H5C4.06812 9 3.60218 9 3.23463 8.84776C2.74458 8.64477 2.35523 8.25542 2.15224 7.76537C2 7.39782 2 6.93188 2 6Z" stroke="currentColor" stroke-width="1.5"></path>
                    <circle cx="5" cy="12" r="1" fill="currentColor"></circle>
                    <circle cx="5" cy="6" r="1" fill="currentColor"></circle>
                    <circle cx="5" cy="18" r="1" fill="#currentColor"></circle> </g></svg>
                Administrace
              </a>
              <hr>
            <?php endif; ?>

            <a href="/dashboard" class="<?= \Core\Nav::linkClass('/dashboard') ?>">
              <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g>
                  <path d="M13.5 15.5C13.5 13.6144 13.5 12.6716 14.0858 12.0858C14.6716 11.5 15.6144 11.5 17.5 11.5C19.3856 11.5 20.3284 11.5 20.9142 12.0858C21.5 12.6716 21.5 13.6144 21.5 15.5V17.5C21.5 19.3856 21.5 20.3284 20.9142 20.9142C20.3284 21.5 19.3856 21.5 17.5 21.5C15.6144 21.5 14.6716 21.5 14.0858 20.9142C13.5 20.3284 13.5 19.3856 13.5 17.5V15.5Z" stroke="currentColor" stroke-width="1.5"></path>
                  <path d="M2 8.5C2 10.3856 2 11.3284 2.58579 11.9142C3.17157 12.5 4.11438 12.5 6 12.5C7.88562 12.5 8.82843 12.5 9.41421 11.9142C10 11.3284 10 10.3856 10 8.5V6.5C10 4.61438 10 3.67157 9.41421 3.08579C8.82843 2.5 7.88562 2.5 6 2.5C4.11438 2.5 3.17157 2.5 2.58579 3.08579C2 3.67157 2 4.61438 2 6.5V8.5Z" stroke="currentColor" stroke-width="1.5"></path>
                  <path d="M13.5 5.5C13.5 4.56812 13.5 4.10218 13.6522 3.73463C13.8552 3.24458 14.2446 2.85523 14.7346 2.65224C15.1022 2.5 15.5681 2.5 16.5 2.5H18.5C19.4319 2.5 19.8978 2.5 20.2654 2.65224C20.7554 2.85523 21.1448 3.24458 21.3478 3.73463C21.5 4.10218 21.5 4.56812 21.5 5.5C21.5 6.43188 21.5 6.89782 21.3478 7.26537C21.1448 7.75542 20.7554 8.14477 20.2654 8.34776C19.8978 8.5 19.4319 8.5 18.5 8.5H16.5C15.5681 8.5 15.1022 8.5 14.7346 8.34776C14.2446 8.14477 13.8552 7.75542 13.6522 7.26537C13.5 6.89782 13.5 6.43188 13.5 5.5Z" stroke="currentColor" stroke-width="1.5"></path>
                  <path d="M2 18.5C2 19.4319 2 19.8978 2.15224 20.2654C2.35523 20.7554 2.74458 21.1448 3.23463 21.3478C3.60218 21.5 4.06812 21.5 5 21.5H7C7.93188 21.5 8.39782 21.5 8.76537 21.3478C9.25542 21.1448 9.64477 20.7554 9.84776 20.2654C10 19.8978 10 19.4319 10 18.5C10 17.5681 10 17.1022 9.84776 16.7346C9.64477 16.2446 9.25542 15.8552 8.76537 15.6522C8.39782 15.5 7.93188 15.5 7 15.5H5C4.06812 15.5 3.60218 15.5 3.23463 15.6522C2.74458 15.8552 2.35523 16.2446 2.15224 16.7346C2 17.1022 2 17.5681 2 18.5Z" stroke="currentColor" stroke-width="1.5"></path>
                </g>
              </svg>
              Nástěnka
            </a>

            <?php if (\Core\Feature::enabled('services')): ?>
              <a href="/services" class="<?= \Core\Nav::linkClass('/services') ?>">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12V14C22 17.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V12Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M7 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M17 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M2.5 9H21.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M18 17C18 17.5523 17.5523 18 17 18C16.4477 18 16 17.5523 16 17C16 16.4477 16.4477 16 17 16C17.5523 16 18 16.4477 18 17Z" fill="currentColor"></path>
                    <path d="M18 13C18 13.5523 17.5523 14 17 14C16.4477 14 16 13.5523 16 13C16 12.4477 16.4477 12 17 12C17.5523 12 18 12.4477 18 13Z" fill="currentColor"></path>
                    <path d="M13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16C12.5523 16 13 16.4477 13 17Z" fill="currentColor"></path>
                    <path d="M13 13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13C11 12.4477 11.4477 12 12 12C12.5523 12 13 12.4477 13 13Z" fill="currentColor"></path>
                    <path d="M8 17C8 17.5523 7.55228 18 7 18C6.44772 18 6 17.5523 6 17C6 16.4477 6.44772 16 7 16C7.55228 16 8 16.4477 8 17Z" fill="currentColor"></path>
                    <path d="M8 13C8 13.5523 7.55228 14 7 14C6.44772 14 6 13.5523 6 13C6 12.4477 6.44772 12 7 12C7.55228 12 8 12.4477 8 13Z" fill="currentColor"></path>
                  </g>
                </svg>
                Služby
              </a>
            <?php endif; ?>

            <?php if (\Core\Feature::enabled('tasks')): ?>
              <a href="/tasks" class="<?= \Core\Nav::linkClass('/tasks') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M6 15.8L7.14286 17L10 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M6 8.8L7.14286 10L10 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M13 9L18 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M13 16L18 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                  </g>
                </svg>
                <span>Úkoly</span>
              </a>
            <?php endif; ?>

            <?php if (\Core\Feature::enabled('vacations')): ?>
              <a href="/vacations" class="<?= \Core\Nav::linkClass('/vacations', 0) ?>">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
                  <g>
                    <circle cx="12" cy="10" r="7" stroke="currentColor" stroke-width="1.5"></circle>
                    <path d="M4 16.5623C5.88838 18.6722 8.63263 20 11.687 20C17.3827 20 22 15.3827 22 9.68699C22 6.63263 20.6722 3.88838 18.5623 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M7 4.55263C7.58518 5.10525 8.79066 6.61576 8.93111 8.2368C9.06238 9.75203 10.0268 10.9832 11.5 10.9999C12.0662 11.0063 12.6388 10.5822 12.6373 9.99503C12.6368 9.81346 12.6079 9.62782 12.5627 9.45703C12.4998 9.21948 12.4942 8.94619 12.625 8.66662C13.0824 7.68861 13.982 7.42589 14.6949 6.89475C15.0111 6.65918 15.2995 6.41067 15.4266 6.2105C15.7777 5.65788 16.1289 4.55263 15.9533 4" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M19 11C18.7804 11.6207 18.625 13.25 16.1455 13.2759C16.1455 13.2759 13.9497 13.2759 13.291 14.5172C12.764 15.5103 13.0714 16.5862 13.291 17" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M12 22V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M12 22H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M14 22H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                  </g>
                </svg>
                Dovolené
              </a>
            <?php endif; ?>

            <?php if (\Core\Feature::enabled('vacations') && in_array(\Core\Auth::role(), ['owner', 'manager'], true)): ?>
              <a href="/vacations/all" class="<?= \Core\Nav::linkClass('/vacations/all') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"></circle>
                    <path d="M6 4.71053C6.78024 5.42105 8.38755 7.36316 8.57481 9.44737C8.74984 11.3955 10.0357 12.9786 12 13C12.7549 13.0082 13.5183 12.4629 13.5164 11.708C13.5158 11.4745 13.4773 11.2358 13.417 11.0163C13.3331 10.7108 13.3257 10.3595 13.5 10C14.1099 8.74254 15.3094 8.40477 16.2599 7.72186C16.6814 7.41898 17.0659 7.09947 17.2355 6.84211C17.7037 6.13158 18.1718 4.71053 17.9377 4" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M22 13C21.6706 13.931 21.4375 16.375 17.7182 16.4138C17.7182 16.4138 14.4246 16.4138 13.4365 18.2759C12.646 19.7655 13.1071 21.3793 13.4365 22" stroke="currentColor" stroke-width="1.5"></path>
                  </g>
                </svg>
                Všechny dovolené
              </a>
            <?php endif; ?>

            <?php if (\Core\Feature::enabled('attendance')): ?>
              <a href="/attendance" class="<?= \Core\Nav::linkClass('/attendance') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"></path>
                  </g>
                </svg>
                <span>Docházka</span>
              </a>
            <?php endif; ?>

            <?php if (\Core\Feature::enabled('payrolls')): ?>
              <a href="/payrolls" class="<?= \Core\Nav::linkClass('/payrolls') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M17.4142 10.4142C18 9.82843 18 8.88562 18 7C18 5.11438 18 4.17157 17.4142 3.58579M17.4142 10.4142C16.8284 11 15.8856 11 14 11H10C8.11438 11 7.17157 11 6.58579 10.4142M17.4142 10.4142C17.4142 10.4142 17.4142 10.4142 17.4142 10.4142ZM17.4142 3.58579C16.8284 3 15.8856 3 14 3L10 3C8.11438 3 7.17157 3 6.58579 3.58579M17.4142 3.58579C17.4142 3.58579 17.4142 3.58579 17.4142 3.58579ZM6.58579 3.58579C6 4.17157 6 5.11438 6 7C6 8.88562 6 9.82843 6.58579 10.4142M6.58579 3.58579C6.58579 3.58579 6.58579 3.58579 6.58579 3.58579ZM6.58579 10.4142C6.58579 10.4142 6.58579 10.4142 6.58579 10.4142Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M13 7C13 7.55228 12.5523 8 12 8C11.4477 8 11 7.55228 11 7C11 6.44772 11.4477 6 12 6C12.5523 6 13 6.44772 13 7Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M18 6C16.3431 6 15 4.65685 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M18 8C16.3431 8 15 9.34315 15 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M6 6C7.65685 6 9 4.65685 9 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M6 8C7.65685 8 9 9.34315 9 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M5 20.3884H7.25993C8.27079 20.3884 9.29253 20.4937 10.2763 20.6964C12.0166 21.0549 13.8488 21.0983 15.6069 20.8138C16.4738 20.6734 17.326 20.4589 18.0975 20.0865C18.7939 19.7504 19.6469 19.2766 20.2199 18.7459C20.7921 18.216 21.388 17.3487 21.8109 16.6707C22.1736 16.0894 21.9982 15.3762 21.4245 14.943C20.7873 14.4619 19.8417 14.462 19.2046 14.9433L17.3974 16.3084C16.697 16.8375 15.932 17.3245 15.0206 17.4699C14.911 17.4874 14.7962 17.5033 14.6764 17.5172M14.6764 17.5172C14.6403 17.5214 14.6038 17.5254 14.5668 17.5292M14.6764 17.5172C14.8222 17.486 14.9669 17.396 15.1028 17.2775C15.746 16.7161 15.7866 15.77 15.2285 15.1431C15.0991 14.9977 14.9475 14.8764 14.7791 14.7759C11.9817 13.1074 7.62942 14.3782 5 16.2429M14.6764 17.5172C14.6399 17.525 14.6033 17.5292 14.5668 17.5292M14.5668 17.5292C14.0434 17.5829 13.4312 17.5968 12.7518 17.5326" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <rect x="2" y="14" width="3" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"></rect>
                  </g>
                </svg>
                <span>Výplaty</span>
              </a>
            <?php endif; ?>

            <?php
              $showPayrollsMenu = false;

              if (\Core\Feature::enabled('payrolls')) {
                  if (\Core\Auth::role() === 'owner') {
                      $showPayrollsMenu = true;
                  } elseif (\Core\Auth::role() === 'manager') {
                      $showPayrollsMenu = (int)($_SESSION['company_payroll_allow_manager'] ?? 0) === 1;
                  } else {
                      $showPayrollsMenu = (int)($_SESSION['user_payroll_active'] ?? 1) === 1;
                  }
              }
            ?>

            <?php if ($showPayrollsMenu): ?>
              <a href="/economic-indicators" class="<?= \Core\Nav::linkClass('/economic-indicators') ?>">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M7 18V9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M12 18V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M17 18V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                  </g>
                </svg>
                <span>Ekonomické ukazatele</span>
              </a>
            <?php endif; ?>

            <?php if (\Core\Auth::check() && \Core\Feature::enabled('temperatures')): ?>
              <a href="/temperatures" class="<?= \Core\Nav::linkClass('/temperatures') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M12 22C15.0376 22 17.5 19.5376 17.5 16.5C17.5 14.7636 16.6954 13.2152 15.4386 12.2072C15.1749 11.9957 15 11.6857 15 11.3477V5C15 3.34315 13.6569 2 12 2C10.3431 2 9 3.34315 9 5V11.3477C9 11.6857 8.82505 11.9957 8.56141 12.2072C7.30465 13.2152 6.5 14.7636 6.5 16.5C6.5 19.5376 8.96243 22 12 22Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M14.5 16.5C14.5 17.8807 13.3807 19 12 19C10.6193 19 9.5 17.8807 9.5 16.5C9.5 15.1193 10.6193 14 12 14C13.3807 14 14.5 15.1193 14.5 16.5Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M12 14V5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                  </g>
                </svg>
                <span>Teploty</span>
              </a>
            <?php endif; ?>

            <?php if (\Core\Auth::check() && \Core\Feature::enabled('sterilization_drying')): ?>
              <a href="/sterilization-drying" class="<?= \Core\Nav::linkClass('/sterilization-drying') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M5 11C5 8.17157 5 6.75736 5.87868 5.87868C6.75736 5 8.17157 5 11 5H13C15.8284 5 17.2426 5 18.1213 5.87868C19 6.75736 19 8.17157 19 11V13C19 15.8284 19 17.2426 18.1213 18.1213C17.2426 19 15.8284 19 13 19H11C8.17157 19 6.75736 19 5.87868 18.1213C5 17.2426 5 15.8284 5 13V11Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12Z" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M13.5 12C13.5 12.8284 12.8284 13.5 12 13.5C11.1716 13.5 10.5 12.8284 10.5 12C10.5 11.1716 11.1716 10.5 12 10.5C12.8284 10.5 13.5 11.1716 13.5 12Z" fill="currentColor"></path>
                    <path d="M12 12V8" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M12 12L15.5 13.5" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M12 12L8.5 13.5" stroke="currentColor" stroke-width="1.5"></path>
                    <path d="M4.5 7V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M4.5 14V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                  </g>
                </svg>
                <span>Sterilizace a sušení</span>
              </a>
            <?php endif; ?>

            <?php if (\Core\Auth::canAccessWasteReports()): ?>
              <a href="/waste-reports" class="<?= \Core\Nav::linkClass('/waste-reports') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M18.8332 8.5L18.3732 15.3991C18.1962 18.054 18.1077 19.3815 17.2427 20.1907C16.3777 21 15.0473 21 12.3865 21H11.6132C8.95235 21 7.62195 21 6.75694 20.1907C5.89194 19.3815 5.80344 18.054 5.62644 15.3991L5.1665 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path>
                  </g>
                </svg>
                <span>Hlášení odpadů</span>
              </a>
            <?php endif; ?>

            <?php if (\Core\Auth::canViewExports()): ?>
              <a href="/exports" class="<?= \Core\Nav::linkClass('/exports') ?>">
                <svg class="w-5 h-5" width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g>
                    <path d="M12 7L12 14M12 14L15 11M12 14L9 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M16 17H12H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"></path>
                  </g>
                </svg>
                <span>Exporty</span>
              </a>
            <?php endif; ?>

            <?php if (\Core\Auth::canManageUsers()): ?>
              <a href="/users" class="<?= \Core\Nav::linkClass('/users') ?>">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g>
                    <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle>
                    <path d="M20 17.5C20 19.9853 20 22 12 22C4 22 4 19.9853 4 17.5C4 15.0147 7.58172 13 12 13C16.4183 13 20 15.0147 20 17.5Z" stroke="currentColor" stroke-width="1.5"></path>
                    </g>
                </svg>
                Uživatelé
              </a>
            <?php endif; ?>

            <?php if (\Core\Auth::canAccessSettings()): ?>
                <a href="/settings" class="<?= \Core\Nav::linkClass('/settings') ?>">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <g>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                      <path d="M13.7654 2.15224C13.3978 2 12.9319 2 12 2C11.0681 2 10.6022 2 10.2346 2.15224C9.74457 2.35523 9.35522 2.74458 9.15223 3.23463C9.05957 3.45834 9.0233 3.7185 9.00911 4.09799C8.98826 4.65568 8.70226 5.17189 8.21894 5.45093C7.73564 5.72996 7.14559 5.71954 6.65219 5.45876C6.31645 5.2813 6.07301 5.18262 5.83294 5.15102C5.30704 5.08178 4.77518 5.22429 4.35436 5.5472C4.03874 5.78938 3.80577 6.1929 3.33983 6.99993C2.87389 7.80697 2.64092 8.21048 2.58899 8.60491C2.51976 9.1308 2.66227 9.66266 2.98518 10.0835C3.13256 10.2756 3.3397 10.437 3.66119 10.639C4.1338 10.936 4.43789 11.4419 4.43786 12C4.43783 12.5581 4.13375 13.0639 3.66118 13.3608C3.33965 13.5629 3.13248 13.7244 2.98508 13.9165C2.66217 14.3373 2.51966 14.8691 2.5889 15.395C2.64082 15.7894 2.87379 16.193 3.33973 17C3.80568 17.807 4.03865 18.2106 4.35426 18.4527C4.77508 18.7756 5.30694 18.9181 5.83284 18.8489C6.07289 18.8173 6.31632 18.7186 6.65204 18.5412C7.14547 18.2804 7.73556 18.27 8.2189 18.549C8.70224 18.8281 8.98826 19.3443 9.00911 19.9021C9.02331 20.2815 9.05957 20.5417 9.15223 20.7654C9.35522 21.2554 9.74457 21.6448 10.2346 21.8478C10.6022 22 11.0681 22 12 22C12.9319 22 13.3978 22 13.7654 21.8478C14.2554 21.6448 14.6448 21.2554 14.8477 20.7654C14.9404 20.5417 14.9767 20.2815 14.9909 19.902C15.0117 19.3443 15.2977 18.8281 15.781 18.549C16.2643 18.2699 16.8544 18.2804 17.3479 18.5412C17.6836 18.7186 17.927 18.8172 18.167 18.8488C18.6929 18.9181 19.2248 18.7756 19.6456 18.4527C19.9612 18.2105 20.1942 17.807 20.6601 16.9999C21.1261 16.1929 21.3591 15.7894 21.411 15.395C21.4802 14.8691 21.3377 14.3372 21.0148 13.9164C20.8674 13.7243 20.6602 13.5628 20.3387 13.3608C19.8662 13.0639 19.5621 12.558 19.5621 11.9999C19.5621 11.4418 19.8662 10.9361 20.3387 10.6392C20.6603 10.4371 20.8675 10.2757 21.0149 10.0835C21.3378 9.66273 21.4803 9.13087 21.4111 8.60497C21.3592 8.21055 21.1262 7.80703 20.6602 7C20.1943 6.19297 19.9613 5.78945 19.6457 5.54727C19.2249 5.22436 18.693 5.08185 18.1671 5.15109C17.9271 5.18269 17.6837 5.28136 17.3479 5.4588C16.8545 5.71959 16.2644 5.73002 15.7811 5.45096C15.2977 5.17191 15.0117 4.65566 14.9909 4.09794C14.9767 3.71848 14.9404 3.45833 14.8477 3.23463C14.6448 2.74458 14.2554 2.35523 13.7654 2.15224Z" stroke="currentColor" stroke-width="1.5"></path>
                      </g>
                  </svg>
                  Nastavení
                </a>
            <?php endif; ?>

        </nav>

        <div class="mt-auto text-xs text-gray-400 p-2 pt-10 text-center">
            Podnikappka v<?= htmlspecialchars($appVersion['version'] ?? '0.0.0') ?> | <button id="open-whats-new">Co je nového?</button><br>
            by <a href="https://vit-marek.cz" target="_blank">Vít Marek</a><br>
            <a href="/license">AGPL-3.0</a> | <a href="/disclaimer">Disclaimer</a>
        </div>

    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col md:ml-0 min-w-0 min-h-0">

      <?php
        $todayNamedayText = \Core\NamedayHelper::getTextForDate();
        $birthdayInfoText = null;

        if (\Core\Auth::check()) {
            $db = \Core\DB::get();

            $today = new \DateTimeImmutable('today');
            $tomorrow = $today->modify('+1 day');

            $todayMonth = (int)$today->format('m');
            $todayDay = (int)$today->format('d');

            $tomorrowMonth = (int)$tomorrow->format('m');
            $tomorrowDay = (int)$tomorrow->format('d');

            $stmt = $db->prepare("
                SELECT first_name, last_name, birth_date
                FROM users
                WHERE company_id = ?
                  AND status = 'active'
                  AND birth_date IS NOT NULL
                  AND (
                        (MONTH(birth_date) = ? AND DAY(birth_date) = ?)
                     OR (MONTH(birth_date) = ? AND DAY(birth_date) = ?)
                  )
                ORDER BY first_name ASC, last_name ASC
            ");
            $stmt->execute([
                \Core\Auth::companyId(),
                $todayMonth,
                $todayDay,
                $tomorrowMonth,
                $tomorrowDay,
            ]);

            $birthdayRows = $stmt->fetchAll() ?: [];

            $todayBirthdays = [];
            $tomorrowBirthdays = [];

            foreach ($birthdayRows as $row) {
                $birthDate = trim((string)($row['birth_date'] ?? ''));
                if ($birthDate === '') {
                    continue;
                }

                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $monthDay = date('m-d', strtotime($birthDate));

                if ($monthDay === $today->format('m-d')) {
                    $todayBirthdays[] = $name;
                } elseif ($monthDay === $tomorrow->format('m-d')) {
                    $tomorrowBirthdays[] = $name;
                }
            }

            $formatBirthdayText = function (array $names, string $when): ?string {
                $count = count($names);

                if ($count === 0) {
                    return null;
                }

                if ($count === 1) {
                    return $when . ' má ' . $names[0] . ' narozeniny.';
                }

                if ($count === 2) {
                    return $when . ' mají ' . $names[0] . ' a ' . $names[1] . ' narozeniny.';
                }

                if ($count === 3) {
                    return $when . ' mají ' . $names[0] . ', ' . $names[1] . ' a ' . $names[2] . ' narozeniny.';
                }

                $remaining = $count - 2;
                return $when . ' mají ' . $names[0] . ', ' . $names[1] . ' a ' . $remaining . ' další narozeniny.';
            };

            if (!empty($todayBirthdays)) {
                $birthdayInfoText = $formatBirthdayText($todayBirthdays, 'Dnes');
            } elseif (!empty($tomorrowBirthdays)) {
                $birthdayInfoText = $formatBirthdayText($tomorrowBirthdays, 'Zítra');
            }
        }
        ?>

      <!-- Topbar -->
      <header class="h-16 flex-shrink-0 shadow px-6 py-2 flex justify-between items-center border-b"
              style="background: var(--bg); border-color: var(--border);">

          <div class="flex items-center gap-4 min-w-0">
              <button type="button"
                      class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                      onclick="openMobileMenu()"
                      aria-label="Otevřít menu">
                  <span class="text-2xl leading-none">☰</span>
              </button>

              <div class="text-xl font-semibold truncate" style="color: var(--text);">
                  <?= htmlspecialchars(\Core\Auth::companyName()) ?>
              </div>
          </div>

          <div class="flex items-center gap-4">
            <div id="topbarDateInfo"
               class="hidden lg:flex flex-col text-right leading-tight"
               style="color: var(--muted); min-width: 320px;">
              <div id="topbarDateTime" class="text-sm font-medium" style="color: var(--text); font-variant-numeric: tabular-nums;">
                  --
              </div>

              <div id="topbarNameday" class="text-xs">
                  <?= htmlspecialchars($todayNamedayText) ?>
              </div>

              <?php if (!empty($birthdayInfoText)): ?>
                  <div id="topbarBirthdayInfo" class="text-xs mt-1" style="color: var(--primary);">
                      <?= htmlspecialchars($birthdayInfoText) ?>
                  </div>
              <?php endif; ?>
            </div>

              <div class="relative">
                  <button id="userMenuButton"
                          type="button"
                          class="flex items-center gap-3 rounded-xl px-3 py-2 transition hover-menu"
                          aria-haspopup="true"
                          aria-expanded="false">

                      <img src="<?= \Core\Auth::avatarUrl(64) ?>"
                           alt="Avatar"
                           class="w-10 h-10 rounded-full object-cover"
                           style="background: var(--card); border: 1px solid var(--border);">

                      <div class="text-left leading-tight hidden sm:block">
                          <div class="font-semibold" style="color: var(--text);">
                              <?= htmlspecialchars(\Core\Auth::fullName()) ?>
                          </div>
                      </div>
                  </button>

                  <div id="userMenu"
                       class="hidden absolute right-0 mt-3 w-80 rounded-2xl shadow-2xl overflow-hidden z-50"
                       style="background: var(--card); border: 1px solid var(--border);">

                      <div class="p-5 flex items-center gap-4">
                          <img src="<?= \Core\Auth::avatarUrl(96) ?>"
                               alt="Avatar"
                               class="w-14 h-14 rounded-full object-cover"
                               style="border: 1px solid var(--border);">

                          <div>
                              <div class="text-lg font-semibold" style="color: var(--text);">
                                  <?= htmlspecialchars(\Core\Auth::fullName()) ?>
                              </div>
                              <div class="text-sm" style="color: var(--text);">
                                  <?= htmlspecialchars(\Core\Auth::email()) ?>
                              </div>
                              <?php if (!empty($user['phone'])): ?>
                                  <div class="text-sm mt-1" style="color: var(--muted);">
                                      <?= htmlspecialchars($user['phone']) ?>
                                  </div>
                              <?php endif; ?>
                          </div>
                      </div>

                      <div class="border-t" style="border-color: var(--border);">
                          <a href="/profile" class="block px-6 py-4 text-base dropdown-menu">
                              Můj profil
                          </a>
                      </div>

                      <div class="border-t" style="border-color: var(--border);">
                          <a href="/logout" class="block px-6 py-4 text-base text-red-500 dropdown-menu">
                              Odhlásit se
                          </a>
                      </div>
                  </div>
              </div>
          </div>
      </header>
      <script>
        (function () {
            const dateTimeEl = document.getElementById('topbarDateTime');
            if (!dateTimeEl) return;

            const dayNames = [
                'neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'
            ];

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            function updateClock() {
                const now = new Date();
                const dayName = dayNames[now.getDay()];

                dateTimeEl.textContent =
                    `Dnes je ${dayName} ${now.getDate()}. ${now.getMonth() + 1}. ${now.getFullYear()} ` +
                    `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            }

            updateClock();
            setInterval(updateClock, 1000);
        })();
      </script>

        <!-- Content -->
        <main id="appContent" class="flex-1 overflow-y-auto overflow-x-hidden p-2 md:p-8">



        <?php require __DIR__ . "/$view.php"; ?>

        </main>

    </div>

</div>
<form id="whatsNewSeenForm" method="post" action="/whats-new/mark-seen" class="hidden">
    <?= \Core\CSRF::field() ?>
</form>
<div
    id="whats-new-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    aria-hidden="true"
>
    <div
        id="whats-new-backdrop"
        class="absolute inset-0"
        style="background: rgba(0,0,0,.55);"
    ></div>

    <div
        class="relative w-full max-w-3xl max-h-[85vh] overflow-hidden rounded-2xl shadow-2xl"
        style="background: var(--card); border: 1px solid var(--border);"
        role="dialog"
        aria-modal="true"
        aria-labelledby="whats-new-title"
    >
        <div
            class="flex items-center justify-between px-5 py-4"
            style="border-bottom: 1px solid var(--border);"
        >
            <div>
                <h2 id="whats-new-title" class="text-lg font-semibold" style="color: var(--text);">
                    Co je nového
                </h2>
                <p class="text-sm mt-1" style="color: var(--muted);">
                    Přehled změn v jednotlivých verzích aplikace.
                </p>
            </div>

            <button
                type="button"
                id="close-whats-new"
                class="h-10 w-10 rounded-xl text-lg font-semibold"
                style="background: var(--bg); color: var(--text); border: 1px solid var(--border);"
                aria-label="Zavřít"
            >
                ×
            </button>
        </div>

        <div class="overflow-y-auto px-5 py-4 space-y-3" style="max-height: calc(85vh - 82px);">
            <?php if (empty($appReleases)): ?>
                <div
                    class="rounded-xl p-4 text-sm italic"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);"
                >
                    Zatím nejsou k dispozici žádné release notes.
                </div>
            <?php else: ?>
                <?php foreach ($appReleases as $index => $release): ?>
                    <details
                        <?= $index === 0 ? 'open' : '' ?>
                        class="rounded-2xl overflow-hidden"
                        style="background: var(--bg); border: 1px solid var(--border);"
                    >
                        <summary
                            class="cursor-pointer list-none px-4 py-4 flex items-start justify-between gap-4"
                            style="color: var(--text);"
                        >
                            <div>
                                <div class="font-semibold">
                                    Verze <?= htmlspecialchars($release['version'] ?? '') ?>
                                    <?php if (!empty($release['title'])): ?>
                                        — <?= htmlspecialchars($release['title']) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="text-sm mt-1" style="color: var(--muted);">
                                    <?= htmlspecialchars($release['released_at'] ?? '') ?>
                                </div>
                            </div>

                            <?php if (!empty($release['important'])): ?>
                                <span
                                    class="text-[11px] px-2 py-1 rounded-full whitespace-nowrap"
                                    style="background: var(--primary); color: #fff;"
                                >
                                    důležité
                                </span>
                            <?php endif; ?>
                        </summary>

                        <div class="px-4 pb-4">
                            <?php if (!empty($release['changes']) && is_array($release['changes'])): ?>
                                <ul class="space-y-2 text-sm pl-5 list-disc" style="color: var(--text);">
                                    <?php foreach ($release['changes'] as $change): ?>
                                        <li><?= htmlspecialchars($change) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-sm italic" style="color: var(--muted);">
                                    Bez detailu změn.
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    const btn = document.getElementById('userMenuButton');
    const menu = document.getElementById('userMenu');

    if (!btn || !menu) return;

    function close() {
        menu.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
    }

    function open() {
        menu.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
    }

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (menu.classList.contains('hidden')) open();
        else close();
    });

    document.addEventListener('click', () => close());

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
})();

// zavření křížkem
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.toast-close');
    if (!btn) return;
    const toast = btn.closest('.toast');
    if (!toast) return;
    toast.classList.add('toast-hide');
    setTimeout(() => toast.remove(), 220);
  });

  // auto-hide po 10s
  document.querySelectorAll('#toasts .toast').forEach((toast) => {
    setTimeout(() => {
      toast.classList.add('toast-hide');
      setTimeout(() => toast.remove(), 220);
    }, 10000);
  });

function openMobileMenu() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('mobileOverlay');
  if (!sidebar || !overlay) return;

  sidebar.classList.remove('-translate-x-full');
  overlay.classList.remove('hidden');

  document.body.classList.add('overflow-hidden');
}

function closeMobileMenu() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('mobileOverlay');
  if (!sidebar || !overlay) return;

  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');

  document.body.classList.remove('overflow-hidden');
}

// Zavřít ESC + zavřít po kliknutí na odkaz v menu (na mobilu)
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeMobileMenu();
});

document.addEventListener('click', function (e) {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('mobileOverlay');

  // pokud klikneš na link uvnitř sidebaru na mobilu, zavři menu
  if (sidebar && overlay && !overlay.classList.contains('hidden')) {
    const link = e.target.closest('a');
    if (link && sidebar.contains(link)) {
      closeMobileMenu();
    }
  }
});

// Pojistka: některé layout reflow/globální handlery umí resetovat scrollTop.
  // Tohle udrží pozici při změně checkbox switchů.
  document.addEventListener('change', (e) => {
    const input = e.target.closest('input[name="notes_enabled"]');
    if (!input) return;

    const scroller = document.getElementById('appContent');
    if (!scroller) return;

    const y = scroller.scrollTop;
    requestAnimationFrame(() => { scroller.scrollTop = y; });
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('open-whats-new');
    const closeBtn = document.getElementById('close-whats-new');
    const modal = document.getElementById('whats-new-modal');
    const backdrop = document.getElementById('whats-new-backdrop');
    const seenForm = document.getElementById('whatsNewSeenForm');

    if (!modal) {
        return;
    }

    let seenMarked = false;

    function getCsrfFormData() {
        if (!seenForm) return null;
        return new FormData(seenForm);
    }

    function markWhatsNewSeen() {
        if (seenMarked) {
            return;
        }

        const formData = getCsrfFormData();
        if (!formData) {
            return;
        }

        seenMarked = true;

        fetch('/whats-new/mark-seen', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).catch(() => {
            seenMarked = false;
        });
    }

    function openWhatsNewModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.dataset.prevOverflow = document.body.style.overflow || '';
        document.body.style.overflow = 'hidden';
    }

    function closeWhatsNewModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = document.body.dataset.prevOverflow || '';
        markWhatsNewSeen();
    }

    if (openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openWhatsNewModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeWhatsNewModal();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeWhatsNewModal);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeWhatsNewModal();
        }
    });

    <?php if ($shouldAutoOpenWhatsNew): ?>
    openWhatsNewModal();
    <?php endif; ?>
});
</script>
</body>
</html>
