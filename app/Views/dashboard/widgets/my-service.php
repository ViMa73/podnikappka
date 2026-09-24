<?php
$todayObj = new \DateTimeImmutable('now');
$tomorrowObj = new \DateTimeImmutable('tomorrow');

$czDays = [
    1 => 'Pondělí',
    2 => 'Úterý',
    3 => 'Středa',
    4 => 'Čtvrtek',
    5 => 'Pátek',
    6 => 'Sobota',
    7 => 'Neděle',
];
?>

<div class="space-y-4">
  <div>
    <div class="text-sm font-semibold" style="color: var(--text);">
      <?= $czDays[(int)$todayObj->format('N')] ?> <?= $todayObj->format('j.n.Y') ?>
    </div>

    <?php if (!empty($myServicesToday)): ?>
      <div class="mt-2 space-y-2">
        <?php foreach ($myServicesToday as $service): ?>
          <div class="px-3 py-2 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <div class="font-semibold">
              <?= htmlspecialchars($service['place_name']) ?>
            </div>

            <?php if (!empty($service['description'])): ?>
              <div class="text-xs mt-1" style="color: var(--muted);">
                <?= htmlspecialchars($service['description']) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="mt-2 text-sm" style="color: var(--muted);">
        Dnes nemáš žádnou službu.
      </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="text-sm font-semibold" style="color: var(--text);">
      <?= $czDays[(int)$tomorrowObj->format('N')] ?> <?= $tomorrowObj->format('j.n.Y') ?>
    </div>

    <?php if (!empty($myServicesTomorrow)): ?>
      <div class="mt-2 space-y-2">
        <?php foreach ($myServicesTomorrow as $service): ?>
          <div class="px-3 py-2 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <div class="font-semibold">
              <?= htmlspecialchars($service['place_name']) ?>
            </div>

            <?php if (!empty($service['description'])): ?>
              <div class="text-xs mt-1" style="color: var(--muted);">
                <?= htmlspecialchars($service['description']) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="mt-2 text-sm" style="color: var(--muted);">
        Zítra nemáš žádnou službu.
      </div>
    <?php endif; ?>
  </div>
</div>
