<div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-800">Přihlášení</h1>
        <p class="text-gray-500 mt-2">Přihlaste se do svého účtu</p>
    </div>

    <form method="POST" action="/login" class="space-y-5">
        <?= \Core\CSRF::field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
            <input type="email" name="email" required
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Heslo</label>
            <input type="password" name="password" required
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition">
            Přihlásit se
        </button>
    </form>
    <div class="text-right">
        <a href="/forgot-password" class="text-sm hover:underline" style="color: var(--primary);">
            Zapomenuté heslo?
        </a>
    </div>