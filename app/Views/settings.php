<?php
$isOwner = \Core\Auth::role() === 'owner';
$canEdit = \Core\Auth::canEditSettings();
$allow = (int)($company['allow_manager_settings'] ?? 0) === 1;
$managerAssign = (int)($company['manager_can_assign_services'] ?? 1) === 1;

use Core\DB;
use Core\Auth;
use Core\WeekDays;

$stmt = DB::get()->prepare("
  SELECT * FROM service_places
  WHERE company_id = ?
  ORDER BY id ASC
");
$stmt->execute([Auth::companyId()]);
$places = $stmt->fetchAll();
?>

<div class="space-y-6">

  <!-- <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 w-full items-start"> -->
  <div class="columns-1 xl:columns-2 gap-6 [column-fill:_balance]">





    <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
      <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Nastavení firmy</h2>
      <p class="text-sm mb-6" style="color: var(--muted);">
        Údaje firmy a oprávnění pro managera.
      </p>

      <form method="POST" action="/settings" class="space-y-4">
        <?= \Core\CSRF::field() ?>

        <div>
          <label class="text-sm" style="color: var(--text);">Název firmy</label>
          <input name="name" value="<?= htmlspecialchars($company['name'] ?? '') ?>" required
                 <?= $canEdit ? '' : 'disabled' ?>
                 class="mt-1 w-full px-4 py-3 rounded-lg disabled:opacity-60" style="background: var(--bg); border: 1px solid var(--border);">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm" style="color: var(--text);">IČO</label>
            <input name="ico" value="<?= htmlspecialchars($company['ico'] ?? '') ?>" required
                   <?= $canEdit ? '' : 'disabled' ?>
                   class="mt-1 w-full px-4 py-3 rounded-lg disabled:opacity-60" style="background: var(--bg); border: 1px solid var(--border);">
          </div>
          <div>
            <label class="text-sm" style="color: var(--text);">DIČ (nepovinné)</label>
            <input name="dic" value="<?= htmlspecialchars($company['dic'] ?? '') ?>"
                   <?= $canEdit ? '' : 'disabled' ?>
                   class="mt-1 w-full px-4 py-3 rounded-lg disabled:opacity-60" style="background: var(--bg); border: 1px solid var(--border);">
          </div>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Ulice a č.p.</label>
          <input name="street" value="<?= htmlspecialchars($company['street'] ?? '') ?>" required
                 <?= $canEdit ? '' : 'disabled' ?>
                 class="mt-1 w-full px-4 py-3 rounded-lg disabled:opacity-60" style="background: var(--bg); border: 1px solid var(--border);">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm" style="color: var(--text);">Město</label>
            <input name="city" value="<?= htmlspecialchars($company['city'] ?? '') ?>" required
                   <?= $canEdit ? '' : 'disabled' ?>
                   class="mt-1 w-full px-4 py-3 rounded-lg disabled:opacity-60" style="background: var(--bg); border: 1px solid var(--border);">
          </div>
          <div>
            <label class="text-sm" style="color: var(--text);">PSČ</label>
            <input name="zip" value="<?= htmlspecialchars($company['zip'] ?? '') ?>" required
                   <?= $canEdit ? '' : 'disabled' ?>
                   class="mt-1 w-full px-4 py-3 rounded-lg disabled:opacity-60" style="background: var(--bg); border: 1px solid var(--border);">
          </div>
        </div>

        <?php if ($isOwner): ?>
          <div class="mt-6 p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between gap-4">
              <div>
                <div class="font-semibold" style="color: var(--text);">Povolit managerovi nastavení</div>
                <div class="text-sm" style="color: var(--muted);">
                  Manager uvidí a může upravovat stránku nastavení firmy.
                </div>
              </div>

              <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" name="allow_manager_settings" class="sr-only peer" <?= $allow ? 'checked' : '' ?>>
                <div class="w-12 h-7 bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] rounded-full relative transition">
                  <div class="dot absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $allow ? 'translate-x-5' : '' ?>"></div>
                </div>
              </label>
            </div>
          </div>

          <script>
            document.addEventListener('change', (e) => {
              const cb = e.target.closest('input[name="allow_manager_settings"]');
              if (!cb) return;
              const dot = cb.closest('label')?.querySelector('.dot');
              if (!dot) return;
              dot.classList.toggle('translate-x-5', cb.checked);
            });
          </script>
        <?php endif; ?>

        <?php if ($canEdit): ?>
          <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit změny
          </button>
        <?php else: ?>
          <div class="text-sm text-gray-500 dark:text-gray-300">
            Nemáš oprávnění upravovat nastavení firmy.
          </div>
        <?php endif; ?>

      </form>
    </div>













    <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
      <?php
        $canEdit = \Core\Auth::canEditSettings();
        $servicesEnabled = \Core\Feature::enabled('services');
        $vacationsEnabled = \Core\Feature::enabled('vacations');
        $attendanceEnabled = \Core\Feature::enabled('attendance');
        $payrollsEnabled = \Core\Feature::enabled('payrolls');
        $economicIndicatorsEnabled = \Core\Feature::enabled('economic_indicators');
        $wasteReportsEnabled = \Core\Feature::enabled('waste_reports');
        $temperaturesEnabled = \Core\Feature::enabled('temperatures');
        $sterilizationDryingEnabled = \Core\Feature::enabled('sterilization_drying');
        $tasksEnabled = \Core\Feature::enabled('tasks');
      ?>

      <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Moduly</h2>
      <p class="text-sm mb-6" style="color: var(--muted);">
        Zapnutí modulů pro firmu.
      </p>

      <form method="POST" action="/settings" class="space-y-4">
        <?= \Core\CSRF::field() ?>

        <input type="hidden" name="_settings_section" value="modules">

        <!-- Modul služby -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul služby</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul pro plánování služeb.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_services" class="peer hidden"
                    <?= $servicesEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-services absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $servicesEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul dovolené -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul dovolené</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul pro plánování dovolených. Propisuje se i do služeb.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_vacations" class="peer hidden"
                    <?= $vacationsEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-vacations absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $vacationsEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul docházka -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul docházka</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul evidence docházky.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_attendance" class="peer hidden"
                    <?= $attendanceEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-attendance absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $attendanceEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul výplaty -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul výplaty</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul pro evidenci a správu výplat.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_payrolls" class="peer hidden"
                    <?= $payrollsEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-payrolls absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $payrollsEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul ekonomické ukazatele -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul ekonomické ukazatele</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul pro evidenci ekonomických ukazatelů firmy.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_economic_indicators" class="peer hidden"
                    <?= $economicIndicatorsEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-economic-indicators absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $economicIndicatorsEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul hlášení odpadů -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul hlášení odpadů</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul hlášení odpadů vhodný pro lékárny.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_waste_reports" class="peer hidden"
                    <?= $wasteReportsEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-waste-reports absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $wasteReportsEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul teploty -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul teploty</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul pro evidenci teplot.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_temperatures" class="peer hidden"
                    <?= $temperaturesEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-temperatures absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $temperaturesEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul sterilizace a sušení -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul sterilizace a sušení</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul pro evidenci sterilizace a sušení vhodný pro lékárny.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_sterilization_drying" class="peer hidden"
                    <?= $sterilizationDryingEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-sterilization-drying absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $sterilizationDryingEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modul úkoly -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-semibold">Používat modul úkoly</div>
              <div class="text-sm text-gray-500 dark:text-gray-300">
                Modul pro správu a evidenci úkolů.
              </div>
            </div>

            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" name="feature_tasks" class="peer hidden"
                    <?= $tasksEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="switch-track w-12 h-7 rounded-full relative transition bg-gray-300 dark:bg-gray-700 peer-checked:bg-[color:var(--primary)] <?= $canEdit ? '' : 'opacity-60' ?>">
                <div class="dot-tasks absolute left-1 top-1 w-5 h-5 bg-white rounded-full transition <?= $tasksEnabled ? 'translate-x-5' : '' ?>"></div>
              </div>
            </label>
          </div>
        </div>

        <?php if ($canEdit): ?>
          <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit moduly
          </button>
        <?php else: ?>
          <div class="text-sm text-gray-500 dark:text-gray-300">
            Nemáš oprávnění upravovat moduly.
          </div>
        <?php endif; ?>
      </form>

      <script>
        document.addEventListener('change', (e) => {
          const servicesCb = e.target.closest('input[name="feature_services"]');
          if (servicesCb) {
            const dot = servicesCb.closest('label')?.querySelector('.dot-services');
            if (dot) dot.classList.toggle('translate-x-5', servicesCb.checked);
          }

          const vacationsCb = e.target.closest('input[name="feature_vacations"]');
          if (vacationsCb) {
            const dot = vacationsCb.closest('label')?.querySelector('.dot-vacations');
            if (dot) dot.classList.toggle('translate-x-5', vacationsCb.checked);
          }

          const attendanceCb = e.target.closest('input[name="feature_attendance"]');
          if (attendanceCb) {
            const dot = attendanceCb.closest('label')?.querySelector('.dot-attendance');
            if (dot) dot.classList.toggle('translate-x-5', attendanceCb.checked);
          }

          const payrollsCb = e.target.closest('input[name="feature_payrolls"]');
          if (payrollsCb) {
            const dot = payrollsCb.closest('label')?.querySelector('.dot-payrolls');
            if (dot) dot.classList.toggle('translate-x-5', payrollsCb.checked);
          }

          const economicIndicatorsCb = e.target.closest('input[name="feature_economic_indicators"]');
          if (economicIndicatorsCb) {
            const dot = economicIndicatorsCb.closest('label')?.querySelector('.dot-economic-indicators');
            if (dot) dot.classList.toggle('translate-x-5', economicIndicatorsCb.checked);
          }

          const wasteReportsCb = e.target.closest('input[name="feature_waste_reports"]');
          if (wasteReportsCb) {
            const dot = wasteReportsCb.closest('label')?.querySelector('.dot-waste-reports');
            if (dot) dot.classList.toggle('translate-x-5', wasteReportsCb.checked);
          }

          const temperaturesCb = e.target.closest('input[name="feature_temperatures"]');
          if (temperaturesCb) {
            const dot = temperaturesCb.closest('label')?.querySelector('.dot-temperatures');
            if (dot) dot.classList.toggle('translate-x-5', temperaturesCb.checked);
          }

          const sterilizationDryingCb = e.target.closest('input[name="feature_sterilization_drying"]');
          if (sterilizationDryingCb) {
            const dot = sterilizationDryingCb.closest('label')?.querySelector('.dot-sterilization-drying');
            if (dot) dot.classList.toggle('translate-x-5', sterilizationDryingCb.checked);
          }

          const tasksCb = e.target.closest('input[name="feature_tasks"]');
          if (tasksCb) {
            const dot = tasksCb.closest('label')?.querySelector('.dot-tasks');
            if (dot) dot.classList.toggle('translate-x-5', tasksCb.checked);
          }
        });
      </script>
    </div>





    <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
      <?php
        $canEdit = \Core\Auth::canEditSettings();
        $managerCanViewExports = !empty($company['manager_can_view_exports']);
        $mealVoucherExportEnabled = !empty($company['meal_voucher_export_enabled']);
        $mealVoucherMinHours = isset($company['meal_voucher_min_hours'])
          ? (float)$company['meal_voucher_min_hours']
          : 6;
      ?>

      <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Exporty</h2>
      <p class="text-sm mb-6" style="color: var(--muted);">
        Nastavení přístupu ke stránce exportů a souvisejících exportních funkcí.
      </p>

      <form method="POST" action="/settings/exports/company" class="space-y-4">
        <?= \Core\CSRF::field() ?>

        <!-- SUBKARTA: PŘÍSTUP -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="font-semibold mb-3" style="color: var(--text);">Přístup</div>

          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox"
                  name="manager_can_view_exports"
                  class="mt-1 h-4 w-4"
                  <?= $managerCanViewExports ? 'checked' : '' ?>
                  <?= $canEdit ? '' : 'disabled' ?>>

            <div>
              <div class="font-semibold" style="color: var(--text);">
                Umožnit exporty manažerovi
              </div>
              <div class="text-sm" style="color: var(--muted);">
                Pokud je vypnuto, stránku Exporty uvidí pouze owner.
              </div>
            </div>
          </label>
        </div>

        <!-- SUBKARTA: STRAVENKY -->
        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <div class="font-semibold mb-3" style="color: var(--text);">Stravenky</div>

          <div class="space-y-4">
            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox"
                    name="meal_voucher_export_enabled"
                    class="mt-1 h-4 w-4"
                    <?= $mealVoucherExportEnabled ? 'checked' : '' ?>
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div>
                <div class="font-semibold" style="color: var(--text);">
                  Používat export stravenek
                </div>
                <div class="text-sm" style="color: var(--muted);">
                  Zapne možnost exportu podkladů pro stravenky.
                </div>
              </div>
            </label>

            <div>
              <label class="block text-sm mb-1" style="color: var(--text);">
                Počet odpracovaných hodin v daném dni pro získání nároku na stravenku
              </label>

              <input type="number"
                    name="meal_voucher_min_hours"
                    step="0.01"
                    min="0"
                    value="<?= htmlspecialchars(number_format($mealVoucherMinHours, 2, '.', '')) ?>"
                    class="w-full px-4 py-3 rounded-lg"
                    style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                    <?= $canEdit ? '' : 'disabled' ?>>

              <div class="text-sm mt-2" style="color: var(--muted);">
                Např. 4.50 nebo 6.00 hodin.
              </div>
            </div>
          </div>
        </div>

        <?php if ($canEdit): ?>
          <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit nastavení exportů
          </button>
        <?php else: ?>
          <div class="text-sm" style="color: var(--muted);">
            Nemáš oprávnění upravovat nastavení.
          </div>
        <?php endif; ?>
      </form>
    </div>





    <?php if (\Core\Feature::enabled('services')): ?>
    <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
      <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Modul Služby</h2>

      <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Místa</h3>

        <?php if (empty($places)): ?>
          <div class="text-sm italic" style="color: var(--muted);">
            Zatím nemáte založené žádné místo.
          </div>
        <?php endif; ?>

        <?php foreach ($places as $place): ?>
          <?php $notesOn = (int)($place['notes_enabled'] ?? 0) === 1; ?>

          <div class="service-place p-4 rounded-xl mb-3"
              data-id="<?= (int)$place['id'] ?>"
              style="background: var(--card); border: 1px solid var(--border);">

            <!-- VIEW -->
            <div class="view flex flex-col md:flex-col md:items-center gap-4">

              <div class="flex-1 min-w-0">
                <div class="font-semibold" style="color: var(--text);">
                  <?= htmlspecialchars($place['name']) ?>
                </div>
                <div class="text-sm break-words" style="color: var(--muted);">
                  <?= htmlspecialchars($place['description'] ?? '') ?>
                </div>
              </div>

              <div class="flex flex-wrap gap-2 text-xs md:justify-end">
                <?php foreach (\Core\WeekDays::$days as $key => $bit): ?>
                  <span class="<?= \Core\WeekDays::isChecked((int)$place['days_mask'], $bit)
                    ? 'bg-green-100 text-green-800'
                    : 'bg-gray-100 text-gray-400'
                  ?> px-2 py-1 rounded">
                    <?= \Core\WeekDays::$labels[$key] ?>
                  </span>
                <?php endforeach; ?>

                <span class="px-2 py-1 rounded text-xs"
                      style="background: <?= $notesOn ? 'color-mix(in srgb, var(--primary) 18%, transparent)' : 'color-mix(in srgb, var(--border) 30%, transparent)' ?>;
                            color: <?= $notesOn ? 'var(--text)' : 'var(--muted)' ?>;
                            border: 1px solid var(--border);">
                  Poznámky: <?= $notesOn ? 'ON' : 'OFF' ?>
                </span>
              </div>

              <div class="flex gap-3 md:justify-end">
                <button type="button" class="edit-btn text-sm hover:underline" style="color: var(--primary);">
                  Upravit
                </button>

                <form method="POST" action="/settings/services/places/delete/<?= (int)$place['id'] ?>">
                  <?= \Core\CSRF::field() ?>
                  <button type="submit" class="text-sm hover:underline" style="color: #dc2626;">
                    Smazat
                  </button>
                </form>
              </div>
            </div>

            <!-- EDIT -->
            <form class="edit hidden mt-4 space-y-4">
              <?= \Core\CSRF::field() ?>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm mb-1" style="color: var(--text);">Název místa</label>
                  <input name="name"
                        value="<?= htmlspecialchars($place['name']) ?>"
                        class="w-full px-4 py-2 rounded-lg"
                        style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                </div>

                <div>
                  <label class="block text-sm mb-1" style="color: var(--text);">Popis</label>
                  <textarea name="description" rows="1"
                            class="w-full px-4 py-2 rounded-lg h-[42px] resize-none"
                            style="background: var(--card); border: 1px solid var(--border); color: var(--text);"><?= htmlspecialchars($place['description'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="grid grid-cols-4 md:grid-cols-7 gap-3 text-sm">
                <?php foreach (\Core\WeekDays::$days as $key => $bit): ?>
                  <label class="flex items-center gap-2"
                        style="color: var(--text);">
                    <input type="checkbox"
                          name="days[<?= $key ?>]"
                          <?= \Core\WeekDays::isChecked((int)$place['days_mask'], $bit) ? 'checked' : '' ?>>
                    <span class="leading-tight"><?= \Core\WeekDays::$names[$key] ?></span>
                  </label>
                <?php endforeach; ?>
              </div>

              <?php $notesOn = (int)($place['notes_enabled'] ?? 0) === 1; ?>
              <div class="flex items-start gap-3 p-4 rounded-xl"
                  style="background: var(--bg); border: 1px solid var(--border);">
                <input type="checkbox"
                      name="notes_enabled"
                      id="notes_enabled_<?= (int)$place['id'] ?>"
                      class="mt-1 h-4 w-4"
                      <?= $notesOn ? 'checked' : '' ?>>
                <label for="notes_enabled_<?= (int)$place['id'] ?>" class="cursor-pointer">
                  <div class="font-semibold" style="color: var(--text);">Evidovat poznámky</div>
                  <div class="text-sm" style="color: var(--muted);">
                    Povolí poznámky u tohoto místa.
                  </div>
                </label>
              </div>

              <div class="flex gap-3">
                <button type="submit" class="btn-primary rounded px-4 py-2 text-sm">
                  Uložit
                </button>
                <button type="button" class="cancel-btn text-sm hover:underline" style="color: var(--muted);">
                  Zrušit
                </button>
              </div>
            </form>

          </div>
        <?php endforeach; ?>

        <!-- ADD -->
        <h3 class="text-lg font-semibold mt-6 mb-2" style="color: var(--text);">Přidat nové místo</h3>

        <form method="POST" action="/settings/services/places/create" class="space-y-4">
          <?= \Core\CSRF::field() ?>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm mb-1" style="color: var(--text);">Název místa</label>
              <input name="name"
                    placeholder="Např. Praha – Karlín"
                    class="w-full px-4 py-2 rounded-lg"
                    style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
            </div>

            <div>
              <label class="block text-sm mb-1" style="color: var(--text);">Popis (nepovinný)</label>
              <textarea name="description"
                        rows="1"
                        placeholder="Např. hlavní pobočka"
                        class="w-full px-4 py-2 rounded-lg h-[42px] resize-none"
                        style="background: var(--card); border: 1px solid var(--border); color: var(--text);"></textarea>
            </div>
          </div>

          <div class="grid grid-cols-4 md:grid-cols-7 gap-3 text-sm">
            <?php foreach (\Core\WeekDays::$days as $key => $bit): ?>
              <label class="flex items-center gap-2" style="color: var(--text);">
                <input type="checkbox" name="days[<?= $key ?>]">
                <span class="leading-tight"><?= \Core\WeekDays::$names[$key] ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="flex items-start gap-3 p-4 rounded-xl"
              style="background: var(--bg); border: 1px solid var(--border);">
            <input type="checkbox"
                  name="notes_enabled"
                  id="notes_enabled_add"
                  class="mt-1 h-4 w-4">
            <label for="notes_enabled_add" class="cursor-pointer">
              <div class="font-semibold" style="color: var(--text);">Evidovat poznámky</div>
              <div class="text-sm" style="color: var(--muted);">
                U tohoto místa bude možné zapisovat poznámky.
              </div>
            </label>
          </div>

          <button type="submit" class="btn-primary rounded-lg px-4 py-2">
            + Přidat místo
          </button>
        </form>

      </div>
      <?php
      $managerAssign = !empty($company['manager_can_assign_services']);
      ?>

      <!-- Nastavení modulu služeb (samostatný formulář) -->
      <div class="mt-6 p-4 rounded-xl"
          style="background: var(--bg); border: 1px solid var(--border);">

        <h4 class="text-base font-semibold mb-3" style="color: var(--text);">
          Nastavení modulu služeb
        </h4>

        <form method="POST" action="/settings/services/company" class="space-y-3">
          <?= \Core\CSRF::field() ?>

          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox"
                  name="manager_can_assign_services"
                  class="mt-1 h-4 w-4"
                  <?= $managerAssign ? 'checked' : '' ?>
                  <?= $canEdit ? '' : 'disabled' ?>>

            <div>
              <div class="font-semibold" style="color: var(--text);">
                Manažer může přihlašovat do služeb kolegy
              </div>
              <div class="text-sm" style="color: var(--muted);">
                Pokud je vypnuto, kolegy do služeb může přihlašovat pouze owner.
              </div>
            </div>
          </label>

          <?php if ($canEdit): ?>
            <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
              Uložit nastavení služeb
            </button>
          <?php else: ?>
            <div class="text-sm" style="color: var(--muted);">
              Nemáš oprávnění upravovat nastavení.
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <?php endif; ?>










    <?php if (\Core\Feature::enabled('payrolls')): ?>
      <?php
        $canEdit = \Core\Auth::canEditSettings();

        $payrollLockAttendance = (int)($company['payroll_lock_attendance_after_creation'] ?? 1) === 1;
        $payrollAllowManager = (int)($company['payroll_allow_manager'] ?? 0) === 1;

        $payrollDefaultSalaryType = (string)($company['payroll_default_salary_type'] ?? 'fixed');
        if (!in_array($payrollDefaultSalaryType, ['fixed', 'hourly', 'mixed'], true)) {
            $payrollDefaultSalaryType = 'fixed';
        }

        $payrollComponentFixedSalary = (int)($company['payroll_component_fixed_salary'] ?? 1) === 1;
        $payrollComponentHourlyWage = (int)($company['payroll_component_hourly_wage'] ?? 0) === 1;
        $payrollComponentCompanyBonus = (int)($company['payroll_component_company_bonus'] ?? 0) === 1;
        $payrollComponentWeekendBonus = (int)($company['payroll_component_weekend_bonus'] ?? 0) === 1;
        $payrollComponentHolidayBonus = (int)($company['payroll_component_holiday_bonus'] ?? 0) === 1;

        $payrollWeekendBonusType = (string)($company['payroll_weekend_bonus_type'] ?? 'shift_amount');
        if (!in_array($payrollWeekendBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
            $payrollWeekendBonusType = 'shift_amount';
        }
        $payrollWeekendBonusValue = (string)($company['payroll_weekend_bonus_value'] ?? '0');

        $payrollHolidayBonusType = (string)($company['payroll_holiday_bonus_type'] ?? 'shift_amount');
        if (!in_array($payrollHolidayBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
            $payrollHolidayBonusType = 'shift_amount';
        }
        $payrollHolidayBonusValue = (string)($company['payroll_holiday_bonus_value'] ?? '0');

        $payrollOverlapRule = (string)($company['payroll_overlap_rule'] ?? 'sum');
        if (!in_array($payrollOverlapRule, ['sum', 'higher', 'lower', 'holiday', 'weekend'], true)) {
            $payrollOverlapRule = 'sum';
        }

        $payrollDefaultCompanyBonusEnabled = (int)($company['payroll_default_company_bonus_enabled'] ?? 0) === 1;
        $payrollDefaultCompanyBonusSourceType = (string)($company['payroll_default_company_bonus_source_type'] ?? '');
        $payrollDefaultCompanyBonusSourceId = (int)($company['payroll_default_company_bonus_source_id'] ?? 0);
        $payrollDefaultCompanyBonusCalcType = (string)($company['payroll_default_company_bonus_calc_type'] ?? 'percent');
        if (!in_array($payrollDefaultCompanyBonusCalcType, ['percent', 'fixed'], true)) {
            $payrollDefaultCompanyBonusCalcType = 'percent';
        }
        $payrollDefaultCompanyBonusValue = (string)($company['payroll_default_company_bonus_value'] ?? '0');

        $payrollRoundingType = (string)($company['payroll_rounding_type'] ?? 'none');
        if (!in_array($payrollRoundingType, ['none', '1', '10', '100'], true)) {
            $payrollRoundingType = 'none';
        }

        $payrollCountOvertime = (int)($company['payroll_count_overtime'] ?? 0) === 1;

        $economicIndicatorTopLevelGroups = $economicIndicatorTopLevelGroups ?? [];
        $economicIndicatorTopLevelIndicators = $economicIndicatorTopLevelIndicators ?? [];

        $payrollFixedSalaryShortfallMode = (string)($company['payroll_fixed_salary_shortfall_mode'] ?? 'full');
        if (!in_array($payrollFixedSalaryShortfallMode, ['full', 'proportional', 'zero'], true)) {
            $payrollFixedSalaryShortfallMode = 'full';
        }
      ?>

      <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Modul výplaty</h2>
        <p class="text-sm mb-6" style="color: var(--muted);">
          Nastavení výchozí logiky výpočtu hrubé výplaty pro firmu.
        </p>

        <form method="POST" action="/settings/payrolls" class="space-y-6">
          <?= \Core\CSRF::field() ?>

          <div class="space-y-4">
            <h3 class="text-lg font-semibold" style="color: var(--text);">Přístup a chování</h3>

            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox"
                     name="payroll_lock_attendance_after_creation"
                     value="1"
                     class="mt-1 h-4 w-4"
                     <?= $payrollLockAttendance ? 'checked' : '' ?>
                     <?= $canEdit ? '' : 'disabled' ?>>

              <div>
                <div class="font-semibold" style="color: var(--text);">
                  Po vytvoření výplaty uzamknout docházku
                </div>
                <div class="text-sm" style="color: var(--muted);">
                  Pokud je zapnuto, po vytvoření výplaty nebude možné upravovat docházku za dané období.
                </div>
              </div>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox"
                     name="payroll_allow_manager"
                     value="1"
                     class="mt-1 h-4 w-4"
                     <?= $payrollAllowManager ? 'checked' : '' ?>
                     <?= $canEdit ? '' : 'disabled' ?>>

              <div>
                <div class="font-semibold" style="color: var(--text);">
                  Umožnit správu výplat manažerovi
                </div>
                <div class="text-sm" style="color: var(--muted);">
                  Pokud je zapnuto, manažer bude moci pracovat s modulem výplat.
                </div>
              </div>
            </label>
          </div>

          <div class="pt-6" style="border-top: 1px solid var(--border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Výchozí typ výpočtu</h3>

            <div>
              <label class="text-sm" style="color: var(--text);">Výchozí typ hrubé mzdy</label>
              <select name="payroll_default_salary_type"
                      <?= $canEdit ? '' : 'disabled' ?>
                      class="mt-1 w-full px-4 py-3 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                <option value="fixed" <?= $payrollDefaultSalaryType === 'fixed' ? 'selected' : '' ?>>Pevná mzda</option>
                <option value="hourly" <?= $payrollDefaultSalaryType === 'hourly' ? 'selected' : '' ?>>Hodinová mzda</option>
                <option value="mixed" <?= $payrollDefaultSalaryType === 'mixed' ? 'selected' : '' ?>>Kombinovaná</option>
              </select>
            </div>
          </div>

          <div class="pt-6" style="border-top: 1px solid var(--border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Aktivní složky mzdy</h3>

            <div class="grid md:grid-cols-2 gap-4">
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="payroll_component_fixed_salary" value="1" class="mt-1 h-4 w-4"
                       <?= $payrollComponentFixedSalary ? 'checked' : '' ?>
                       <?= $canEdit ? '' : 'disabled' ?>>
                <div>
                  <div class="font-semibold" style="color: var(--text);">Základní mzda</div>
                  <div class="text-sm" style="color: var(--muted);">Pevná měsíční složka mzdy.</div>
                </div>
              </label>

              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="payroll_component_hourly_wage" value="1" class="mt-1 h-4 w-4"
                       <?= $payrollComponentHourlyWage ? 'checked' : '' ?>
                       <?= $canEdit ? '' : 'disabled' ?>>
                <div>
                  <div class="font-semibold" style="color: var(--text);">Hodinová mzda</div>
                  <div class="text-sm" style="color: var(--muted);">Výpočet podle odpracovaných hodin.</div>
                </div>
              </label>

              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="payroll_component_company_bonus" value="1" class="mt-1 h-4 w-4"
                       <?= $payrollComponentCompanyBonus ? 'checked' : '' ?>
                       <?= $canEdit ? '' : 'disabled' ?>>
                <div>
                  <div class="font-semibold" style="color: var(--text);">Firemní prémie</div>
                  <div class="text-sm" style="color: var(--muted);">Prémie navázaná na ekonomický ukazatel nebo skupinu.</div>
                </div>
              </label>

              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="payroll_component_weekend_bonus" value="1" class="mt-1 h-4 w-4"
                       <?= $payrollComponentWeekendBonus ? 'checked' : '' ?>
                       <?= $canEdit ? '' : 'disabled' ?>>
                <div>
                  <div class="font-semibold" style="color: var(--text);">Příplatek za víkend</div>
                  <div class="text-sm" style="color: var(--muted);">Příplatek za víkendovou práci.</div>
                </div>
              </label>

              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="payroll_component_holiday_bonus" value="1" class="mt-1 h-4 w-4"
                       <?= $payrollComponentHolidayBonus ? 'checked' : '' ?>
                       <?= $canEdit ? '' : 'disabled' ?>>
                <div>
                  <div class="font-semibold" style="color: var(--text);">Příplatek za svátek</div>
                  <div class="text-sm" style="color: var(--muted);">Příplatek za práci ve svátek.</div>
                </div>
              </label>
            </div>
          </div>

          <div class="pt-6" style="border-top: 1px solid var(--border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Výchozí příplatky</h3>

            <div class="grid md:grid-cols-2 gap-4">
              <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
                <div class="font-semibold mb-3" style="color: var(--text);">Víkendový příplatek</div>

                <div class="space-y-3">
                  <div>
                    <label class="text-sm" style="color: var(--text);">Typ</label>
                    <select name="payroll_weekend_bonus_type"
                            <?= $canEdit ? '' : 'disabled' ?>
                            class="mt-1 w-full px-4 py-3 rounded-lg"
                            style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                      <option value="shift_amount" <?= $payrollWeekendBonusType === 'shift_amount' ? 'selected' : '' ?>>Pevná částka za směnu</option>
                      <option value="hour_amount" <?= $payrollWeekendBonusType === 'hour_amount' ? 'selected' : '' ?>>Pevná částka za hodinu</option>
                      <option value="hourly_rate_percent" <?= $payrollWeekendBonusType === 'hourly_rate_percent' ? 'selected' : '' ?>>Procento z hodinové sazby</option>
                    </select>
                  </div>

                  <div>
                    <label class="text-sm" style="color: var(--text);">Hodnota</label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="payroll_weekend_bonus_value"
                           value="<?= htmlspecialchars($payrollWeekendBonusValue) ?>"
                           <?= $canEdit ? '' : 'disabled' ?>
                           class="mt-1 w-full px-4 py-3 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>
                </div>
              </div>

              <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
                <div class="font-semibold mb-3" style="color: var(--text);">Sváteční příplatek</div>

                <div class="space-y-3">
                  <div>
                    <label class="text-sm" style="color: var(--text);">Typ</label>
                    <select name="payroll_holiday_bonus_type"
                            <?= $canEdit ? '' : 'disabled' ?>
                            class="mt-1 w-full px-4 py-3 rounded-lg"
                            style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                      <option value="shift_amount" <?= $payrollHolidayBonusType === 'shift_amount' ? 'selected' : '' ?>>Pevná částka za směnu</option>
                      <option value="hour_amount" <?= $payrollHolidayBonusType === 'hour_amount' ? 'selected' : '' ?>>Pevná částka za hodinu</option>
                      <option value="hourly_rate_percent" <?= $payrollHolidayBonusType === 'hourly_rate_percent' ? 'selected' : '' ?>>Procento z hodinové sazby</option>
                    </select>
                  </div>

                  <div>
                    <label class="text-sm" style="color: var(--text);">Hodnota</label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="payroll_holiday_bonus_value"
                           value="<?= htmlspecialchars($payrollHolidayBonusValue) ?>"
                           <?= $canEdit ? '' : 'disabled' ?>
                           class="mt-1 w-full px-4 py-3 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <label class="text-sm" style="color: var(--text);">Když směna spadne zároveň na víkend i svátek</label>
              <select name="payroll_overlap_rule"
                      <?= $canEdit ? '' : 'disabled' ?>
                      class="mt-1 w-full px-4 py-3 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                <option value="sum" <?= $payrollOverlapRule === 'sum' ? 'selected' : '' ?>>Sečíst oba příplatky</option>
                <option value="higher" <?= $payrollOverlapRule === 'higher' ? 'selected' : '' ?>>Použít vyšší příplatek</option>
                <option value="lower" <?= $payrollOverlapRule === 'lower' ? 'selected' : '' ?>>Použít nižší příplatek</option>
                <option value="holiday" <?= $payrollOverlapRule === 'holiday' ? 'selected' : '' ?>>Použít sváteční příplatek</option>
                <option value="weekend" <?= $payrollOverlapRule === 'weekend' ? 'selected' : '' ?>>Použít víkendový příplatek</option>
              </select>
            </div>
          </div>

          <div class="pt-6" style="border-top: 1px solid var(--border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Výchozí firemní prémie</h3>

            <label class="flex items-start gap-3 cursor-pointer mb-4">
              <input type="checkbox"
                     name="payroll_default_company_bonus_enabled"
                     value="1"
                     class="mt-1 h-4 w-4"
                     <?= $payrollDefaultCompanyBonusEnabled ? 'checked' : '' ?>
                     <?= $canEdit ? '' : 'disabled' ?>>

              <div>
                <div class="font-semibold" style="color: var(--text);">
                  Používat výchozí firemní prémii
                </div>
                <div class="text-sm" style="color: var(--muted);">
                  Například každý zaměstnanec dostane 1 % z vybraného ekonomického ukazatele nebo skupiny.
                </div>
              </div>
            </label>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm" style="color: var(--text);">Zdroj prémie</label>
                <select name="payroll_default_company_bonus_source_type"
                        <?= $canEdit ? '' : 'disabled' ?>
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="">Nevybráno</option>
                  <option value="group" <?= $payrollDefaultCompanyBonusSourceType === 'group' ? 'selected' : '' ?>>Skupina ukazatelů</option>
                  <option value="indicator" <?= $payrollDefaultCompanyBonusSourceType === 'indicator' ? 'selected' : '' ?>>Samostatný ukazatel</option>
                </select>
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">Ukazatel / skupina</label>
                <select name="payroll_default_company_bonus_source_id"
                        <?= $canEdit ? '' : 'disabled' ?>
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="">Nevybráno</option>

                  <?php if (!empty($economicIndicatorTopLevelGroups)): ?>
                    <optgroup label="Skupiny">
                      <?php foreach ($economicIndicatorTopLevelGroups as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= $payrollDefaultCompanyBonusSourceId === (int)$item['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($item['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </optgroup>
                  <?php endif; ?>

                  <?php if (!empty($economicIndicatorTopLevelIndicators)): ?>
                    <optgroup label="Samostatné ukazatele">
                      <?php foreach ($economicIndicatorTopLevelIndicators as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= $payrollDefaultCompanyBonusSourceId === (int)$item['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($item['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </optgroup>
                  <?php endif; ?>
                </select>
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">Typ výpočtu</label>
                <select name="payroll_default_company_bonus_calc_type"
                        <?= $canEdit ? '' : 'disabled' ?>
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="percent" <?= $payrollDefaultCompanyBonusCalcType === 'percent' ? 'selected' : '' ?>>Procento</option>
                  <option value="fixed" <?= $payrollDefaultCompanyBonusCalcType === 'fixed' ? 'selected' : '' ?>>Pevná částka</option>
                </select>
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">Hodnota</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="payroll_default_company_bonus_value"
                       value="<?= htmlspecialchars($payrollDefaultCompanyBonusValue) ?>"
                       <?= $canEdit ? '' : 'disabled' ?>
                       class="mt-1 w-full px-4 py-3 rounded-lg"
                       style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              </div>
            </div>
          </div>

          <div class="pt-6" style="border-top: 1px solid var(--border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Pravidla výpočtu</h3>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm" style="color: var(--text);">Zaokrouhlování výplaty</label>
                <select name="payroll_rounding_type"
                        <?= $canEdit ? '' : 'disabled' ?>
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="none" <?= $payrollRoundingType === 'none' ? 'selected' : '' ?>>Bez zaokrouhlování</option>
                  <option value="1" <?= $payrollRoundingType === '1' ? 'selected' : '' ?>>Na celé koruny</option>
                  <option value="10" <?= $payrollRoundingType === '10' ? 'selected' : '' ?>>Na desítky korun</option>
                  <option value="100" <?= $payrollRoundingType === '100' ? 'selected' : '' ?>>Na stovky korun</option>
                </select>
              </div>

              <div class="flex items-center">
                <label class="flex items-start gap-3 cursor-pointer mt-6">
                  <input type="checkbox"
                         name="payroll_count_overtime"
                         value="1"
                         class="mt-1 h-4 w-4"
                         <?= $payrollCountOvertime ? 'checked' : '' ?>
                         <?= $canEdit ? '' : 'disabled' ?>>

                  <div>
                    <div class="font-semibold" style="color: var(--text);">
                      Počítat přesčasy
                    </div>
                    <div class="text-sm" style="color: var(--muted);">
                      Připraví systém pro pozdější výpočet přesčasových složek.
                    </div>
                  </div>
                </label>
              </div>
            </div>
            <div>
              <label class="text-sm" style="color: var(--text);">Když zaměstnanec nesplní měsíční dotaci hodin</label>
              <select name="payroll_fixed_salary_shortfall_mode"
                      <?= $canEdit ? '' : 'disabled' ?>
                      class="mt-1 w-full px-4 py-3 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                <option value="full" <?= $payrollFixedSalaryShortfallMode === 'full' ? 'selected' : '' ?>>
                  Vždy celá základní mzda
                </option>
                <option value="proportional" <?= $payrollFixedSalaryShortfallMode === 'proportional' ? 'selected' : '' ?>>
                  Podíl podle docházky
                </option>
                <option value="zero" <?= $payrollFixedSalaryShortfallMode === 'zero' ? 'selected' : '' ?>>
                  Pokud nesplní dotaci hodin, základní mzda je nula
                </option>
              </select>
            </div>
          </div>

          <?php if ($canEdit): ?>
            <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
              Uložit nastavení výplat
            </button>
          <?php else: ?>
            <div class="text-sm" style="color: var(--muted);">
              Nemáš oprávnění upravovat nastavení výplat.
            </div>
          <?php endif; ?>
        </form>
      </div>
    <?php endif; ?>


    <?php if (\Core\Feature::enabled('economic_indicators')): ?>
      <?php
        $canEdit = \Core\Auth::canEditSettings();
        $economicIndicatorsAllowManager = (int)($company['economic_indicators_allow_manager'] ?? 0) === 1;

        $definitions = $economicIndicatorDefinitions ?? [];
        $groupOptions = $economicIndicatorGroupOptions ?? [];

        $childrenMap = [];
        foreach ($definitions as $def) {
            $parentKey = $def['parent_id'] ? (int)$def['parent_id'] : 0;
            if (!isset($childrenMap[$parentKey])) {
                $childrenMap[$parentKey] = [];
            }
            $childrenMap[$parentKey][] = $def;
        }

        $periodLabels = [
            'daily' => 'Denní',
            'weekly' => 'Týdenní',
            'monthly' => 'Měsíční',
            'quarterly' => 'Kvartální',
            'yearly' => 'Roční',
        ];

        $renderEconomicIndicatorTree = function ($parentId = 0, $level = 0) use (&$renderEconomicIndicatorTree, $childrenMap, $canEdit, $periodLabels) {
            if (empty($childrenMap[$parentId])) {
                return;
            }

            echo '<div class="space-y-3">';
            foreach ($childrenMap[$parentId] as $item) {
                $id = (int)$item['id'];
                $type = (string)$item['type'];
                $name = (string)$item['name'];
                $unit = (string)($item['unit'] ?? '');
                $periodType = (string)($item['period_type'] ?? '');
                $isGroup = $type === 'group';
                $margin = $level * 28;

                echo '<div class="rounded-xl p-4" style="margin-left:' . $margin . 'px; background: var(--bg); border: 1px solid var(--border);">';
                echo '  <div class="flex items-start justify-between gap-4">';
                echo '    <div class="min-w-0">';
                echo '      <div class="flex items-center gap-2 flex-wrap">';
                echo '        <div class="font-semibold" style="color: var(--text);">' . htmlspecialchars($name) . '</div>';

                echo '        <span class="text-xs px-2 py-1 rounded-full" style="background: var(--card); border: 1px solid var(--border); color: var(--muted);">';
                echo              ($isGroup ? 'Skupina' : 'Ukazatel');
                echo '        </span>';

                if (!$isGroup && $unit !== '') {
                    echo '    <span class="text-xs px-2 py-1 rounded-full" style="background: var(--card); border: 1px solid var(--border); color: var(--muted);">';
                    echo          'Jednotka: ' . htmlspecialchars($unit);
                    echo '    </span>';
                }

                if ($periodType !== '') {
                    echo '    <span class="text-xs px-2 py-1 rounded-full" style="background: var(--card); border: 1px solid var(--border); color: var(--muted);">';
                    echo          'Období: ' . htmlspecialchars($periodLabels[$periodType] ?? $periodType);
                    echo '    </span>';
                }

                echo '      </div>';

                if ($isGroup) {
                    echo '  <div class="text-sm mt-1" style="color: var(--muted);">Součet hodnot všech podřízených ukazatelů.</div>';
                } else {
                    if (!empty($item['parent_id'])) {
                        echo '  <div class="text-sm mt-1" style="color: var(--muted);">Ukazatel přebírá období zadávání ze své skupiny.</div>';
                    } else {
                        echo '  <div class="text-sm mt-1" style="color: var(--muted);">Samostatný ukazatel pro zadávání hodnot.</div>';
                    }
                }

                echo '    </div>';

                if ($canEdit) {
                    echo '  <div class="flex items-center gap-2 shrink-0">';
                    echo '    <button type="button"
                                    class="js-open-economic-indicator-edit px-3 py-2 rounded-lg text-sm"
                                    style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                                    data-id="' . $id . '"
                                    data-name="' . htmlspecialchars($name, ENT_QUOTES) . '"
                                    data-type="' . htmlspecialchars($type, ENT_QUOTES) . '"
                                    data-parent-id="' . (int)($item['parent_id'] ?? 0) . '"
                                    data-unit="' . htmlspecialchars($unit, ENT_QUOTES) . '"
                                    data-period-type="' . htmlspecialchars($periodType, ENT_QUOTES) . '">
                                  Upravit
                                </button>';

                    echo '    <form method="POST" action="/settings/economic-indicators/item/delete" onsubmit="return confirm(\'Opravdu chceš tuto položku smazat?\');">';
                    echo          \Core\CSRF::field();
                    echo '      <input type="hidden" name="id" value="' . $id . '">';
                    echo '      <button class="px-3 py-2 rounded-lg text-sm"
                                        style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;">
                                  Smazat
                                </button>';
                    echo '    </form>';
                    echo '  </div>';
                }

                echo '  </div>';

                if (!empty($childrenMap[$id])) {
                    echo '<div class="mt-3">';
                    $renderEconomicIndicatorTree($id, $level + 1);
                    echo '</div>';
                }

                echo '</div>';
            }
            echo '</div>';
        };
      ?>

      <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Modul ekonomické ukazatele</h2>
        <p class="text-sm mb-6" style="color: var(--muted);">
          Nastavení modulu a struktury ekonomických ukazatelů.
        </p>

        <form method="POST" action="/settings/economic-indicators" class="space-y-4">
          <?= \Core\CSRF::field() ?>

          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox"
                  name="economic_indicators_allow_manager"
                  value="1"
                  class="mt-1 h-4 w-4"
                  <?= $economicIndicatorsAllowManager ? 'checked' : '' ?>
                  <?= $canEdit ? '' : 'disabled' ?>>

            <div>
              <div class="font-semibold" style="color: var(--text);">
                Umožnit správu ekonomických ukazatelů manažerovi
              </div>
              <div class="text-sm" style="color: var(--muted);">
                Pokud je zapnuto, manažer bude moci spravovat ekonomické ukazatele firmy.
              </div>
            </div>
          </label>

          <?php if ($canEdit): ?>
            <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
              Uložit nastavení ekonomických ukazatelů
            </button>
          <?php else: ?>
            <div class="text-sm" style="color: var(--muted);">
              Nemáš oprávnění upravovat nastavení ekonomických ukazatelů.
            </div>
          <?php endif; ?>
        </form>

        <div class="mt-8 pt-6" style="border-top: 1px solid var(--border);">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="text-lg font-semibold" style="color: var(--text);">Položky evidence</h3>
              <p class="text-sm mt-1" style="color: var(--muted);">
                Můžeš zakládat skupiny i samostatné ukazatele. Skupina i samostatný ukazatel mají vlastní období zadávání.
              </p>
            </div>

            <?php if ($canEdit): ?>
              <div class="flex gap-2">
                <button type="button"
                        class="js-open-economic-indicator-create px-4 py-2 rounded-lg font-semibold"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                        data-type="group">
                  Nová skupina
                </button>

                <button type="button"
                        class="js-open-economic-indicator-create btn-primary px-4 py-2 rounded-lg font-semibold"
                        data-type="indicator">
                  Nový ukazatel
                </button>
              </div>
            <?php endif; ?>
          </div>

          <?php if (empty($definitions)): ?>
            <div class="text-sm italic p-4 rounded-xl"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
              Zatím nemáš založené žádné ekonomické ukazatele.
            </div>
          <?php else: ?>
            <?php $renderEconomicIndicatorTree(0, 0); ?>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($canEdit): ?>
        <!-- MODAL CREATE -->
        <div id="economicIndicatorCreateModal" class="fixed inset-0 z-50 hidden items-center justify-center" aria-hidden="true">
          <div class="absolute inset-0" style="background: rgba(0,0,0,.45);" data-close-economic-indicator-create="1"></div>

          <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
              style="background: var(--card); border: 1px solid var(--border);">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 id="economicIndicatorCreateHeading" class="text-lg font-semibold" style="color: var(--text);">Nová položka</h3>
                <p class="text-sm mt-1" style="color: var(--muted);">
                  Vytvoření nové skupiny nebo ukazatele.
                </p>
              </div>

              <button type="button"
                      class="px-3 py-2 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                      data-close-economic-indicator-create="1">✕</button>
            </div>

            <form method="POST" action="/settings/economic-indicators/item/create" class="mt-5 space-y-4">
              <?= \Core\CSRF::field() ?>

              <input type="hidden" name="type" id="economicIndicatorCreateType" value="indicator">

              <div>
                <label class="text-sm" style="color: var(--text);">Název</label>
                <input type="text"
                      name="name"
                      class="mt-1 w-full px-4 py-3 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                      required>
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">Nadřazená skupina</label>
                <select name="parent_id"
                        id="economicIndicatorCreateParent"
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="">Bez nadřazené skupiny</option>
                  <?php foreach ($groupOptions as $group): ?>
                    <option value="<?= (int)$group['id'] ?>">
                      <?= htmlspecialchars($group['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div id="economicIndicatorCreatePeriodWrap">
                <label class="text-sm" style="color: var(--text);">Období zadávání</label>
                <select name="period_type"
                        id="economicIndicatorCreatePeriod"
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="">Vyber období</option>
                  <option value="daily">Denní</option>
                  <option value="weekly">Týdenní</option>
                  <option value="monthly">Měsíční</option>
                  <option value="quarterly">Kvartální</option>
                  <option value="yearly">Roční</option>
                </select>
              </div>

              <div id="economicIndicatorCreateUnitWrap">
                <label class="text-sm" style="color: var(--text);">Jednotka</label>
                <input type="text"
                      name="unit"
                      placeholder="např. Kč"
                      class="mt-1 w-full px-4 py-3 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              </div>

              <div class="flex gap-3 justify-end pt-2">
                <button type="button"
                        class="px-5 py-3 rounded-lg font-semibold"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                        data-close-economic-indicator-create="1">
                  Zrušit
                </button>

                <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
                  Uložit
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- MODAL EDIT -->
        <div id="economicIndicatorEditModal" class="fixed inset-0 z-50 hidden items-center justify-center" aria-hidden="true">
          <div class="absolute inset-0" style="background: rgba(0,0,0,.45);" data-close-economic-indicator-edit="1"></div>

          <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
              style="background: var(--card); border: 1px solid var(--border);">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-lg font-semibold" style="color: var(--text);">Upravit položku</h3>
                <p class="text-sm mt-1" style="color: var(--muted);">
                  Úprava názvu, zařazení, jednotky a období zadávání.
                </p>
              </div>

              <button type="button"
                      class="px-3 py-2 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                      data-close-economic-indicator-edit="1">✕</button>
            </div>

            <form method="POST" action="/settings/economic-indicators/item/update" class="mt-5 space-y-4">
              <?= \Core\CSRF::field() ?>

              <input type="hidden" name="id" id="economicIndicatorEditId">
              <input type="hidden" name="type" id="economicIndicatorEditType">

              <div>
                <label class="text-sm" style="color: var(--text);">Název</label>
                <input type="text"
                      name="name"
                      id="economicIndicatorEditName"
                      class="mt-1 w-full px-4 py-3 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                      required>
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">Nadřazená skupina</label>
                <select name="parent_id"
                        id="economicIndicatorEditParent"
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="">Bez nadřazené skupiny</option>
                  <?php foreach ($groupOptions as $group): ?>
                    <option value="<?= (int)$group['id'] ?>">
                      <?= htmlspecialchars($group['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div id="economicIndicatorEditPeriodWrap">
                <label class="text-sm" style="color: var(--text);">Období zadávání</label>
                <select name="period_type"
                        id="economicIndicatorEditPeriod"
                        class="mt-1 w-full px-4 py-3 rounded-lg"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value="">Vyber období</option>
                  <option value="daily">Denní</option>
                  <option value="weekly">Týdenní</option>
                  <option value="monthly">Měsíční</option>
                  <option value="quarterly">Kvartální</option>
                  <option value="yearly">Roční</option>
                </select>
              </div>

              <div id="economicIndicatorEditUnitWrap">
                <label class="text-sm" style="color: var(--text);">Jednotka</label>
                <input type="text"
                      name="unit"
                      id="economicIndicatorEditUnit"
                      class="mt-1 w-full px-4 py-3 rounded-lg"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              </div>

              <div class="flex gap-3 justify-end pt-2">
                <button type="button"
                        class="px-5 py-3 rounded-lg font-semibold"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                        data-close-economic-indicator-edit="1">
                  Zrušit
                </button>

                <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
                  Uložit změny
                </button>
              </div>
            </form>
          </div>
        </div>

        <script>
          (function () {
            const createModal = document.getElementById('economicIndicatorCreateModal');
            const editModal = document.getElementById('economicIndicatorEditModal');

            const createType = document.getElementById('economicIndicatorCreateType');
            const createHeading = document.getElementById('economicIndicatorCreateHeading');
            const createUnitWrap = document.getElementById('economicIndicatorCreateUnitWrap');
            const createParent = document.getElementById('economicIndicatorCreateParent');
            const createPeriodWrap = document.getElementById('economicIndicatorCreatePeriodWrap');
            const createPeriod = document.getElementById('economicIndicatorCreatePeriod');

            const editId = document.getElementById('economicIndicatorEditId');
            const editType = document.getElementById('economicIndicatorEditType');
            const editName = document.getElementById('economicIndicatorEditName');
            const editParent = document.getElementById('economicIndicatorEditParent');
            const editUnit = document.getElementById('economicIndicatorEditUnit');
            const editUnitWrap = document.getElementById('economicIndicatorEditUnitWrap');
            const editPeriodWrap = document.getElementById('economicIndicatorEditPeriodWrap');
            const editPeriod = document.getElementById('economicIndicatorEditPeriod');

            function syncCreatePeriodVisibility() {
              const type = createType.value;
              const hasParent = !!createParent.value;

              if (type === 'group') {
                createPeriodWrap.style.display = '';
                return;
              }

              if (type === 'indicator' && hasParent) {
                createPeriodWrap.style.display = 'none';
                createPeriod.value = '';
                return;
              }

              createPeriodWrap.style.display = '';
            }

            function syncEditPeriodVisibility() {
              const type = editType.value;
              const hasParent = !!editParent.value;

              if (type === 'group') {
                editPeriodWrap.style.display = '';
                return;
              }

              if (type === 'indicator' && hasParent) {
                editPeriodWrap.style.display = 'none';
                editPeriod.value = '';
                return;
              }

              editPeriodWrap.style.display = '';
            }

            function openCreateModal(type) {
              createType.value = type;
              createHeading.textContent = type === 'group' ? 'Nová skupina ukazatelů' : 'Nový ukazatel';
              createUnitWrap.style.display = type === 'group' ? 'none' : '';
              createParent.disabled = false;
              createParent.value = '';
              createPeriod.value = '';

              syncCreatePeriodVisibility();

              createModal.classList.remove('hidden');
              createModal.classList.add('flex');
              createModal.setAttribute('aria-hidden', 'false');
            }

            function closeCreateModal() {
              createModal.classList.add('hidden');
              createModal.classList.remove('flex');
              createModal.setAttribute('aria-hidden', 'true');
            }

            function openEditModal(data) {
              editId.value = data.id || '';
              editType.value = data.type || 'indicator';
              editName.value = data.name || '';
              editParent.value = data.parentId || '';
              editUnit.value = data.unit || '';
              editPeriod.value = data.periodType || '';

              editUnitWrap.style.display = data.type === 'group' ? 'none' : '';

              syncEditPeriodVisibility();

              editModal.classList.remove('hidden');
              editModal.classList.add('flex');
              editModal.setAttribute('aria-hidden', 'false');
            }

            function closeEditModal() {
              editModal.classList.add('hidden');
              editModal.classList.remove('flex');
              editModal.setAttribute('aria-hidden', 'true');
            }

            document.addEventListener('click', (e) => {
              const createBtn = e.target.closest('.js-open-economic-indicator-create');
              if (createBtn) {
                e.preventDefault();
                openCreateModal(createBtn.getAttribute('data-type') || 'indicator');
                return;
              }

              const editBtn = e.target.closest('.js-open-economic-indicator-edit');
              if (editBtn) {
                e.preventDefault();
                openEditModal({
                  id: editBtn.getAttribute('data-id') || '',
                  type: editBtn.getAttribute('data-type') || 'indicator',
                  name: editBtn.getAttribute('data-name') || '',
                  parentId: editBtn.getAttribute('data-parent-id') || '',
                  unit: editBtn.getAttribute('data-unit') || '',
                  periodType: editBtn.getAttribute('data-period-type') || ''
                });
                return;
              }

              if (e.target && e.target.getAttribute('data-close-economic-indicator-create') === '1') {
                e.preventDefault();
                closeCreateModal();
                return;
              }

              if (e.target && e.target.getAttribute('data-close-economic-indicator-edit') === '1') {
                e.preventDefault();
                closeEditModal();
                return;
              }
            });

            if (createParent) {
              createParent.addEventListener('change', syncCreatePeriodVisibility);
            }

            if (editParent) {
              editParent.addEventListener('change', syncEditPeriodVisibility);
            }

            document.addEventListener('keydown', (e) => {
              if (e.key === 'Escape') {
                if (createModal && !createModal.classList.contains('hidden')) {
                  closeCreateModal();
                }
                if (editModal && !editModal.classList.contains('hidden')) {
                  closeEditModal();
                }
              }
            });
          })();
        </script>
      <?php endif; ?>
    <?php endif; ?>












    <!-- teploměry -->
     <?php if (\Core\Feature::enabled('temperatures')): ?>
      <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Modul teploty</h2>

        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Teploměry</h3>

          <?php if (empty($temperaturePlaces)): ?>
            <div class="text-sm italic" style="color: var(--muted);">
              Zatím nemáte založený žádný teploměr.
            </div>
          <?php endif; ?>

          <?php foreach ($temperaturePlaces as $place): ?>
            <?php $humidityOn = (int)($place['humidity_enabled'] ?? 0) === 1; ?>

            <div class="temperature-place p-4 rounded-xl mb-3"
                data-id="<?= (int)$place['id'] ?>"
                style="background: var(--card); border: 1px solid var(--border);">

              <!-- VIEW -->
              <div class="view flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="font-semibold" style="color: var(--text);">
                    <?= htmlspecialchars($place['name']) ?>
                  </div>
                </div>

                <div class="flex flex-wrap gap-2 text-xs shrink-0">
                  <span class="px-2 py-1 rounded text-xs"
                        style="background: <?= $humidityOn ? 'color-mix(in srgb, var(--primary) 18%, transparent)' : 'color-mix(in srgb, var(--border) 30%, transparent)' ?>;
                              color: <?= $humidityOn ? 'var(--text)' : 'var(--muted)' ?>;
                              border: 1px solid var(--border);">
                    Vlhkost: <?= $humidityOn ? 'ON' : 'OFF' ?>
                  </span>
                </div>

                <div class="flex gap-3 shrink-0">
                  <button type="button" class="edit-btn text-sm hover:underline" style="color: var(--primary);">
                    Upravit
                  </button>

                  <form method="POST" action="/settings/temperatures/places/delete/<?= (int)$place['id'] ?>">
                    <?= \Core\CSRF::field() ?>
                    <button type="submit" class="text-sm hover:underline" style="color: #dc2626;">
                      Smazat
                    </button>
                  </form>
                </div>
              </div>

              <!-- EDIT -->
              <form class="edit hidden mt-4 space-y-4">
                <?= \Core\CSRF::field() ?>

                <div>
                  <label class="block text-sm mb-1" style="color: var(--text);">Název teploměru</label>
                  <input name="name"
                        value="<?= htmlspecialchars($place['name']) ?>"
                        class="w-full px-4 py-2 rounded-lg"
                        style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                </div>

                <div class="flex items-start gap-3 p-4 rounded-xl"
                    style="background: var(--bg); border: 1px solid var(--border);">
                  <input type="checkbox"
                        name="humidity_enabled"
                        id="humidity_enabled_<?= (int)$place['id'] ?>"
                        class="mt-1 h-4 w-4"
                        <?= $humidityOn ? 'checked' : '' ?>>
                  <label for="humidity_enabled_<?= (int)$place['id'] ?>" class="cursor-pointer">
                    <div class="font-semibold" style="color: var(--text);">Evidovat i vlhkost</div>
                    <div class="text-sm" style="color: var(--muted);">
                      U tohoto teploměru bude možné evidovat i vlhkost.
                    </div>
                  </label>
                </div>

                <div class="flex gap-3">
                  <button type="submit" class="btn-primary rounded px-4 py-2 text-sm">
                    Uložit
                  </button>
                  <button type="button" class="cancel-btn text-sm hover:underline" style="color: var(--muted);">
                    Zrušit
                  </button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>

          <!-- ADD -->
          <h3 class="text-lg font-semibold mt-6 mb-2" style="color: var(--text);">Přidat nový teploměr</h3>

          <form method="POST" action="/settings/temperatures/places/create" class="space-y-4">
            <?= \Core\CSRF::field() ?>

            <div>
              <label class="block text-sm mb-1" style="color: var(--text);">Název teploměru</label>
              <input name="name"
                    placeholder="Např. Lednice 1"
                    class="w-full px-4 py-2 rounded-lg"
                    style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl"
                style="background: var(--bg); border: 1px solid var(--border);">
              <input type="checkbox"
                    name="humidity_enabled"
                    id="humidity_enabled_add"
                    class="mt-1 h-4 w-4">
              <label for="humidity_enabled_add" class="cursor-pointer">
                <div class="font-semibold" style="color: var(--text);">Evidovat i vlhkost</div>
                <div class="text-sm" style="color: var(--muted);">
                  U tohoto teploměru bude možné evidovat i vlhkost.
                </div>
              </label>
            </div>

            <button type="submit" class="btn-primary rounded-lg px-4 py-2">
              + Přidat teploměr
            </button>
          </form>
        </div>
      </div>

      <script>
        document.addEventListener('click', (e) => {
          const row = e.target.closest('.temperature-place');
          if (!row) return;

          if (e.target.classList.contains('edit-btn')) {
            row.querySelector('.view').classList.add('hidden');
            row.querySelector('.edit').classList.remove('hidden');
          }

          if (e.target.classList.contains('cancel-btn')) {
            row.querySelector('.edit').classList.add('hidden');
            row.querySelector('.view').classList.remove('hidden');
          }
        });

        document.addEventListener('submit', async (e) => {
          const form = e.target.closest('.temperature-place .edit');
          if (!form) return;

          e.preventDefault();
          const row = form.closest('.temperature-place');
          const id = row.dataset.id;

          const res = await fetch(`/settings/temperatures/places/update/${id}`, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' }
          });

          if (!res.ok) {
            alert('Chyba při ukládání');
            return;
          }

          location.reload();
        });
      </script>
    <?php endif; ?>


    <?php if (\Core\Feature::enabled('attendance')): ?>
      <?php
        $canEdit = \Core\Auth::canEditSettings();
        $attendanceRounding = (int)($company['attendance_rounding_minutes'] ?? 1);
        $attendanceAllowBreak = (int)($company['attendance_allow_break'] ?? 0) === 1;

        $allowedRoundingOptions = [1, 5, 10, 15, 30];
        if (!in_array($attendanceRounding, $allowedRoundingOptions, true)) {
            $attendanceRounding = 1;
        }
      ?>

      <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Modul docházka</h2>
        <p class="text-sm mb-6" style="color: var(--muted);">
          Nastavení chování modulu docházky.
        </p>

        <form method="POST" action="/settings/attendance" class="space-y-4">
          <?= \Core\CSRF::field() ?>

          <div>
            <label class="text-sm" style="color: var(--text);">Zaokrouhlování příchodu a odchodu</label>
            <select name="attendance_rounding_minutes"
                    <?= $canEdit ? '' : 'disabled' ?>
                    class="mt-1 w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              <option value="1"  <?= $attendanceRounding === 1  ? 'selected' : '' ?>>1 minuta</option>
              <option value="5"  <?= $attendanceRounding === 5  ? 'selected' : '' ?>>5 minut</option>
              <option value="10" <?= $attendanceRounding === 10 ? 'selected' : '' ?>>10 minut</option>
              <option value="15" <?= $attendanceRounding === 15 ? 'selected' : '' ?>>15 minut</option>
              <option value="30" <?= $attendanceRounding === 30 ? 'selected' : '' ?>>30 minut</option>
            </select>
          </div>

          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox"
                  name="attendance_allow_break"
                  value="1"
                  class="mt-1 h-4 w-4"
                  <?= $attendanceAllowBreak ? 'checked' : '' ?>
                  <?= $canEdit ? '' : 'disabled' ?>>

            <div>
              <div class="font-semibold" style="color: var(--text);">
                Umožnit v docházce přestávku
              </div>
              <div class="text-sm" style="color: var(--muted);">
                Pokud je zapnuto, uživatelé budou moci v docházce evidovat začátek a konec přestávky.
              </div>
            </div>
          </label>

          <?php if ($canEdit): ?>
            <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
              Uložit nastavení docházky
            </button>
          <?php else: ?>
            <div class="text-sm" style="color: var(--muted);">
              Nemáš oprávnění upravovat nastavení docházky.
            </div>
          <?php endif; ?>
        </form>
      </div>
    <?php endif; ?>


    <!-- sterilizátory -->
     <?php if (\Core\Feature::enabled('sterilization_drying')): ?>
      <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Modul sterilizace a sušení</h2>

        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Místa sterilizace a sušení</h3>

          <?php if (empty($sterilizationPlaces)): ?>
            <div class="text-sm italic" style="color: var(--muted);">
              Zatím nemáte založené žádné místo.
            </div>
          <?php endif; ?>

          <?php foreach ($sterilizationPlaces as $place): ?>
            <?php
              $sterilizerOn = (int)($place['is_sterilizer'] ?? 0) === 1;
              $dryingOn = (int)($place['is_drying'] ?? 0) === 1;
            ?>

            <div class="sterilization-place p-4 rounded-xl mb-3"
                data-id="<?= (int)$place['id'] ?>"
                style="background: var(--card); border: 1px solid var(--border);">

              <!-- VIEW -->
              <div class="view flex flex-col gap-4">
                <div class="flex-1 min-w-0">
                  <div class="font-semibold" style="color: var(--text);">
                    <?= htmlspecialchars($place['name']) ?>
                  </div>
                </div>

                <div class="flex flex-wrap gap-2 text-xs">
                  <span class="px-2 py-1 rounded text-xs"
                        style="background: <?= $sterilizerOn ? 'color-mix(in srgb, var(--primary) 18%, transparent)' : 'color-mix(in srgb, var(--border) 30%, transparent)' ?>;
                              color: <?= $sterilizerOn ? 'var(--text)' : 'var(--muted)' ?>;
                              border: 1px solid var(--border);">
                    Sterilizátor: <?= $sterilizerOn ? 'ON' : 'OFF' ?>
                  </span>

                  <span class="px-2 py-1 rounded text-xs"
                        style="background: <?= $dryingOn ? 'color-mix(in srgb, var(--primary) 18%, transparent)' : 'color-mix(in srgb, var(--border) 30%, transparent)' ?>;
                              color: <?= $dryingOn ? 'var(--text)' : 'var(--muted)' ?>;
                              border: 1px solid var(--border);">
                    Sušárna: <?= $dryingOn ? 'ON' : 'OFF' ?>
                  </span>
                </div>

                <div class="flex gap-3">
                  <button type="button" class="edit-btn text-sm hover:underline" style="color: var(--primary);">
                    Upravit
                  </button>

                  <form method="POST" action="/settings/sterilization-drying/places/delete/<?= (int)$place['id'] ?>">
                    <?= \Core\CSRF::field() ?>
                    <button type="submit" class="text-sm hover:underline" style="color: #dc2626;">
                      Smazat
                    </button>
                  </form>
                </div>
              </div>

              <!-- EDIT -->
              <form class="edit hidden mt-4 space-y-4">
                <?= \Core\CSRF::field() ?>

                <div>
                  <label class="block text-sm mb-1" style="color: var(--text);">Název místa</label>
                  <input name="name"
                        value="<?= htmlspecialchars($place['name']) ?>"
                        class="w-full px-4 py-2 rounded-lg"
                        style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                </div>

                <div class="space-y-3">
                  <div class="flex items-start gap-3 p-4 rounded-xl"
                      style="background: var(--bg); border: 1px solid var(--border);">
                    <input type="checkbox"
                          name="is_sterilizer"
                          id="is_sterilizer_<?= (int)$place['id'] ?>"
                          class="mt-1 h-4 w-4"
                          <?= $sterilizerOn ? 'checked' : '' ?>>
                    <label for="is_sterilizer_<?= (int)$place['id'] ?>" class="cursor-pointer">
                      <div class="font-semibold" style="color: var(--text);">Sterilizátor</div>
                      <div class="text-sm" style="color: var(--muted);">
                        Toto místo slouží jako sterilizátor.
                      </div>
                    </label>
                  </div>

                  <div class="flex items-start gap-3 p-4 rounded-xl"
                      style="background: var(--bg); border: 1px solid var(--border);">
                    <input type="checkbox"
                          name="is_drying"
                          id="is_drying_<?= (int)$place['id'] ?>"
                          class="mt-1 h-4 w-4"
                          <?= $dryingOn ? 'checked' : '' ?>>
                    <label for="is_drying_<?= (int)$place['id'] ?>" class="cursor-pointer">
                      <div class="font-semibold" style="color: var(--text);">Sušárna</div>
                      <div class="text-sm" style="color: var(--muted);">
                        Toto místo slouží jako sušárna.
                      </div>
                    </label>
                  </div>
                </div>

                <div class="flex gap-3">
                  <button type="submit" class="btn-primary rounded px-4 py-2 text-sm">
                    Uložit
                  </button>
                  <button type="button" class="cancel-btn text-sm hover:underline" style="color: var(--muted);">
                    Zrušit
                  </button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>

          <!-- ADD -->
          <h3 class="text-lg font-semibold mt-6 mb-2" style="color: var(--text);">Přidat nové místo</h3>

          <form method="POST" action="/settings/sterilization-drying/places/create" class="space-y-4">
            <?= \Core\CSRF::field() ?>

            <div>
              <label class="block text-sm mb-1" style="color: var(--text);">Název místa</label>
              <input name="name"
                    placeholder="Např. Sterilizátor A"
                    class="w-full px-4 py-2 rounded-lg"
                    style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
            </div>

            <div class="space-y-3">
              <div class="flex items-start gap-3 p-4 rounded-xl"
                  style="background: var(--bg); border: 1px solid var(--border);">
                <input type="checkbox"
                      name="is_sterilizer"
                      id="is_sterilizer_add"
                      class="mt-1 h-4 w-4">
                <label for="is_sterilizer_add" class="cursor-pointer">
                  <div class="font-semibold" style="color: var(--text);">Sterilizátor</div>
                  <div class="text-sm" style="color: var(--muted);">
                    Toto místo slouží jako sterilizátor.
                  </div>
                </label>
              </div>

              <div class="flex items-start gap-3 p-4 rounded-xl"
                  style="background: var(--bg); border: 1px solid var(--border);">
                <input type="checkbox"
                      name="is_drying"
                      id="is_drying_add"
                      class="mt-1 h-4 w-4">
                <label for="is_drying_add" class="cursor-pointer">
                  <div class="font-semibold" style="color: var(--text);">Sušárna</div>
                  <div class="text-sm" style="color: var(--muted);">
                    Toto místo slouží jako sušárna.
                  </div>
                </label>
              </div>
            </div>

            <button type="submit" class="btn-primary rounded-lg px-4 py-2">
              + Přidat místo
            </button>
          </form>
        </div>
      </div>

      <script>
        document.addEventListener('click', (e) => {
          const row = e.target.closest('.sterilization-place');
          if (!row) return;

          if (e.target.classList.contains('edit-btn')) {
            row.querySelector('.view').classList.add('hidden');
            row.querySelector('.edit').classList.remove('hidden');
          }

          if (e.target.classList.contains('cancel-btn')) {
            row.querySelector('.edit').classList.add('hidden');
            row.querySelector('.view').classList.remove('hidden');
          }
        });

        document.addEventListener('submit', async (e) => {
          const form = e.target.closest('.sterilization-place .edit');
          if (!form) return;

          e.preventDefault();
          const row = form.closest('.sterilization-place');
          const id = row.dataset.id;

          const res = await fetch(`/settings/sterilization-drying/places/update/${id}`, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' }
          });

          if (!res.ok) {
            alert('Chyba při ukládání');
            return;
          }

          location.reload();
        });
      </script>
    <?php endif; ?>












    <?php if (\Core\Feature::enabled('waste_reports')): ?>
      <?php
        $managerWasteReports = !empty($company['manager_can_view_waste_reports']);
      ?>

      <div class="rounded-2xl shadow p-6 break-inside-avoid mb-6" style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Nastavení hlášení odpadů</h2>

        <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
          <h3 class="text-lg font-semibold mb-4" style="color: var(--text);">Místa svozů</h3>

          <?php if (empty($wasteReportPlaces)): ?>
            <div class="text-sm italic" style="color: var(--muted);">
              Zatím nemáte založené žádné místo svozu.
            </div>
          <?php endif; ?>

          <?php foreach ($wasteReportPlaces as $place): ?>
            <div class="waste-place p-4 rounded-xl mb-3"
                 data-id="<?= (int)$place['id'] ?>"
                 style="background: var(--card); border: 1px solid var(--border);">

              <!-- VIEW -->
              <div class="view flex flex-col gap-4">
                <div>
                  <div class="font-semibold" style="color: var(--text);">
                    <?= htmlspecialchars($place['pharmacy_name']) ?>
                  </div>
                  <div class="text-sm" style="color: var(--muted);">
                    IČP: <?= htmlspecialchars($place['icp'] ?? '-') ?>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                  <div style="color: var(--text);"><span style="color: var(--muted);">Ulice a čp:</span> <?= htmlspecialchars($place['street'] ?? '-') ?></div>
                  <div style="color: var(--text);"><span style="color: var(--muted);">Obec:</span> <?= htmlspecialchars($place['city'] ?? '-') ?></div>
                  <div style="color: var(--text);"><span style="color: var(--muted);">PSČ:</span> <?= htmlspecialchars($place['zip'] ?? '-') ?></div>
                  <div style="color: var(--text);"><span style="color: var(--muted);">IČZUJ:</span> <?= htmlspecialchars($place['iczuj'] ?? '-') ?></div>
                  <div style="color: var(--text);"><span style="color: var(--muted);">Krajský úřad:</span> <?= htmlspecialchars($place['regional_office'] ?? '-') ?></div>
                  <div style="color: var(--text);"><span style="color: var(--muted);">IČO osoby nakládající s odpady:</span> <?= htmlspecialchars($place['waste_handler_ico'] ?? '-') ?></div>
                  <div style="color: var(--text);"><span style="color: var(--muted);">IČZ zařízení:</span> <?= htmlspecialchars($place['waste_facility_icz'] ?? '-') ?></div>
                </div>

                <div class="flex gap-3">
                  <button type="button" class="edit-btn text-sm hover:underline" style="color: var(--primary);">
                    Upravit
                  </button>

                  <form method="POST" action="/settings/waste-reports/places/delete/<?= (int)$place['id'] ?>">
                    <?= \Core\CSRF::field() ?>
                    <button type="submit" class="text-sm hover:underline" style="color: #dc2626;">
                      Smazat
                    </button>
                  </form>
                </div>
              </div>

              <!-- EDIT -->
              <form class="edit hidden mt-4 space-y-4">
                <?= \Core\CSRF::field() ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">Název lékárny</label>
                    <input name="pharmacy_name"
                           value="<?= htmlspecialchars($place['pharmacy_name']) ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>

                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">Identifikační číslo provozovny (IČP)</label>
                    <input name="icp"
                           value="<?= htmlspecialchars($place['icp'] ?? '') ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">Ulice a čp</label>
                    <input name="street"
                           value="<?= htmlspecialchars($place['street'] ?? '') ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>

                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">Obec</label>
                    <input name="city"
                           value="<?= htmlspecialchars($place['city'] ?? '') ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">PSČ</label>
                    <input name="zip"
                           value="<?= htmlspecialchars($place['zip'] ?? '') ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>

                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">IČZUJ</label>
                    <input name="iczuj"
                           value="<?= htmlspecialchars($place['iczuj'] ?? '') ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">Krajský úřad</label>
                    <input name="regional_office"
                           value="<?= htmlspecialchars($place['regional_office'] ?? '') ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>

                  <div>
                    <label class="block text-sm mb-1" style="color: var(--text);">IČO osoby nakládající s odpady</label>
                    <input name="waste_handler_ico"
                           value="<?= htmlspecialchars($place['waste_handler_ico'] ?? '') ?>"
                           class="w-full px-4 py-2 rounded-lg"
                           style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                  </div>
                </div>

                <div>
                  <label class="block text-sm mb-1" style="color: var(--text);">Identifikační číslo zařízení nakládajícího s odpady (IČZ)</label>
                  <input name="waste_facility_icz"
                         value="<?= htmlspecialchars($place['waste_facility_icz'] ?? '') ?>"
                         class="w-full px-4 py-2 rounded-lg"
                         style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
                </div>

                <div class="flex gap-3">
                  <button type="submit" class="btn-primary rounded px-4 py-2 text-sm">
                    Uložit
                  </button>
                  <button type="button" class="cancel-btn text-sm hover:underline" style="color: var(--muted);">
                    Zrušit
                  </button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>

          <!-- ADD -->
          <h3 class="text-lg font-semibold mt-6 mb-2" style="color: var(--text);">Přidat nové místo svozu</h3>

          <form method="POST" action="/settings/waste-reports/places/create" class="space-y-4">
            <?= \Core\CSRF::field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Název lékárny</label>
                <input name="pharmacy_name"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>

              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Identifikační číslo provozovny (IČP)</label>
                <input name="icp"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Ulice a čp</label>
                <input name="street"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>

              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Obec</label>
                <input name="city"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">PSČ</label>
                <input name="zip"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>

              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">IČZUJ</label>
                <input name="iczuj"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Krajský úřad</label>
                <input name="regional_office"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>

              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">IČO osoby nakládající s odpady</label>
                <input name="waste_handler_ico"
                       class="w-full px-4 py-2 rounded-lg"
                       style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              </div>
            </div>

            <div>
              <label class="block text-sm mb-1" style="color: var(--text);">Identifikační číslo zařízení nakládajícího s odpady (IČZ)</label>
              <input name="waste_facility_icz"
                     class="w-full px-4 py-2 rounded-lg"
                     style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
            </div>

            <button type="submit" class="btn-primary rounded-lg px-4 py-2">
              + Přidat místo svozu
            </button>
          </form>
        </div>

        <!-- Nastavení modulu hlášení odpadů -->
        <div class="mt-6 p-4 rounded-xl"
             style="background: var(--bg); border: 1px solid var(--border);">

          <h4 class="text-base font-semibold mb-3" style="color: var(--text);">
            Nastavení modulu hlášení odpadů
          </h4>

          <form method="POST" action="/settings/waste-reports/company" class="space-y-3">
            <?= \Core\CSRF::field() ?>

            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox"
                     name="manager_can_view_waste_reports"
                     class="mt-1 h-4 w-4"
                     <?= $managerWasteReports ? 'checked' : '' ?>
                     <?= $canEdit ? '' : 'disabled' ?>>

              <div>
                <div class="font-semibold" style="color: var(--text);">
                  Manager vidí modul hlášení odpadů
                </div>
                <div class="text-sm" style="color: var(--muted);">
                  Pokud je vypnuto, modul hlášení odpadů uvidí pouze owner.
                </div>
              </div>
            </label>

            <?php if ($canEdit): ?>
              <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
                Uložit nastavení hlášení odpadů
              </button>
            <?php else: ?>
              <div class="text-sm" style="color: var(--muted);">
                Nemáš oprávnění upravovat nastavení.
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <script>
        document.addEventListener('click', (e) => {
          const row = e.target.closest('.waste-place');
          if (!row) return;

          if (e.target.classList.contains('edit-btn')) {
            row.querySelector('.view').classList.add('hidden');
            row.querySelector('.edit').classList.remove('hidden');
          }

          if (e.target.classList.contains('cancel-btn')) {
            row.querySelector('.edit').classList.add('hidden');
            row.querySelector('.view').classList.remove('hidden');
          }
        });

        document.addEventListener('submit', async (e) => {
          const form = e.target.closest('.waste-place .edit');
          if (!form) return;

          e.preventDefault();
          const row = form.closest('.waste-place');
          const id = row.dataset.id;

          const res = await fetch(`/settings/waste-reports/places/update/${id}`, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' }
          });

          if (!res.ok) {
            alert('Chyba při ukládání');
            return;
          }

          location.reload();
        });
      </script>
    <?php endif; ?>




</div>
</div>
<script>
document.addEventListener('click', (e) => {
  const row = e.target.closest('.service-place');
  if (!row) return;

  // otevřít edit
  if (e.target.classList.contains('edit-btn')) {
    row.querySelector('.view').classList.add('hidden');
    row.querySelector('.edit').classList.remove('hidden');
  }

  // zrušit edit
  if (e.target.classList.contains('cancel-btn')) {
    row.querySelector('.edit').classList.add('hidden');
    row.querySelector('.view').classList.remove('hidden');
  }
});

// submit editace
document.addEventListener('submit', async (e) => {
  const form = e.target.closest('.service-place .edit');
  if (!form) return;

  e.preventDefault();
  const row = form.closest('.service-place');
  const id = row.dataset.id;

  const res = await fetch(`/settings/services/places/update/${id}`, {
    method: 'POST',
    body: new FormData(form),
    headers: { 'Accept': 'application/json' }
  });

  if (!res.ok) {
    alert('Chyba při ukládání');
    return;
  }

  // jednoduchý refresh – nejčistší řešení teď
  location.reload();
});
</script>
