<h2 class="text-xl font-semibold mb-2" style="color: var(--text);">Detail dovolené</h2>

<div class="rounded-2xl shadow p-6"
     style="background: var(--card); border: 1px solid var(--border);">

  <form method="POST" action="/vacations/<?= (int)$vacation['id'] ?>" class="space-y-4">
    <?= \Core\CSRF::field() ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div hidden>
        <label class="text-sm" style="color: var(--text);">ID</label>
        <input value="<?= (int)$vacation['id'] ?>" disabled
               class="mt-1 w-full px-4 py-3 rounded-lg opacity-70"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>

      <div hidden>
        <label class="text-sm" style="color: var(--text);">ID majitele</label>
        <input value="<?= (int)$vacation['user_id'] ?>" disabled
               class="mt-1 w-full px-4 py-3 rounded-lg opacity-70"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>
    </div>

    <div>
      <label class="text-sm" style="color: var(--text);">Název</label>
      <input name="title" value="<?= htmlspecialchars($vacation['title']) ?>" required
             class="mt-1 w-full px-4 py-3 rounded-lg"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm" style="color: var(--text);">Počáteční datum</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($vacation['date_from']) ?>" required
               class="mt-1 w-full px-4 py-3 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>

      <div>
        <label class="text-sm" style="color: var(--text);">Koncové datum</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($vacation['date_to']) ?>" required
               class="mt-1 w-full px-4 py-3 rounded-lg"
               style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
      </div>
    </div>

    <p class="text-sm mb-6" style="color: var(--muted);">
      Pozor! Dovolená která je přes přelom měsíce nebo roku musí být rozdělena.
    </p>

    <div>
      <label class="text-sm" style="color: var(--text);">Celkem hodin</label>
      <input name="total_hours" value="<?= htmlspecialchars($vacation['total_hours'] ?? '') ?>"
             class="mt-1 w-full px-4 py-3 rounded-lg"
             style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
    </div>

    <p class="text-sm mb-6" style="color: var(--muted);">
      Počet pracovních dní * denní úvazek.
    </p>

    <div>
      <label class="text-sm" style="color: var(--text);">Poznámka</label>
      <textarea name="note" rows="5"
                class="mt-1 w-full px-4 py-3 rounded-lg"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"><?= htmlspecialchars($vacation['note'] ?? '') ?></textarea>
    </div>

    <div class="flex items-center justify-between gap-3 pt-2">
      <div>
        <button type="submit"
                form="vacation-delete-form"
                onclick="return confirm('Opravdu chceš tuto dovolenou smazat?');"
                class="px-5 py-3 rounded-lg font-semibold"
                style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;">
          Smazat dovolenou
        </button>
      </div>

      <div>
        <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
          Uložit změny
        </button>
      </div>
    </div>
  </form>

  <form id="vacation-delete-form" method="POST" action="/vacations/<?= (int)$vacation['id'] ?>/delete" class="hidden">
    <?= \Core\CSRF::field() ?>
  </form>
</div>
