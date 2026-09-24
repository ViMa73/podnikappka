<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

  <!-- LEVÁ ČÁST -->
  <div class="lg:col-span-3 space-y-6">

    <!-- KARTA 2 -->
    <div class="rounded-2xl shadow p-6"
         style="background:var(--card);border:1px solid var(--border);">

      <h2 class="text-xl font-semibold mb-4" style="color:var(--text)">
        Přidat nový svoz
      </h2>

      <form method="POST" action="/waste-reports/create">
        <?= \Core\CSRF::field() ?>

        <div class="grid md:grid-cols-2 gap-4">

          <div>
            <label class="text-sm">Datum</label>
            <input type="date"
                   name="collection_date"
                   required
                   class="w-full px-4 py-2 rounded-lg"
                   style="background:var(--bg);border:1px solid var(--border)">
          </div>

          <div>
            <label class="text-sm">Místo svozu</label>
            <select name="place_id"
                    class="w-full px-4 py-2 rounded-lg"
                    style="background:var(--bg);border:1px solid var(--border)">
              <?php foreach($places as $p): ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['pharmacy_name']) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>

          <div>
            <label class="text-sm">
              20 01 31 – nebezpečná cytostatika (t)
            </label>
            <input type="number"
                   step="0.001"
                   name="waste_200131"
                   value="0"
                   class="w-full px-4 py-2 rounded-lg"
                   style="background:var(--bg);border:1px solid var(--border)">
          </div>

          <div>
            <label class="text-sm">
              20 01 32 – léky od občanů (t)
            </label>
            <input type="number"
                   step="0.001"
                   name="waste_200132"
                   value="0"
                   class="w-full px-4 py-2 rounded-lg"
                   style="background:var(--bg);border:1px solid var(--border)">
          </div>

        </div>

        <button class="btn-primary px-5 py-3 rounded-lg mt-4">
          Uložit svoz
        </button>

      </form>

    </div>





    <!-- KARTA 1 -->
    <div class="rounded-2xl shadow p-6"
         style="background:var(--card);border:1px solid var(--border);">

      <h2 class="text-xl font-semibold mb-4" style="color:var(--text)">
        Seznam svozů
      </h2>

      <table class="w-full text-sm">
        <thead>
          <tr style="color:var(--muted)">
            <th class="text-left py-2">Datum</th>
            <th class="text-left py-2">Místo</th>
            <th class="text-left py-2">20 01 31</th>
            <th class="text-left py-2">20 01 32</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
        <?php foreach ($collections as $c): ?>
          <tr style="border-top:1px solid var(--border)">
            <td class="py-2"><?= date('d.m.Y',strtotime($c['collection_date'])) ?></td>
            <td><?= htmlspecialchars($c['pharmacy_name']) ?></td>
            <td><?= number_format($c['waste_200131'],3,","," ") ?> t</td>
            <td><?= number_format($c['waste_200132'],3,","," ") ?> t</td>
            <td>
              <button type="button"
                      class="text-sm js-open-edit-collection hover:underline"
                      style="color: var(--primary);"
                      data-id="<?= (int)$c['id'] ?>"
                      data-date="<?= htmlspecialchars($c['collection_date']) ?>"
                      data-place-id="<?= (int)$c['place_id'] ?>"
                      data-w131="<?= htmlspecialchars(number_format((float)$c['waste_200131'], 3, '.', '')) ?>"
                      data-w132="<?= htmlspecialchars(number_format((float)$c['waste_200132'], 3, '.', '')) ?>">
                Upravit
              </button>
              <form method="POST"
                    action="/waste-reports/delete/<?= (int)$c['id'] ?>"
                    onsubmit="return confirm('Opravdu chceš smazat tento svoz?');"
                    style="display:inline">
                <?= \Core\CSRF::field() ?>

                <button type="submit"
                        class="text-sm hover:underline"
                        style="color:#dc2626;">
                  Smazat svoz
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div id="editCollectionModal"
           class="fixed inset-0 z-50 hidden items-center justify-center"
           aria-hidden="true">

        <div class="absolute inset-0"
             style="background: rgba(0,0,0,.45);"
             data-close-edit-collection="1"></div>

        <div class="relative w-full max-w-2xl mx-4 rounded-2xl shadow-xl p-6"
             style="background: var(--card); border: 1px solid var(--border);">

          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold" style="color: var(--text);">Upravit svoz</h3>
              <p class="text-sm mt-1" style="color: var(--muted);">
                Změna data, místa a množství odpadu.
              </p>
            </div>

            <button type="button"
                    class="px-3 py-2 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                    data-close-edit-collection="1"
                    aria-label="Zavřít">✕</button>
          </div>

          <form method="POST" action="" class="mt-5 space-y-4" id="editCollectionForm">
            <?= \Core\CSRF::field() ?>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm" style="color: var(--text);">Datum</label>
                <input type="date"
                       name="collection_date"
                       id="editCollectionDate"
                       required
                       class="w-full px-4 py-2 rounded-lg mt-1"
                       style="background:var(--bg);border:1px solid var(--border);color:var(--text);">
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">Místo svozu</label>
                <select name="place_id"
                        id="editCollectionPlaceId"
                        class="w-full px-4 py-2 rounded-lg mt-1"
                        style="background:var(--bg);border:1px solid var(--border);color:var(--text);">
                  <?php foreach ($places as $p): ?>
                    <option value="<?= (int)$p['id'] ?>">
                      <?= htmlspecialchars($p['pharmacy_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">20 01 31 (t)</label>
                <input type="number"
                       step="0.001"
                       name="waste_200131"
                       id="editCollectionW131"
                       class="w-full px-4 py-2 rounded-lg mt-1"
                       style="background:var(--bg);border:1px solid var(--border);color:var(--text);">
              </div>

              <div>
                <label class="text-sm" style="color: var(--text);">20 01 32 (t)</label>
                <input type="number"
                       step="0.001"
                       name="waste_200132"
                       id="editCollectionW132"
                       class="w-full px-4 py-2 rounded-lg mt-1"
                       style="background:var(--bg);border:1px solid var(--border);color:var(--text);">
              </div>
            </div>

            <div class="flex gap-3 justify-end pt-2">
              <button type="button"
                      class="px-5 py-3 rounded-lg font-semibold"
                      style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                      data-close-edit-collection="1">
                Zrušit
              </button>

              <button type="submit"
                      class="btn-primary px-5 py-3 rounded-lg font-semibold">
                Uložit změny
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>


  </div>


  <!-- PRAVÝ PANEL (STICKY EXPORT) -->
  <div class="lg:col-span-1">

    <div class="sticky rounded-2xl shadow p-6"
         style="background:var(--card);border:1px solid var(--border);">

      <h2 class="text-lg font-semibold mb-4" style="color:var(--text)">
        Export hlášení
      </h2>

      <form method="GET" action="/waste-reports/export" target="_blank">

        <div class="space-y-4">

          <div>
            <label class="text-sm">Kvartál</label>
            <select name="quarter"
                    class="w-full px-4 py-2 rounded-lg"
                    style="background:var(--bg);border:1px solid var(--border)">
              <option value="1">1. kvartál</option>
              <option value="2">2. kvartál</option>
              <option value="3">3. kvartál</option>
              <option value="4">4. kvartál</option>
            </select>
          </div>

          <div>
            <label class="text-sm">Rok</label>
            <select name="year"
                    class="w-full px-4 py-2 rounded-lg"
                    style="background:var(--bg);border:1px solid var(--border)">
              <?php foreach ($exportYears as $y): ?>
                <option value="<?= (int)$y ?>" <?= (int)$y === (int)date('Y') ? 'selected' : '' ?>>
                  <?= (int)$y ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="text-sm">Místo</label>
            <select name="place_id"
                    class="w-full px-4 py-2 rounded-lg"
                    style="background:var(--bg);border:1px solid var(--border)">
              <?php foreach($places as $p): ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['pharmacy_name']) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>

        </div>

        <button class="btn-primary w-full mt-4 py-3 rounded-lg">
          Export PDF
        </button>

      </form>

    </div>

  </div>

</div>

<script>
(function () {
  const modal = document.getElementById('editCollectionModal');
  const form = document.getElementById('editCollectionForm');
  if (!modal || !form) return;

  const dateEl = document.getElementById('editCollectionDate');
  const placeEl = document.getElementById('editCollectionPlaceId');
  const w131El = document.getElementById('editCollectionW131');
  const w132El = document.getElementById('editCollectionW132');

  function openModal(btn) {
    const id = btn.getAttribute('data-id') || '';
    const deleteid = btn.getAttribute('data-delete') || '';
    const date = btn.getAttribute('data-date') || '';
    const placeId = btn.getAttribute('data-place-id') || '';
    const w131 = btn.getAttribute('data-w131') || '0.000';
    const w132 = btn.getAttribute('data-w132') || '0.000';

    form.action = '/waste-reports/update/' + id;
    dateEl.value = date;
    placeEl.value = placeId;
    w131El.value = w131;
    w132El.value = w132;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('.js-open-edit-collection');
    if (openBtn) {
      e.preventDefault();
      openModal(openBtn);
      return;
    }

    const closeBtn = e.target && e.target.getAttribute('data-close-edit-collection') === '1';
    if (closeBtn && !modal.classList.contains('hidden')) {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
})();
</script>
