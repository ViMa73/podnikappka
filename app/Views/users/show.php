<?php
// v show() jsme vytvořili $user, $canEditBasic, $canEditExtra
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full items-start">

  <!-- KARTA 1: Detail uživatele -->
  <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Detail uživatele</h2>
    <p class="text-sm mb-6" style="color: var(--muted);">Základní údaje a role.</p>

    <?php if ($canEditBasic): ?>
      <form method="POST" action="/users/<?= (int)$user['id'] ?>/update" class="space-y-4" style="display: inline;">
        <?= \Core\CSRF::field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm" style="color: var(--text);">Jméno</label>
            <input name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required
                   class="mt-1 w-full px-4 py-3 border rounded-lg"
                   style="background: var(--bg); border-color: var(--border); color: var(--text);">
          </div>
          <div>
            <label class="text-sm" style="color: var(--text);">Příjmení</label>
            <input name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required
                   class="mt-1 w-full px-4 py-3 border rounded-lg"
                   style="background: var(--bg); border-color: var(--border); color: var(--text);">
          </div>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Email</label>
          <input name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required
                 class="mt-1 w-full px-4 py-3 border rounded-lg"
                 style="background: var(--bg); border-color: var(--border); color: var(--text);">
          <div class="text-xs mt-1" style="color: var(--muted);">
            Email po změně znovu neověřujeme (zatím).
          </div>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Datum narození</label>
          <input name="birth_date"
                 type="date"
                 value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>"
                 class="mt-1 w-full px-4 py-3 border rounded-lg"
                 style="background: var(--bg); border-color: var(--border); color: var(--text);">
          <div class="text-xs mt-1" style="color: var(--muted);">
            Nepovinné pole.
          </div>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Telefon</label>
          <input name="phone"
                 value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                 class="mt-1 w-full px-4 py-3 border rounded-lg"
                 style="background: var(--bg); border-color: var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Role</label>
          <select name="role"
                  class="mt-1 w-full px-4 py-3 border rounded-lg"
                  style="background: var(--bg); border-color: var(--border); color: var(--text);"
                  <?= (\Core\Auth::role() === 'owner') ? '' : 'disabled' ?>>
            <option value="worker"  <?= $user['role']==='worker'?'selected':'' ?>>Pracovník</option>
            <option value="manager" <?= $user['role']==='manager'?'selected':'' ?>>Manažer</option>
            <option value="owner"   <?= $user['role']==='owner'?'selected':'' ?>>Majitel</option>
          </select>
          <?php if (\Core\Auth::role() !== 'owner'): ?>
            <div class="text-xs mt-1" style="color: var(--muted);">
              Role může měnit pouze owner.
            </div>
          <?php endif; ?>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Stav</label>
          <div class="mt-1 px-4 py-3 rounded-lg border"
               style="border-color: var(--border); color: var(--text); background: color-mix(in srgb, var(--bg) 60%, transparent);">
            <?= ($user['status'] === 'active') ? 'Aktivní' : 'Pozvánka odeslána' ?>
          </div>
        </div>

        <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
          Uložit změny
        </button>
      </form>
      <?php
        $currentRole = \Core\Auth::role();
        $targetRole = $user['role'];

        $canDelete =
            ($currentRole === 'owner') ||
            ($currentRole === 'manager' && $targetRole === 'worker');
        ?>

        <?php if ($canDelete): ?>
            <form method="POST"
                  action="/users/<?= (int)$user['id'] ?>/delete"
                  onsubmit="return confirm('Opravdu chcete tohoto uživatele smazat? Smazání nelze vzít zpět!');"
                  style="display: inline;">

                <?= \Core\CSRF::field() ?>

                <button
                    type="submit"
                    class="px-5 py-3 rounded-lg font-semibold text-white"
                    style="background:#dc2626">
                    Smazat uživatele
                </button>
            </form>
        <?php endif; ?>
    <?php else: ?>
      <div class="space-y-2 text-sm" style="color: var(--text);">
        <div><span style="color: var(--muted);">Jméno:</span> <strong><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></strong></div>
        <div><span style="color: var(--muted);">Email:</span> <?= htmlspecialchars($user['email']) ?></div>
        <div><span style="color: var(--muted);">Datum narození:</span><?= !empty($user['birth_date']) ? htmlspecialchars(date('j. n. Y', strtotime($user['birth_date']))) : '—' ?></div>
        <div><span style="color: var(--muted);">Role:</span>
          <?= ($user['role']==='owner'?'Vlastník':($user['role']==='manager'?'Manažer':'Pracovník')) ?>
        </div>
        <div><span style="color: var(--muted);">Stav:</span>
          <?= ($user['status'] === 'active') ? 'Aktivní' : 'Pozvánka odeslána' ?>
        </div>

        <div class="mt-4 text-sm" style="color: var(--muted);">
          Nemáš oprávnění upravovat tohoto uživatele.
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- KARTA 2: Rozšiřující údaje -->
  <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Rozšiřující údaje</h2>
    <p class="text-sm mb-6" style="color: var(--muted);">Mzda, úvazek, dovolená a nastavení výplat.</p>

    <?php if ($canEditExtra): ?>
      <form method="POST" action="/users/<?= (int)$user['id'] ?>/extra" class="space-y-6">
        <?= \Core\CSRF::field() ?>

        <?php
          $payrollEnabled = \Core\Feature::enabled('payrolls');

          $payrollActive = (int)($user['payroll_active'] ?? 1) === 1;

          $payrollSalaryMode = (string)($user['payroll_salary_mode'] ?? 'company_default');
          if (!in_array($payrollSalaryMode, ['company_default', 'fixed', 'hourly', 'mixed'], true)) {
              $payrollSalaryMode = 'company_default';
          }

          $payrollCompanyBonusMode = (string)($user['payroll_company_bonus_mode'] ?? 'company_default');
          if (!in_array($payrollCompanyBonusMode, ['company_default', 'none', 'custom'], true)) {
              $payrollCompanyBonusMode = 'company_default';
          }

          $payrollCustomBonusSourceType = (string)($user['payroll_custom_bonus_source_type'] ?? '');
          if (!in_array($payrollCustomBonusSourceType, ['indicator', 'group'], true)) {
              $payrollCustomBonusSourceType = '';
          }

          $payrollCustomBonusSourceId = (int)($user['payroll_custom_bonus_source_id'] ?? 0);

          $payrollCustomBonusCalcType = (string)($user['payroll_custom_bonus_calc_type'] ?? 'percent');
          if (!in_array($payrollCustomBonusCalcType, ['percent', 'fixed'], true)) {
              $payrollCustomBonusCalcType = 'percent';
          }

          $payrollWeekendBonusMode = (string)($user['payroll_weekend_bonus_mode'] ?? 'company_default');
          if (!in_array($payrollWeekendBonusMode, ['company_default', 'none', 'custom'], true)) {
              $payrollWeekendBonusMode = 'company_default';
          }

          $payrollWeekendBonusType = (string)($user['payroll_weekend_bonus_type'] ?? 'shift_amount');
          if (!in_array($payrollWeekendBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
              $payrollWeekendBonusType = 'shift_amount';
          }

          $payrollHolidayBonusMode = (string)($user['payroll_holiday_bonus_mode'] ?? 'company_default');
          if (!in_array($payrollHolidayBonusMode, ['company_default', 'none', 'custom'], true)) {
              $payrollHolidayBonusMode = 'company_default';
          }

          $payrollHolidayBonusType = (string)($user['payroll_holiday_bonus_type'] ?? 'shift_amount');
          if (!in_array($payrollHolidayBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
              $payrollHolidayBonusType = 'shift_amount';
          }

          $economicIndicatorTopLevelGroups = $economicIndicatorTopLevelGroups ?? [];
          $economicIndicatorTopLevelIndicators = $economicIndicatorTopLevelIndicators ?? [];
        ?>

        <?php if ($payrollEnabled): ?>
          <div class="space-y-4">
            <h3 class="text-lg font-semibold" style="color: var(--text);">Výplaty</h3>

            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox"
                     name="payroll_active"
                     value="1"
                     class="mt-1 h-4 w-4"
                     <?= $payrollActive ? 'checked' : '' ?>>

              <div>
                <div class="font-semibold" style="color: var(--text);">
                  Zahrnovat do výplat
                </div>
                <div class="text-sm" style="color: var(--muted);">
                  Pokud je vypnuto, uživatel nebude zahrnován do generování výplat.
                </div>
              </div>
            </label>

            <div>
              <label class="text-sm" style="color: var(--text);">Režim výpočtu mzdy</label>
              <select name="payroll_salary_mode"
                      id="payrollSalaryMode"
                      class="mt-1 w-full px-4 py-3 border rounded-lg"
                      style="background: var(--bg); border-color: var(--border); color: var(--text);">
                <option value="company_default" <?= $payrollSalaryMode === 'company_default' ? 'selected' : '' ?>>Podle firmy</option>
                <option value="fixed" <?= $payrollSalaryMode === 'fixed' ? 'selected' : '' ?>>Pevná mzda</option>
                <option value="hourly" <?= $payrollSalaryMode === 'hourly' ? 'selected' : '' ?>>Hodinová mzda</option>
                <option value="mixed" <?= $payrollSalaryMode === 'mixed' ? 'selected' : '' ?>>Kombinovaná mzda</option>
              </select>
              <div id="payrollFixedSalaryWrap">
                <label class="text-sm" style="color: var(--text);">Základní hrubá mzda</label>
                <input name="payroll_fixed_salary" inputmode="decimal"
                       value="<?= htmlspecialchars($user['payroll_fixed_salary'] ?? '') ?>"
                       placeholder="např. 35000"
                       class="mt-1 w-full px-4 py-3 border rounded-lg"
                       style="background: var(--bg); border-color: var(--border); color: var(--text);">
              </div>
            </div>

            <div id="payrollFixedSalaryWrap">
              <label class="text-sm" style="color: var(--text);">Pevná mzda</label>
              <input name="payroll_fixed_salary" inputmode="decimal"
                     value="<?= htmlspecialchars($user['payroll_fixed_salary'] ?? '') ?>"
                     placeholder="např. 35000"
                     class="mt-1 w-full px-4 py-3 border rounded-lg"
                     style="background: var(--bg); border-color: var(--border); color: var(--text);">
            </div>

            <div id="payrollHourlyRateWrap">
              <label class="text-sm" style="color: var(--text);">Hodinová sazba</label>
              <input name="payroll_hourly_rate" inputmode="decimal"
                     value="<?= htmlspecialchars($user['payroll_hourly_rate'] ?? '') ?>"
                     placeholder="např. 180"
                     class="mt-1 w-full px-4 py-3 border rounded-lg"
                     style="background: var(--bg); border-color: var(--border); color: var(--text);">
            </div>

            <div class="pt-4" style="border-top: 1px solid var(--border);">
              <h4 class="text-base font-semibold mb-3" style="color: var(--text);">Prémie</h4>

              <div>
                <label class="text-sm" style="color: var(--text);">Režim firemní prémie</label>
                <select name="payroll_company_bonus_mode"
                        id="payrollCompanyBonusMode"
                        class="mt-1 w-full px-4 py-3 border rounded-lg"
                        style="background: var(--bg); border-color: var(--border); color: var(--text);">
                  <option value="company_default" <?= $payrollCompanyBonusMode === 'company_default' ? 'selected' : '' ?>>Podle firmy</option>
                  <option value="none" <?= $payrollCompanyBonusMode === 'none' ? 'selected' : '' ?>>Bez prémie</option>
                  <option value="custom" <?= $payrollCompanyBonusMode === 'custom' ? 'selected' : '' ?>>Vlastní prémie</option>
                </select>
              </div>

              <div id="payrollCompanyBonusCustomWrap" class="mt-4 space-y-4">
                <div>
                  <label class="text-sm" style="color: var(--text);">Zdroj prémie</label>
                  <select name="payroll_custom_bonus_source_type"
                          class="mt-1 w-full px-4 py-3 border rounded-lg"
                          style="background: var(--bg); border-color: var(--border); color: var(--text);">
                    <option value="">Nevybráno</option>
                    <option value="group" <?= $payrollCustomBonusSourceType === 'group' ? 'selected' : '' ?>>Skupina ukazatelů</option>
                    <option value="indicator" <?= $payrollCustomBonusSourceType === 'indicator' ? 'selected' : '' ?>>Samostatný ukazatel</option>
                  </select>
                </div>

                <div>
                  <label class="text-sm" style="color: var(--text);">Ukazatel / skupina</label>
                  <select name="payroll_custom_bonus_source_id"
                          class="mt-1 w-full px-4 py-3 border rounded-lg"
                          style="background: var(--bg); border-color: var(--border); color: var(--text);">
                    <option value="">Nevybráno</option>

                    <?php if (!empty($economicIndicatorTopLevelGroups)): ?>
                      <optgroup label="Skupiny">
                        <?php foreach ($economicIndicatorTopLevelGroups as $item): ?>
                          <option value="<?= (int)$item['id'] ?>" <?= $payrollCustomBonusSourceId === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </optgroup>
                    <?php endif; ?>

                    <?php if (!empty($economicIndicatorTopLevelIndicators)): ?>
                      <optgroup label="Samostatné ukazatele">
                        <?php foreach ($economicIndicatorTopLevelIndicators as $item): ?>
                          <option value="<?= (int)$item['id'] ?>" <?= $payrollCustomBonusSourceId === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </optgroup>
                    <?php endif; ?>
                  </select>
                </div>

                <div>
                  <label class="text-sm" style="color: var(--text);">Typ výpočtu</label>
                  <select name="payroll_custom_bonus_calc_type"
                          class="mt-1 w-full px-4 py-3 border rounded-lg"
                          style="background: var(--bg); border-color: var(--border); color: var(--text);">
                    <option value="percent" <?= $payrollCustomBonusCalcType === 'percent' ? 'selected' : '' ?>>Procento</option>
                    <option value="fixed" <?= $payrollCustomBonusCalcType === 'fixed' ? 'selected' : '' ?>>Pevná částka</option>
                  </select>
                </div>

                <div>
                  <label class="text-sm" style="color: var(--text);">Hodnota</label>
                  <input name="payroll_custom_bonus_value" inputmode="decimal"
                         value="<?= htmlspecialchars($user['payroll_custom_bonus_value'] ?? '') ?>"
                         placeholder="např. 1 nebo 5000"
                         class="mt-1 w-full px-4 py-3 border rounded-lg"
                         style="background: var(--bg); border-color: var(--border); color: var(--text);">
                </div>
              </div>
            </div>

            <div class="pt-4" style="border-top: 1px solid var(--border);">
              <h4 class="text-base font-semibold mb-3" style="color: var(--text);">Příplatky</h4>

              <div class="grid md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
                  <div class="font-semibold mb-3" style="color: var(--text);">Víkendový příplatek</div>

                  <div class="space-y-3">
                    <div>
                      <label class="text-sm" style="color: var(--text);">Režim</label>
                      <select name="payroll_weekend_bonus_mode"
                              id="payrollWeekendBonusMode"
                              class="mt-1 w-full px-4 py-3 border rounded-lg"
                              style="background: var(--card); border-color: var(--border); color: var(--text);">
                        <option value="company_default" <?= $payrollWeekendBonusMode === 'company_default' ? 'selected' : '' ?>>Podle firmy</option>
                        <option value="none" <?= $payrollWeekendBonusMode === 'none' ? 'selected' : '' ?>>Bez příplatku</option>
                        <option value="custom" <?= $payrollWeekendBonusMode === 'custom' ? 'selected' : '' ?>>Vlastní</option>
                      </select>
                    </div>

                    <div id="payrollWeekendBonusCustomWrap" class="space-y-3">
                      <div>
                        <label class="text-sm" style="color: var(--text);">Typ</label>
                        <select name="payroll_weekend_bonus_type"
                                class="mt-1 w-full px-4 py-3 border rounded-lg"
                                style="background: var(--card); border-color: var(--border); color: var(--text);">
                          <option value="shift_amount" <?= $payrollWeekendBonusType === 'shift_amount' ? 'selected' : '' ?>>Pevná částka za směnu</option>
                          <option value="hour_amount" <?= $payrollWeekendBonusType === 'hour_amount' ? 'selected' : '' ?>>Pevná částka za hodinu</option>
                          <option value="hourly_rate_percent" <?= $payrollWeekendBonusType === 'hourly_rate_percent' ? 'selected' : '' ?>>Procento z hodinové sazby</option>
                        </select>
                      </div>

                      <div>
                        <label class="text-sm" style="color: var(--text);">Hodnota</label>
                        <input name="payroll_weekend_bonus_value" inputmode="decimal"
                               value="<?= htmlspecialchars($user['payroll_weekend_bonus_value'] ?? '') ?>"
                               placeholder="např. 200"
                               class="mt-1 w-full px-4 py-3 border rounded-lg"
                               style="background: var(--card); border-color: var(--border); color: var(--text);">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
                  <div class="font-semibold mb-3" style="color: var(--text);">Sváteční příplatek</div>

                  <div class="space-y-3">
                    <div>
                      <label class="text-sm" style="color: var(--text);">Režim</label>
                      <select name="payroll_holiday_bonus_mode"
                              id="payrollHolidayBonusMode"
                              class="mt-1 w-full px-4 py-3 border rounded-lg"
                              style="background: var(--card); border-color: var(--border); color: var(--text);">
                        <option value="company_default" <?= $payrollHolidayBonusMode === 'company_default' ? 'selected' : '' ?>>Podle firmy</option>
                        <option value="none" <?= $payrollHolidayBonusMode === 'none' ? 'selected' : '' ?>>Bez příplatku</option>
                        <option value="custom" <?= $payrollHolidayBonusMode === 'custom' ? 'selected' : '' ?>>Vlastní</option>
                      </select>
                    </div>

                    <div id="payrollHolidayBonusCustomWrap" class="space-y-3">
                      <div>
                        <label class="text-sm" style="color: var(--text);">Typ</label>
                        <select name="payroll_holiday_bonus_type"
                                class="mt-1 w-full px-4 py-3 border rounded-lg"
                                style="background: var(--card); border-color: var(--border); color: var(--text);">
                          <option value="shift_amount" <?= $payrollHolidayBonusType === 'shift_amount' ? 'selected' : '' ?>>Pevná částka za směnu</option>
                          <option value="hour_amount" <?= $payrollHolidayBonusType === 'hour_amount' ? 'selected' : '' ?>>Pevná částka za hodinu</option>
                          <option value="hourly_rate_percent" <?= $payrollHolidayBonusType === 'hourly_rate_percent' ? 'selected' : '' ?>>Procento z hodinové sazby</option>
                        </select>
                      </div>

                      <div>
                        <label class="text-sm" style="color: var(--text);">Hodnota</label>
                        <input name="payroll_holiday_bonus_value" inputmode="decimal"
                               value="<?= htmlspecialchars($user['payroll_holiday_bonus_value'] ?? '') ?>"
                               placeholder="např. 300"
                               class="mt-1 w-full px-4 py-3 border rounded-lg"
                               style="background: var(--card); border-color: var(--border); color: var(--text);">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="pt-6" style="border-top: 1px solid var(--border);">
          <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Úvazek a dovolená</h3>

          <div>
            <label class="text-sm" style="color: var(--text);">Úvazek (hod./den)</label>
            <input name="workload_hours" inputmode="decimal"
                   value="<?= htmlspecialchars($user['workload_hours'] ?? '') ?>"
                   placeholder="např. 7,5"
                   class="mt-1 w-full px-4 py-3 border rounded-lg"
                   style="background: var(--bg); border-color: var(--border); color: var(--text);">
          </div>

          <div>
            <label class="text-sm" style="color: var(--text);">Dovolená (hod./rok)</label>
            <input name="vacation_hours_year" inputmode="decimal"
                   value="<?= htmlspecialchars($user['vacation_hours_year'] ?? '') ?>"
                   placeholder="např. 160"
                   class="mt-1 w-full px-4 py-3 border rounded-lg"
                   style="background: var(--bg); border-color: var(--border); color: var(--text);">
          </div>

          <div class="mt-3 space-y-1 text-sm">
            <div style="color: var(--text);">
              <span style="color: var(--muted);">Čerpaná dovolená v roce <?= (int)$currentYear ?>:</span>
              <?= htmlspecialchars(number_format((float)$vacationUsedYear, 2, ',', ' ')) ?> hod.
            </div>

            <div style="color: var(--text);">
              <span style="color: var(--muted);">Zbývající dovolená v roce <?= (int)$currentYear ?>:</span>
              <?= htmlspecialchars(number_format((float)$vacationRemainingYear, 2, ',', ' ')) ?> hod.
            </div>
          </div>
        </div>

        <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
          Uložit rozšiřující údaje
        </button>
      </form>

      <?php if ($payrollEnabled): ?>
        <script>
          (function () {
            const salaryMode = document.getElementById('payrollSalaryMode');
            const fixedWrap = document.getElementById('payrollFixedSalaryWrap');
            const hourlyWrap = document.getElementById('payrollHourlyRateWrap');

            const companyBonusMode = document.getElementById('payrollCompanyBonusMode');
            const companyBonusCustomWrap = document.getElementById('payrollCompanyBonusCustomWrap');

            const weekendMode = document.getElementById('payrollWeekendBonusMode');
            const weekendCustomWrap = document.getElementById('payrollWeekendBonusCustomWrap');

            const holidayMode = document.getElementById('payrollHolidayBonusMode');
            const holidayCustomWrap = document.getElementById('payrollHolidayBonusCustomWrap');

            function syncSalaryMode() {
              const mode = salaryMode ? salaryMode.value : 'company_default';

              if (fixedWrap) {
                fixedWrap.style.display = (mode === 'fixed' || mode === 'mixed') ? '' : 'none';
              }

              if (hourlyWrap) {
                hourlyWrap.style.display = (mode === 'hourly' || mode === 'mixed') ? '' : 'none';
              }
            }

            function syncCompanyBonusMode() {
              if (!companyBonusMode || !companyBonusCustomWrap) return;
              companyBonusCustomWrap.style.display = companyBonusMode.value === 'custom' ? '' : 'none';
            }

            function syncWeekendMode() {
              if (!weekendMode || !weekendCustomWrap) return;
              weekendCustomWrap.style.display = weekendMode.value === 'custom' ? '' : 'none';
            }

            function syncHolidayMode() {
              if (!holidayMode || !holidayCustomWrap) return;
              holidayCustomWrap.style.display = holidayMode.value === 'custom' ? '' : 'none';
            }

            if (salaryMode) {
              salaryMode.addEventListener('change', syncSalaryMode);
              syncSalaryMode();
            }

            if (companyBonusMode) {
              companyBonusMode.addEventListener('change', syncCompanyBonusMode);
              syncCompanyBonusMode();
            }

            if (weekendMode) {
              weekendMode.addEventListener('change', syncWeekendMode);
              syncWeekendMode();
            }

            if (holidayMode) {
              holidayMode.addEventListener('change', syncHolidayMode);
              syncHolidayMode();
            }
          })();
        </script>
      <?php endif; ?>

    <?php elseif ($canViewExtra): ?>
      <?php
        $payrollEnabled = \Core\Feature::enabled('payrolls');

        $salaryModeLabels = [
          'company_default' => 'Podle firmy',
          'fixed' => 'Pevná mzda',
          'hourly' => 'Hodinová mzda',
          'mixed' => 'Kombinovaná mzda',
        ];

        $bonusModeLabels = [
          'company_default' => 'Podle firmy',
          'none' => 'Bez prémie',
          'custom' => 'Vlastní',
        ];

        $bonusTypeLabels = [
          'shift_amount' => 'Pevná částka za směnu',
          'hour_amount' => 'Pevná částka za hodinu',
          'hourly_rate_percent' => 'Procento z hodinové sazby',
        ];
      ?>

      <div class="space-y-4 text-sm" style="color: var(--text);">
        <?php if ($payrollEnabled): ?>
          <div>
            <span style="color: var(--muted);">Zahrnovat do výplat:</span>
            <?= (int)($user['payroll_active'] ?? 1) === 1 ? 'Ano' : 'Ne' ?>
          </div>

          <div>
            <span style="color: var(--muted);">Režim výpočtu mzdy:</span>
            <?= htmlspecialchars($salaryModeLabels[$user['payroll_salary_mode'] ?? 'company_default'] ?? 'Podle firmy') ?>
          </div>

          <div>
            <span style="color: var(--muted);">Pevná mzda:</span>
            <?= htmlspecialchars($user['payroll_fixed_salary'] ?? '-') ?>
          </div>

          <div>
            <span style="color: var(--muted);">Hodinová sazba:</span>
            <?= htmlspecialchars($user['payroll_hourly_rate'] ?? '-') ?>
          </div>

          <div>
            <span style="color: var(--muted);">Prémie:</span>
            <?= htmlspecialchars($bonusModeLabels[$user['payroll_company_bonus_mode'] ?? 'company_default'] ?? 'Podle firmy') ?>
          </div>

          <div>
            <span style="color: var(--muted);">Víkendový příplatek:</span>
            <?= htmlspecialchars($bonusModeLabels[$user['payroll_weekend_bonus_mode'] ?? 'company_default'] ?? 'Podle firmy') ?>
          </div>

          <div>
            <span style="color: var(--muted);">Sváteční příplatek:</span>
            <?= htmlspecialchars($bonusModeLabels[$user['payroll_holiday_bonus_mode'] ?? 'company_default'] ?? 'Podle firmy') ?>
          </div>
        <?php endif; ?>

        <div>
          <span style="color: var(--muted);">Úvazek:</span>
          <?= htmlspecialchars($user['workload_hours'] ?? '-') ?>
        </div>

        <div>
          <span style="color: var(--muted);">Dovolená:</span>
          <?= htmlspecialchars($user['vacation_hours_year'] ?? '-') ?> hod./rok
        </div>

        <div class="mt-4" style="color: var(--muted);">
          Nemáš oprávnění změnit rozšiřující údaje tohoto uživatele.
        </div>
      </div>

    <?php else: ?>
      <div class="space-y-2 text-sm" style="color: var(--text);">
        <div class="mt-4" style="color: var(--muted);">
          Nemáš oprávnění zobrazit rozšiřující údaje tohoto uživatele.
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>
