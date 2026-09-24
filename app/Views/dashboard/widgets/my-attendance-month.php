<?php
$data = $myAttendanceMonthWidget ?? null;

if (!$data):
?>
  <div class="text-sm" style="color: var(--muted);">
    Docházku se nepodařilo vyhodnotit.
  </div>
<?php
  return;
endif;

$status = $data['status'] ?? 'ok';

$badgeBg = 'var(--bg)';
$badgeColor = 'var(--text)';
$accentBg = 'var(--bg)';
$accentBorder = 'var(--border)';
$accentText = 'var(--text)';

if ($status === 'plus') {
    $badgeBg = 'var(--success-bg, rgba(34,197,94,0.12))';
    $badgeColor = 'var(--success, #16a34a)';
    $accentBg = 'var(--success-bg, rgba(34,197,94,0.12))';
    $accentBorder = 'var(--success, #16a34a)';
    $accentText = 'var(--success, #16a34a)';
} elseif ($status === 'minus') {
    $badgeBg = 'var(--error-bg, rgba(239,68,68,0.12))';
    $badgeColor = 'var(--error, #dc2626)';
    $accentBg = 'var(--error-bg, rgba(239,68,68,0.12))';
    $accentBorder = 'var(--error, #dc2626)';
    $accentText = 'var(--error, #dc2626)';
} else {
    $badgeBg = 'var(--bg)';
    $badgeColor = 'var(--text)';
    $accentBg = 'var(--bg)';
    $accentBorder = 'var(--border)';
    $accentText = 'var(--text)';
}
?>

<div class="space-y-4">
  <div class="flex items-start justify-between gap-3">
    <div>
      <div class="text-sm font-semibold" style="color: var(--text);">
        <?= htmlspecialchars($data['month_label']) ?>
      </div>
      <div class="text-xs mt-1" style="color: var(--muted);">
        Počítáno k <?= htmlspecialchars($data['today_label']) ?>
      </div>
    </div>

    <div class="px-3 py-1 rounded-full text-xs font-semibold"
         style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border: 1px solid <?= $accentBorder ?>;">
      <?= htmlspecialchars($data['status_text']) ?>
    </div>
  </div>

  <div class="rounded-xl p-4"
       style="background: <?= $accentBg ?>; border: 1px solid <?= $accentBorder ?>;">
    <div class="text-xs uppercase tracking-wide mb-1" style="color: var(--muted);">
      Saldo
    </div>
    <div class="text-3xl font-bold" style="color: <?= $accentText ?>;">
      <?= htmlspecialchars($data['balance_label']) ?>
    </div>
  </div>

  <div class="grid grid-cols-2 gap-3">
    <div class="rounded-xl p-4"
         style="background: var(--bg); border: 1px solid var(--border);">
      <div class="text-xs uppercase tracking-wide mb-1" style="color: var(--muted);">
        Odpracováno
      </div>
      <div class="text-xl font-bold" style="color: var(--text);">
        <?= htmlspecialchars($data['worked_label']) ?>
      </div>
    </div>

    <div class="rounded-xl p-4"
         style="background: var(--bg); border: 1px solid var(--border);">
      <div class="text-xs uppercase tracking-wide mb-1" style="color: var(--muted);">
        Má být
      </div>
      <div class="text-xl font-bold" style="color: var(--text);">
        <?= htmlspecialchars($data['expected_label']) ?>
      </div>
    </div>
  </div>

  <div class="text-sm leading-6" style="color: var(--muted);">
    Do dneška je započítáno
    <strong style="color: var(--text);"><?= (int)$data['expected_days'] ?></strong>
    pracovních dnů po–pá,
    denní úvazek
    <strong style="color: var(--text);"><?= htmlspecialchars($data['workload_label']) ?></strong>.
  </div>
</div>
