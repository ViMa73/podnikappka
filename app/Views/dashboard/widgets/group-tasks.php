<div id="dashboardGroupTasksWidget">
  <div id="dashboardGroupTasksGrid" class="grid grid-cols-1 xl:grid-cols-3 gap-3"></div>
</div>

<script>
(function () {
  const root = document.getElementById('dashboardGroupTasksWidget');
  if (!root) return;

  const csrf = document.querySelector('#dashboardCsrfHolder input[name="_csrf"]')?.value || '';
  const grid = document.getElementById('dashboardGroupTasksGrid');

  const state = {
    groups: [],
    items: {}
  };

  const groupColorMap = {
    white:  { bg: '#FFFFFF', border: '#9CA3AF' },
    yellow: { bg: '#FEF3C7', border: '#F59E0B' },
    orange: { bg: '#FED7AA', border: '#F97316' },
    red:    { bg: '#FECACA', border: '#EF4444' },
    purple: { bg: '#E9D5FF', border: '#8B5CF6' },
    blue:   { bg: '#BFDBFE', border: '#3B82F6' },
    cyan:   { bg: '#CFFAFE', border: '#06B6D4' },
    green:  { bg: '#D1FAE5', border: '#10B981' },
    gray:   { bg: '#E5E7EB', border: '#6B7280' }
  };

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function getGroupColorStyle(color) {
    const c = groupColorMap[color] || groupColorMap.white;
    return `background: ${c.bg}; border: 1px solid ${c.border};`;
  }

  async function api(url, data = {}, method = 'POST') {
    const options = {
      method,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    };

    if (method !== 'GET') {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify({ ...data, _csrf: csrf });
    }

    const res = await fetch(url, options);
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

  async function loadGroups() {
    const data = await api('/tasks/dashboard-groups-data', {}, 'GET');
    state.groups = data.groups || [];
    state.items = data.items || {};
    render();
  }

  function render() {
    if (!state.groups.length) {
      grid.innerHTML = `
        <div class="xl:col-span-3 text-sm italic px-3 py-2 rounded-lg"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Zatím nemáš žádnou skupinu úkolů.
        </div>
      `;

      if (window.DashboardMasonryResize) {
        window.DashboardMasonryResize();
      }
      return;
    }

    grid.innerHTML = state.groups.map(group => {
      const items = state.items[group.id] || [];
      const openItems = items.filter(item => !item.completed_at);
      const doneItems = items.filter(item => !!item.completed_at).slice(0, 3);
      const colorStyle = getGroupColorStyle(group.color || 'white');

      return `
        <div class="rounded-2xl p-3"
             style="${colorStyle}">
          <div class="font-semibold mb-3 truncate"
               style="color: #111827;"
               title="${escapeHtml(group.title)}">
            ${escapeHtml(group.title)}
          </div>

          <div class="space-y-2">
            ${openItems.map(item => `
              <div class="flex items-start gap-2">
                <label class="mt-0.5 shrink-0">
                  <input type="checkbox"
                         onchange="DashboardGroupTasksWidget.toggleItem(${Number(item.id)})">
                </label>

                <div class="text-sm flex-1 min-w-0"
                     style="color: #111827;">
                  ${escapeHtml(item.title)}
                </div>
              </div>
            `).join('')}

            <input type="text"
                   class="w-full mt-2 px-3 py-2 rounded-lg text-sm"
                   style="background: rgba(255,255,255,.7); border: 1px solid rgba(0,0,0,.08); color: #111827;"
                   placeholder="+ nový úkol"
                   onkeydown="DashboardGroupTasksWidget.handleInput(event, ${Number(group.id)})">
          </div>

          ${doneItems.length ? `
            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(0,0,0,.1);">
              <div class="text-xs mb-2" style="color: rgba(17,24,39,.7);">Splněné</div>
              <div class="space-y-2">
                ${doneItems.map(item => `
                  <div class="flex items-start gap-2" style="opacity: .75;">
                    <label class="mt-0.5 shrink-0">
                      <input type="checkbox"
                             checked
                             onchange="DashboardGroupTasksWidget.toggleItem(${Number(item.id)})">
                    </label>
                    <div class="text-sm flex-1 min-w-0"
                         style="color: #111827; text-decoration: line-through;">
                      ${escapeHtml(item.title)}
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
          ` : ''}
        </div>
      `;
    }).join('');

    if (window.DashboardMasonryResize) {
      window.DashboardMasonryResize();
    }
  }

  async function toggleItem(id) {
    await api('/tasks/group/item/toggle', { id });
    await loadGroups();
  }

  async function handleInput(event, groupId) {
    if (event.key !== 'Enter') return;

    event.preventDefault();

    const input = event.target;
    const title = input.value.trim();
    if (!title) return;

    await api('/tasks/group/item/create', {
      group_id: groupId,
      title
    });

    input.value = '';
    await loadGroups();
  }

  window.DashboardGroupTasksWidget = {
    toggleItem: async function (id) {
      try {
        await toggleItem(id);
      } catch (err) {
        alert(err.message || 'Nepodařilo se změnit stav skupinového úkolu.');
      }
    },
    handleInput: async function (event, groupId) {
      try {
        await handleInput(event, groupId);
      } catch (err) {
        alert(err.message || 'Nepodařilo se přidat skupinový úkol.');
      }
    }
  };

  loadGroups();
})();
</script>
