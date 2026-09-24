<div class="rounded-2xl shadow p-6"
     style="background: var(--card); border: 1px solid var(--border);">

  <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Přehled všech dovolených</h2>
  <p class="text-sm mb-6" style="color: var(--muted);">
    Seznam všech dovolených v rámci firmy.
  </p>

  <?php if (empty($vacations)): ?>
    <div class="text-sm italic" style="color: var(--muted);">
      Zatím tu nejsou žádné dovolené.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr style="color: var(--muted); border-bottom: 1px solid var(--border);">
            <th class="text-left py-3 pr-4">Uživatel</th>
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
                <?= htmlspecialchars(trim(($vacation['first_name'] ?? '') . ' ' . ($vacation['last_name'] ?? ''))) ?>
              </td>
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
          <a href="/vacations/all?page=<?= $i ?>"
             class="px-3 py-2 rounded-lg text-sm"
             style="background: <?= $i === $page ? 'color-mix(in srgb, var(--primary) 14%, var(--bg))' : 'var(--bg)' ?>; border: 1px solid var(--border); color: var(--text);">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>