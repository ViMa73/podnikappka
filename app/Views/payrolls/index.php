<?php
  $canManage = (\Core\Auth::role() === 'owner' || \Core\Auth::role() === 'manager');
?>

<div class="space-y-6">
  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold" style="color: var(--text);">Výplaty</h1>
        <p class="text-sm mt-1" style="color: var(--muted);">
          Přehled výplat a podkladů pro účetní.
        </p>
      </div>

      <?php if ($canManage): ?>
        <a href="/payrolls/create"
           class="btn-primary px-5 py-3 rounded-lg font-semibold">
          Nová výplata
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <?php if (empty($periods)): ?>
      <div class="text-sm italic p-4 rounded-xl"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
        Zatím nejsou vytvořené žádné výplaty.
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($periods as $row): ?>
          <a href="/payrolls/<?= (int)$row['id'] ?>"
             class="block rounded-xl p-4"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="font-semibold">
                  <?= sprintf('%02d/%04d', (int)$row['month'], (int)$row['year']) ?>
                </div>
                <div class="text-sm mt-1" style="color: var(--muted);">
                  Stav:
                  <?= ($row['status'] ?? '') === 'approved' ? 'Schváleno' : 'Rozpracováno' ?>
                  <?php if (isset($row['items_count'])): ?>
                    • zaměstnanců: <?= (int)$row['items_count'] ?>
                  <?php endif; ?>
                </div>
              </div>

              <?php if (isset($row['gross_total_amount'])): ?>
                <div class="text-right">
                  <div class="font-semibold"><?= number_format((float)$row['gross_total_amount'], 2, ',', ' ') ?> Kč</div>
                  <div class="text-sm mt-1" style="color: var(--muted);">Moje výplata</div>
                </div>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>