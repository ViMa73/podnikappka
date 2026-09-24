<div id="dashboardTodayTemperaturesWidget">
  <div id="dashboardTodayTemperaturesDate"
       class="text-xs font-semibold mb-3"
       style="color: var(--muted);"></div>

  <div id="dashboardTodayTemperaturesList" class="space-y-2"></div>
</div>

<script>
(function () {
  const root = document.getElementById('dashboardTodayTemperaturesWidget');
  if (!root) return;

  const csrf = document.querySelector('#dashboardCsrfHolder input[name="_csrf"]')?.value || '';
  const dateEl = document.getElementById('dashboardTodayTemperaturesDate');
  const list = document.getElementById('dashboardTodayTemperaturesList');

  const state = {
    today: '',
    rows: []
  };

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatDate(value) {
    if (!value) return '';
    const parts = String(value).split('-');
    if (parts.length !== 3) return value;
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
  }

  function setRowGreen(input) {
    const wrap = input.closest('.js-dashboard-temp-row');
    if (wrap) {
      wrap.style.background = 'color-mix(in srgb, var(--taken) 60%, transparent)';
    }
  }

  function setRowRed(input) {
    const wrap = input.closest('.js-dashboard-temp-row');
    if (wrap) {
      wrap.style.background = 'color-mix(in srgb, var(--free) 60%, transparent)';
    }
  }

  function setSaving(input, saving) {
    const wrap = input.closest('.js-dashboard-temp-row');
    if (wrap) {
      wrap.style.opacity = saving ? '0.7' : '1';
    }
  }

  async function apiGet(url) {
    const res = await fetch(url, {
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
    setRowGreen(input);
  }

  function focusNextRow(currentInput) {
    const rowIndex = parseInt(currentInput.getAttribute('data-row-index') || '-1', 10);
    if (rowIndex < 0) return;

    const next = root.querySelector('.js-dashboard-temp-inline[data-row-index="' + (rowIndex + 1) + '"]');
    if (next) {
      next.focus();
      next.select();
    }
  }

  function render() {
    dateEl.textContent = state.today ? `Dnes: ${formatDate(state.today)}` : '';

    if (!state.rows.length) {
      list.innerHTML = `
        <div class="text-sm italic px-3 py-2 rounded-lg"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Nemáš založený žádný teploměr.
        </div>
      `;

      if (window.DashboardMasonryResize) {
        window.DashboardMasonryResize();
      }
      return;
    }

    list.innerHTML = state.rows.map((row, index) => {
      const hasValue = !!row.has_value;
      const rowBg = hasValue
        ? 'color-mix(in srgb, var(--taken) 60%, transparent)'
        : 'color-mix(in srgb, var(--free) 60%, transparent)';

      return `
        <div class="js-dashboard-temp-row rounded-xl px-3 py-2"
             style="background: ${rowBg}; border: 1px solid var(--border);">
          <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1 text-sm font-medium truncate"
                 style="color: var(--text);"
                 title="${escapeHtml(row.label)}">
              ${escapeHtml(row.label)}
            </div>

            <input type="text"
                   value="${escapeHtml(row.value || '')}"
                   data-row-index="${index}"
                   data-place-id="${Number(row.place_id)}"
                   data-record-type="${escapeHtml(row.record_type)}"
                   data-record-date="${escapeHtml(state.today)}"
                   class="js-dashboard-temp-inline w-24 px-2 py-1.5 rounded-lg text-sm text-center shrink-0"
                   style="background: rgba(255,255,255,0.45); border: 1px solid var(--border); color: var(--text);">
          </div>
        </div>
      `;
    }).join('');

    bindInputs();

    if (window.DashboardMasonryResize) {
      window.DashboardMasonryResize();
    }
  }

  function bindInputs() {
    const inputs = root.querySelectorAll('.js-dashboard-temp-inline');
    let isSaving = false;

    inputs.forEach((input) => {
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

          if (window.DashboardMasonryResize) {
            window.DashboardMasonryResize();
          }
        } catch (err) {
          input.disabled = false;
          setSaving(input, false);
          setRowRed(input);
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

          if (window.DashboardMasonryResize) {
            window.DashboardMasonryResize();
          }
        } catch (err) {
          input.disabled = false;
          setSaving(input, false);
          setRowRed(input);
          alert(err.message || 'Chyba při ukládání.');
        } finally {
          isSaving = false;
        }
      });
    });
  }

  async function loadData() {
    const data = await apiGet('/temperatures/dashboard-data');
    state.today = data.today || '';
    state.rows = data.rows || [];
    render();
  }

  loadData();
})();
</script>