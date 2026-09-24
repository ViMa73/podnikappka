<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 w-full">

  <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Moje dovolené</h2>
    <p class="text-sm mb-6" style="color: var(--muted);">
      Seznam všech tvých dovolených.
    </p>

    <?php if (empty($vacations)): ?>
      <div class="text-sm italic" style="color: var(--muted);">
        Zatím nemáš žádnou dovolenou.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr style="color: var(--muted); border-bottom: 1px solid var(--border);">
              <th class="text-left py-3 pr-4">Název</th>
              <th class="text-left py-3 pr-4">Od</th>
              <th class="text-left py-3 pr-4">Do</th>
              <th class="text-left py-3">Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($vacations as $vacation): ?>
              <tr style="border-bottom: 1px solid var(--border);">
                <td class="py-3 pr-4" style="color: var(--text);">
                  <?= htmlspecialchars($vacation['title']) ?>
                </td>
                <td class="py-3 pr-4" style="color: var(--text);">
                  <?= (new DateTime($vacation['date_from']))->format('d. m. Y') ?>
                </td>
                <td class="py-3 pr-4" style="color: var(--text);">
                  <?= (new DateTime($vacation['date_to']))->format('d. m. Y') ?>
                </td>
                <td class="py-3">
                  <a href="/vacations/<?= (int)$vacation['id'] ?>"
                     class="px-3 py-2 rounded-lg text-sm font-semibold"
                     style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                    Detail
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <div class="flex flex-wrap gap-2 mt-6">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="/vacations?page=<?= $i ?>"
               class="px-3 py-2 rounded-lg text-sm"
               style="background: <?= $i === $page ? 'color-mix(in srgb, var(--primary) 14%, var(--bg))' : 'var(--bg)' ?>; border: 1px solid var(--border); color: var(--text);">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Nová dovolená</h2>
    <p class="text-sm mb-6" style="color: var(--muted);">
      Pozor! Dovolená která je přes přelom měsíce nebo roku musí být rozdělena.
    </p>

    <form method="POST" action="/vacations" class="space-y-4">
      <?= \Core\CSRF::field() ?>

      <div>
        <label class="text-sm" style="color: var(--text);">Název</label>
        <input name="title" required
               class="mt-1 w-full px-4 py-3 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm" style="color: var(--text);">Počáteční datum</label>
          <input type="date" name="date_from" required
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Koncové datum</label>
          <input type="date" name="date_to" required
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>
      </div>

      <div>
        <label class="text-sm" style="color: var(--text);">Celkem hodin</label>
        <input name="total_hours"
               class="mt-1 w-full px-4 py-3 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>
      <p class="text-sm mb-6" style="color: var(--muted);">
        Počet pracovních dní * denní úvazek.
      </p>

      <div>
        <label class="text-sm" style="color: var(--text);">Poznámka</label>
        <textarea name="note" rows="4"
                  class="mt-1 w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"></textarea>
      </div>

      <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
        Uložit dovolenou
      </button>
    </form>
  </div>

</div>