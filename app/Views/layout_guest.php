<!doctype html>
<html lang="cs" class="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'App') ?></title>
  <link rel="manifest" href="/uploads/site.webmanifest">
  <meta name="theme-color" content="#1E3A5F">
  <link rel="icon" type="image/svg+xml" href="/uploads/podnikappka_icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/uploads/podnikappka_icon_32.png">
  <link rel="icon" type="image/png" sizes="192x192" href="/uploads_podnikappka_icon_192.png">
  <link rel="apple-touch-icon" href="/uploads/podnikappka_icon_180.png">
  <script src="https://cdn.tailwindcss.com"></script>
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
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6" style="background: var(--bg); color: var(--text);">
  <?php $guestWidth = $guestWidth ?? 'max-w-lg'; ?>
  <div class="w-full <?= $guestWidth ?> rounded-2xl shadow p-8" style="background: var(--card); border: 1px solid var(--border);">

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash-message mb-4 flex items-start justify-between gap-4 rounded-lg px-4 py-3"
           style="background: color-mix(in srgb, var(--primary) 12%, white); color: var(--text); border: 1px solid var(--border);">
        <div><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
        <button type="button" class="flash-close text-xl leading-none">&times;</button>
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash-message mb-4 flex items-start justify-between gap-4 rounded-lg px-4 py-3"
           style="background: color-mix(in srgb, #16a34a 14%, white); color: var(--text); border: 1px solid var(--border);">
        <div><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
        <button type="button" class="flash-close text-xl leading-none">&times;</button>
      </div>
    <?php endif; ?>

    <?php require __DIR__ . "/{$view}.php"; ?>
  </div>

  <script>
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.flash-close');
    if (!btn) return;
    const msg = btn.closest('.flash-message');
    if (msg) msg.remove();
  });
  </script>
</body>
</html>
