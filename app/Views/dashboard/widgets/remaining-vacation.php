<div class="space-y-3">
  <div class="flex items-center justify-between">
    <span class="text-sm" style="color: var(--muted);">Rok</span>
    <span class="font-semibold" style="color: var(--text);">
      <?= (int)$currentYear ?>
    </span>
  </div>

  <div class="flex items-center justify-between">
    <span class="text-sm" style="color: var(--muted);">Nárok</span>
    <span class="font-semibold" style="color: var(--text);">
      <?= number_format((float)$vacationEntitlementYear, 2, ',', ' ') ?> h
    </span>
  </div>

  <div class="flex items-center justify-between">
    <span class="text-sm" style="color: var(--muted);">Vyčerpáno</span>
    <span class="font-semibold" style="color: var(--text);">
      <?= number_format((float)$vacationUsedYear, 2, ',', ' ') ?> h
    </span>
  </div>

  <div class="pt-3 mt-3 flex items-center justify-between"
       style="border-top: 1px solid var(--border);">
    <span class="text-sm font-semibold" style="color: var(--text);">Zbývá</span>
    <span class="text-2xl font-bold" style="color: var(--text);">
      <?= number_format((float)$vacationRemainingYear, 2, ',', ' ') ?> h
    </span>
  </div>
</div>
