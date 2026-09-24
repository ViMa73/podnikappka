<?php
// $users je z controlleru
function roleBadge($role) {
  $label = ['owner'=>'Majitel','manager'=>'Manažer','worker'=>'Zaměstnanec'][$role] ?? $role;
  $style = "background: color-mix(in srgb, var(--primary) 12%, transparent); border: 1px solid var(--border); color: var(--text);";
  if ($role === 'owner') $style = "background: color-mix(in srgb, #f59e0b 18%, transparent); border: 1px solid var(--border); color: var(--text);";
  if ($role === 'worker') $style = "background: color-mix(in srgb, var(--muted) 18%, transparent); border: 1px solid var(--border); color: var(--text);";
  return "<span class='px-2 py-1 rounded-full text-xs font-semibold' style=\"$style\">{$label}</span>";
}
function statusBadge($status) {
  if ($status === 'active') {
    return "<span class='px-2 py-1 rounded-full text-xs font-semibold' style=\"background: color-mix(in srgb, var(--success) 18%, transparent); border: 1px solid var(--border); color: var(--text);\">Aktivní</span>";
  }
  return "<span class='px-2 py-1 rounded-full text-xs font-semibold' style=\"background: color-mix(in srgb, var(--error) 18%, transparent); border: 1px solid var(--border); color: var(--text);\">Pozvánka odeslána</span>";
}
?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">

  <!-- KARTA 1: seznam -->
  <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Uživatelé</h2>
    <p class="text-sm mb-6" style="color: var(--muted);">Seznam všech uživatelů ve firmě.</p>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr style="color: var(--muted);">
            <th class="text-left py-2 pr-3 font-semibold">Jméno</th>
            <th class="text-left py-2 pr-3 font-semibold">Role</th>
            <th class="text-left py-2 pr-3 font-semibold">Stav</th>
            <th class="text-right py-2 font-semibold">Akce</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr class="border-t" style="border-color: var(--border);">
              <td class="py-3 pr-3" style="color: var(--text);">
                <div class="font-semibold"><?= htmlspecialchars(trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? ''))) ?></div>
                <div class="text-xs" style="color: var(--muted);"><?= htmlspecialchars($u['email']) ?></div>
              </td>
              <td class="py-3 pr-3"><?= roleBadge($u['role']) ?></td>
              <td class="py-3 pr-3"><?= statusBadge($u['status']) ?></td>
              <td class="py-3 text-right">
                <a href="/users/<?= (int)$u['id'] ?>"
                   class="px-3 py-2 rounded-lg text-sm font-semibold inline-block btn-secondary">
                  Detail
                </a>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($users)): ?>
            <tr>
              <td colspan="4" class="py-6 text-sm" style="color: var(--muted);">
                Zatím zde nejsou žádní uživatelé.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- KARTA 2: pozvánka -->
  <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Pozvat uživatele</h2>
    <p class="text-sm mb-6" style="color: var(--muted);">
      Přidej nového uživatele a pošli mu odkaz pro nastavení hesla.
    </p>

    <form method="POST" action="/users/invite" class="space-y-4">
      <?= \Core\CSRF::field() ?>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm" style="color: var(--text);">Jméno</label>
          <input name="first_name" required class="mt-1 w-full px-4 py-3 border rounded-lg" style="border-color: var(--border); background: var(--card); color: var(--text);">
        </div>
        <div>
          <label class="text-sm" style="color: var(--text);">Příjmení</label>
          <input name="last_name" required class="mt-1 w-full px-4 py-3 border rounded-lg" style="border-color: var(--border); background: var(--card); color: var(--text);">
        </div>
      </div>

      <div>
        <label class="text-sm" style="color: var(--text);">Email</label>
        <input name="email" type="email" required class="mt-1 w-full px-4 py-3 border rounded-lg" style="border-color: var(--border); background: var(--card); color: var(--text);">
      </div>

      <div>
        <label class="text-sm" style="color: var(--text);">Role</label>
        <select name="role" class="mt-1 w-full px-4 py-3 border rounded-lg" style="border-color: var(--border); background: var(--card); color: var(--text);">
          <option value="worker">Zaměstnanec</option>
          <option value="manager">Manažer</option>
          <?php if (\Core\Auth::role() === 'owner'): ?>
            <option value="owner">Majitel</option>
          <?php endif; ?>
        </select>
      </div>

      <button class="btn-primary px-5 py-3 rounded-lg font-semibold w-full md:w-auto">
        Odeslat pozvánku
      </button>
    </form>
  </div>

</div>
