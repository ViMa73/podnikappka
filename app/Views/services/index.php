<?php
$weekStr     = $monday->format('Y-m-d');
$prevWeek    = $monday->modify('-7 days')->format('Y-m-d');
$nextWeek    = $monday->modify('+7 days')->format('Y-m-d');
$currentWeek = (new \DateTimeImmutable('now'))->modify('monday this week')->format('Y-m-d');

$todayStr = (new \DateTimeImmutable('now'))->format('Y-m-d');

$meId = (int)($_SESSION['user_id'] ?? 0);
$role = \Core\Auth::role();

$managerCanAssign = !empty($company['manager_can_assign_services']);
$canAssignColleaguesGlobal = ($role === 'owner') || ($role === 'manager' && $managerCanAssign);
$canManageDayNotesForOthers = in_array($role, ['owner', 'manager'], true);

$bgClosed      = 'color-mix(in srgb, var(--closed) 60%, transparent)';
$bgFree        = 'color-mix(in srgb, var(--free) 60%, transparent)';
$bgTaken       = 'color-mix(in srgb, var(--taken) 60%, transparent)';
$bgVacation    = 'color-mix(in srgb, var(--vacation) 60%, transparent)';
$bgDayNote     = 'color-mix(in srgb, var(--note) 60%, transparent)';

$bgClosedToday   = 'color-mix(in srgb, var(--closed-today) 60%, transparent)';
$bgFreeToday     = 'color-mix(in srgb, var(--free-today) 60%, transparent)';
$bgTakenToday    = 'color-mix(in srgb, var(--taken-today) 60%, transparent)';
$bgVacationToday = 'color-mix(in srgb, var(--vacation-today) 60%, transparent)';
$bgDayNoteToday  = 'color-mix(in srgb, var(--note-today) 60%, transparent)';

$dayNotesMapJson = json_encode($dayNotesMap ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
  <h2 class="text-xl font-semibold mb-2" style="color: var(--text);">Služby</h2>
  <p class="text-sm mb-4" style="color: var(--muted);">
    Týden:
    <strong style="color: var(--text);"><?= $monday->format('j.n.') ?></strong>
    –
    <strong style="color: var(--text);"><?= $sunday->format('j.n.') ?></strong>
  </p>

  <div class="overflow-x-auto">
    <table class="min-w-[900px] w-full text-sm border-separate text-center" style="border-spacing:0;">
      <thead>
        <tr>
          <th class="sticky left-0 z-10 text-center px-4 py-3 whitespace-nowrap"
              style="background: var(--card); color: var(--muted); border: 1px solid var(--border);">
            Místo
          </th>

          <?php foreach ($days as $d): ?>
            <?php $isTodayCol = (($d['dateY'] ?? '') === $todayStr); ?>
            <th class="text-center px-4 py-3 whitespace-nowrap"
                style="color: var(--muted); border: 1px solid var(--border);
                       <?= $isTodayCol ? 'background: color-mix(in srgb, var(--primary) 10%, transparent);' : '' ?>">
              <?= htmlspecialchars($d['label'] ?? '') ?>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($places)): ?>
          <tr>
            <td class="px-4 py-4" colspan="<?= 1 + count($days) ?>" style="color: var(--muted);">
              Nemáš založená žádná místa. Nejdřív je vytvoř v Nastavení → Modul Služby.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($places as $p): ?>
            <?php
              $mask = (int)($p['days_mask'] ?? 0);
              $placeId = (int)($p['id'] ?? 0);
              $placeName = (string)($p['name'] ?? '');
              $placeDesc = (string)($p['description'] ?? '');
              $notesEnabledPlace = ((int)($p['notes_enabled'] ?? 0) === 1);
            ?>
            <tr>
              <td class="sticky left-0 z-10 px-4 py-3 align-top"
                  style="background: var(--card); border: 1px solid var(--border);">
                <div class="font-semibold" style="color: var(--text);">
                  <?= htmlspecialchars($placeName) ?>
                </div>
                <?php if ($placeDesc !== ''): ?>
                  <div class="text-xs" style="color: var(--muted);">
                    <?= htmlspecialchars($placeDesc) ?>
                  </div>
                <?php endif; ?>
              </td>

              <?php foreach ($days as $d): ?>
                <?php
                  $bit   = (int)($d['bit'] ?? 0);
                  $dateY = (string)($d['dateY'] ?? '');
                  $isToday = ($dateY === $todayStr);

                  $open = ($bit > 0) && (($mask & $bit) === $bit);

                  $a = $assignments[$placeId][$dateY] ?? null;
                  $isAssigned = is_array($a);

                  $assignedName   = $isAssigned ? (string)($a['name'] ?? '') : '';
                  $assignedUserId = $isAssigned ? (int)($a['user_id'] ?? 0) : 0;
                  $note           = $isAssigned ? trim((string)($a['note'] ?? '')) : '';

                  $canUnassign = $isAssigned && (
                      $assignedUserId === $meId ||
                      $role === 'owner' ||
                      ($role === 'manager' && $managerCanAssign)
                  );

                  $canEditNote = $notesEnabledPlace && $isAssigned && (
                      $assignedUserId === $meId ||
                      $role === 'owner' ||
                      ($role === 'manager' && $managerCanAssign)
                  );

                  if (!$open) {
                      $cellBg = $isToday ? $bgClosedToday : $bgClosed;
                  } elseif ($isAssigned) {
                      $cellBg = $isToday ? $bgTakenToday : $bgTaken;
                  } else {
                      $cellBg = $isToday ? $bgFreeToday : $bgFree;
                  }
                ?>

                <td class="px-3 py-3 align-top"
                    style="border: 1px solid var(--border); background: <?= $cellBg ?>;">

                  <?php if (!$open): ?>
                    <div class="text-xs font-semibold" style="color: var(--text);">Zavřeno</div>

                  <?php elseif ($isAssigned): ?>
                    <div class="text-sm font-semibold" style="color: var(--text);">
                      <?= htmlspecialchars($assignedName) ?>
                    </div>

                    <?php if ($note !== ''): ?>
                      <div class="mt-1 text-xs" style="color: var(--muted);">
                        <?= nl2br(htmlspecialchars($note)) ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($canEditNote): ?>
                      <button type="button"
                              class="mt-2 px-3 py-1 rounded-lg text-xs font-semibold js-open-note-modal btn-secondary"
                              style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                              data-place-id="<?= $placeId ?>"
                              data-service-date="<?= htmlspecialchars($dateY) ?>"
                              data-place-name="<?= htmlspecialchars($placeName) ?>"
                              data-day-label="<?= htmlspecialchars((string)($d['label'] ?? '')) ?>"
                              data-note="<?= htmlspecialchars($note, ENT_QUOTES) ?>">
                        Poznámka
                      </button>
                    <?php endif; ?>

                    <?php if ($canUnassign): ?>
                      <form method="POST" action="/services/unassign" class="mt-2">
                        <?= \Core\CSRF::field() ?>
                        <input type="hidden" name="place_id" value="<?= $placeId ?>">
                        <input type="hidden" name="service_date" value="<?= htmlspecialchars($dateY) ?>">
                        <input type="hidden" name="_week" value="<?= htmlspecialchars($weekStr) ?>">
                        <button type="submit"
                                class="px-3 py-1 rounded-lg text-xs font-semibold btn-primary">
                          Odhlásit
                        </button>
                      </form>
                    <?php endif; ?>

                  <?php else: ?>
                    <div class="text-sm font-semibold" style="color: var(--text);">K dispozici</div>

                    <form method="POST" action="/services/assign" class="mt-2">
                      <?= \Core\CSRF::field() ?>
                      <input type="hidden" name="place_id" value="<?= $placeId ?>">
                      <input type="hidden" name="service_date" value="<?= htmlspecialchars($dateY) ?>">
                      <input type="hidden" name="_week" value="<?= htmlspecialchars($weekStr) ?>">
                      <button type="submit"
                              class="px-3 py-1 rounded-lg text-xs font-semibold btn-primary">
                        Zapsat
                      </button>
                    </form>

                    <?php if ($canAssignColleaguesGlobal && !empty($colleagues)): ?>
                      <button type="button"
                              class="mt-2 px-3 py-1 rounded-lg text-xs font-semibold js-open-assign-modal btn-primary"
                              data-place-id="<?= $placeId ?>"
                              data-service-date="<?= htmlspecialchars($dateY) ?>"
                              data-place-name="<?= htmlspecialchars($placeName) ?>"
                              data-day-label="<?= htmlspecialchars((string)($d['label'] ?? '')) ?>">
                        Zapsat kolegu
                      </button>
                    <?php endif; ?>

                  <?php endif; ?>

                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>

          <!-- ŘÁDEK: Dovolené -->
          <tr>
            <td class="sticky left-0 z-10 px-4 py-3 align-top font-semibold"
                style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              Dovolené
            </td>

            <?php foreach ($days as $d): ?>
              <?php
                $dateY = (string)($d['dateY'] ?? '');
                $isToday = ($dateY === $todayStr);
                $vacationUsers = $vacationsByDate[$dateY] ?? [];
                $cellBg = $isToday ? $bgVacationToday : $bgVacation;
              ?>
              <td class="px-3 py-3 align-top"
                  style="border: 1px solid var(--border); background: <?= $cellBg ?>;">

                <?php if (!empty($vacationUsers)): ?>
                  <div class="space-y-1">
                    <?php foreach ($vacationUsers as $vacationUser): ?>
                      <div class="text-sm font-semibold" style="color: var(--text);">
                        <?= htmlspecialchars($vacationUser) ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

              </td>
            <?php endforeach; ?>
          </tr>

          <!-- ŘÁDEK: Denní poznámky -->
          <tr>
            <td class="sticky left-0 z-10 px-4 py-3 align-top font-semibold"
                style="background: var(--card); border: 1px solid var(--border); color: var(--text);">
              Denní poznámky
            </td>

            <?php foreach ($days as $d): ?>
              <?php
                $dateY = (string)($d['dateY'] ?? '');
                $isToday = ($dateY === $todayStr);
                $dayNotes = $dayNotesByDate[$dateY] ?? [];
                $myDayNote = $dayNotesMap[$dateY][$meId] ?? '';
                $cellBg = $isToday ? $bgDayNoteToday : $bgDayNote;
              ?>
              <td class="px-3 py-3 align-top"
                  style="border: 1px solid var(--border); background: <?= $cellBg ?>;">

                <?php if (!empty($dayNotes)): ?>
                  <div class="space-y-2 text-left">
                    <?php foreach ($dayNotes as $idx => $dayNote): ?>
                      <div>
                        <div class="text-sm font-semibold" style="color: var(--text);">
                          <?= htmlspecialchars($dayNote['name']) ?>
                        </div>
                        <div class="text-xs mt-1" style="color: var(--muted);">
                          <?= nl2br(htmlspecialchars($dayNote['note'])) ?>
                        </div>
                      </div>

                      <?php if ($idx < count($dayNotes) - 1): ?>
                        <div style="border-top: 1px solid var(--border); opacity: .6;"></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <button type="button"
                        class="mt-3 px-3 py-1 rounded-lg text-xs font-semibold js-open-day-note-modal"
                        style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                        data-note-date="<?= htmlspecialchars($dateY) ?>"
                        data-day-label="<?= htmlspecialchars((string)($d['label'] ?? '')) ?>"
                        data-user-id="<?= $meId ?>"
                        data-user-name="Moje poznámka"
                        data-note="<?= htmlspecialchars((string)$myDayNote, ENT_QUOTES) ?>"
                        data-mode="self">
                  Poznámka
                </button>

                <?php if ($canManageDayNotesForOthers && !empty($colleagues)): ?>
                  <button type="button"
                          class="mt-2 px-3 py-1 rounded-lg text-xs font-semibold js-open-day-note-modal"
                          style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                          data-note-date="<?= htmlspecialchars($dateY) ?>"
                          data-day-label="<?= htmlspecialchars((string)($d['label'] ?? '')) ?>"
                          data-mode="colleague">
                    Poznámka kolegy
                  </button>
                <?php endif; ?>

              </td>
            <?php endforeach; ?>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- MODAL: Zapsat kolegu -->
  <?php if (!empty($colleagues)): ?>
    <div id="assignModal" class="fixed inset-0 z-50 hidden items-center justify-center" aria-hidden="true">
      <div class="absolute inset-0" style="background: rgba(0,0,0,.45);" data-close-modal="1"></div>

      <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
           style="background: var(--card); border: 1px solid var(--border);">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold" style="color: var(--text);">Zapsat kolegu</h3>
            <p class="text-sm mt-1" style="color: var(--muted);" id="assignModalSubtitle"></p>
          </div>

          <button type="button"
                  class="px-3 py-2 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  data-close-modal="1"
                  aria-label="Zavřít">✕</button>
        </div>

        <form method="POST" action="/services/assign" class="mt-5 space-y-4" id="assignModalForm">
          <?= \Core\CSRF::field() ?>
          <input type="hidden" name="place_id" id="assignModalPlaceId" value="">
          <input type="hidden" name="service_date" id="assignModalServiceDate" value="">
          <input type="hidden" name="_week" value="<?= htmlspecialchars($weekStr) ?>">

          <div>
            <label class="block text-sm mb-1" style="color: var(--text);">Vyber kolegu</label>
            <select name="user_id"
                    class="w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                    id="assignModalUserSelect" required>
              <?php foreach ($colleagues as $u): ?>
                <?php
                  $uid = (int)($u['id'] ?? 0);
                  $fn  = (string)($u['first_name'] ?? '');
                  $ln  = (string)($u['last_name'] ?? '');
                ?>
                <option value="<?= $uid ?>"><?= htmlspecialchars(trim($fn.' '.$ln)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="flex gap-3 justify-end">
            <button type="button"
                    class="px-5 py-3 rounded-lg font-semibold"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                    data-close-modal="1">
              Zrušit
            </button>

            <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
              Potvrdit zápis
            </button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- MODAL: Poznámka ke službě -->
  <div id="noteModal" class="fixed inset-0 z-50 hidden items-center justify-center" aria-hidden="true">
    <div class="absolute inset-0" style="background: rgba(0,0,0,.45);" data-close-note="1"></div>

    <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-lg font-semibold" style="color: var(--text);">Poznámka ke službě</h3>
          <p class="text-sm mt-1" style="color: var(--muted);" id="noteModalSubtitle"></p>
        </div>

        <button type="button"
                class="px-3 py-2 rounded-lg"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                data-close-note="1"
                aria-label="Zavřít">✕</button>
      </div>

      <form method="POST" action="/services/note" class="mt-5 space-y-4">
        <?= \Core\CSRF::field() ?>
        <input type="hidden" name="place_id" id="noteModalPlaceId" value="">
        <input type="hidden" name="service_date" id="noteModalServiceDate" value="">
        <input type="hidden" name="_week" value="<?= htmlspecialchars($weekStr) ?>">

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Text poznámky</label>
          <textarea name="note" id="noteModalTextarea" rows="4"
                    class="w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                    placeholder="Napiš krátkou poznámku..."></textarea>
          <div class="text-xs mt-2" style="color: var(--muted);">
            Poznámku můžeš i smazat (nechat prázdné a uložit).
          </div>
        </div>

        <div class="flex gap-3 justify-end">
          <button type="button"
                  class="px-5 py-3 rounded-lg font-semibold"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  data-close-note="1">
            Zrušit
          </button>
          <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit poznámku
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: Denní poznámka -->
  <div id="dayNoteModal" class="fixed inset-0 z-50 hidden items-center justify-center" aria-hidden="true">
    <div class="absolute inset-0" style="background: rgba(0,0,0,.45);" data-close-day-note="1"></div>

    <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-lg font-semibold" style="color: var(--text);">Denní poznámka</h3>
          <p class="text-sm mt-1" style="color: var(--muted);" id="dayNoteModalSubtitle"></p>
        </div>

        <button type="button"
                class="px-3 py-2 rounded-lg"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                data-close-day-note="1"
                aria-label="Zavřít">✕</button>
      </div>

      <form method="POST" action="/services/day-note" class="mt-5 space-y-4">
        <?= \Core\CSRF::field() ?>
        <input type="hidden" name="note_date" id="dayNoteModalDate" value="">
        <input type="hidden" name="user_id" id="dayNoteModalUserId" value="">
        <input type="hidden" name="_week" value="<?= htmlspecialchars($weekStr) ?>">

        <div id="dayNoteUserSelectWrap" class="hidden">
          <label class="block text-sm mb-1" style="color: var(--text);">Uživatel</label>
          <select id="dayNoteModalUserSelect"
                  class="w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <?php foreach ($colleagues as $u): ?>
              <?php
                $uid = (int)($u['id'] ?? 0);
                $fn  = (string)($u['first_name'] ?? '');
                $ln  = (string)($u['last_name'] ?? '');
              ?>
              <option value="<?= $uid ?>"><?= htmlspecialchars(trim($fn . ' ' . $ln)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1" style="color: var(--text);">Text poznámky</label>
          <textarea name="note" id="dayNoteModalTextarea" rows="5"
                    class="w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                    placeholder="Napiš poznámku ke dni..."></textarea>
          <div class="text-xs mt-2" style="color: var(--muted);">
            Když poznámku smažeš a uložíš prázdné pole, v tabulce se už nezobrazí.
          </div>
        </div>

        <div class="flex gap-3 justify-end">
          <button type="button"
                  class="px-5 py-3 rounded-lg font-semibold"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  data-close-day-note="1">
            Zrušit
          </button>
          <button type="submit" class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit poznámku
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="flex flex-wrap gap-3 mt-6 justify-center">
    <a href="/services?week=<?= htmlspecialchars($prevWeek) ?>"
       class="px-4 py-2 rounded-lg"
       style="border: 1px solid var(--border); color: var(--text); background: var(--bg);">
      ← Předchozí týden
    </a>

    <a href="/services?week=<?= htmlspecialchars($currentWeek) ?>"
       class="px-4 py-2 rounded-lg font-semibold"
       style="border: 1px solid var(--border); color: var(--text);
              background: color-mix(in srgb, var(--primary) 14%, var(--bg));">
      Aktuální týden
    </a>

    <a href="/services?week=<?= htmlspecialchars($nextWeek) ?>"
       class="px-4 py-2 rounded-lg"
       style="border: 1px solid var(--border); color: var(--text); background: var(--bg);">
      Následující týden →
    </a>
  </div>

  <div class="mt-6 flex flex-col items-center">
    <label class="block text-sm mb-2" style="color: var(--muted);">
      Přejít na týden podle data:
    </label>
    <input type="date" id="weekPicker"
           class="px-4 py-2 rounded-lg"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
           value="<?= htmlspecialchars($weekStr) ?>">
  </div>
</div>

<script>
  const picker = document.getElementById('weekPicker');
  if (picker) {
    picker.addEventListener('change', () => {
      if (!picker.value) return;
      const d = new Date(picker.value + 'T00:00:00');
      const day = d.getDay();
      const diffToMonday = (day === 0) ? -6 : (1 - day);
      d.setDate(d.getDate() + diffToMonday);

      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');

      window.location.href = `/services?week=${yyyy}-${mm}-${dd}`;
    });
  }
</script>

<script>
(function () {
  const modal = document.getElementById('assignModal');
  if (!modal) return;

  const placeIdEl = document.getElementById('assignModalPlaceId');
  const dateEl = document.getElementById('assignModalServiceDate');
  const subtitleEl = document.getElementById('assignModalSubtitle');
  const userSelect = document.getElementById('assignModalUserSelect');

  function openModal(btn) {
    placeIdEl.value = btn.getAttribute('data-place-id') || '';
    dateEl.value = btn.getAttribute('data-service-date') || '';
    const placeName = btn.getAttribute('data-place-name') || '';
    const dayLabel = btn.getAttribute('data-day-label') || '';
    subtitleEl.textContent = `${placeName} • ${dayLabel}`;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => userSelect && userSelect.focus(), 0);
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-open-assign-modal');
    if (!btn) return;
    e.preventDefault();
    openModal(btn);
  });

  modal.addEventListener('click', (e) => {
    if (e.target && e.target.getAttribute('data-close-modal') === '1') {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
  });
})();
</script>

<script>
(function () {
  const modal = document.getElementById('noteModal');
  if (!modal) return;

  const placeIdEl = document.getElementById('noteModalPlaceId');
  const dateEl = document.getElementById('noteModalServiceDate');
  const subtitleEl = document.getElementById('noteModalSubtitle');
  const textarea = document.getElementById('noteModalTextarea');

  function openModal(btn) {
    placeIdEl.value = btn.getAttribute('data-place-id') || '';
    dateEl.value = btn.getAttribute('data-service-date') || '';
    const placeName = btn.getAttribute('data-place-name') || '';
    const dayLabel = btn.getAttribute('data-day-label') || '';
    subtitleEl.textContent = `${placeName} • ${dayLabel}`;

    textarea.value = btn.getAttribute('data-note') || '';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => textarea && textarea.focus(), 0);
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-open-note-modal');
    if (btn) {
      e.preventDefault();
      openModal(btn);
      return;
    }
  });

  modal.addEventListener('click', (e) => {
    if (e.target && e.target.getAttribute('data-close-note') === '1') {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
  });
})();
</script>

<script>
(function () {
  const modal = document.getElementById('dayNoteModal');
  if (!modal) return;

  const notesMap = <?= $dayNotesMapJson ?: '{}' ?>;

  const dateEl = document.getElementById('dayNoteModalDate');
  const userIdEl = document.getElementById('dayNoteModalUserId');
  const subtitleEl = document.getElementById('dayNoteModalSubtitle');
  const textarea = document.getElementById('dayNoteModalTextarea');
  const userWrap = document.getElementById('dayNoteUserSelectWrap');
  const userSelect = document.getElementById('dayNoteModalUserSelect');

  function fillNote(dateKey, userId) {
    const note = (notesMap?.[dateKey]?.[userId]) || '';
    textarea.value = note;
  }

  function openModal(btn) {
    const dateKey = btn.getAttribute('data-note-date') || '';
    const dayLabel = btn.getAttribute('data-day-label') || '';
    const mode = btn.getAttribute('data-mode') || 'self';

    dateEl.value = dateKey;

    if (mode === 'colleague') {
      userWrap.classList.remove('hidden');
      const selectedUserId = userSelect.value || '';
      userIdEl.value = selectedUserId;
      subtitleEl.textContent = `Kolega • ${dayLabel}`;
      fillNote(dateKey, selectedUserId);
      setTimeout(() => userSelect && userSelect.focus(), 0);
    } else {
      userWrap.classList.add('hidden');
      const userId = btn.getAttribute('data-user-id') || '';
      const userName = btn.getAttribute('data-user-name') || 'Moje poznámka';
      userIdEl.value = userId;
      subtitleEl.textContent = `${userName} • ${dayLabel}`;
      fillNote(dateKey, userId);
      setTimeout(() => textarea && textarea.focus(), 0);
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

  if (userSelect) {
    userSelect.addEventListener('change', () => {
      userIdEl.value = userSelect.value || '';
      fillNote(dateEl.value, userSelect.value || '');
    });
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-open-day-note-modal');
    if (btn) {
      e.preventDefault();
      openModal(btn);
      return;
    }
  });

  modal.addEventListener('click', (e) => {
    if (e.target && e.target.getAttribute('data-close-day-note') === '1') {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
  });
})();
</script>
