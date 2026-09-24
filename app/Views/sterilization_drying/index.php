<?php
function sd_type_label(string $type): string
{
    return $type === 'drying' ? 'Sušení' : 'Sterilizace';
}
?>

<div class="space-y-6">

  <!-- NOVÝ ZÁZNAM -->
  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Nový záznam</h2>
    <p class="text-sm mb-6" style="color: var(--muted);">
      Vytvoření nového záznamu sterilizace nebo sušení.
    </p>

    <form method="POST" action="/sterilization-drying/create" class="space-y-4" id="sd-create-form">
      <?= \Core\CSRF::field() ?>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Místo</label>
          <select name="place_id"
                  id="sd-create-place"
                  class="w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  required>
            <option value="">Vyber místo</option>
            <?php foreach ($places as $place): ?>
              <option value="<?= (int)$place['id'] ?>"
                      data-is-sterilizer="<?= (int)($place['is_sterilizer'] ?? 0) ?>"
                      data-is-drying="<?= (int)($place['is_drying'] ?? 0) ?>">
                <?= htmlspecialchars($place['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Datum</label>
          <input type="date"
                 name="record_date"
                 value="<?= date('Y-m-d') ?>"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Druh</label>
          <select name="record_type"
                  id="sd-create-type"
                  class="w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  required>
            <option value="">Nejdřív vyber místo</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Předmět sterilizace / sušení</label>
          <input type="text"
                 name="item_name"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Množství (ks)</label>
          <input type="number"
                 name="quantity"
                 min="0"
                 value="0"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Doba (min)</label>
          <input type="number"
                 name="duration_minutes"
                 min="0"
                 value="0"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Teplota (°C)</label>
          <input type="number"
                 name="temperature_c"
                 step="0.01"
                 value="0"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>
      </div>

      <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
        Uložit záznam
      </button>
    </form>
  </div>

  <!-- KARTY PODLE MÍST -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php foreach ($places as $place): ?>
      <?php
        $placeId = (int)$place['id'];
        $placeRecords = $recordsByPlace[$placeId] ?? [];
        $isSterilizer = (int)($place['is_sterilizer'] ?? 0) === 1;
        $isDrying = (int)($place['is_drying'] ?? 0) === 1;
      ?>

      <div class="rounded-2xl shadow p-6"
           style="background: var(--card); border: 1px solid var(--border);">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <h3 class="text-lg font-semibold" style="color: var(--text);">
              <?= htmlspecialchars($place['name']) ?>
            </h3>
            <div class="flex flex-wrap gap-2 mt-2 text-xs">
              <span class="px-2 py-1 rounded"
                    style="background: <?= $isSterilizer ? 'color-mix(in srgb, var(--primary) 18%, transparent)' : 'color-mix(in srgb, var(--border) 30%, transparent)' ?>;
                           color: <?= $isSterilizer ? 'var(--text)' : 'var(--muted)' ?>;
                           border: 1px solid var(--border);">
                Sterilizátor: <?= $isSterilizer ? 'ON' : 'OFF' ?>
              </span>
              <span class="px-2 py-1 rounded"
                    style="background: <?= $isDrying ? 'color-mix(in srgb, var(--primary) 18%, transparent)' : 'color-mix(in srgb, var(--border) 30%, transparent)' ?>;
                           color: <?= $isDrying ? 'var(--text)' : 'var(--muted)' ?>;
                           border: 1px solid var(--border);">
                Sušárna: <?= $isDrying ? 'ON' : 'OFF' ?>
              </span>
            </div>
          </div>
        </div>

        <?php if (empty($placeRecords)): ?>
          <div class="text-sm italic" style="color: var(--muted);">
            Pro toto místo zatím nejsou žádné záznamy.
          </div>
        <?php else: ?>
          <div class="space-y-4">
            <?php foreach ($placeRecords as $record): ?>
              <?php
                $recordId = (int)$record['id'];
                $authorName = trim(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? ''));
              ?>

              <div class="sd-record p-4 rounded-xl"
                   style="background: var(--bg); border: 1px solid var(--border);">
                <!-- VIEW -->
                <div class="view space-y-3">
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <div class="font-semibold" style="color: var(--text);">
                        <?= htmlspecialchars(sd_type_label((string)$record['record_type'])) ?>
                      </div>
                      <div class="text-sm" style="color: var(--muted);">
                        <?= htmlspecialchars(date('j.n.Y', strtotime($record['record_date']))) ?>
                      </div>
                    </div>

                    <div class="flex gap-3 shrink-0">
                      <button type="button"
                              class="edit-btn text-sm hover:underline"
                              style="color: var(--primary);">
                        Upravit
                      </button>

                      <form method="POST" action="/sterilization-drying/delete/<?= $recordId ?>"
                            onsubmit="return confirm('Opravdu chceš smazat tento záznam?');">
                        <?= \Core\CSRF::field() ?>
                        <button type="submit"
                                class="text-sm hover:underline"
                                style="color: #dc2626;">
                          Smazat
                        </button>
                      </form>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div style="color: var(--text);"><span style="color: var(--muted);">Předmět:</span> <?= htmlspecialchars($record['item_name']) ?></div>
                    <div style="color: var(--text);"><span style="color: var(--muted);">Množství:</span> <?= (int)$record['quantity'] ?> ks</div>
                    <div style="color: var(--text);"><span style="color: var(--muted);">Doba:</span> <?= (int)$record['duration_minutes'] ?> min</div>
                    <div style="color: var(--text);"><span style="color: var(--muted);">Teplota:</span> <?= number_format((float)$record['temperature_c'], 2, ',', ' ') ?> °C</div>
                    <div class="md:col-span-2" style="color: var(--text);">
                      <span style="color: var(--muted);">Uložil:</span> <?= htmlspecialchars($authorName) ?>
                    </div>
                  </div>
                </div>

                <!-- EDIT -->
                <form method="POST"
                      action="/sterilization-drying/update/<?= $recordId ?>"
                      class="edit hidden mt-4 space-y-4">
                  <?= \Core\CSRF::field() ?>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm mb-1" style="color: var(--text);">Místo</label>
                      <select name="place_id"
                              class="sd-edit-place w-full px-4 py-3 rounded-lg"
                              style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                              required>
                        <?php foreach ($places as $p): ?>
                          <option value="<?= (int)$p['id'] ?>"
                                  data-is-sterilizer="<?= (int)($p['is_sterilizer'] ?? 0) ?>"
                                  data-is-drying="<?= (int)($p['is_drying'] ?? 0) ?>"
                                  <?= (int)$p['id'] === (int)$record['place_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm mb-1" style="color: var(--text);">Datum</label>
                      <input type="date"
                             name="record_date"
                             value="<?= htmlspecialchars($record['record_date']) ?>"
                             class="w-full px-4 py-3 rounded-lg"
                             style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                             required>
                    </div>

                    <div>
                      <label class="block text-sm mb-1" style="color: var(--text);">Druh</label>
                      <select name="record_type"
                              class="sd-edit-type w-full px-4 py-3 rounded-lg"
                              style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                              data-current-type="<?= htmlspecialchars($record['record_type']) ?>"
                              required>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm mb-1" style="color: var(--text);">Předmět sterilizace / sušení</label>
                      <input type="text"
                             name="item_name"
                             value="<?= htmlspecialchars($record['item_name']) ?>"
                             class="w-full px-4 py-3 rounded-lg"
                             style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                             required>
                    </div>

                    <div>
                      <label class="block text-sm mb-1" style="color: var(--text);">Množství (ks)</label>
                      <input type="number"
                             name="quantity"
                             min="0"
                             value="<?= (int)$record['quantity'] ?>"
                             class="w-full px-4 py-3 rounded-lg"
                             style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                             required>
                    </div>

                    <div>
                      <label class="block text-sm mb-1" style="color: var(--text);">Doba (min)</label>
                      <input type="number"
                             name="duration_minutes"
                             min="0"
                             value="<?= (int)$record['duration_minutes'] ?>"
                             class="w-full px-4 py-3 rounded-lg"
                             style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                             required>
                    </div>

                    <div>
                      <label class="block text-sm mb-1" style="color: var(--text);">Teplota (°C)</label>
                      <input type="number"
                             name="temperature_c"
                             step="0.01"
                             value="<?= htmlspecialchars(number_format((float)$record['temperature_c'], 2, '.', '')) ?>"
                             class="w-full px-4 py-3 rounded-lg"
                             style="background: var(--card); border: 1px solid var(--border); color: var(--text);"
                             required>
                    </div>
                  </div>

                  <div class="flex gap-3">
                    <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm">
                      Uložit
                    </button>
                    <button type="button" class="cancel-btn text-sm hover:underline" style="color: var(--muted);">
                      Zrušit
                    </button>
                  </div>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function () {
  function fillTypeOptions(placeSelect, typeSelect, currentType) {
    if (!placeSelect || !typeSelect) return;

    const selected = placeSelect.options[placeSelect.selectedIndex];
    const isSterilizer = selected ? selected.getAttribute('data-is-sterilizer') === '1' : false;
    const isDrying = selected ? selected.getAttribute('data-is-drying') === '1' : false;

    const options = [];
    if (isSterilizer) {
      options.push({ value: 'sterilization', label: 'Sterilizace' });
    }
    if (isDrying) {
      options.push({ value: 'drying', label: 'Sušení' });
    }

    typeSelect.innerHTML = '';

    if (options.length === 0) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'Místo nemá povolený žádný druh';
      typeSelect.appendChild(opt);
      return;
    }

    options.forEach((item, index) => {
      const opt = document.createElement('option');
      opt.value = item.value;
      opt.textContent = item.label;
      if (currentType) {
        opt.selected = item.value === currentType;
      } else if (index === 0) {
        opt.selected = true;
      }
      typeSelect.appendChild(opt);
    });
  }

  const createPlace = document.getElementById('sd-create-place');
  const createType = document.getElementById('sd-create-type');

  if (createPlace && createType) {
    fillTypeOptions(createPlace, createType, '');
    createPlace.addEventListener('change', () => fillTypeOptions(createPlace, createType, ''));
  }

  document.querySelectorAll('.sd-record').forEach((row) => {
    const editBtn = row.querySelector('.edit-btn');
    const cancelBtn = row.querySelector('.cancel-btn');
    const view = row.querySelector('.view');
    const edit = row.querySelector('.edit');

    const placeSelect = row.querySelector('.sd-edit-place');
    const typeSelect = row.querySelector('.sd-edit-type');

    if (placeSelect && typeSelect) {
      fillTypeOptions(placeSelect, typeSelect, typeSelect.getAttribute('data-current-type') || '');
      placeSelect.addEventListener('change', () => fillTypeOptions(placeSelect, typeSelect, typeSelect.value || ''));
    }

    if (editBtn && view && edit) {
      editBtn.addEventListener('click', () => {
        view.classList.add('hidden');
        edit.classList.remove('hidden');
      });
    }

    if (cancelBtn && view && edit) {
      cancelBtn.addEventListener('click', () => {
        edit.classList.add('hidden');
        view.classList.remove('hidden');
      });
    }
  });
})();
</script>