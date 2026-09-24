<div id="dashboardCsrfHolder" class="hidden">
  <?= \Core\CSRF::field() ?>
</div>

<div class="space-y-6 mb-8">

  <!-- HORNÍ LIŠTA -->
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold" style="color: var(--text);">Dashboard</h1>
      <p class="text-sm mt-1" style="color: var(--muted);">
        Přetahováním měníš pořadí widgetů. Rozložení se ukládá automaticky.
      </p>
    </div>

    <button type="button"
            id="dashboardControlsToggle"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold"
            style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
      <span>Ovládací centrum</span>
      <svg id="dashboardControlsChevron"
           class="w-5 h-5 transition-transform duration-200"
           viewBox="0 0 24 24"
           fill="none">
        <path d="M6 9L12 15L18 9"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <!-- OVLÁDACÍ CENTRUM -->
  <div id="dashboardControlsPanel"
       class="hidden rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
      <div>
        <h2 class="text-xl font-semibold mb-1" style="color: var(--text);">Ovládací centrum dashboardu</h2>
        <p class="text-sm" style="color: var(--muted);">
          Přetahováním měníš pořadí widgetů. Tady si je můžeš také zapnout nebo vypnout a obnovit výchozí rozložení.
        </p>
      </div>

      <div class="shrink-0">
        <button type="button"
                id="dashboardResetBtn"
                class="px-4 py-2 rounded-lg font-semibold"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          Obnovit výchozí rozložení
        </button>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <?php foreach (($dashboardWidgetControls ?? []) as $widgetControl): ?>
        <?php if (!empty($widgetControl['available'])): ?>
          <?php
            $enabledNow = false;
            foreach (($dashboardWidgets ?? []) as $w) {
              if (($w['key'] ?? '') === ($widgetControl['key'] ?? '')) {
                $enabledNow = !empty($w['is_enabled']);
                break;
              }
            }
          ?>
          <div class="p-4 rounded-xl"
               style="background: var(--bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between gap-4">
              <div>
                <div class="font-semibold" style="color: var(--text);">
                  <?= htmlspecialchars($widgetControl['label']) ?>
                </div>
                <div class="text-sm mt-1" style="color: var(--muted);">
                  Šířka widgetu: <?= (int)$widgetControl['span'] ?>/12
                </div>
              </div>

              <label class="inline-flex items-center cursor-pointer select-none">
                <input type="checkbox"
                       class="hidden js-dashboard-widget-toggle"
                       data-widget-key="<?= htmlspecialchars($widgetControl['key']) ?>"
                       <?= $enabledNow ? 'checked' : '' ?>>

                <div class="w-12 h-7 rounded-full relative transition"
                     style="background: <?= $enabledNow ? 'var(--primary)' : '#9ca3af' ?>;">
                  <div class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition js-dashboard-widget-toggle-dot <?= $enabledNow ? 'translate-x-5' : '' ?>"></div>
                </div>
              </label>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- GRID DASHBOARDU -->
  <div id="dashboardGrid"
       class="grid grid-cols-12 gap-6 items-start"
       style="grid-auto-rows: 8px; grid-auto-flow: dense;">
    <?php foreach (($dashboardWidgets ?? []) as $widget): ?>
      <?php if (!empty($widget['is_enabled'])): ?>
        <div class="dashboard-widget col-span-12 xl:col-span-<?= (int)$widget['span'] ?> self-start"
             data-widget-key="<?= htmlspecialchars($widget['key']) ?>"
             draggable="true">
          <div class="dashboard-widget-inner rounded-2xl shadow"
               style="background: var(--card); border: 1px solid var(--border);">
            <div class="dashboard-widget-handle flex items-center justify-between gap-4 px-6 py-4 cursor-move"
                 style="border-bottom: 1px solid var(--border);">
              <div class="text-sm font-medium" style="color: var(--muted);">
                <?= htmlspecialchars($widget['label']) ?>
              </div>

              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" style="color: var(--muted);">
                <path d="M9 6H9.01M15 6H15.01M9 12H9.01M15 12H15.01M9 18H9.01M15 18H15.01"
                      stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              </svg>
            </div>

            <div class="p-6">
              <?php require __DIR__ . '/' . $widget['view'] . '.php'; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

</div>

<script>
(function () {
  const csrf = document.querySelector('#dashboardCsrfHolder input[name="_csrf"]')?.value || '';
  const grid = document.getElementById('dashboardGrid');
  const resetBtn = document.getElementById('dashboardResetBtn');
  const toggleInputs = Array.from(document.querySelectorAll('.js-dashboard-widget-toggle'));

  const controlsToggle = document.getElementById('dashboardControlsToggle');
  const controlsPanel = document.getElementById('dashboardControlsPanel');
  const controlsChevron = document.getElementById('dashboardControlsChevron');
  const controlsStorageKey = 'dashboard-controls-collapsed';

  let dragged = null;

  async function api(url, data = {}) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ ...data, _csrf: csrf })
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

  function setControlsCollapsed(collapsed) {
    if (!controlsPanel || !controlsChevron) return;

    if (collapsed) {
      controlsPanel.classList.add('hidden');
      controlsChevron.style.transform = 'rotate(-90deg)';
      localStorage.setItem(controlsStorageKey, '1');
    } else {
      controlsPanel.classList.remove('hidden');
      controlsChevron.style.transform = 'rotate(0deg)';
      localStorage.setItem(controlsStorageKey, '0');
    }
  }

  function initControlsCollapse() {
    if (!controlsPanel || !controlsChevron) return;

    const stored = localStorage.getItem(controlsStorageKey);
    const collapsed = stored === null ? true : stored === '1';

    setControlsCollapsed(collapsed);

    if (controlsToggle) {
      controlsToggle.addEventListener('click', () => {
        const isCollapsed = controlsPanel.classList.contains('hidden');
        setControlsCollapsed(!isCollapsed);
      });
    }
  }

  function currentOrder() {
    if (!grid) return [];

    return Array.from(grid.querySelectorAll('.dashboard-widget'))
      .map(el => el.getAttribute('data-widget-key'))
      .filter(Boolean);
  }

  async function saveOrder() {
    await api('/dashboard/layout/save', {
      widgets: currentOrder()
    });
  }

  function resizeDashboardWidget(widget) {
    if (!grid || !widget) return;

    const inner = widget.querySelector('.dashboard-widget-inner');
    if (!inner) return;

    const rowSize = 8;
    const gap = 24; // gap-6
    const height = inner.getBoundingClientRect().height;
    const span = Math.ceil((height + gap) / (rowSize + gap));

    widget.style.gridRowEnd = `span ${Math.max(span, 1)}`;
  }

  function resizeAllDashboardWidgets() {
    if (!grid) return;
    grid.querySelectorAll('.dashboard-widget').forEach(resizeDashboardWidget);
  }

  window.DashboardMasonryResize = resizeAllDashboardWidgets;

  if (grid) {
    grid.addEventListener('dragstart', (e) => {
      const widget = e.target.closest('.dashboard-widget');
      if (!widget) return;

      dragged = widget;
      widget.style.opacity = '0.6';
      e.dataTransfer.effectAllowed = 'move';
    });

    grid.addEventListener('dragend', (e) => {
      const widget = e.target.closest('.dashboard-widget');
      if (widget) {
        widget.style.opacity = '';
      }
      dragged = null;
      resizeAllDashboardWidgets();
    });

    grid.addEventListener('dragover', (e) => {
      e.preventDefault();

      const target = e.target.closest('.dashboard-widget');
      if (!dragged || !target || dragged === target) return;

      const rect = target.getBoundingClientRect();
      const before = e.clientY < rect.top + rect.height / 2;

      if (before) {
        grid.insertBefore(dragged, target);
      } else {
        grid.insertBefore(dragged, target.nextSibling);
      }
    });

    grid.addEventListener('drop', async (e) => {
      e.preventDefault();
      if (!dragged) return;

      try {
        await saveOrder();
        resizeAllDashboardWidgets();
      } catch (err) {
        alert(err.message || 'Nepodařilo se uložit pořadí widgetů.');
      }
    });
  }

  toggleInputs.forEach((input) => {
    input.addEventListener('change', async () => {
      const widgetKey = input.getAttribute('data-widget-key') || '';
      const wrap = input.closest('label');
      const track = wrap ? wrap.querySelector('div') : null;
      const dot = wrap ? wrap.querySelector('.js-dashboard-widget-toggle-dot') : null;

      try {
        await api('/dashboard/layout/widget-enabled', {
          widget_key: widgetKey,
          enabled: input.checked ? 1 : 0
        });

        if (track) {
          track.style.background = input.checked ? 'var(--primary)' : '#9ca3af';
        }
        if (dot) {
          dot.classList.toggle('translate-x-5', input.checked);
        }

        window.location.reload();
      } catch (err) {
        input.checked = !input.checked;
        alert(err.message || 'Nepodařilo se uložit nastavení widgetu.');
      }
    });
  });

  if (resetBtn) {
    resetBtn.addEventListener('click', async () => {
      const ok = confirm('Opravdu chceš obnovit výchozí rozložení dashboardu?');
      if (!ok) return;

      try {
        await api('/dashboard/layout/reset');
        window.location.reload();
      } catch (err) {
        alert(err.message || 'Nepodařilo se obnovit výchozí rozložení.');
      }
    });
  }

  initControlsCollapse();
  resizeAllDashboardWidgets();

  window.addEventListener('load', resizeAllDashboardWidgets);
  window.addEventListener('resize', resizeAllDashboardWidgets);
})();
</script>

<?php if (!empty($showAttendanceWidget)): ?>
  <script>
    (function () {
      function timeToMinutes(value) {
        if (!value || !/^\d{2}:\d{2}$/.test(value)) return null;
        const [h, m] = value.split(':').map(Number);
        return h * 60 + m;
      }

      function minutesToHuman(minutes) {
        if (minutes === null || minutes < 0) return '00:00';
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
      }

      function updateForm(form) {
        const special = form.querySelector('.js-dashboard-attendance-special');
        const specialValue = special ? special.value : '';
        const defaultSpecial = form.dataset.defaultSpecial || '00:00';
        const workedEl = form.querySelector('.js-dashboard-attendance-worked');
        const timeInputs = form.querySelectorAll('.js-dashboard-attendance-time');

        timeInputs.forEach((input) => {
          if (specialValue !== '') {
            input.value = '';
            input.disabled = true;
            input.style.opacity = '0.65';
          } else {
            input.disabled = false;
            input.style.opacity = '1';
          }
        });

        if (specialValue !== '') {
          workedEl.textContent = defaultSpecial;
          if (window.DashboardMasonryResize) {
            window.DashboardMasonryResize();
          }
          return;
        }

        const arrival = form.querySelector('input[name="arrival_time"]')?.value || '';
        const departure = form.querySelector('input[name="departure_time"]')?.value || '';
        const lunchFrom = form.querySelector('input[name="lunch_from"]')?.value || '';
        const lunchTo = form.querySelector('input[name="lunch_to"]')?.value || '';
        const breakFrom = form.querySelector('input[name="break_from"]')?.value || '';
        const breakTo = form.querySelector('input[name="break_to"]')?.value || '';

        const arrivalMin = timeToMinutes(arrival);
        const departureMin = timeToMinutes(departure);

        if (arrival === '' && departure === '') {
          workedEl.textContent = '00:00';
          if (window.DashboardMasonryResize) {
            window.DashboardMasonryResize();
          }
          return;
        }

        if (arrivalMin === null || departureMin === null || departureMin < arrivalMin) {
          workedEl.textContent = '00:00';
          if (window.DashboardMasonryResize) {
            window.DashboardMasonryResize();
          }
          return;
        }

        let worked = departureMin - arrivalMin;

        const lunchFromMin = timeToMinutes(lunchFrom);
        const lunchToMin = timeToMinutes(lunchTo);
        if (lunchFrom !== '' && lunchTo !== '' && lunchFromMin !== null && lunchToMin !== null && lunchToMin >= lunchFromMin) {
          worked -= (lunchToMin - lunchFromMin);
        }

        const breakFromMin = timeToMinutes(breakFrom);
        const breakToMin = timeToMinutes(breakTo);
        if (breakFrom !== '' && breakTo !== '' && breakFromMin !== null && breakToMin !== null && breakToMin >= breakFromMin) {
          worked -= (breakToMin - breakFromMin);
        }

        if (worked < 0) worked = 0;
        workedEl.textContent = minutesToHuman(worked);

        if (window.DashboardMasonryResize) {
          window.DashboardMasonryResize();
        }
      }

      document.querySelectorAll('.js-dashboard-attendance-form').forEach((form) => {
        updateForm(form);

        form.querySelectorAll('.js-dashboard-attendance-special, .js-dashboard-attendance-time').forEach((input) => {
          input.addEventListener('change', () => updateForm(form));
          input.addEventListener('input', () => updateForm(form));
        });

        const copyBtn = form.querySelector('.js-dashboard-copy-prev');
        if (copyBtn) {
          copyBtn.addEventListener('click', () => {
            const raw = form.dataset.prevCopy || '';
            if (!raw) return;

            let prev;
            try {
              prev = JSON.parse(raw);
            } catch (e) {
              return;
            }

            const special = form.querySelector('.js-dashboard-attendance-special');
            if (special) special.value = '';

            [
              'arrival_time',
              'departure_time',
              'lunch_from',
              'lunch_to',
              'break_from',
              'break_to'
            ].forEach((name) => {
              const input = form.querySelector(`input[name="${name}"]`);
              if (input && typeof prev[name] !== 'undefined') {
                input.value = prev[name] || '';
              }
            });

            updateForm(form);
          });
        }
      });

      if (window.DashboardMasonryResize) {
        window.DashboardMasonryResize();
      }
    })();
  </script>
<?php endif; ?>