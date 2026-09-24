<?php
$todayStr = date('Y-m-d');
?>

<div class="space-y-6">

  <!-- TABULKA -->
  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-xl font-semibold mb-2" style="color: var(--text);">Teploty</h2>
    <p class="text-sm mb-4" style="color: var(--muted);">
      Klikni do buňky, napiš hodnotu a stiskni Enter. Hodnota se uloží a kurzor skočí o řádek níž.
    </p>

    <div class="overflow-x-auto">
      <table class="min-w-[900px] w-full text-sm border-separate text-center" style="border-spacing:0;">
        <thead>
          <tr>
            <th class="sticky left-0 z-10 text-left px-4 py-3 whitespace-nowrap"
                style="background: var(--card); color: var(--muted); border: 1px solid var(--border);">
              Místo / typ
            </th>

            <?php foreach ($dates as $d): ?>
              <th class="px-4 py-3 whitespace-nowrap"
                  style="color: var(--muted); border: 1px solid var(--border);">
                <?= htmlspecialchars($d['day_name']) ?>
                <?= htmlspecialchars($d['label']) ?>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($tableRows)): ?>
            <tr>
              <td colspan="<?= 1 + count($dates) ?>" class="px-4 py-4"
                  style="color: var(--muted); border: 1px solid var(--border);">
                Nemáš založený žádný teploměr. Nejdřív ho vytvoř v nastavení.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tableRows as $rowIndex => $row): ?>
              <?php
                $placeId = (int)$row['place_id'];
                $recordType = (string)$row['record_type'];
              ?>
              <tr>
                <td class="sticky left-0 z-10 px-4 py-3 text-left align-middle"
                    style="background: var(--card); border: 1px solid var(--border);">
                  <div class="font-semibold" style="color: var(--text);">
                    <?= htmlspecialchars($row['label']) ?>
                  </div>
                </td>

                <?php foreach ($dates as $colIndex => $d): ?>
                  <?php
                    $dateY = $d['dateY'];
                    $record = $records[$placeId][$recordType][$dateY] ?? null;
                    $hasValue = is_array($record);
                    $value = $hasValue ? number_format((float)$record['value'], 2, '.', '') : '';

                    $cellBg = $hasValue
                      ? 'color-mix(in srgb, var(--taken) 60%, transparent)'
                      : 'color-mix(in srgb, var(--free) 60%, transparent)';
                  ?>
                  <td class="px-2 py-2 align-middle"
                      style="border: 1px solid var(--border); background: <?= $cellBg ?>;">

                    <input type="text"
                           value="<?= htmlspecialchars($value) ?>"
                           onclick="console.log('clicked input')"
                           data-row-index="<?= $rowIndex ?>"
                           data-col-index="<?= $colIndex ?>"
                           data-place-id="<?= $placeId ?>"
                           data-record-type="<?= htmlspecialchars($recordType) ?>"
                           data-record-date="<?= htmlspecialchars($dateY) ?>"
                           class="js-temp-inline w-full px-2 py-2 rounded-lg text-center text-sm"
                           style="background: rgba(255,255,255,0.3); border: 1px solid var(--border); color: var(--text);">
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- FORMULÁŘ -->
  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <h3 class="text-lg font-semibold mb-2" style="color: var(--text);">Doplnit hodnotu</h3>
    <p class="text-sm mb-6" style="color: var(--muted);">
      Tady můžeš uložit hodnotu i pro datum, které už není v tabulce posledních 7 dní.
    </p>

    <form method="POST" action="/temperatures/save" class="space-y-4">
      <?= \Core\CSRF::field() ?>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Datum</label>
          <input type="date"
                 name="record_date"
                 value="<?= htmlspecialchars($todayStr) ?>"
                 max="<?= htmlspecialchars($todayStr) ?>"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Místo</label>

          <select name="place_key"
                  class="w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  required>

            <option value="">Vyber místo</option>

            <?php foreach ($places as $place): ?>
              <option value="<?= (int)$place['id'] ?>|temperature">
                <?= htmlspecialchars($place['name']) ?> – teplota
              </option>

              <?php if ((int)$place['humidity_enabled'] === 1): ?>
                <option value="<?= (int)$place['id'] ?>|humidity">
                  <?= htmlspecialchars($place['name']) ?> – vlhkost
                </option>
              <?php endif; ?>

            <?php endforeach; ?>

          </select>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Hodnota</label>
          <input type="number"
                 name="value_c"
                 step="0.01"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>
      </div>

      <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
        Uložit hodnotu
      </button>
    </form>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const inputs = document.querySelectorAll('.js-temp-inline');
  const csrfInput = document.querySelector('input[name="_csrf"]');
  const csrf = csrfInput ? csrfInput.value : '';

  let isSaving = false;

  function focusNextRow(currentInput) {
    const rowIndex = parseInt(currentInput.getAttribute('data-row-index') || '-1', 10);
    const colIndex = parseInt(currentInput.getAttribute('data-col-index') || '-1', 10);

    if (rowIndex < 0 || colIndex < 0) return;

    const next = document.querySelector(
      '.js-temp-inline[data-row-index="' + (rowIndex + 1) + '"][data-col-index="' + colIndex + '"]'
    );

    if (next) {
      next.focus();
      next.select();
    }
  }

  function setCellGreen(input) {
    const td = input.closest('td');
    if (td) td.style.background = 'color-mix(in srgb, var(--taken) 60%, transparent)';
  }

  function setCellRed(input) {
    const td = input.closest('td');
    if (td) td.style.background = 'color-mix(in srgb, var(--free) 60%, transparent)';
  }

  function setSaving(input, saving) {
    const td = input.closest('td');
    if (td) td.style.opacity = saving ? '0.7' : '1';
  }

  async function saveInline(input) {
    const formData = new FormData();
    formData.append('_csrf', csrf);
    formData.append('place_id', input.getAttribute('data-place-id') || '');
    formData.append('record_type', input.getAttribute('data-record-type') || '');
    formData.append('record_date', input.getAttribute('data-record-date') || '');
    formData.append('value_c', (input.value || '').trim().replace(',', '.'));

    const response = await fetch('/temperatures/save-inline', {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const text = await response.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      throw new Error('Server nevrátil JSON.');
    }

    if (!response.ok || !data || !data.ok) {
      throw new Error(data && data.message ? data.message : 'Chyba při ukládání.');
    }

    input.value = data.formatted_raw ? data.formatted_raw : input.value;
    setCellGreen(input);
  }

  inputs.forEach(function (input) {

    input.addEventListener('focus', function () {
      input.select();
    });

    input.addEventListener('keydown', async function (e) {
      if (e.key !== 'Enter') return;

      e.preventDefault();

      if (isSaving) return;

      const value = (input.value || '').trim();
      if (value === '') {
        alert('Zadej číslo.');
        return;
      }

      try {
        isSaving = true;
        input.disabled = true;
        setSaving(input, true);

        await saveInline(input);

        input.disabled = false;
        setSaving(input, false);

        focusNextRow(input);
      } catch (err) {
        input.disabled = false;
        setSaving(input, false);
        setCellRed(input);
        alert(err.message || 'Chyba při ukládání.');
      } finally {
        isSaving = false;
      }
    });

    input.addEventListener('blur', async function () {
      if (isSaving) return;

      const value = (input.value || '').trim();
      if (value === '') return;

      try {
        isSaving = true;
        input.disabled = true;
        setSaving(input, true);

        await saveInline(input);

        input.disabled = false;
        setSaving(input, false);
      } catch (err) {
        input.disabled = false;
        setSaving(input, false);
        setCellRed(input);
        alert(err.message || 'Chyba při ukládání.');
      } finally {
        isSaving = false;
      }
    });

  });

});
</script>
