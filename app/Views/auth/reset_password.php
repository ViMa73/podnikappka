<h1 class="text-3xl font-bold mb-2" style="color: var(--text);">Nastavit nové heslo</h1>

<?php if (!$valid): ?>
  <p class="text-sm mb-6" style="color: var(--muted);">
    Odkaz je neplatný nebo vypršel.
  </p>
  <a href="/forgot-password" class="btn-primary inline-block px-5 py-3 rounded-lg font-semibold">
    Zkusit znovu
  </a>
<?php else: ?>
  <p class="text-sm mb-6" style="color: var(--muted);">
    Zadej nové heslo (min. 8 znaků).
  </p>

  <form method="POST" action="/reset-password/<?= htmlspecialchars($token) ?>" class="space-y-5">
    <?= \Core\CSRF::field() ?>

    <div>
      <label class="block text-sm font-medium mb-1" style="color: var(--text);">Nové heslo</label>
      <input type="password" name="password" required
             class="w-full px-4 py-3 border rounded-lg"
             style="background: var(--card); border-color: var(--border); color: var(--text);">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" style="color: var(--text);">Nové heslo znovu</label>
      <input type="password" name="password_confirm" required
             class="w-full px-4 py-3 border rounded-lg"
             style="background: var(--card); border-color: var(--border); color: var(--text);">
    </div>

    <button type="submit" class="btn-primary w-full py-3 rounded-lg font-semibold">
      Uložit heslo
    </button>
  </form>
<?php endif; ?>