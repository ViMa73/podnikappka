<div id="dashboardMyTasksWidget">
  <div class="flex items-center justify-between gap-4 mb-4">
    <div class="text-sm" style="color: var(--muted);">
      Osobní úkoly přiřazené tobě.
    </div>

    <button type="button"
            id="dashboardMyTasksCreateBtn"
            class="btn-primary px-3 py-2 rounded-lg font-semibold text-sm">
      +
    </button>
  </div>

  <div id="dashboardMyTasksList" class="space-y-2"></div>

  <!-- MODAL: MOJE ÚKOLY -->
  <div id="dashboardMyTasksModal"
       class="fixed inset-0 z-50 hidden items-center justify-center"
       aria-hidden="true">

    <div class="absolute inset-0"
         style="background: rgba(0,0,0,.45);"
         data-close-dashboard-my-tasks-modal="1"></div>

    <div class="relative w-full max-w-2xl mx-4 rounded-2xl shadow-xl p-6"
         style="background: var(--card); border: 1px solid var(--border);">

      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 id="dashboardMyTasksModalHeading"
              class="text-lg font-semibold"
              style="color: var(--text);">Nový úkol</h3>
          <p class="text-sm mt-1" style="color: var(--muted);">
            Vytvoření nebo úprava osobního úkolu.
          </p>
        </div>

        <button type="button"
                class="px-3 py-2 rounded-lg"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                data-close-dashboard-my-tasks-modal="1"
                aria-label="Zavřít">✕</button>
      </div>

      <div class="mt-5 space-y-4">
        <input type="hidden" id="dashboardMyTasksModalId" value="">
        <input type="hidden" id="dashboardMyTasksModalMode" value="create">

        <div>
          <label class="text-sm" style="color: var(--text);">Název</label>
          <input id="dashboardMyTasksModalTitle"
                 class="w-full px-4 py-3 rounded-lg mt-1"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Popis</label>
          <textarea id="dashboardMyTasksModalDescription"
                    rows="4"
                    class="w-full px-4 py-3 rounded-lg mt-1"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"></textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm" style="color: var(--text);">Termín</label>
            <input type="date"
                   id="dashboardMyTasksModalDueDate"
                   class="w-full px-4 py-3 rounded-lg mt-1"
                   style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          </div>

          <div>
            <label class="text-sm" style="color: var(--text);">Opakování</label>
            <select id="dashboardMyTasksModalRepeatType"
                    class="w-full px-4 py-3 rounded-lg mt-1"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              <option value="none">Neopakovat</option>
              <option value="daily">Denně</option>
              <option value="weekly">Týdně</option>
              <option value="monthly">Měsíčně</option>
              <option value="quarterly">Kvartálně</option>
              <option value="yearly">Ročně</option>
            </select>
          </div>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Interval opakování</label>
          <input type="number"
                 min="1"
                 value="1"
                 id="dashboardMyTasksModalRepeatInterval"
                 class="w-full px-4 py-3 rounded-lg mt-1"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div id="dashboardMyTasksModalMeta"
             class="hidden p-4 rounded-xl text-sm"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);"></div>

        <div class="flex gap-3 justify-end pt-2">
          <button type="button"
                  class="px-5 py-3 rounded-lg font-semibold"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  data-close-dashboard-my-tasks-modal="1">
            Zrušit
          </button>

          <button type="button"
                  id="dashboardMyTasksModalSaveButton"
                  class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const root = document.getElementById('dashboardMyTasksWidget');
  if (!root) return;

  const csrf = document.querySelector('#dashboardCsrfHolder input[name="_csrf"]')?.value || '';

  const list = document.getElementById('dashboardMyTasksList');
  const createBtn = document.getElementById('dashboardMyTasksCreateBtn');

  const modal = document.getElementById('dashboardMyTasksModal');
  const modalHeading = document.getElementById('dashboardMyTasksModalHeading');
  const modalId = document.getElementById('dashboardMyTasksModalId');
  const modalMode = document.getElementById('dashboardMyTasksModalMode');
  const modalTitle = document.getElementById('dashboardMyTasksModalTitle');
  const modalDescription = document.getElementById('dashboardMyTasksModalDescription');
  const modalDueDate = document.getElementById('dashboardMyTasksModalDueDate');
  const modalRepeatType = document.getElementById('dashboardMyTasksModalRepeatType');
  const modalRepeatInterval = document.getElementById('dashboardMyTasksModalRepeatInterval');
  const modalMeta = document.getElementById('dashboardMyTasksModalMeta');
  const saveBtn = document.getElementById('dashboardMyTasksModalSaveButton');

  const state = {
    tasks: []
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

  function buildCompletedMeta(task) {
    if (!task.completed_at) return '';

    const completedBy = `${task.completed_by_first_name || ''} ${task.completed_by_last_name || ''}`.trim();
    let text = `Splněno: ${task.completed_at}`;
    if (completedBy) {
      text += ` • ${completedBy}`;
    }
    return text;
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

  function openModal(task = null) {
    modalId.value = task && task.id ? String(task.id) : '';
    modalMode.value = task && task.id ? 'edit' : 'create';

    modalHeading.textContent = task && task.id ? 'Detail úkolu' : 'Nový úkol';

    modalTitle.value = task?.title || '';
    modalDescription.value = task?.description || '';
    modalDueDate.value = task?.due_date || '';
    modalRepeatType.value = task?.repeat_type || 'none';
    modalRepeatInterval.value = task?.repeat_interval || 1;

    if (task && task.completed_at) {
      modalMeta.textContent = buildCompletedMeta(task);
      modalMeta.classList.remove('hidden');
    } else {
      modalMeta.textContent = '';
      modalMeta.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  async function loadTasks() {
    const data = await api('/tasks/dashboard-data', {}, 'GET');
    state.tasks = data.tasks || [];
    render();
  }

  function render() {
    if (!state.tasks.length) {
      list.innerHTML = `
        <div class="text-sm italic p-3 rounded-lg"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Žádné otevřené úkoly.
        </div>
      `;
      return;
    }

    list.innerHTML = state.tasks.map(task => `
      <div class="rounded-xl p-3 cursor-pointer js-dashboard-my-task-open"
           style="background: var(--bg); border: 1px solid var(--border);"
           data-id="${Number(task.id)}">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div style="color: var(--text);">
              ${escapeHtml(task.title)}
            </div>
            ${task.due_date ? `
              <div class="text-xs mt-1" style="color: var(--muted);">
                Termín: ${escapeHtml(formatDate(task.due_date))}
              </div>
            ` : ''}
          </div>

          <label onclick="event.stopPropagation()">
            <input type="checkbox"
                   onchange="DashboardMyTasksWidget.toggleTask(${Number(task.id)})">
          </label>
        </div>
      </div>
    `).join('');

    if (window.DashboardMasonryResize) {
      window.DashboardMasonryResize();
    }
  }

  async function saveTask() {
    const payload = {
      id: modalId.value,
      title: modalTitle.value.trim(),
      description: modalDescription.value.trim(),
      due_date: modalDueDate.value,
      repeat_type: modalRepeatType.value,
      repeat_interval: modalRepeatInterval.value
    };

    if (!payload.title) {
      alert('Vyplň název úkolu.');
      return;
    }

    const endpoint = modalMode.value === 'edit' ? '/tasks/update' : '/tasks/create';
    await api(endpoint, payload);
    closeModal();
    await loadTasks();
  }

  async function toggleTask(id) {
    await api('/tasks/toggle', { id });
    await loadTasks();
  }

  if (createBtn) {
    createBtn.addEventListener('click', () => openModal());
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      try {
        await saveTask();
      } catch (err) {
        alert(err.message || 'Úkol se nepodařilo uložit.');
      }
    });
  }

  document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('.js-dashboard-my-task-open');
    if (openBtn && root.contains(openBtn)) {
      const id = Number(openBtn.getAttribute('data-id') || 0);
      const task = state.tasks.find(t => Number(t.id) === id);
      if (task) {
        openModal(task);
      }
      return;
    }

    if (e.target && e.target.getAttribute('data-close-dashboard-my-tasks-modal') === '1') {
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });

  window.DashboardMyTasksWidget = {
    toggleTask: async function (id) {
      try {
        await toggleTask(id);
      } catch (err) {
        alert(err.message || 'Nepodařilo se změnit stav úkolu.');
      }
    }
  };

  loadTasks();
})();
</script>