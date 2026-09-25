<?php
$monthCurrent = $month ?? date('Y-m');
$currentMonthValue = date('Y-m');
$todayKey = date('Y-m-d');

$czDays = [
    1 => 'Pondělí',
    2 => 'Úterý',
    3 => 'Středa',
    4 => 'Čtvrtek',
    5 => 'Pátek',
    6 => 'Sobota',
    7 => 'Neděle',
];

if (!function_exists('attendance_value_time')) {
    function attendance_value_time($value): string
    {
        if (empty($value)) return '';
        return substr((string)$value, 0, 5);
    }
}

if (!function_exists('attendance_worked_human')) {
    function attendance_worked_human($minutes): string
    {
        $minutes = (int)$minutes;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
}

if (!function_exists('attendance_special_label')) {
    function attendance_special_label($code): string
    {
        $code = (string)$code;

        if ($code === 'D') return 'Dovolená';
        if ($code === 'O') return 'OČR';
        if ($code === 'PN') return 'Pracovní neschopnost';
        if ($code === 'S') return 'Svátek';

        return 'Běžná docházka';
    }
}

$defaultWorkedSpecial = attendance_worked_human((int)($userWorkloadMinutes ?? 0));

$summaryWorkedMinutes = 0;
$summarySpecial = [
    'D' => 0,
    'O' => 0,
    'PN' => 0,
    'S' => 0,
];
$summarySavedDays = 0;

foreach ($days as $day) {
    $record = $day['record'] ?? null;
    if (!$record) {
        continue;
    }

    $summarySavedDays++;
    $summaryWorkedMinutes += (int)($record['worked_minutes'] ?? 0);

    $special = (string)($record['special_code'] ?? '');
    if ($special !== '' && isset($summarySpecial[$special])) {
        $summarySpecial[$special]++;
    }
}
?>

<div class="space-y-6">

  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
       <h2 class="text-xl font-semibold mb-2" style="color: var(--text);">Docházka</h2>

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
      <form method="GET" action="/attendance" class="flex flex-col sm:flex-row gap-3">
        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Měsíc a rok</label>
          <input type="month"
                 name="month"
                 value="<?= htmlspecialchars($monthCurrent) ?>"
                 class="px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div class="flex items-end gap-3">
          <button type="submit"
                  class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Načíst měsíc
          </button>

          <a href="/attendance?month=<?= htmlspecialchars($currentMonthValue) ?>"
             class="px-5 py-3 rounded-lg font-semibold"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            Aktuální měsíc
          </a>

          <a href="/attendance/export?month=<?= urlencode($monthCurrent) ?>" target="attendanceExportWindow" onclick="window.open(this.href, 'attendanceExportWindow', 'width=1100,height=900,resizable=yes,scrollbars=yes'); return false;"
             class="px-5 py-3 rounded-lg font-semibold inline-flex items-center gap-2"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
             title="Exportovat docházku">
            <span aria-hidden="true">🖨</span> Exportovat
          </a>
        </div>
      </form>

      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="rounded-xl p-4"
             style="background: var(--bg); border: 1px solid var(--border);">
          <div class="text-xs" style="color: var(--muted);">Odpracováno</div>
          <div class="text-lg font-bold mt-1" style="color: var(--text);">
            <?= attendance_worked_human($summaryWorkedMinutes) ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: var(--bg); border: 1px solid var(--border);">
          <div class="text-xs" style="color: var(--muted);">Uložené dny</div>
          <div class="text-lg font-bold mt-1" style="color: var(--text);">
            <?= (int)$summarySavedDays ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: color-mix(in srgb, var(--attendance-vacation) 60%, var(--bg)); border: 1px solid var(--border);">
          <div class="text-xs" style="color: var(--muted);">Dovolená</div>
          <div class="text-lg font-bold mt-1" style="color: var(--text);">
            <?= (int)$summarySpecial['D'] ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: color-mix(in srgb, var(--attendance-ocr) 60%, var(--bg)); border: 1px solid var(--border);">
          <div class="text-xs" style="color: var(--muted);">OČR</div>
          <div class="text-lg font-bold mt-1" style="color: var(--text);">
            <?= (int)$summarySpecial['O'] ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: color-mix(in srgb, var(--attendance-sick) 60%, var(--bg)); border: 1px solid var(--border);">
          <div class="text-xs" style="color: var(--muted);">PN</div>
          <div class="text-lg font-bold mt-1" style="color: var(--text);">
            <?= (int)$summarySpecial['PN'] ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: color-mix(in srgb, var(--attendance-holiday) 60%, var(--bg)); border: 1px solid var(--border);">
          <div class="text-xs" style="color: var(--muted);">Svátek</div>
          <div class="text-lg font-bold mt-1" style="color: var(--text);">
            <?= (int)$summarySpecial['S'] ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <?php foreach ($days as $index => $day): ?>
      <?php
        $dateObj = $day['date'];
        $dateKey = $day['date_key'];
        $record = $day['record'] ?? null;
        $isFuture = !empty($day['is_future']);
        $isToday = ($dateKey === $todayKey);

        $specialCode = (string)($record['special_code'] ?? '');
        $arrivalTime = attendance_value_time($record['arrival_time'] ?? '');
        $departureTime = attendance_value_time($record['departure_time'] ?? '');
        $lunchFrom = attendance_value_time($record['lunch_from'] ?? '');
        $lunchTo = attendance_value_time($record['lunch_to'] ?? '');
        $breakFrom = attendance_value_time($record['break_from'] ?? '');
        $breakTo = attendance_value_time($record['break_to'] ?? '');
        $note = (string)($record['note'] ?? '');
        $workedMinutes = (int)($record['worked_minutes'] ?? 0);

        $isExisting = !empty($record);
        $timeDisabled = $isFuture || $specialCode !== '';

        $dayLabel = $czDays[(int)$dateObj->format('N')] . ' ' . $dateObj->format('d. m. Y');
        $statusLabel = attendance_special_label($specialCode);

        $headerBg = 'var(--card)';
        if ($isFuture) {
            $headerBg = 'color-mix(in srgb, var(--border) 20%, transparent)';
        } elseif ($specialCode === 'D') {
            $headerBg = 'color-mix(in srgb, var(--attendance-vacation) 18%, var(--card))';
        } elseif ($specialCode === 'O') {
            $headerBg = 'color-mix(in srgb, var(--attendance-ocr) 18%, var(--card))';
        } elseif ($specialCode === 'PN') {
            $headerBg = 'color-mix(in srgb, var(--attendance-sick) 18%, var(--card))';
        } elseif ($specialCode === 'S') {
            $headerBg = 'color-mix(in srgb, var(--attendance-holiday) 18%, var(--card))';
        } elseif ($isToday) {
            $headerBg = 'color-mix(in srgb, var(--primary) 8%, var(--card))';
        }

        $prevRecord = null;
        for ($j = $index - 1; $j >= 0; $j--) {
            if (!empty($days[$j]['record']) && empty($days[$j]['record']['special_code'])) {
                $prevRecord = $days[$j]['record'];
                break;
            }
        }

        $prevCopy = null;
        if ($prevRecord) {
            $prevCopy = [
                'arrival_time'   => attendance_value_time($prevRecord['arrival_time'] ?? ''),
                'departure_time' => attendance_value_time($prevRecord['departure_time'] ?? ''),
                'lunch_from'     => attendance_value_time($prevRecord['lunch_from'] ?? ''),
                'lunch_to'       => attendance_value_time($prevRecord['lunch_to'] ?? ''),
                'break_from'     => attendance_value_time($prevRecord['break_from'] ?? ''),
                'break_to'       => attendance_value_time($prevRecord['break_to'] ?? ''),
            ];
        }

        $prevCopyJson = $prevCopy ? htmlspecialchars(json_encode($prevCopy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) : '';
        $isOpenDefault = $isToday;
      ?>

      <div class="rounded-2xl shadow overflow-hidden attendance-card"
           style="background: var(--card); border: 1px solid var(--border);">

        <button type="button"
                class="w-full text-left px-5 py-4 flex items-center justify-between gap-4 attendance-toggle"
                style="background: <?= $headerBg ?>;">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <div class="font-semibold" style="color: var(--text);">
                <?= htmlspecialchars($dayLabel) ?>
              </div>

              <?php if ($isToday): ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                      style="background: color-mix(in srgb, var(--primary) 16%, transparent); color: var(--text); border: 1px solid var(--border);">
                  Dnes
                </span>
              <?php endif; ?>

              <?php if ($isFuture): ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                      style="background: color-mix(in srgb, var(--border) 25%, transparent); color: var(--muted); border: 1px solid var(--border);">
                  Budoucí den
                </span>
              <?php elseif ($specialCode !== ''): ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                      style="background: var(--bg); color: var(--text); border: 1px solid var(--border);">
                  <?= htmlspecialchars($statusLabel) ?>
                </span>
              <?php elseif ($isExisting): ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                      style="background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--text); border: 1px solid var(--border);">
                  Uloženo
                </span>
              <?php else: ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                      style="background: color-mix(in srgb, var(--error) 10%, transparent); color: var(--text); border: 1px solid var(--border);">
                  Bez záznamu
                </span>
              <?php endif; ?>
            </div>

            <div class="text-sm mt-1" style="color: var(--muted);">
              <?= $specialCode !== '' ? htmlspecialchars($statusLabel) : 'Běžná docházka' ?>
            </div>
          </div>

          <div class="flex items-center gap-4 shrink-0">
            <div class="text-right">
              <div class="text-xs" style="color: var(--muted);">Odpracováno</div>
              <div class="text-lg font-bold attendance-worked-display" style="color: var(--text);">
                <?= attendance_worked_human($workedMinutes) ?>
              </div>
            </div>

            <div class="attendance-chevron text-xl leading-none" style="color: var(--muted);">
              <?= $isOpenDefault ? '−' : '+' ?>
            </div>
          </div>
        </button>

        <div class="attendance-panel <?= $isOpenDefault ? '' : 'hidden' ?>">
          <form method="POST"
                action="/attendance/day"
                class="p-5 space-y-4 attendance-day-form"
                data-default-special="<?= htmlspecialchars($defaultWorkedSpecial) ?>"
                <?= $prevCopyJson !== '' ? 'data-prev-copy="' . $prevCopyJson . '"' : '' ?>>
            <?= \Core\CSRF::field() ?>
            <input type="hidden" name="_month" value="<?= htmlspecialchars($monthCurrent) ?>">
            <input type="hidden" name="work_date" value="<?= htmlspecialchars($dateKey) ?>">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Speciální</label>
                <select name="special_code"
                        class="attendance-special w-full px-4 py-3 rounded-lg"
                        <?= $isFuture ? 'disabled' : '' ?>
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                  <option value=""   <?= $specialCode === '' ? 'selected' : '' ?>>--- Nic</option>
                  <option value="D"  <?= $specialCode === 'D' ? 'selected' : '' ?>>D - Dovolená</option>
                  <option value="O"  <?= $specialCode === 'O' ? 'selected' : '' ?>>O - Ošetřování člena rodiny</option>
                  <option value="PN" <?= $specialCode === 'PN' ? 'selected' : '' ?>>PN - Pracovní neschopnost</option>
                  <option value="S"  <?= $specialCode === 'S' ? 'selected' : '' ?>>S - Svátek</option>
                </select>
              </div>

              <div class="flex items-end">
                <?php if (!$isFuture && $prevCopyJson !== ''): ?>
                  <button type="button"
                          class="attendance-copy-prev px-4 py-3 rounded-lg font-semibold"
                          style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                    Zkopírovat časy z předchozího dne
                  </button>
                <?php endif; ?>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Příchod</label>
                <input type="time"
                       name="arrival_time"
                       value="<?= htmlspecialchars($arrivalTime) ?>"
                       step="<?= (int)$timeStepSeconds ?>"
                       class="attendance-time w-full px-4 py-3 rounded-lg"
                       <?= $timeDisabled ? 'disabled' : '' ?>
                       data-future="<?= $isFuture ? '1' : '0' ?>"
                       style="background: var(--bg); border: 1px solid var(--border); color: var(--text); <?= $specialCode !== '' ? 'opacity:.65;' : '' ?>">
              </div>

              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Odchod</label>
                <input type="time"
                       name="departure_time"
                       value="<?= htmlspecialchars($departureTime) ?>"
                       step="<?= (int)$timeStepSeconds ?>"
                       class="attendance-time w-full px-4 py-3 rounded-lg"
                       <?= $timeDisabled ? 'disabled' : '' ?>
                       data-future="<?= $isFuture ? '1' : '0' ?>"
                       style="background: var(--bg); border: 1px solid var(--border); color: var(--text); <?= $specialCode !== '' ? 'opacity:.65;' : '' ?>">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Oběd - od</label>
                <input type="time"
                       name="lunch_from"
                       value="<?= htmlspecialchars($lunchFrom) ?>"
                       step="<?= (int)$timeStepSeconds ?>"
                       class="attendance-time w-full px-4 py-3 rounded-lg"
                       <?= $timeDisabled ? 'disabled' : '' ?>
                       data-future="<?= $isFuture ? '1' : '0' ?>"
                       style="background: var(--bg); border: 1px solid var(--border); color: var(--text); <?= $specialCode !== '' ? 'opacity:.65;' : '' ?>">
              </div>

              <div>
                <label class="block text-sm mb-1" style="color: var(--text);">Oběd - do</label>
                <input type="time"
                       name="lunch_to"
                       value="<?= htmlspecialchars($lunchTo) ?>"
                       step="<?= (int)$timeStepSeconds ?>"
                       class="attendance-time w-full px-4 py-3 rounded-lg"
                       <?= $timeDisabled ? 'disabled' : '' ?>
                       data-future="<?= $isFuture ? '1' : '0' ?>"
                       style="background: var(--bg); border: 1px solid var(--border); color: var(--text); <?= $specialCode !== '' ? 'opacity:.65;' : '' ?>">
              </div>
            </div>

            <?php if ($attendanceAllowBreak): ?>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm mb-1" style="color: var(--text);">Přestávka - od</label>
                  <input type="time"
                         name="break_from"
                         value="<?= htmlspecialchars($breakFrom) ?>"
                         step="<?= (int)$timeStepSeconds ?>"
                         class="attendance-time w-full px-4 py-3 rounded-lg"
                         <?= $timeDisabled ? 'disabled' : '' ?>
                         data-future="<?= $isFuture ? '1' : '0' ?>"
                         style="background: var(--bg); border: 1px solid var(--border); color: var(--text); <?= $specialCode !== '' ? 'opacity:.65;' : '' ?>">
                </div>

                <div>
                  <label class="block text-sm mb-1" style="color: var(--text);">Přestávka - do</label>
                  <input type="time"
                         name="break_to"
                         value="<?= htmlspecialchars($breakTo) ?>"
                         step="<?= (int)$timeStepSeconds ?>"
                         class="attendance-time w-full px-4 py-3 rounded-lg"
                         <?= $timeDisabled ? 'disabled' : '' ?>
                         data-future="<?= $isFuture ? '1' : '0' ?>"
                         style="background: var(--bg); border: 1px solid var(--border); color: var(--text); <?= $specialCode !== '' ? 'opacity:.65;' : '' ?>">
                </div>
              </div>
            <?php endif; ?>

            <div>
              <label class="block text-sm mb-1" style="color: var(--text);">Poznámka</label>
              <input type="text"
                     name="note"
                     value="<?= htmlspecialchars($note) ?>"
                     class="w-full px-4 py-3 rounded-lg"
                     <?= $isFuture ? 'disabled' : '' ?>
                     style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2"
                 style="border-top: 1px solid var(--border);">
              <div>
                <div class="text-sm" style="color: var(--muted);">Odpracováno</div>
                <div class="text-2xl font-bold attendance-worked"
                     style="color: var(--text);">
                  <?= attendance_worked_human($workedMinutes) ?>
                </div>
              </div>

              <div>
                <?php if ($isFuture): ?>
                  <span class="text-sm" style="color: var(--muted);">
                    Budoucí dny zatím nelze upravovat.
                  </span>
                <?php else: ?>
                  <button type="submit"
                          class="<?= $isExisting ? 'btn-secondary' : 'btn-primary' ?> px-5 py-3 rounded-lg font-semibold">
                    <?= $isExisting ? 'Upravit záznam' : 'Uložit záznam' ?>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function () {
  function timeToMinutes(value) {
    if (!value || !/^\d{2}:\d{2}$/.test(value)) return null;
    const parts = value.split(':');
    return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
  }

  function minutesToHuman(minutes) {
    if (minutes === null || minutes < 0) return '00:00';
    var h = Math.floor(minutes / 60);
    var m = minutes % 60;
    return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
  }

  function updateWorkedInForm(form, text) {
    var big = form.querySelector('.attendance-worked');
    if (big) big.textContent = text;

    var card = form.closest('.attendance-card');
    var header = card ? card.querySelector('.attendance-worked-display') : null;
    if (header) header.textContent = text;
  }

  function updateTimeInputsState(form) {
    var special = form.querySelector('.attendance-special');
    var specialValue = special ? special.value : '';
    var timeInputs = form.querySelectorAll('.attendance-time');
    var submitButton = form.querySelector('button[type="submit"]');
    var isFuture = !submitButton;

    timeInputs.forEach(function (input) {
      if (specialValue !== '') {
        input.value = '';
      }

      input.disabled = isFuture || specialValue !== '';
      input.style.opacity = (specialValue !== '') ? '0.65' : '1';
    });
  }

  function updateRow(form) {
    var special = form.querySelector('.attendance-special');
    var specialValue = special ? special.value : '';
    var defaultSpecial = form.dataset.defaultSpecial || '00:00';

    updateTimeInputsState(form);

    if (specialValue !== '') {
      updateWorkedInForm(form, defaultSpecial);
      return;
    }

    var arrival = (form.querySelector('input[name="arrival_time"]') || {}).value || '';
    var departure = (form.querySelector('input[name="departure_time"]') || {}).value || '';
    var lunchFrom = (form.querySelector('input[name="lunch_from"]') || {}).value || '';
    var lunchTo = (form.querySelector('input[name="lunch_to"]') || {}).value || '';
    var breakFrom = (form.querySelector('input[name="break_from"]') || {}).value || '';
    var breakTo = (form.querySelector('input[name="break_to"]') || {}).value || '';

    var arrivalMin = timeToMinutes(arrival);
    var departureMin = timeToMinutes(departure);

    if (arrival === '' && departure === '') {
      updateWorkedInForm(form, '00:00');
      return;
    }

    if (arrivalMin === null || departureMin === null || departureMin < arrivalMin) {
      updateWorkedInForm(form, '00:00');
      return;
    }

    var worked = departureMin - arrivalMin;

    var lunchFromMin = timeToMinutes(lunchFrom);
    var lunchToMin = timeToMinutes(lunchTo);
    if (lunchFrom !== '' && lunchTo !== '' && lunchFromMin !== null && lunchToMin !== null && lunchToMin >= lunchFromMin) {
      worked -= (lunchToMin - lunchFromMin);
    }

    var breakFromMin = timeToMinutes(breakFrom);
    var breakToMin = timeToMinutes(breakTo);
    if (breakFrom !== '' && breakTo !== '' && breakFromMin !== null && breakToMin !== null && breakToMin >= breakFromMin) {
      worked -= (breakToMin - breakFromMin);
    }

    if (worked < 0) worked = 0;
    updateWorkedInForm(form, minutesToHuman(worked));
  }

  function closeAllCards(exceptCard) {
    document.querySelectorAll('.attendance-card').forEach(function (card) {
      if (exceptCard && card === exceptCard) return;

      var panel = card.querySelector('.attendance-panel');
      var chevron = card.querySelector('.attendance-chevron');

      if (panel && !panel.classList.contains('hidden')) {
        panel.classList.add('hidden');
      }
      if (chevron) {
        chevron.textContent = '+';
      }
    });
  }

  document.querySelectorAll('.attendance-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('.attendance-card');
      var panel = card.querySelector('.attendance-panel');
      var chevron = card.querySelector('.attendance-chevron');
      var isHidden = panel.classList.contains('hidden');

      closeAllCards(isHidden ? card : null);

      if (isHidden) {
        panel.classList.remove('hidden');
        chevron.textContent = '−';
      } else {
        panel.classList.add('hidden');
        chevron.textContent = '+';
      }
    });
  });

  document.querySelectorAll('.attendance-day-form').forEach(function (form) {
    updateRow(form);

    form.querySelectorAll('.attendance-special, .attendance-time').forEach(function (input) {
      input.addEventListener('change', function () { updateRow(form); });
      input.addEventListener('input', function () { updateRow(form); });
    });

    var copyBtn = form.querySelector('.attendance-copy-prev');
    if (copyBtn) {
      copyBtn.addEventListener('click', function () {
        var raw = form.dataset.prevCopy || '';
        if (!raw) return;

        var prev;
        try {
          prev = JSON.parse(raw);
        } catch (e) {
          return;
        }

        var special = form.querySelector('.attendance-special');
        if (special) {
          special.value = '';
        }

        var mapping = [
          'arrival_time',
          'departure_time',
          'lunch_from',
          'lunch_to',
          'break_from',
          'break_to'
        ];

        mapping.forEach(function (name) {
          var input = form.querySelector('input[name="' + name + '"]');
          if (input && typeof prev[name] !== 'undefined') {
            input.value = prev[name] || '';
          }
        });

        updateRow(form);
      });
    }
  });
})();
</script>
