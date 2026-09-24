<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold" style="color: var(--text);">Firmy</h1>
    <p class="text-sm mt-1" style="color: var(--muted);">
      Přehled všech firem, jejich licencí a uživatelů.
    </p>
  </div>

  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <?php if (empty($companies)): ?>
      <div class="text-sm italic p-4 rounded-xl"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
        Nejsou evidované žádné firmy.
      </div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($companies as $company): ?>
          <?php
            $companyId = (int)$company['id'];
            $plan = trim((string)($company['subscription_plan'] ?? 'free'));
            $planLabel = $plan === 'paid' ? 'Plná verze' : 'Free';

            $expires = 'Bez omezení';
            if (!empty($company['subscription_ended_at'])) {
                $ts = strtotime((string)$company['subscription_ended_at']);
                $expires = $ts ? date('j.n.Y H:i', $ts) : (string)$company['subscription_ended_at'];
            }

            $users = $usersByCompany[$companyId] ?? [];
            $collapseId = 'company-users-' . $companyId;
            $licenseModalId = 'license-modal-' . $companyId;
            $blockModalId = 'block-modal-' . $companyId;

            $isBlocked = (($company['company_status'] ?? 'active') === 'blocked');

            $defaultExpiry = new \DateTimeImmutable('now +1 year');
            if ($plan === 'paid' && !empty($company['subscription_ended_at'])) {
                $currentEndTs = strtotime((string)$company['subscription_ended_at']);
                if ($currentEndTs && $currentEndTs > time()) {
                    $defaultExpiry = (new \DateTimeImmutable((string)$company['subscription_ended_at']))->modify('+1 year');
                }
            }

            $defaultExpiryValue = $defaultExpiry->format('Y-m-d\TH:i');
          ?>

          <div class="rounded-2xl overflow-hidden"
               style="background: var(--bg); border: 1px solid var(--border);">
            <div class="p-5">
              <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                <div class="min-w-0">
                  <div class="flex items-center gap-3 flex-wrap">
                    <div class="text-lg font-semibold" style="color: var(--text);">
                      <?= htmlspecialchars((string)$company['name']) ?>
                    </div>

                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                          style="background: <?= $isBlocked ? 'rgba(239,68,68,0.12)' : 'var(--card)' ?>; border: 1px solid <?= $isBlocked ? 'rgba(239,68,68,0.35)' : 'var(--border)' ?>; color: <?= $isBlocked ? '#b91c1c' : 'var(--text)' ?>;">
                      <?= $isBlocked ? 'Blokováno' : 'Aktivní' ?>
                    </span>
                  </div>

                  <div class="text-sm mt-2" style="color: var(--muted);">
                    IČO: <?= htmlspecialchars((string)($company['ico'] ?? '—')) ?>
                    <?php if (!empty($company['city'])): ?>
                      • <?= htmlspecialchars((string)$company['city']) ?>
                    <?php endif; ?>
                    <?php if (!empty($company['created_at'])): ?>
                      • založeno <?= htmlspecialchars((string)$company['created_at']) ?>
                    <?php endif; ?>
                  </div>

                  <?php if ($isBlocked && !empty($company['blocked_reason'])): ?>
                    <div class="text-sm mt-2" style="color: #b91c1c;">
                      Důvod blokace: <?= nl2br(htmlspecialchars((string)$company['blocked_reason'])) ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 xl:min-w-[360px]">
                  <div class="rounded-xl p-3"
                       style="background: var(--card); border: 1px solid var(--border);">
                    <div class="text-xs" style="color: var(--muted);">Licence</div>
                    <div class="font-semibold mt-1" style="color: var(--text);">
                      <?= htmlspecialchars($planLabel) ?>
                    </div>
                  </div>

                  <div class="rounded-xl p-3"
                       style="background: var(--card); border: 1px solid var(--border);">
                    <div class="text-xs" style="color: var(--muted);">Expirace</div>
                    <div class="font-semibold mt-1" style="color: var(--text);">
                      <?= htmlspecialchars($expires) ?>
                    </div>
                  </div>

                  <div class="rounded-xl p-3"
                       style="background: var(--card); border: 1px solid var(--border);">
                    <div class="text-xs" style="color: var(--muted);">Uživatelů</div>
                    <div class="font-semibold mt-1" style="color: var(--text);">
                      <?= (int)$company['users_count'] ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap gap-3">
                <button type="button"
                        class="js-company-users-toggle px-4 py-2 rounded-lg font-semibold"
                        data-target="<?= htmlspecialchars($collapseId) ?>"
                        style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  Zobrazit uživatele
                </button>

                <button type="button"
                        class="js-open-modal px-4 py-2 rounded-lg font-semibold"
                        data-target="<?= htmlspecialchars($licenseModalId) ?>"
                        style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  Spravovat licenci
                </button>

                <?php if ($isBlocked): ?>
                  <form method="POST" action="/admin/companies/unblock"
                        onsubmit="return confirm('Opravdu chceš firmu odblokovat?');">
                    <?= \Core\CSRF::field() ?>
                    <input type="hidden" name="company_id" value="<?= $companyId ?>">
                    <button class="px-4 py-2 rounded-lg font-semibold"
                            style="background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.35); color: #15803d;">
                      Odblokovat firmu
                    </button>
                  </form>
                <?php else: ?>
                  <button type="button"
                          class="js-open-modal px-4 py-2 rounded-lg font-semibold"
                          data-target="<?= htmlspecialchars($blockModalId) ?>"
                          style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); color: #b91c1c;">
                    Zablokovat firmu
                  </button>
                <?php endif; ?>
              </div>
            </div>

            <div id="<?= htmlspecialchars($collapseId) ?>"
                 class="hidden px-5 pb-5">
              <?php if (empty($users)): ?>
                <div class="rounded-xl p-4"
                     style="background: var(--card); border: 1px solid var(--border); color: var(--muted);">
                  Firma zatím nemá žádné uživatele.
                </div>
              <?php else: ?>
                <div class="overflow-auto">
                  <table class="w-full text-sm">
                    <thead>
                      <tr style="color: var(--muted); border-bottom: 1px solid var(--border);">
                        <th class="text-left py-3 pr-4">Jméno</th>
                        <th class="text-left py-3 pr-4">Email</th>
                        <th class="text-left py-3 pr-4">Role</th>
                        <th class="text-left py-3">Stav</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($users as $user): ?>
                        <?php
                          $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                          $roleLabels = [
                              'owner' => 'Owner',
                              'manager' => 'Manager',
                              'worker' => 'Worker',
                          ];
                        ?>
                        <tr style="border-bottom: 1px solid var(--border); color: var(--text);">
                          <td class="py-3 pr-4">
                            <?= htmlspecialchars($userName !== '' ? $userName : '—') ?>
                          </td>
                          <td class="py-3 pr-4">
                            <?= htmlspecialchars((string)($user['email'] ?? '')) ?>
                          </td>
                          <td class="py-3 pr-4">
                            <?= htmlspecialchars($roleLabels[$user['role'] ?? ''] ?? (string)($user['role'] ?? '—')) ?>
                          </td>
                          <td class="py-3">
                            <?= htmlspecialchars((string)($user['status'] ?? '—')) ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- MODAL LICENCE -->
          <div id="<?= htmlspecialchars($licenseModalId) ?>"
               class="fixed inset-0 z-50 hidden items-center justify-center"
               aria-hidden="true">
            <div class="absolute inset-0"
                 style="background: rgba(0,0,0,.45);"
                 data-close-modal="1"></div>

            <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
                 style="background: var(--card); border: 1px solid var(--border);">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-lg font-semibold" style="color: var(--text);">Správa licence</h3>
                  <p class="text-sm mt-1" style="color: var(--muted);">
                    Firma: <?= htmlspecialchars((string)$company['name']) ?>
                  </p>
                </div>

                <button type="button"
                        class="px-3 py-2 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                        data-close-modal="1">✕</button>
              </div>

              <form method="POST" action="/admin/companies/license" class="mt-5 space-y-4">
                <?= \Core\CSRF::field() ?>
                <input type="hidden" name="company_id" value="<?= $companyId ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="rounded-xl p-4"
                       style="background: var(--bg); border: 1px solid var(--border);">
                    <div class="text-sm" style="color: var(--muted);">Aktuální plán</div>
                    <div class="font-semibold mt-1" style="color: var(--text);">
                      <?= htmlspecialchars($planLabel) ?>
                    </div>
                  </div>

                  <div class="rounded-xl p-4"
                       style="background: var(--bg); border: 1px solid var(--border);">
                    <div class="text-sm" style="color: var(--muted);">Aktuální expirace</div>
                    <div class="font-semibold mt-1" style="color: var(--text);">
                      <?= htmlspecialchars($expires) ?>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="text-sm" style="color: var(--text);">Nový plán</label>
                  <select name="plan"
                          class="mt-1 w-full px-4 py-3 rounded-lg js-license-plan-select"
                          style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                    <option value="paid" <?= $plan === 'paid' ? 'selected' : '' ?>>Plná verze</option>
                    <option value="free" <?= $plan === 'free' ? 'selected' : '' ?>>Free</option>
                  </select>
                </div>

                <div class="js-license-expiry-wrap">
                  <label class="text-sm" style="color: var(--text);">Datum expirace</label>
                  <input type="datetime-local"
                         name="ended_at"
                         value="<?= htmlspecialchars($defaultExpiryValue) ?>"
                         class="mt-1 w-full px-4 py-3 rounded-lg"
                         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <div class="text-xs mt-2" style="color: var(--muted);">
                    Předvyplněno na 1 rok od dneška nebo od konce aktuální licence.
                  </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                  <button type="button"
                          class="px-5 py-3 rounded-lg font-semibold"
                          style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                          data-close-modal="1">
                    Zrušit
                  </button>

                  <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
                    Uložit licenci
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- MODAL BLOKACE -->
          <div id="<?= htmlspecialchars($blockModalId) ?>"
               class="fixed inset-0 z-50 hidden items-center justify-center"
               aria-hidden="true">
            <div class="absolute inset-0"
                 style="background: rgba(0,0,0,.45);"
                 data-close-modal="1"></div>

            <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
                 style="background: var(--card); border: 1px solid var(--border);">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-lg font-semibold" style="color: var(--text);">Zablokovat firmu</h3>
                  <p class="text-sm mt-1" style="color: var(--muted);">
                    Firma: <?= htmlspecialchars((string)$company['name']) ?>
                  </p>
                </div>

                <button type="button"
                        class="px-3 py-2 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                        data-close-modal="1">✕</button>
              </div>

              <form method="POST" action="/admin/companies/block" class="mt-5 space-y-4">
                <?= \Core\CSRF::field() ?>
                <input type="hidden" name="company_id" value="<?= $companyId ?>">

                <div>
                  <label class="text-sm" style="color: var(--text);">Důvod blokace (volitelné)</label>
                  <textarea name="blocked_reason"
                            rows="4"
                            class="mt-1 w-full px-4 py-3 rounded-lg"
                            style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                  <button type="button"
                          class="px-5 py-3 rounded-lg font-semibold"
                          style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                          data-close-modal="1">
                    Zrušit
                  </button>

                  <button class="px-5 py-3 rounded-lg font-semibold"
                          style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;">
                    Zablokovat firmu
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  document.addEventListener('click', function (e) {
    const usersBtn = e.target.closest('.js-company-users-toggle');
    if (usersBtn) {
      const targetId = usersBtn.getAttribute('data-target');
      const target = document.getElementById(targetId);
      if (target) {
        const isHidden = target.classList.contains('hidden');
        target.classList.toggle('hidden', !isHidden);
        usersBtn.textContent = isHidden ? 'Skrýt uživatele' : 'Zobrazit uživatele';
      }
      return;
    }

    const openBtn = e.target.closest('.js-open-modal');
    if (openBtn) {
      const targetId = openBtn.getAttribute('data-target');
      const modal = document.getElementById(targetId);
      if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
      }
      return;
    }

    if (e.target && e.target.getAttribute('data-close-modal') === '1') {
      const modal = e.target.closest('.fixed.inset-0.z-50');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
      }
    }
  });

  document.addEventListener('change', function (e) {
    const select = e.target.closest('.js-license-plan-select');
    if (!select) return;

    const form = select.closest('form');
    if (!form) return;

    const wrap = form.querySelector('.js-license-expiry-wrap');
    if (!wrap) return;

    wrap.style.display = select.value === 'paid' ? '' : 'none';
  });

  document.querySelectorAll('.js-license-plan-select').forEach((select) => {
    const form = select.closest('form');
    if (!form) return;

    const wrap = form.querySelector('.js-license-expiry-wrap');
    if (!wrap) return;

    wrap.style.display = select.value === 'paid' ? '' : 'none';
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;

    document.querySelectorAll('.fixed.inset-0.z-50').forEach((modal) => {
      if (!modal.classList.contains('hidden')) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
      }
    });
  });
</script>
