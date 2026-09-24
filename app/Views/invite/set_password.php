<h1 class="text-2xl font-bold mb-2" style="color: var(--text);">Nastavit heslo</h1>
<p class="text-sm mb-6 leading-6" style="color: var(--muted);">
  Dokonči aktivaci účtu nastavením hesla.
</p>

<form method="POST" class="space-y-5">
  <?= \Core\CSRF::field() ?>

  <div class="space-y-2">
    <label class="text-sm font-medium" style="color: var(--text);">Heslo</label>
    <input
      type="password"
      name="password"
      required
      class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      style="border-color: var(--border); background: var(--card); color: var(--text);"
    >
    <p class="text-xs" style="color: var(--muted);">
      Heslo musí mít alespoň 8 znaků.
    </p>
  </div>

  <div class="space-y-2">
    <label class="text-sm font-medium" style="color: var(--text);">Heslo znovu</label>
    <input
      type="password"
      name="password_confirm"
      required
      class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      style="border-color: var(--border); background: var(--card); color: var(--text);"
    >
  </div>

  <button class="btn-primary px-5 py-3 rounded-lg font-semibold w-full">
    Uložit heslo a aktivovat účet
  </button>
</form>
