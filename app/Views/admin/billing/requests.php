<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold" style="color: var(--text);">Čekající platby</h1>
    <p class="text-sm mt-1" style="color: var(--muted);">
      Přehled žádostí o upgrade nebo prodloužení předplatného, které čekají na ruční schválení.
    </p>
  </div>

  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <?php if (empty($billingRequests)): ?>
      <div class="text-sm italic p-4 rounded-xl"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
        Nejsou zde žádné čekající platby.
      </div>
    <?php else: ?>
      <div class="overflow-auto">
        <table class="w-full text-sm">
          <thead>
            <tr style="color: var(--muted); border-bottom: 1px solid var(--border);">
              <th class="text-left py-3 pr-4">Firma</th>
              <th class="text-left py-3 pr-4">Typ</th>
              <th class="text-left py-3 pr-4">Částka</th>
              <th class="text-left py-3 pr-4">VS</th>
              <th class="text-left py-3 pr-4">Žadatel</th>
              <th class="text-left py-3 pr-4">Vytvořeno</th>
              <th class="text-left py-3">Akce</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($billingRequests as $row): ?>
              <?php
                $userLabel = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                if ($userLabel === '') {
                    $userLabel = (string)($row['email'] ?? '—');
                }
              ?>
              <tr style="border-bottom: 1px solid var(--border); color: var(--text);">
                <td class="py-3 pr-4 align-top">
                  <div class="font-semibold"><?= htmlspecialchars($row['company_name'] ?? '') ?></div>
                  <div class="text-xs mt-1" style="color: var(--muted);">
                    IČO: <?= htmlspecialchars((string)($row['company_ico'] ?? '—')) ?>
                  </div>
                </td>

                <td class="py-3 pr-4 align-top">
                  <?= ($row['type'] ?? '') === 'renewal' ? 'Prodloužení' : 'Upgrade' ?>
                </td>

                <td class="py-3 pr-4 align-top">
                  <?= htmlspecialchars(number_format((float)($row['amount'] ?? 0), 2, ',', ' ')) ?>
                  <?= htmlspecialchars((string)($row['currency'] ?? 'CZK')) ?>
                </td>

                <td class="py-3 pr-4 align-top">
                  <span class="font-semibold"><?= htmlspecialchars((string)($row['variable_symbol'] ?? '')) ?></span>
                </td>

                <td class="py-3 pr-4 align-top">
                  <div><?= htmlspecialchars($userLabel) ?></div>
                  <div class="text-xs mt-1" style="color: var(--muted);">
                    <?= htmlspecialchars((string)($row['email'] ?? '')) ?>
                  </div>
                </td>

                <td class="py-3 pr-4 align-top">
                  <?= htmlspecialchars((string)($row['created_at'] ?? '')) ?>
                </td>

                <td class="py-3 align-top">
                  <form method="POST" action="/admin/billing/requests/approve"
                        onsubmit="return confirm('Opravdu chceš tuto platbu schválit a aktivovat předplatné?');">
                    <?= \Core\CSRF::field() ?>
                    <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">

                    <button class="btn-primary px-4 py-2 rounded-lg font-semibold">
                      Schválit
                    </button>
                  </form>
                </td>
              </tr>

              <?php if (!empty($row['message_for_receiver']) || !empty($row['note'])): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                  <td colspan="7" class="pb-4 pt-0">
                    <div class="rounded-xl p-4"
                         style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
                      <?php if (!empty($row['message_for_receiver'])): ?>
                        <div><strong style="color: var(--text);">Zpráva:</strong> <?= htmlspecialchars((string)$row['message_for_receiver']) ?></div>
                      <?php endif; ?>

                      <?php if (!empty($row['note'])): ?>
                        <div class="mt-2"><strong style="color: var(--text);">Poznámka:</strong> <?= nl2br(htmlspecialchars((string)$row['note'])) ?></div>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
