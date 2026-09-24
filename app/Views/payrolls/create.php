<div class="max-w-2xl mx-auto">
  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <h1 class="text-2xl font-semibold mb-2" style="color: var(--text);">Nová výplata</h1>
    <p class="text-sm mb-6" style="color: var(--muted);">
      Vytvoří rozpracovanou výplatu za zvolený měsíc pro všechny zaměstnance zahrnuté do výplat.
    </p>

    <form method="POST" action="/payrolls/create" class="space-y-4">
      <?= \Core\CSRF::field() ?>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm" style="color: var(--text);">Rok</label>
          <input type="number"
                 name="year"
                 min="2000"
                 max="2100"
                 value="<?= date('Y') ?>"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Měsíc</label>
          <select name="month"
                  class="mt-1 w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= $m === (int)date('n') ? 'selected' : '' ?>>
                <?= sprintf('%02d', $m) ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="flex gap-3 pt-3">
        <a href="/payrolls"
           class="px-5 py-3 rounded-lg font-semibold"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          Zpět
        </a>

        <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
          Vytvořit výplatu
        </button>
      </div>
    </form>
  </div>
</div>
