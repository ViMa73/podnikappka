<?php
  $billingSettings = $billingSettings ?? [];
?>

<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold" style="color: var(--text);">Platební údaje</h1>
    <p class="text-sm mt-1" style="color: var(--muted);">
      Nastavení bankovního účtu, ceny ročního předplatného a platební poznámky.
    </p>
  </div>

  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <form method="POST" action="/admin/billing/settings" class="space-y-4">
      <?= \Core\CSRF::field() ?>

      <div>
        <label class="text-sm" style="color: var(--text);">Příjemce</label>
        <input name="receiver_name"
               value="<?= htmlspecialchars((string)($billingSettings['receiver_name'] ?? '')) ?>"
               required
               class="mt-1 w-full px-4 py-3 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm" style="color: var(--text);">Číslo účtu</label>
          <input name="bank_account"
                 value="<?= htmlspecialchars((string)($billingSettings['bank_account'] ?? '')) ?>"
                 required
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Kód banky</label>
          <input name="bank_code"
                 value="<?= htmlspecialchars((string)($billingSettings['bank_code'] ?? '')) ?>"
                 required
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm" style="color: var(--text);">Cena na 1 rok</label>
          <input name="yearly_price"
                 type="number"
                 min="0"
                 step="0.01"
                 value="<?= htmlspecialchars((string)($billingSettings['yearly_price'] ?? '')) ?>"
                 required
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Měna</label>
          <input name="currency"
                 value="<?= htmlspecialchars((string)($billingSettings['currency'] ?? 'CZK')) ?>"
                 required
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>
      </div>

      <div>
        <label class="text-sm" style="color: var(--text);">Zpráva pro příjemce</label>
        <input name="payment_note"
               value="<?= htmlspecialchars((string)($billingSettings['payment_note'] ?? 'Roční předplatné Podnikappka')) ?>"
               class="mt-1 w-full px-4 py-3 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>

      <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
        Uložit platební údaje
      </button>
    </form>
  </div>

  <?php if (!empty($billingSettings)): ?>
    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Aktuální náhled</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="rounded-xl p-4"
             style="background: var(--bg); border: 1px solid var(--border);">
          <div class="text-sm mb-1" style="color: var(--muted);">Příjemce</div>
          <div class="font-semibold" style="color: var(--text);">
            <?= htmlspecialchars((string)($billingSettings['receiver_name'] ?? '')) ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: var(--bg); border: 1px solid var(--border);">
          <div class="text-sm mb-1" style="color: var(--muted);">Účet</div>
          <div class="font-semibold" style="color: var(--text);">
            <?= htmlspecialchars((string)($billingSettings['bank_account'] ?? '')) ?>/<?= htmlspecialchars((string)($billingSettings['bank_code'] ?? '')) ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: var(--bg); border: 1px solid var(--border);">
          <div class="text-sm mb-1" style="color: var(--muted);">Roční cena</div>
          <div class="font-semibold" style="color: var(--text);">
            <?= htmlspecialchars(number_format((float)($billingSettings['yearly_price'] ?? 0), 2, ',', ' ')) ?>
            <?= htmlspecialchars((string)($billingSettings['currency'] ?? 'CZK')) ?>
          </div>
        </div>

        <div class="rounded-xl p-4"
             style="background: var(--bg); border: 1px solid var(--border);">
          <div class="text-sm mb-1" style="color: var(--muted);">Zpráva pro příjemce</div>
          <div class="font-semibold" style="color: var(--text);">
            <?= htmlspecialchars((string)($billingSettings['payment_note'] ?? '')) ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
