<?php
$previousMonth = (new \DateTimeImmutable('first day of last month'))->format('Y-m');
?>

<div id="exportsCsrfHolder" class="hidden">
  <?= \Core\CSRF::field() ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

  <?php if (!empty($mealVoucherExportEnabled)): ?>
    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <h2 class="text-xl font-semibold mb-2" style="color: var(--text);">
        Stravenky
      </h2>

      <?php if (empty($attendanceEnabled)): ?>
        <div class="rounded-xl px-4 py-3"
             style="background: color-mix(in srgb, var(--error-notification, #fecaca) 45%, transparent); border: 1px solid var(--border); color: var(--text);">
          Export stravenek nelze použít, protože není zapnutý modul Docházka.
        </div>
      <?php else: ?>
        <p class="text-sm mb-6" style="color: var(--muted);">
          Nárok vzniká za dny, kdy zaměstnanec odpracoval alespoň
          <?= htmlspecialchars(number_format((float)$mealVoucherMinHours, 2, ',', ' ')) ?> hodin
          a zároveň nejde o speciální den.
        </p>

        <form id="mealVoucherExportForm" class="space-y-4">
          <div>
            <label class="block text-sm mb-1" style="color: var(--text);">Měsíc</label>
            <input type="month"
                   id="mealVoucherMonth"
                   value="<?= htmlspecialchars($previousMonth) ?>"
                   class="w-full px-4 py-3 rounded-lg"
                   style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          </div>

          <button type="submit"
                  class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Export
          </button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($temperaturesEnabled)): ?>
    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <h2 class="text-xl font-semibold mb-2" style="color: var(--text);">
        Teploty
      </h2>

      <p class="text-sm mb-6" style="color: var(--muted);">
        Vyber měsíc a zařízení, která chceš zahrnout do exportu pro tisk nebo PDF.
      </p>

      <form method="GET" action="/exports/temperatures/print" target="_blank" class="space-y-4">
        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Měsíc</label>
          <input type="month"
                 name="month"
                 value="<?= htmlspecialchars($previousMonth) ?>"
                 class="w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <div class="block text-sm mb-2" style="color: var(--text);">Teploměry a vlhkoměry</div>

          <?php if (empty($temperatureExportOptions)): ?>
            <div class="text-sm italic p-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
              Nemáš založený žádný teploměr ani vlhkoměr.
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 max-h-72 overflow-auto pr-1">
              <?php foreach ($temperatureExportOptions as $option): ?>
                <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg"
                       style="background: var(--bg); border: 1px solid var(--border);">
                  <input type="checkbox"
                         name="place_keys[]"
                         value="<?= htmlspecialchars($option['key']) ?>"
                         class="mt-1 h-4 w-4"
                         checked>

                  <div class="text-sm" style="color: var(--text);">
                    <?= htmlspecialchars($option['label']) ?>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <button class="btn-primary px-5 py-3 rounded-lg font-semibold"
                <?= empty($temperatureExportOptions) ? 'disabled' : '' ?>>
          Exportovat
        </button>
      </form>
    </div>
  <?php endif; ?>

</div>

<!-- MODAL: STRAVENKY -->
<div id="mealVoucherModal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     aria-hidden="true">

  <div class="absolute inset-0"
       style="background: rgba(0,0,0,.45);"
       data-close-meal-voucher-modal="1"></div>

  <div class="relative w-full max-w-3xl mx-4 rounded-2xl shadow-xl p-6 max-h-[85vh] overflow-hidden"
       style="background: var(--card); border: 1px solid var(--border);">

    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 class="text-lg font-semibold" style="color: var(--text);">Export stravenek</h3>
        <p id="mealVoucherModalSubheading" class="text-sm mt-1" style="color: var(--muted);">
          Přehled nároků za vybraný měsíc.
        </p>
      </div>

      <button type="button"
              class="px-3 py-2 rounded-lg"
              style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
              data-close-meal-voucher-modal="1"
              aria-label="Zavřít">✕</button>
    </div>

    <div class="mt-5 overflow-auto max-h-[65vh]">
      <div id="mealVoucherModalSummary"
           class="mb-4 p-4 rounded-xl text-sm"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"></div>

      <div id="mealVoucherModalContent"></div>
    </div>
  </div>
</div>

<script>
(function () {
  const csrf = document.querySelector('#exportsCsrfHolder input[name="_csrf"]')?.value || '';
  const form = document.getElementById('mealVoucherExportForm');
  const monthInput = document.getElementById('mealVoucherMonth');

  const modal = document.getElementById('mealVoucherModal');
  const modalSubheading = document.getElementById('mealVoucherModalSubheading');
  const modalSummary = document.getElementById('mealVoucherModalSummary');
  const modalContent = document.getElementById('mealVoucherModalContent');

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function openModal() {
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  async function postForm(url, data) {
    const formData = new FormData();
    Object.keys(data).forEach((key) => formData.append(key, data[key]));
    formData.append('_csrf', csrf);

    const res = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const text = await res.text();
    let json = {};

    try {
      json = text ? JSON.parse(text) : {};
    } catch (e) {
      throw new Error('Server nevrátil platnou odpověď.');
    }

    if (!res.ok || json.ok === false) {
      throw new Error(json.message || 'Chyba komunikace se serverem.');
    }

    return json;
  }

  function renderMealVoucherResult(data) {
    modalSubheading.textContent = `Přehled nároků za ${data.month_label}.`;

    modalSummary.innerHTML = `
      <div><strong>Měsíc:</strong> ${escapeHtml(data.month_label)}</div>
      <div class="mt-1"><strong>Minimální odpracovaná doba:</strong> ${escapeHtml(data.min_hours)} h</div>
      <div class="mt-1"><strong>Celkem stravenek:</strong> ${Number(data.total_vouchers || 0)}</div>
    `;

    if (!data.items || !data.items.length) {
      modalContent.innerHTML = `
        <div class="text-sm italic p-4 rounded-xl"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Nebyli nalezeni žádní pracovníci.
        </div>
      `;
      return;
    }

    modalContent.innerHTML = `
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr>
              <th class="text-left px-4 py-3"
                  style="color: var(--muted); border-bottom: 1px solid var(--border);">
                Pracovník
              </th>
              <th class="text-right px-4 py-3"
                  style="color: var(--muted); border-bottom: 1px solid var(--border);">
                Nárok na stravenky
              </th>
            </tr>
          </thead>
          <tbody>
            ${data.items.map(item => `
              <tr>
                <td class="px-4 py-3"
                    style="color: var(--text); border-bottom: 1px solid var(--border);">
                  ${escapeHtml(item.name)}
                </td>
                <td class="px-4 py-3 text-right font-semibold"
                    style="color: var(--text); border-bottom: 1px solid var(--border);">
                  ${Number(item.voucher_days || 0)}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
  }

  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      const month = monthInput ? monthInput.value : '';
      if (!month) {
        alert('Vyber měsíc.');
        return;
      }

      try {
        const data = await postForm('/exports/meal-vouchers/preview', { month });
        renderMealVoucherResult(data);
        openModal();
      } catch (err) {
        alert(err.message || 'Export se nepodařilo připravit.');
      }
    });
  }

  document.addEventListener('click', function (e) {
    if (e.target && e.target.getAttribute('data-close-meal-voucher-modal') === '1') {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
})();
</script>
