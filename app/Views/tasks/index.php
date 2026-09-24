<div id="tasksCsrfHolder" class="hidden">
  <?= \Core\CSRF::field() ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

  <!-- LEVÁ ČÁST -->
  <div class="lg:col-span-3 space-y-6">

    <!-- SKUPINOVÉ ÚKOLY -->
    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">

      <div class="flex items-center justify-between gap-4 mb-4">
        <div>
          <h2 class="text-xl font-semibold" style="color: var(--text)">Skupinové úkoly</h2>
          <p class="text-sm mt-1" style="color: var(--muted);">
            Jednoduché skupiny úkolů ve stylu rychlých poznámek.
          </p>
        </div>

        <button type="button"
                class="js-open-group-modal btn-primary px-4 py-2 rounded-lg font-semibold"
                data-mode="create"
                data-id=""
                data-title=""
                data-color="yellow">
          Nová skupina
        </button>
      </div>

      <div id="taskGroupsGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>
    </div>

    <!-- VŠECHNY ÚKOLY -->
    <?php if (\Core\Auth::role() === 'owner' || \Core\Auth::role() === 'manager'): ?>
      <div class="rounded-2xl shadow p-6"
           style="background: var(--card); border: 1px solid var(--border);">

        <div class="flex items-center justify-between gap-4 mb-4">
          <div>
            <h2 class="text-lg font-semibold" style="color: var(--text)">Všechny úkoly</h2>
            <p class="text-sm mt-1" style="color: var(--muted);">
              Přehled osobních úkolů všech uživatelů.
            </p>
          </div>

          <button type="button"
                  class="js-open-task-modal btn-primary px-4 py-2 rounded-lg font-semibold"
                  data-mode="create"
                  data-id=""
                  data-assigned-user-id=""
                  data-title=""
                  data-description=""
                  data-due-date=""
                  data-repeat-type="none"
                  data-repeat-interval="1"
                  data-completed-meta="">
            Nový
          </button>
        </div>

        <div class="mb-4">
          <select id="allTaskFilter"
                  class="w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <option value="open">Nesplněné</option>
            <option value="all">Vše</option>
          </select>
        </div>

        <div id="allTasksList" class="space-y-2"></div>
      </div>
    <?php endif; ?>

  </div>

  <!-- PRAVÝ PANEL -->
  <div class="lg:col-span-1 space-y-6">

    <!-- MOJE ÚKOLY -->
    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">

      <div class="flex items-center justify-between gap-4 mb-4">
        <div>
          <h2 class="text-xl font-semibold" style="color: var(--text)">Moje úkoly</h2>
          <p class="text-sm mt-1" style="color: var(--muted);">
            Osobní úkoly přiřazené tobě.
          </p>
        </div>

        <button type="button"
                class="js-open-task-modal btn-primary px-4 py-2 rounded-lg font-semibold"
                data-mode="create"
                data-id=""
                data-assigned-user-id=""
                data-title=""
                data-description=""
                data-due-date=""
                data-repeat-type="none"
                data-repeat-interval="1"
                data-completed-meta="">
          +
        </button>
      </div>

      <div class="mb-4">
        <select id="personalTaskFilter"
                class="w-full px-4 py-3 rounded-lg"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          <option value="open">Nesplněné</option>
          <option value="all">Vše</option>
        </select>
      </div>

      <div id="personalTasksList" class="space-y-2"></div>
    </div>
  </div>

</div>

<!-- MODAL: ÚKOL -->
<div id="taskModal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     aria-hidden="true">

  <div class="absolute inset-0"
       style="background: rgba(0,0,0,.45);"
       data-close-task-modal="1"></div>

  <div class="relative w-full max-w-2xl mx-4 rounded-2xl shadow-xl p-6"
       style="background: var(--card); border: 1px solid var(--border);">

    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 id="taskModalHeading" class="text-lg font-semibold" style="color: var(--text);">Nový úkol</h3>
        <p id="taskModalSubheading" class="text-sm mt-1" style="color: var(--muted);">
          Vytvoření nebo úprava osobního úkolu.
        </p>
      </div>

      <button type="button"
              class="px-3 py-2 rounded-lg"
              style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
              data-close-task-modal="1"
              aria-label="Zavřít">✕</button>
    </div>

    <div class="mt-5 space-y-4">
      <input type="hidden" id="taskModalId" value="">
      <input type="hidden" id="taskModalMode" value="create">

      <div>
        <label class="text-sm" style="color: var(--text);">Název</label>
        <input id="taskModalTitle"
               class="w-full px-4 py-3 rounded-lg mt-1"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>

      <div>
        <label class="text-sm" style="color: var(--text);">Popis</label>
        <textarea id="taskModalDescription"
                  rows="4"
                  class="w-full px-4 py-3 rounded-lg mt-1"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"></textarea>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm" style="color: var(--text);">Termín</label>
          <input type="date"
                 id="taskModalDueDate"
                 class="w-full px-4 py-3 rounded-lg mt-1"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Opakování</label>
          <select id="taskModalRepeatType"
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

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm" style="color: var(--text);">Interval opakování</label>
          <input type="number"
                 min="1"
                 value="1"
                 id="taskModalRepeatInterval"
                 class="w-full px-4 py-3 rounded-lg mt-1"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <?php if (\Core\Auth::role() === 'owner' || \Core\Auth::role() === 'manager'): ?>
          <div>
            <label class="text-sm" style="color: var(--text);">Přiřadit uživateli</label>
            <select id="taskModalAssignedUser"
                    class="w-full px-4 py-3 rounded-lg mt-1"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              <option value="">Pro mě</option>
              <?php foreach (($users ?? []) as $u): ?>
                <option value="<?= (int)$u['id'] ?>">
                  <?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
      </div>

      <div id="taskModalMeta"
           class="hidden p-4 rounded-xl text-sm"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);"></div>

      <div class="flex items-center justify-between gap-3 pt-2">
        <div>
          <?php if (\Core\Auth::role() === 'owner' || \Core\Auth::role() === 'manager'): ?>
            <button type="button"
                    id="taskModalDeleteButton"
                    class="hidden px-5 py-3 rounded-lg font-semibold"
                    style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;">
              Smazat úkol
            </button>
          <?php endif; ?>
        </div>

        <div class="flex gap-3">
          <button type="button"
                  class="px-5 py-3 rounded-lg font-semibold"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  data-close-task-modal="1">
            Zrušit
          </button>

          <button type="button"
                  id="taskModalSaveButton"
                  class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: SKUPINA -->
<div id="groupModal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     aria-hidden="true">

  <div class="absolute inset-0"
       style="background: rgba(0,0,0,.45);"
       data-close-group-modal="1"></div>

  <div class="relative w-full max-w-md mx-4 rounded-2xl shadow-xl p-6"
       style="background: var(--card); border: 1px solid var(--border);">

    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 id="groupModalHeading" class="text-lg font-semibold" style="color: var(--text);">Nová skupina</h3>
        <p id="groupModalSubheading" class="text-sm mt-1" style="color: var(--muted);">
          Zadej název nové skupiny úkolů a vyber zvýraznění.
        </p>
      </div>

      <button type="button"
              class="px-3 py-2 rounded-lg"
              style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
              data-close-group-modal="1"
              aria-label="Zavřít">✕</button>
    </div>

    <div class="mt-5 space-y-4">
      <input type="hidden" id="groupModalId" value="">
      <input type="hidden" id="groupModalMode" value="create">

      <div>
        <label class="text-sm" style="color: var(--text);">Název skupiny</label>
        <input id="groupModalTitle"
               class="w-full px-4 py-3 rounded-lg mt-1"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>

      <div>
        <label class="text-sm block mb-2" style="color: var(--text);">Barva zvýraznění</label>

        <div id="groupColorPicker" class="grid grid-cols-3 gap-3">
          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="white" style="background:#FFFFFF; border-color:#9CA3AF;"></button>
          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="yellow" style="background:#FEF3C7; border-color:#F59E0B;"></button>
          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="orange" style="background:#FED7AA; border-color:#F97316;"></button>
          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="red" style="background:#FECACA; border-color:#EF4444;"></button>

          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="purple" style="background:#E9D5FF; border-color:#8B5CF6;"></button>
          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="blue" style="background:#BFDBFE; border-color:#3B82F6;"></button>

          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="cyan" style="background:#CFFAFE; border-color:#06B6D4;"></button>
          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="green" style="background:#D1FAE5; border-color:#10B981;"></button>
          <button type="button" class="js-group-color rounded-xl h-12 border-2" data-color="gray" style="background:#E5E7EB; border-color:#6B7280;"></button>
        </div>

        <input type="hidden" id="groupModalColor" value="yellow">
      </div>

      <div class="flex gap-3 justify-end">
        <button type="button"
                class="px-5 py-3 rounded-lg font-semibold"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                data-close-group-modal="1">
          Zrušit
        </button>

        <button type="button"
                id="groupModalSaveButton"
                class="btn-primary px-5 py-3 rounded-lg font-semibold">
          Uložit
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const csrf = document.querySelector('#tasksCsrfHolder input[name="_csrf"]')?.value || '';

  const personalFilter = document.getElementById('personalTaskFilter');
  const allFilter = document.getElementById('allTaskFilter');
  const personalTasksList = document.getElementById('personalTasksList');
  const allTasksList = document.getElementById('allTasksList');
  const taskGroupsGrid = document.getElementById('taskGroupsGrid');

  const taskModal = document.getElementById('taskModal');
  const taskModalHeading = document.getElementById('taskModalHeading');
  const taskModalSubheading = document.getElementById('taskModalSubheading');
  const taskModalId = document.getElementById('taskModalId');
  const taskModalMode = document.getElementById('taskModalMode');
  const taskModalTitle = document.getElementById('taskModalTitle');
  const taskModalDescription = document.getElementById('taskModalDescription');
  const taskModalDueDate = document.getElementById('taskModalDueDate');
  const taskModalRepeatType = document.getElementById('taskModalRepeatType');
  const taskModalRepeatInterval = document.getElementById('taskModalRepeatInterval');
  const taskModalAssignedUser = document.getElementById('taskModalAssignedUser');
  const taskModalMeta = document.getElementById('taskModalMeta');
  const taskModalSaveButton = document.getElementById('taskModalSaveButton');
  const taskModalDeleteButton = document.getElementById('taskModalDeleteButton');

  const groupModal = document.getElementById('groupModal');
  const groupModalHeading = document.getElementById('groupModalHeading');
  const groupModalSubheading = document.getElementById('groupModalSubheading');
  const groupModalId = document.getElementById('groupModalId');
  const groupModalMode = document.getElementById('groupModalMode');
  const groupModalTitle = document.getElementById('groupModalTitle');
  const groupModalColor = document.getElementById('groupModalColor');
  const groupModalSaveButton = document.getElementById('groupModalSaveButton');
  const groupColorButtons = Array.from(document.querySelectorAll('.js-group-color'));

  const state = {
    personal: [],
    groups: [],
    items: {},
    all: []
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

  function formatDate(value) {
    if (!value) return '';
    const parts = String(value).split('-');
    if (parts.length !== 3) return value;
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
  }

  function getGroupColorStyle(color) {
    const c = groupColorMap[color] || groupColorMap.yellow;
    return `background: ${c.bg}; border: 1px solid ${c.border};`;
  }

  function setSelectedGroupColor(color) {
    const selected = groupColorMap[color] ? color : 'yellow';
    groupModalColor.value = selected;

    groupColorButtons.forEach((btn) => {
      const isActive = btn.getAttribute('data-color') === selected;
      btn.style.outline = isActive ? '3px solid var(--primary)' : 'none';
      btn.style.outlineOffset = isActive ? '2px' : '0';
    });
  }

  function openTaskModal(data = null) {
    taskModalId.value = data && data.id ? String(data.id) : '';
    taskModalMode.value = data && data.id ? 'edit' : 'create';

    taskModalHeading.textContent = data && data.id ? 'Detail úkolu' : 'Nový úkol';
    taskModalSubheading.textContent = data && data.id
      ? 'Úprava osobního úkolu.'
      : 'Vytvoření nebo úprava osobního úkolu.';

    taskModalTitle.value = data?.title || '';
    taskModalDescription.value = data?.description || '';
    taskModalDueDate.value = data?.due_date || '';
    taskModalRepeatType.value = data?.repeat_type || 'none';
    taskModalRepeatInterval.value = data?.repeat_interval || 1;

    if (taskModalAssignedUser) {
      taskModalAssignedUser.value = data?.assigned_user_id || '';
    }

    if (data && data.completed_meta) {
      taskModalMeta.textContent = data.completed_meta;
      taskModalMeta.classList.remove('hidden');
    } else {
      taskModalMeta.textContent = '';
      taskModalMeta.classList.add('hidden');
    }

    if (taskModalDeleteButton) {
      if (data && data.id) {
        taskModalDeleteButton.classList.remove('hidden');
      } else {
        taskModalDeleteButton.classList.add('hidden');
      }
    }

    taskModal.classList.remove('hidden');
    taskModal.classList.add('flex');
    taskModal.setAttribute('aria-hidden', 'false');
  }

  function closeTaskModal() {
    taskModal.classList.add('hidden');
    taskModal.classList.remove('flex');
    taskModal.setAttribute('aria-hidden', 'true');
  }

  function openGroupModal(data = null) {
    const isEdit = !!(data && data.id);

    groupModalId.value = isEdit ? String(data.id) : '';
    groupModalMode.value = isEdit ? 'edit' : 'create';
    groupModalTitle.value = data?.title || '';

    groupModalHeading.textContent = isEdit ? 'Upravit skupinu' : 'Nová skupina';
    groupModalSubheading.textContent = isEdit
      ? 'Můžeš upravit název a barvu zvýraznění skupiny.'
      : 'Zadej název nové skupiny úkolů a vyber zvýraznění.';

    setSelectedGroupColor(data?.color || 'yellow');

    groupModal.classList.remove('hidden');
    groupModal.classList.add('flex');
    groupModal.setAttribute('aria-hidden', 'false');
  }

  function closeGroupModal() {
    groupModal.classList.add('hidden');
    groupModal.classList.remove('flex');
    groupModal.setAttribute('aria-hidden', 'true');
  }

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

  async function loadTasks() {
    const res = await fetch('/tasks/data', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const data = await res.json();
    state.personal = data.personal || [];
    state.groups = data.groups || [];
    state.items = data.items || {};
    state.all = data.all || [];

    renderPersonalTasks();
    renderGroups();
    renderAllTasks();
  }

  function buildCompletedMeta(task) {
    if (!task.completed_at) {
      return '';
    }

    const completedBy = `${task.completed_by_first_name || ''} ${task.completed_by_last_name || ''}`.trim();
    let text = `Splněno: ${task.completed_at}`;
    if (completedBy) {
      text += ` • ${completedBy}`;
    }
    return text;
  }

  function renderPersonalTasks() {
    if (!personalTasksList) return;

    const filter = personalFilter ? personalFilter.value : 'open';
    const tasks = state.personal.filter(task => filter === 'all' || !task.completed_at);

    if (!tasks.length) {
      personalTasksList.innerHTML = `
        <div class="text-sm italic p-3 rounded-lg"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Žádné úkoly.
        </div>
      `;
      return;
    }

    personalTasksList.innerHTML = tasks.map(task => {
      const done = !!task.completed_at;

      return `
        <div class="rounded-xl p-3 cursor-pointer js-open-task-modal"
             style="background: var(--bg); border: 1px solid var(--border);"
             data-mode="edit"
             data-id="${Number(task.id)}"
             data-assigned-user-id="${Number(task.assigned_user_id)}"
             data-title="${escapeHtml(task.title)}"
             data-description="${escapeHtml(task.description || '')}"
             data-due-date="${escapeHtml(task.due_date || '')}"
             data-repeat-type="${escapeHtml(task.repeat_type || 'none')}"
             data-repeat-interval="${Number(task.repeat_interval || 1)}"
             data-completed-meta="${escapeHtml(buildCompletedMeta(task))}">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div style="color: var(--text); ${done ? 'text-decoration: line-through; opacity: .65;' : ''}">
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
                     ${done ? 'checked' : ''}
                     onchange="TasksPage.toggleTask(${Number(task.id)})">
            </label>
          </div>
        </div>
      `;
    }).join('');
  }

  function renderGroups() {
    if (!taskGroupsGrid) return;

    if (!state.groups.length) {
      taskGroupsGrid.innerHTML = `
        <div class="md:col-span-2 xl:col-span-3 text-sm italic p-4 rounded-xl"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Zatím nemáš žádnou skupinu úkolů.
        </div>
      `;
      return;
    }

    taskGroupsGrid.innerHTML = state.groups.map(group => {
      const items = state.items[group.id] || [];
      const openItems = items.filter(item => !item.completed_at);
      const doneItems = items.filter(item => !!item.completed_at);
      const colorStyle = getGroupColorStyle(group.color || 'yellow');

      return `
        <div class="rounded-2xl shadow p-4"
             style="${colorStyle}">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div class="font-semibold min-w-0" style="color: #111827;">
              ${escapeHtml(group.title)}
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button type="button"
                      class="js-open-group-modal rounded-lg p-2"
                      title="Upravit skupinu"
                      style="background: rgba(255,255,255,.55); border: 1px solid rgba(0,0,0,.08); color: #111827;"
                      data-mode="edit"
                      data-id="${Number(group.id)}"
                      data-title="${escapeHtml(group.title)}"
                      data-color="${escapeHtml(group.color || 'yellow')}">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 20H8L18.5 9.5C19.3 8.7 19.3 7.3 18.5 6.5L17.5 5.5C16.7 4.7 15.3 4.7 14.5 5.5L4 16V20Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                  <path d="M13.5 6.5L17.5 10.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
              </button>

              ${doneItems.length ? `
                <button type="button"
                        class="js-delete-checked-group-items rounded-lg p-2"
                        title="Smazat zaškrtnuté"
                        style="background: rgba(255,255,255,.55); border: 1px solid rgba(0,0,0,.08); color: #B45309;"
                        data-id="${Number(group.id)}"
                        data-title="${escapeHtml(group.title)}">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 11L11 13L15 9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="4" y="4" width="16" height="16" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                  </svg>
                </button>
              ` : ''}

              <button type="button"
                      class="js-delete-group rounded-lg p-2"
                      title="Smazat skupinu"
                      style="background: rgba(255,255,255,.55); border: 1px solid rgba(0,0,0,.08); color: #991B1B;"
                      data-id="${Number(group.id)}"
                      data-title="${escapeHtml(group.title)}">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 7H20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                  <path d="M10 11V17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                  <path d="M14 11V17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                  <path d="M6 7L7 19C7.1 20.1 8 21 9.1 21H14.9C16 21 16.9 20.1 17 19L18 7" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                  <path d="M9 7V4.8C9 4.36 9.36 4 9.8 4H14.2C14.64 4 15 4.36 15 4.8V7" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="space-y-2">
            ${openItems.map(item => `
              <div class="flex items-start gap-2">
                <label class="mt-0.5">
                  <input type="checkbox" onchange="TasksPage.toggleGroupItem(${Number(item.id)})">
                </label>
                <div class="text-sm" style="color: #111827;">
                  ${escapeHtml(item.title)}
                </div>
              </div>
            `).join('')}

            <input type="text"
                   class="w-full mt-2 px-3 py-2 rounded-lg text-sm"
                   style="background: rgba(255,255,255,.7); border: 1px solid rgba(0,0,0,.08); color: #111827;"
                   placeholder="+ nový úkol"
                   onkeydown="TasksPage.handleGroupInput(event, ${Number(group.id)})">
          </div>

          ${doneItems.length ? `
            <div class="mt-4 pt-3" style="border-top: 1px solid rgba(0,0,0,.1);">
              <div class="text-xs mb-2" style="color: rgba(17,24,39,.7);">Splněné</div>
              <div class="space-y-2">
                ${doneItems.map(item => `
                  <div class="flex items-start gap-2" style="opacity: .75;">
                    <label class="mt-0.5">
                      <input type="checkbox" checked onchange="TasksPage.toggleGroupItem(${Number(item.id)})">
                    </label>
                    <div class="text-sm" style="color: #111827; text-decoration: line-through;">
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
  }

  function renderAllTasks() {
    if (!allTasksList) return;

    const filter = allFilter ? allFilter.value : 'open';
    const tasks = state.all.filter(task => filter === 'all' || !task.completed_at);

    if (!tasks.length) {
      allTasksList.innerHTML = `
        <div class="text-sm italic p-3 rounded-lg"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
          Žádné úkoly.
        </div>
      `;
      return;
    }

    allTasksList.innerHTML = tasks.map(task => {
      const done = !!task.completed_at;
      const owner = `${task.first_name || ''} ${task.last_name || ''}`.trim();

      return `
        <div class="rounded-xl p-3 cursor-pointer js-open-task-modal"
             style="background: var(--bg); border: 1px solid var(--border);"
             data-mode="edit"
             data-id="${Number(task.id)}"
             data-assigned-user-id="${Number(task.assigned_user_id)}"
             data-title="${escapeHtml(task.title)}"
             data-description="${escapeHtml(task.description || '')}"
             data-due-date="${escapeHtml(task.due_date || '')}"
             data-repeat-type="${escapeHtml(task.repeat_type || 'none')}"
             data-repeat-interval="${Number(task.repeat_interval || 1)}"
             data-completed-meta="${escapeHtml(buildCompletedMeta(task))}">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div style="color: var(--text); ${done ? 'text-decoration: line-through; opacity: .65;' : ''}">
                ${escapeHtml(task.title)}
              </div>
              <div class="text-xs mt-1" style="color: var(--muted);">
                ${escapeHtml(owner || 'Bez uživatele')}
                ${task.due_date ? ` • termín ${escapeHtml(formatDate(task.due_date))}` : ''}
              </div>
            </div>

            <label onclick="event.stopPropagation()">
              <input type="checkbox"
                     ${done ? 'checked' : ''}
                     onchange="TasksPage.toggleTask(${Number(task.id)})">
            </label>
          </div>
        </div>
      `;
    }).join('');
  }

  async function saveTask() {
    const payload = {
      id: taskModalId.value,
      title: taskModalTitle.value.trim(),
      description: taskModalDescription.value.trim(),
      due_date: taskModalDueDate.value,
      repeat_type: taskModalRepeatType.value,
      repeat_interval: taskModalRepeatInterval.value,
      assigned_user_id: taskModalAssignedUser ? taskModalAssignedUser.value : ''
    };

    if (!payload.title) {
      alert('Vyplň název úkolu.');
      return;
    }

    const endpoint = taskModalMode.value === 'edit' ? '/tasks/update' : '/tasks/create';
    await api(endpoint, payload);
    closeTaskModal();
    await loadTasks();
  }

  async function deleteTask() {
    const id = Number(taskModalId.value || 0);
    if (!id) return;

    const ok = confirm('Opravdu chceš tento úkol smazat?');
    if (!ok) return;

    await api('/tasks/delete', { id });
    closeTaskModal();
    await loadTasks();
  }

  async function saveGroup() {
    const payload = {
      id: groupModalId.value,
      title: groupModalTitle.value.trim(),
      color: groupModalColor.value
    };

    if (!payload.title) {
      alert('Vyplň název skupiny.');
      return;
    }

    const endpoint = groupModalMode.value === 'edit' ? '/tasks/group/update' : '/tasks/group/create';
    await api(endpoint, payload);
    closeGroupModal();
    await loadTasks();
  }

  async function deleteGroup(id, title) {
    const ok = confirm(`Opravdu chceš smazat skupinu "${title}"? Smažou se i všechny úkoly v ní.`);
    if (!ok) return;

    await api('/tasks/group/delete', { id });
    await loadTasks();
  }

  async function deleteCheckedGroupItems(groupId, title) {
    const ok = confirm(`Opravdu chceš ve skupině "${title}" smazat všechny zaškrtnuté úkoly?`);
    if (!ok) return;

    await api('/tasks/group/items/delete-completed', { group_id: groupId });
    await loadTasks();
  }

  async function toggleTask(id) {
    await api('/tasks/toggle', { id });
    await loadTasks();
  }

  async function toggleGroupItem(id) {
    await api('/tasks/group/item/toggle', { id });
    await loadTasks();
  }

  async function handleGroupInput(event, groupId) {
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
    await loadTasks();
  }

  document.addEventListener('click', async (e) => {
    const taskOpenBtn = e.target.closest('.js-open-task-modal');
    if (taskOpenBtn) {
      e.preventDefault();

      openTaskModal({
        id: taskOpenBtn.getAttribute('data-id') || '',
        assigned_user_id: taskOpenBtn.getAttribute('data-assigned-user-id') || '',
        title: taskOpenBtn.getAttribute('data-title') || '',
        description: taskOpenBtn.getAttribute('data-description') || '',
        due_date: taskOpenBtn.getAttribute('data-due-date') || '',
        repeat_type: taskOpenBtn.getAttribute('data-repeat-type') || 'none',
        repeat_interval: taskOpenBtn.getAttribute('data-repeat-interval') || 1,
        completed_meta: taskOpenBtn.getAttribute('data-completed-meta') || ''
      });
      return;
    }

    const groupOpenBtn = e.target.closest('.js-open-group-modal');
    if (groupOpenBtn) {
      e.preventDefault();

      openGroupModal({
        id: groupOpenBtn.getAttribute('data-id') || '',
        title: groupOpenBtn.getAttribute('data-title') || '',
        color: groupOpenBtn.getAttribute('data-color') || 'yellow'
      });
      return;
    }

    const deleteCheckedBtn = e.target.closest('.js-delete-checked-group-items');
    if (deleteCheckedBtn) {
      e.preventDefault();
      try {
        await deleteCheckedGroupItems(
          Number(deleteCheckedBtn.getAttribute('data-id') || 0),
          deleteCheckedBtn.getAttribute('data-title') || 'bez názvu'
        );
      } catch (err) {
        alert(err.message || 'Zaškrtnuté úkoly se nepodařilo smazat.');
      }
      return;
    }

    const deleteGroupBtn = e.target.closest('.js-delete-group');
    if (deleteGroupBtn) {
      e.preventDefault();
      try {
        await deleteGroup(
          Number(deleteGroupBtn.getAttribute('data-id') || 0),
          deleteGroupBtn.getAttribute('data-title') || 'bez názvu'
        );
      } catch (err) {
        alert(err.message || 'Skupinu se nepodařilo smazat.');
      }
      return;
    }

    const colorBtn = e.target.closest('.js-group-color');
    if (colorBtn) {
      e.preventDefault();
      setSelectedGroupColor(colorBtn.getAttribute('data-color') || 'yellow');
      return;
    }

    if (e.target && e.target.getAttribute('data-close-task-modal') === '1') {
      e.preventDefault();
      closeTaskModal();
      return;
    }

    if (e.target && e.target.getAttribute('data-close-group-modal') === '1') {
      e.preventDefault();
      closeGroupModal();
      return;
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (!taskModal.classList.contains('hidden')) {
        closeTaskModal();
      }
      if (!groupModal.classList.contains('hidden')) {
        closeGroupModal();
      }
    }
  });

  if (taskModalSaveButton) {
    taskModalSaveButton.addEventListener('click', async () => {
      try {
        await saveTask();
      } catch (err) {
        alert(err.message || 'Úkol se nepodařilo uložit.');
      }
    });
  }

  if (taskModalDeleteButton) {
    taskModalDeleteButton.addEventListener('click', async () => {
      try {
        await deleteTask();
      } catch (err) {
        alert(err.message || 'Úkol se nepodařilo smazat.');
      }
    });
  }

  if (groupModalSaveButton) {
    groupModalSaveButton.addEventListener('click', async () => {
      try {
        await saveGroup();
      } catch (err) {
        alert(err.message || 'Skupinu se nepodařilo uložit.');
      }
    });
  }

  if (personalFilter) {
    personalFilter.addEventListener('change', renderPersonalTasks);
  }

  if (allFilter) {
    allFilter.addEventListener('change', renderAllTasks);
  }

  window.TasksPage = {
    toggleTask: async function (id) {
      try {
        await toggleTask(id);
      } catch (err) {
        alert(err.message || 'Nepodařilo se změnit stav úkolu.');
      }
    },
    toggleGroupItem: async function (id) {
      try {
        await toggleGroupItem(id);
      } catch (err) {
        alert(err.message || 'Nepodařilo se změnit stav úkolu.');
      }
    },
    handleGroupInput: async function (event, groupId) {
      try {
        await handleGroupInput(event, groupId);
      } catch (err) {
        alert(err.message || 'Nepodařilo se přidat skupinový úkol.');
      }
    }
  };

  setSelectedGroupColor('yellow');
  loadTasks();
})();
</script>
