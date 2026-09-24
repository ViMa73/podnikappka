<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 – Stránka nenalezena</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-6">

<div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-10 text-center">
    <div class="text-sm text-gray-500 mb-2">Chyba 404</div>
    <h1 class="text-3xl font-bold text-gray-800 mb-3">Stránka nenalezena</h1>
    <p class="text-gray-500 mb-8">Odkaz je neplatný nebo stránka byla přesunuta.</p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="/" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg font-semibold">
            Přihlášení
        </a>
        <button onclick="history.back()"
                class="bg-gray-100 hover:bg-gray-200 px-5 py-3 rounded-lg font-semibold">
            Zpět
        </button>
    </div>
</div>

</body>
</html>