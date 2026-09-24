<?php
$attendanceRecord = $dashboardAttendance ?? null;
$attendancePrev = $dashboardAttendancePrev ?? null;

$specialCode = (string)($attendanceRecord['special_code'] ?? '');
$arrivalTime = !empty($attendanceRecord['arrival_time']) ? substr((string)$attendanceRecord['arrival_time'], 0, 5) : '';
$departureTime = !empty($attendanceRecord['departure_time']) ? substr((string)$attendanceRecord['departure_time'], 0, 5) : '';
$lunchFrom = !empty($attendanceRecord['lunch_from']) ? substr((string)$attendanceRecord['lunch_from'], 0, 5) : '';
$lunchTo = !empty($attendanceRecord['lunch_to']) ? substr((string)$attendanceRecord['lunch_to'], 0, 5) : '';
$breakFrom = !empty($attendanceRecord['break_from']) ? substr((string)$attendanceRecord['break_from'], 0, 5) : '';
$breakTo = !empty($attendanceRecord['break_to']) ? substr((string)$attendanceRecord['break_to'], 0, 5) : '';
$attendanceNote = (string)($attendanceRecord['note'] ?? '');
$attendanceWorkedMinutes = (int)($attendanceRecord['worked_minutes'] ?? 0);

$prevPayload = '';
if (!empty($attendancePrev)) {
    $prevPayload = htmlspecialchars(json_encode([
        'arrival_time' => !empty($attendancePrev['arrival_time']) ? substr((string)$attendancePrev['arrival_time'], 0, 5) : '',
        'departure_time' => !empty($attendancePrev['departure_time']) ? substr((string)$attendancePrev['departure_time'], 0, 5) : '',
        'lunch_from' => !empty($attendancePrev['lunch_from']) ? substr((string)$attendancePrev['lunch_from'], 0, 5) : '',
        'lunch_to' => !empty($attendancePrev['lunch_to']) ? substr((string)$attendancePrev['lunch_to'], 0, 5) : '',
        'break_from' => !empty($attendancePrev['break_from']) ? substr((string)$attendancePrev['break_from'], 0, 5) : '',
        'break_to' => !empty($attendancePrev['break_to']) ? substr((string)$attendancePrev['break_to'], 0, 5) : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
}

$dashboardDefaultWorked = sprintf(
    '%02d:%02d',
    floor((int)$dashboardAttendanceUserWorkloadMinutes / 60),
    (int)$dashboardAttendanceUserWorkloadMinutes % 60
);
?>

<form method="POST"
      action="/dashboard/attendance-today"
      class="space-y-3 js-dashboard-attendance-form"
      data-default-special="<?= htmlspecialchars($dashboardDefaultWorked) ?>"
      <?= $prevPayload !== '' ? 'data-prev-copy="' . $prevPayload . '"' : '' ?>>
  <?= \Core\CSRF::field() ?>
  <input type="hidden" name="work_date" value="<?= date('Y-m-d') ?>">

  <div class="text-sm font-semibold" style="color: var(--text);">
    <?= (new \DateTimeImmutable('now'))->format('j.n.Y') ?>
  </div>

  <div>
    <label class="block text-xs mb-1" style="color: var(--muted);">Speciální</label>
    <select name="special_code"
            class="js-dashboard-attendance-special w-full px-3 py-2 rounded-lg text-sm"
            style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      <option value="" <?= $specialCode === '' ? 'selected' : '' ?>>--- Nic</option>
      <option value="D" <?= $specialCode === 'D' ? 'selected' : '' ?>>D - Dovolená</option>
      <option value="O" <?= $specialCode === 'O' ? 'selected' : '' ?>>O - Ošetřování člena rodiny</option>
      <option value="PN" <?= $specialCode === 'PN' ? 'selected' : '' ?>>PN - Pracovní neschopnost</option>
      <option value="S" <?= $specialCode === 'S' ? 'selected' : '' ?>>S - Svátek</option>
    </select>
  </div>

  <?php if ($prevPayload !== ''): ?>
    <button type="button"
            class="js-dashboard-copy-prev px-3 py-2 rounded-lg text-sm font-semibold"
            style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      Zkopírovat časy z předchozího dne
    </button>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs mb-1" style="color: var(--muted);">Příchod</label>
      <input type="time"
             name="arrival_time"
             value="<?= htmlspecialchars($arrivalTime) ?>"
             step="<?= (int)$dashboardAttendanceTimeStepSeconds ?>"
             class="js-dashboard-attendance-time w-full px-3 py-2 rounded-lg text-sm"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
    </div>
    <div>
      <label class="block text-xs mb-1" style="color: var(--muted);">Odchod</label>
      <input type="time"
             name="departure_time"
             value="<?= htmlspecialchars($departureTime) ?>"
             step="<?= (int)$dashboardAttendanceTimeStepSeconds ?>"
             class="js-dashboard-attendance-time w-full px-3 py-2 rounded-lg text-sm"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs mb-1" style="color: var(--muted);">Oběd - od</label>
      <input type="time"
             name="lunch_from"
             value="<?= htmlspecialchars($lunchFrom) ?>"
             step="<?= (int)$dashboardAttendanceTimeStepSeconds ?>"
             class="js-dashboard-attendance-time w-full px-3 py-2 rounded-lg text-sm"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
    </div>
    <div>
      <label class="block text-xs mb-1" style="color: var(--muted);">Oběd - do</label>
      <input type="time"
             name="lunch_to"
             value="<?= htmlspecialchars($lunchTo) ?>"
             step="<?= (int)$dashboardAttendanceTimeStepSeconds ?>"
             class="js-dashboard-attendance-time w-full px-3 py-2 rounded-lg text-sm"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
    </div>
  </div>

  <?php if (!empty($attendanceAllowBreak)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs mb-1" style="color: var(--muted);">Přestávka - od</label>
        <input type="time"
               name="break_from"
               value="<?= htmlspecialchars($breakFrom) ?>"
               step="<?= (int)$dashboardAttendanceTimeStepSeconds ?>"
               class="js-dashboard-attendance-time w-full px-3 py-2 rounded-lg text-sm"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>
      <div>
        <label class="block text-xs mb-1" style="color: var(--muted);">Přestávka - do</label>
        <input type="time"
               name="break_to"
               value="<?= htmlspecialchars($breakTo) ?>"
               step="<?= (int)$dashboardAttendanceTimeStepSeconds ?>"
               class="js-dashboard-attendance-time w-full px-3 py-2 rounded-lg text-sm"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>
    </div>
  <?php endif; ?>

  <div>
    <label class="block text-xs mb-1" style="color: var(--muted);">Poznámka</label>
    <input type="text"
           name="note"
           value="<?= htmlspecialchars($attendanceNote) ?>"
           class="w-full px-3 py-2 rounded-lg text-sm"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
  </div>

  <div class="pt-3 flex items-center justify-between"
       style="border-top: 1px solid var(--border);">
    <div>
      <div class="text-xs" style="color: var(--muted);">Odpracováno</div>
      <div class="text-xl font-bold js-dashboard-attendance-worked" style="color: var(--text);">
        <?= sprintf('%02d:%02d', floor($attendanceWorkedMinutes / 60), $attendanceWorkedMinutes % 60) ?>
      </div>
    </div>

    <button type="submit"
            class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
      <?= !empty($attendanceRecord) ? 'Upravit' : 'Uložit' ?>
    </button>
  </div>
</form>
