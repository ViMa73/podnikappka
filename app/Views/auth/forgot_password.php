<h1 class="text-3xl font-bold mb-2" style="color: var(--text);">Zapomenuté heslo</h1>
<p class="text-sm mb-6" style="color: var(--muted);">
  Zadej email a pošleme ti odkaz pro nastavení nového hesla.
</p>

<form method="POST" action="/forgot-password" class="space-y-5">
  <?= \Core\CSRF::field() ?>

  <div>
    <label class="block text-sm font-medium mb-1" style="color: var(--text);">Email</label>
    <input type="email" name="email" required
           class="w-full px-4 py-3 border rounded-lg"
           style="background: var(--card); border-color: var(--border); color: var(--text);">
  </div>

  <button type="submit" class="btn-primary w-full py-3 rounded-lg font-semibold">
    Odeslat odkaz
  </button>
</form>

<div class="mt-6 text-center text-sm" style="color: var(--muted);">
  <a href="/" class="hover:underline" style="color: var(--primary);">Zpět na přihlášení</a>
</div>