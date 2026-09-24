<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold" style="color: var(--text);">Správa adminů</h1>
    <p class="text-sm mt-1" style="color: var(--muted);">
      Přehled superadminů a možnost přidat nového admina.
    </p>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Aktuální admini</h2>

      <?php if (empty($admins)): ?>
        <div class="text-sm italic p-4 rounded-xl"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Zatím nejsou evidováni žádní admini.
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($admins as $admin): ?>
            <?php
              $name = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
              $name = $name !== '' ? $name : ($admin['email'] ?? '—');
            ?>
            <div class="rounded-xl p-4"
                 style="background: var(--bg); border: 1px solid var(--border);">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="font-semibold" style="color: var(--text);">
                    <?= htmlspecialchars($name) ?>
                  </div>
                  <div class="text-sm mt-1" style="color: var(--muted);">
                    <?= htmlspecialchars((string)($admin['email'] ?? '')) ?>
                  </div>
                  <div class="text-xs mt-2" style="color: var(--muted);">
                    <?= !empty($admin['company_name']) ? 'Původní firma: ' . htmlspecialchars((string)$admin['company_name']) : 'Samostatný admin účet' ?>
                  </div>
                </div>

                <?php if ((int)\Core\Auth::user() !== (int)$admin['id']): ?>
                  <form method="POST" action="/admin/admins/remove"
                        onsubmit="return confirm('Opravdu chceš odebrat admin práva tomuto uživateli?');">
                    <?= \Core\CSRF::field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>">
                    <button class="px-4 py-2 rounded-lg text-sm font-semibold"
                            style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;">
                      Odebrat
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="space-y-6">
      <div class="rounded-2xl shadow p-6"
           style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Povýšit existujícího uživatele</h2>

        <form method="POST" action="/admin/admins/promote-existing" class="space-y-4">
          <?= \Core\CSRF::field() ?>

          <div>
            <label class="text-sm" style="color: var(--text);">Vyber uživatele</label>
            <select name="user_id"
                    class="mt-1 w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              <?php foreach ($availableUsers as $user): ?>
                <?php
                  $labelName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                  $labelName = $labelName !== '' ? $labelName : ($user['email'] ?? '—');
                  $companyLabel = !empty($user['company_name']) ? ' — ' . $user['company_name'] : '';
                ?>
                <option value="<?= (int)$user['id'] ?>">
                  <?= htmlspecialchars($labelName . ' (' . ($user['email'] ?? '') . ')' . $companyLabel) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Přidat mezi adminy
          </button>
        </form>
      </div>

      <div class="rounded-2xl shadow p-6"
           style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Vytvořit nového admina</h2>

        <form method="POST" action="/admin/admins/create" class="space-y-4">
          <?= \Core\CSRF::field() ?>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm" style="color: var(--text);">Jméno</label>
              <input name="first_name"
                     class="mt-1 w-full px-4 py-3 rounded-lg"
                     style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            </div>

            <div>
              <label class="text-sm" style="color: var(--text);">Příjmení</label>
              <input name="last_name"
                     class="mt-1 w-full px-4 py-3 rounded-lg"
                     style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            </div>
          </div>

          <div>
            <label class="text-sm" style="color: var(--text);">Email</label>
            <input type="email"
                   name="email"
                   required
                   class="mt-1 w-full px-4 py-3 rounded-lg"
                   style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          </div>

          <div>
            <label class="text-sm" style="color: var(--text);">Heslo</label>
            <input type="password"
                   name="password"
                   required
                   class="mt-1 w-full px-4 py-3 rounded-lg"
                   style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          </div>

          <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Vytvořit admina
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
