<!DOCTYPE html>
<html lang="cs" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace firmy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      html.light {
        --bg: #f3f4f6;
        --card: #ffffff;
        --text: #111827;
        --muted: #6b7280;
        --border: #e5e7eb;
        --primary: #2563eb;
        --primary-hover: #1d4ed8;
        --primary-text: #ffffff;
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
<body class="min-h-screen flex items-center justify-center py-10 px-4" style="background: var(--bg); color: var(--text);">

<div class="w-full max-w-2xl rounded-2xl shadow-xl p-10" style="background: var(--card); border: 1px solid var(--border);">

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold" style="color: var(--text);">Registrace firmy</h1>
        <p class="mt-2" style="color: var(--muted);">Vytvořte firmu a účet vlastníka</p>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash-message mb-4 flex items-start justify-between gap-4 rounded-lg px-4 py-3"
           style="background: color-mix(in srgb, var(--primary) 12%, white); color: var(--text); border: 1px solid var(--border);">
        <div><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <button type="button" class="flash-close text-xl leading-none">&times;</button>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash-message mb-4 flex items-start justify-between gap-4 rounded-lg px-4 py-3"
           style="background: color-mix(in srgb, #16a34a 14%, white); color: var(--text); border: 1px solid var(--border);">
        <div><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <button type="button" class="flash-close text-xl leading-none">&times;</button>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <form method="POST" action="/register-company" class="space-y-6">
        <?= \Core\CSRF::field() ?>

        <input type="hidden" name="terms_version" value="1.0">
        <input type="hidden" name="gdpr_version" value="1.0">
        <input type="hidden" name="dpa_version" value="1.0">

        <div class="border-b pb-6" style="border-color: var(--border);">
            <h2 class="font-semibold mb-4" style="color: var(--text);">Údaje o firmě</h2>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="text" name="company_name" placeholder="Název firmy" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none md:col-span-2">

                <input type="text" name="ico" placeholder="IČO" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <input type="text" name="dic" placeholder="DIČ (nepovinné)"
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <input type="text" name="street" placeholder="Ulice a č.p." required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none md:col-span-2">

                <input type="text" name="city" placeholder="Město" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <input type="text" name="zip" placeholder="PSČ" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </div>

        <div class="border-b pb-6" style="border-color: var(--border);">
            <h2 class="font-semibold mb-4" style="color: var(--text);">Vlastník účtu</h2>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="text" name="first_name" placeholder="Jméno" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <input type="text" name="last_name" placeholder="Příjmení" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <input type="email" name="email" placeholder="Email" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none md:col-span-2">

                <input type="password" name="password" placeholder="Heslo" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <input type="password" name="password_confirm" placeholder="Ověření hesla" required
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </div>

        <div class="space-y-4">
            <h2 class="font-semibold" style="color: var(--text);">Souhlasy a potvrzení</h2>

            <div class="rounded-xl p-4 space-y-4"
                 style="background: color-mix(in srgb, var(--primary) 5%, white); border: 1px solid var(--border);">

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="accept_terms" value="1" required
                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm leading-6" style="color: var(--text);">
                        Souhlasím s
                        <a href="/terms" target="_blank" class="underline font-medium">obchodními podmínkami</a>
                        služby PodnikAppka.
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="accept_gdpr" value="1" required
                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm leading-6" style="color: var(--text);">
                        Potvrzuji, že jsem se seznámil(a) se
                        <a href="/gdpr" target="_blank" class="underline font-medium">zásadami ochrany osobních údajů</a>.
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="accept_dpa" value="1" required
                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm leading-6" style="color: var(--text);">
                        Souhlasím se
                        <a href="/dpa" target="_blank" class="underline font-medium">zpracovatelskou smlouvou (DPA)</a>,
                        která upravuje zpracování osobních údajů mezi firmou a provozovatelem služby.
                    </span>
                </label>
            </div>

            <p class="text-xs leading-5" style="color: var(--muted);">
                Odesláním formuláře vytvoříte firmu a účet vlastníka. Verze přijatých dokumentů budou uloženy
                spolu s datem a časem souhlasu.
            </p>
        </div>

        <button type="submit" class="w-full btn-primary py-3 rounded-lg font-semibold">
            Vytvořit firmu
        </button>
    </form>
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
