<?php
  $companyPayrollAllowManager = (int)($company['payroll_allow_manager'] ?? ($_SESSION['company_payroll_allow_manager'] ?? 0)) === 1;
  $canManage = \Core\Auth::role() === 'owner'
      || (\Core\Auth::role() === 'manager' && $companyPayrollAllowManager);

  $statusLabel = ($period['status'] ?? '') === 'approved' ? 'Schváleno' : 'Rozpracováno';
?>

<div class="space-y-6">
  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold" style="color: var(--text);">
          Výplata <?= sprintf('%02d/%04d', (int)$period['month'], (int)$period['year']) ?>
        </h1>
        <p class="text-sm mt-1" style="color: var(--muted);">
          Stav: <?= $statusLabel ?>
          • Docházka: <?= (int)($period['attendance_locked'] ?? 0) === 1 ? 'uzamčena' : 'neuzamčena' ?>
        </p>
      </div>

      <?php if ($canManage): ?>
        <div class="flex gap-2">
          <?php if (($period['status'] ?? '') === 'draft'): ?>
            <form method="POST" action="/payrolls/<?= (int)$period['id'] ?>/approve">
              <?= \Core\CSRF::field() ?>
              <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
                Schválit výplatu
              </button>
            </form>
          <?php else: ?>
            <form method="POST" action="/payrolls/<?= (int)$period['id'] ?>/reopen">
              <?= \Core\CSRF::field() ?>
              <button class="px-5 py-3 rounded-lg font-semibold"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                Znovu otevřít
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <div class="text-sm italic" style="color: var(--muted);">
        Pro tuto výplatu nejsou dostupné žádné položky.
      </div>
    </div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($items as $item): ?>
        <?php
          $hoursShortfall = (float)$item['credited_hours'] < (float)$item['target_hours'];
        ?>

        <div class="rounded-2xl shadow p-6"
             style="background: var(--card); border: 1px solid var(--border);">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div>
              <h2 class="text-lg font-semibold" style="color: var(--text);">
                <?= htmlspecialchars($item['user_name_snapshot']) ?>
              </h2>
              <p class="text-sm mt-1" style="color: var(--muted);">
                Režim mzdy: <?= htmlspecialchars($item['payroll_salary_mode_snapshot']) ?>
              </p>
            </div>

            <div class="text-right">
              <div class="text-xl font-semibold" style="color: var(--text);">
                <?= number_format((float)$item['gross_total_amount'], 2, ',', ' ') ?> Kč
              </div>
              <div class="text-sm" style="color: var(--muted);">
                Hrubá mzda pro účetní
              </div>
            </div>
          </div>

          <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Základní mzda</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['base_salary_amount'], 2, ',', ' ') ?> Kč
              </div>
            </div>

            <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Firemní prémie</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['company_bonus_amount'], 2, ',', ' ') ?> Kč
              </div>
            </div>

            <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Příplatek víkend</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['weekend_bonus_amount'], 2, ',', ' ') ?> Kč
              </div>
            </div>

            <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Příplatek svátek</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['holiday_bonus_amount'], 2, ',', ' ') ?> Kč
              </div>
            </div>
          </div>

          <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Dotační hodiny</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['target_hours'], 2, ',', ' ') ?> hod.
              </div>
            </div>

            <div class="rounded-xl p-4"
                 style="
                   background: <?= $hoursShortfall ? 'var(--notification-error-bg, #fee2e2)' : 'var(--bg)' ?>;
                   border: 1px solid <?= $hoursShortfall ? 'var(--notification-error-border, #fecaca)' : 'var(--border)' ?>;
                 ">
              <div class="text-sm" style="color: <?= $hoursShortfall ? 'var(--notification-error-text, #991b1b)' : 'var(--muted)' ?>;">
                Uznané hodiny
              </div>
              <div class="font-semibold mt-1" style="color: <?= $hoursShortfall ? 'var(--notification-error-text, #991b1b)' : 'var(--text)' ?>;">
                <?= number_format((float)$item['credited_hours'], 2, ',', ' ') ?> hod.
              </div>
              <?php if ($hoursShortfall): ?>
                <div class="text-xs mt-2" style="color: var(--notification-error-text, #991b1b);">
                  Nesplněna měsíční dotace hodin.
                </div>
              <?php endif; ?>
            </div>

            <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Víkendy / svátky</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['weekend_hours'], 2, ',', ' ') ?> / <?= number_format((float)$item['holiday_hours'], 2, ',', ' ') ?> hod.
              </div>
            </div>

            <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Dovolená / OČR / PN</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['vacation_hours'], 2, ',', ' ') ?> /
                <?= number_format((float)$item['ocr_hours'], 2, ',', ' ') ?> /
                <?= number_format((float)$item['sick_hours'], 2, ',', ' ') ?> hod.
              </div>
            </div>
          </div>

          <?php if ($canManage && ($period['status'] ?? '') === 'draft'): ?>
            <form method="POST" action="/payrolls/<?= (int)$period['id'] ?>/update-item" class="space-y-4 mb-3">
              <?= \Core\CSRF::field() ?>
              <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm" style="color: var(--text);">Osobní ohodnocení</label>
                  <input type="text"
                         name="personal_bonus_amount"
                         value="<?= htmlspecialchars((string)$item['personal_bonus_amount']) ?>"
                         class="mt-1 w-full px-4 py-3 rounded-lg"
                         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                </div>

                <div>
                  <label class="text-sm" style="color: var(--text);">Poznámka</label>
                  <input type="text"
                         name="note"
                         value="<?= htmlspecialchars((string)($item['note'] ?? '')) ?>"
                         class="mt-1 w-full px-4 py-3 rounded-lg"
                         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                </div>
              </div>

              <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
                Uložit úpravy zaměstnance
              </button>
            </form>

            <div class="flex flex-wrap gap-2 mb-5">
              <form method="POST" action="/payrolls/<?= (int)$period['id'] ?>/allow-attendance-edit">
                <?= \Core\CSRF::field() ?>
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                <button class="px-4 py-2 rounded-lg font-semibold"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  Otevřít docházku k úpravě
                </button>
              </form>

              <form method="POST" action="/payrolls/<?= (int)$period['id'] ?>/recalculate-item">
                <?= \Core\CSRF::field() ?>
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                <button class="px-4 py-2 rounded-lg font-semibold"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  Přepočítat z docházky
                </button>
              </form>
            </div>
          <?php elseif ((float)$item['personal_bonus_amount'] > 0 || !empty($item['note'])): ?>
            <div class="mb-5 rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
              <div class="text-sm" style="color: var(--muted);">Osobní ohodnocení</div>
              <div class="font-semibold mt-1" style="color: var(--text);">
                <?= number_format((float)$item['personal_bonus_amount'], 2, ',', ' ') ?> Kč
              </div>

              <?php if (!empty($item['note'])): ?>
                <div class="text-sm mt-3" style="color: var(--muted);">
                  Poznámka: <?= htmlspecialchars((string)$item['note']) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="rounded-xl p-4" style="background: var(--bg); border: 1px solid var(--border);">
            <div class="font-semibold mb-3" style="color: var(--text);">Rozpis dní</div>

            <?php $days = $daysByItem[(int)$item['id']] ?? []; ?>
            <?php if (empty($days)): ?>
              <div class="text-sm italic" style="color: var(--muted);">Bez rozpadu dní.</div>
            <?php else: ?>
              <div class="space-y-2">
                <?php foreach ($days as $day): ?>
                  <div class="flex items-center justify-between gap-4 text-sm" style="color: var(--text);">
                    <div>
                      <?= htmlspecialchars((string)$day['day_date']) ?>
                      <span style="color: var(--muted);">• <?= htmlspecialchars((string)$day['label']) ?></span>
                    </div>
                    <div><?= number_format((float)$day['hours'], 2, ',', ' ') ?> hod.</div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
