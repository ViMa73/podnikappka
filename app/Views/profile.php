<h2 class="text-xl font-semibold mb-2">Můj profil</h2>

<div class="space-y-6">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">

    <!-- KARTA 1: Profil + avatar + theme -->
    <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
        <div class="flex items-start justify-between gap-6 flex-col sm:flex-row">

            <div class="flex items-center gap-4">
                <img src="<?= \Core\Auth::avatarUrl(96) ?>" class="w-20 h-20 rounded-full object-cover border" style="border-color: var(--border);" alt="Avatar">

                <div>
                    <div class="text-xl font-semibold">
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </div>
                    <div class="text-sm mt-1" style="color: var(--muted);">
                        <?= htmlspecialchars($user['email']) ?>
                    </div>
                    <?php if (!empty($user['phone'])): ?>
                      <div class="text-sm mt-1" style="color: var(--muted);">
                        <?= htmlspecialchars($user['phone']) ?>
                      </div>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="/profile/avatar" enctype="multipart/form-data" class="w-full sm:w-auto">
                <?= \Core\CSRF::field() ?>
                <div class="flex items-center gap-3">
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg
                                   file:border-0 file:text-sm file:font-semibold
                                   file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200
                                   dark:file:bg-gray-700 dark:file:text-gray-100 dark:hover:file:bg-gray-600">
                    <button class="btn-primary px-4 py-2 rounded-lg font-semibold">
                        Nahrát
                    </button>
                </div>
                <p class="mt-2 text-xs" style="color: var(--muted)" >
                    JPG/PNG/WEBP, max 4 MB. Automaticky ořízneme na čtverec a zmenšíme.
                </p>
            </form>
        </div>

        <hr class="my-6 border-gray-100 dark:border-gray-700" style="border-color: var(--border)">

        <form method="POST" action="/profile/update" class="space-y-4">
            <?= \Core\CSRF::field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm" style="color: var(--text);">Jméno</label>
                    <input name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required
                           class="mt-1 w-full px-4 py-3 rounded-lg" style="background: var(--bg); border: 1px solid var(--border);">
                </div>
                <div>
                    <label class="text-sm" style="color: var(--text);">Příjmení</label>
                    <input name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required class="mt-1 w-full px-4 py-3 rounded-lg" style="background: var(--bg); border: 1px solid var(--border);">
                </div>
            </div>

            <div class="flex items-center justify-between flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-auto">
                    <label class="text-sm" style="color: var(--text);">Téma</label>
                    <select name="theme"
                            class="mt-1 w-full sm:w-56 px-4 py-3 border rounded-lg" style="border: 1px solid var(--border); color: var(--text); background: var(--bg);">
                        <option value="light" <?= ($user['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Světlé</option>
                        <option value="dark" <?= ($user['theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Tmavé</option>
                        <option value="coffee" <?= ($user['theme'] ?? 'coffee') === 'coffee' ? 'selected' : '' ?>>Coffee</option>
                        <option value="olive" <?= ($user['theme'] ?? 'olive') === 'olive' ? 'selected' : '' ?>>Olive</option>
                        <option value="slate" <?= ($user['theme'] ?? 'slate') === 'slate' ? 'selected' : '' ?>>Slate</option>
                        <option value="forest" <?= ($user['theme'] ?? 'forest') === 'forest' ? 'selected' : '' ?>>Forest</option>
                        <option value="midnight" <?= ($user['theme'] ?? 'midnight') === 'midnight' ? 'selected' : '' ?>>Midnight</option>
                        <option value="crazy" <?= ($user['theme'] ?? 'crazy') === 'crazy' ? 'selected' : '' ?>>Crazy</option>
                    </select>
                </div>

                <button class="btn-primary w-full sm:w-auto px-5 py-3 rounded-lg font-semibold">
                    Uložit změny
                </button>
            </div>
        </form>
    </div>

    <!-- KARTA 2: Změna hesla -->
    <div class="rounded-2xl shadow p-6" style="background: var(--card); border: 1px solid var(--border);">
        <h2 class="text-lg font-semibold mb-1">Změna hesla</h2>
        <p class="text-sm mb-6" style="color: var(--text);">
            Po změně hesla budeš odhlášen.
        </p>

        <form method="POST" action="/profile/password" class="space-y-4">
            <?= \Core\CSRF::field() ?>

            <div>
                <label class="text-sm" style="color: var(--text);">Původní heslo</label>
                <input type="password" name="current_password" required
                       class="mt-1 w-full px-4 py-3 rounded-lg" style="background: var(--bg); border: 1px solid var(--border);">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm" style="color: var(--text);">Nové heslo</label>
                    <input type="password" name="new_password" required
                           class="mt-1 w-full px-4 py-3 rounded-lg" style="background: var(--bg); border: 1px solid var(--border);">
                </div>
                <div>
                    <label class="text-sm" style="color: var(--text);">Nové heslo znovu</label>
                    <input type="password" name="new_password_confirm" required
                           class="mt-1 w-full px-4 py-3 rounded-lg" style="background: var(--bg); border: 1px solid var(--border);">
                </div>
            </div>

            <button class="bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-lg font-semibold">
                Změnit heslo
            </button>
        </form>
    </div>



  </div>
</div>
