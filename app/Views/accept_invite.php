<h2 class="text-2xl font-bold mb-4">Dokončení registrace</h2>

<form method="POST" class="max-w-sm bg-white dark:bg-gray-800 p-6 rounded shadow">
    <?= \Core\CSRF::field() ?>
    <input type="password" name="password" placeholder="Zvolte heslo" required class="w-full p-2 mb-2 border rounded">

    <select name="role" class="w-full p-2 mb-4 border rounded">
        <option value="manager">Manager</option>
        <option value="worker">Worker</option>
    </select>

    <button class="bg-green-500 text-white px-4 py-2 rounded">
        Dokončit registraci
    </button>
</form>