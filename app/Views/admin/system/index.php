<?php
$hasPendingMigrations = !empty($pendingMigrations);
$runButtonClass = $hasPendingMigrations
    ? ''
    : 'opacity-60 cursor-not-allowed';

$runButtonStyle = $hasPendingMigrations
    ? 'background: var(--primary); color: var(--primary-text);'
    : 'background: var(--border); color: var(--muted);';

$runButtonDisabled = $hasPendingMigrations ? '' : 'disabled';
?>

<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold" style="color: var(--text);">Systém / Aktualizace</h1>
    <p class="text-sm mt-1" style="color: var(--muted);">
      Přehled nasazené verze, release notes a databázových migrací.
    </p>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="p-4 rounded-xl" style="background: var(--success-bg, #ecfdf5); color: var(--success-text, #065f46); border: 1px solid var(--success-border, #a7f3d0);">
      <?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="p-4 rounded-xl" style="background: var(--error-bg, #fef2f2); color: var(--error-text, #991b1b); border: 1px solid var(--error-border, #fecaca);">
      <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
      <div class="flex-1">
        <h2 class="text-lg font-semibold" style="color: var(--text);">Vzdálené aktualizace z GitHubu</h2>
        <p class="text-sm mt-2" style="color: var(--muted);">Oficiální zdroj aktualizací: GitHub · <a href="https://github.com/ViMa73/podnikappka" target="_blank" rel="noopener noreferrer" class="underline">ViMa73/podnikappka</a></p>
          <?php if (!empty($updateError)): ?>
            <div class="mt-4 p-3 rounded-xl text-sm" style="background: var(--error-bg, #fef2f2); color: var(--error-text, #991b1b); border: 1px solid var(--error-border, #fecaca);">Kontrolu aktualizací se nepodařilo provést: <?= htmlspecialchars($updateError) ?></div>
          <?php elseif (!empty($updateInfo['available'])): ?>
            <div class="mt-4 p-4 rounded-xl" style="background: color-mix(in srgb, var(--primary) 8%, var(--card)); border: 1px solid var(--border);">
              <div class="font-semibold" style="color: var(--text);">Je dostupná verze <?= htmlspecialchars($updateInfo['latest']) ?></div>
              <div class="text-sm mt-1" style="color: var(--muted);"><?= htmlspecialchars($updateInfo['name'] ?? '') ?></div>
              <?php if (!empty($updateInfo['notes'])): ?><div class="text-sm mt-3 whitespace-pre-line" style="color: var(--text);"><?= htmlspecialchars(mb_substr($updateInfo['notes'], 0, 2000)) ?></div><?php endif; ?>
              <form method="post" action="/admin/system/install-update" class="mt-4" onsubmit="return confirm('Opravdu chcete nainstalovat verzi <?= htmlspecialchars($updateInfo['latest']) ?>? Před aktualizací doporučujeme mít také vlastní zálohu databáze.');">
                <?= \Core\CSRF::field() ?>
                <button class="px-4 py-2 rounded-xl text-sm font-medium" style="background: var(--primary); color: var(--primary-text);">Aktualizovat nyní</button>
              </form>
            </div>
          <?php else: ?>
            <div class="mt-4 text-sm font-medium" style="color: var(--text);">✓ Používáte nejnovější dostupnou verzi <?= htmlspecialchars($updateInfo['current'] ?? '') ?>.</div>
          <?php endif; ?>
      </div>
    </div>
    <div class="text-xs mt-4" style="color: var(--muted);">Aktualizace se nikdy neinstaluje sama. Instalaci musí vždy potvrdit vlastník aplikace. Při aktualizaci se zachová lokální konfigurace, storage a nahrané soubory.</div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
      <div class="text-sm" style="color: var(--muted);">Aktuální verze</div>
      <div class="text-3xl font-bold mt-2" style="color: var(--text);">
        <?= htmlspecialchars($currentVersion['version'] ?? '0.0.0') ?>
      </div>
      <div class="text-sm mt-2" style="color: var(--muted);">
        Datum vydání: <?= htmlspecialchars($currentVersion['released_at'] ?? '—') ?>
      </div>
      <div class="text-sm mt-1" style="color: var(--muted);">
        Název: <?= htmlspecialchars($currentVersion['name'] ?? '—') ?>
      </div>
    </div>

    <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
      <div class="text-sm" style="color: var(--muted);">Čekající migrace</div>
      <div class="text-3xl font-bold mt-2" style="color: var(--text);">
        <?= count($pendingMigrations) ?>
      </div>
      <div class="text-sm mt-2" style="color: var(--muted);">
        <?= count($pendingMigrations) > 0 ? 'Databáze vyžaduje aktualizaci.' : 'Databáze je aktuální.' ?>
      </div>
    </div>

    <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
      <div class="text-sm" style="color: var(--muted);">Akce</div>

      <form method="post" action="/admin/system/run-migrations" class="mt-4">
        <?= \Core\CSRF::field() ?>

        <button
          type="submit"
          class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-sm font-medium <?= $runButtonClass ?>"
          style="<?= htmlspecialchars($runButtonStyle) ?>"
          <?= $runButtonDisabled ?>
        >
          Spustit migrace
        </button>
      </form>

      <div class="text-xs mt-3" style="color: var(--muted);">
        Doporučeno před spuštěním udělat zálohu databáze.
      </div>
    </div>
  </div>

  <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Aktualizace</h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
      <div class="p-4 rounded-xl" style="background: color-mix(in srgb, var(--primary) 8%, var(--card)); border: 1px solid var(--border); color: var(--text);">
        <div class="flex items-center gap-2 mb-2">
          <div class="font-semibold">Aktualizace na kliknutí</div>
          <span class="text-xs px-2 py-0.5 rounded-full" style="background: var(--primary); color: var(--primary-text);">Doporučeno</span>
        </div>
        <p style="color: var(--muted);">
          Nejsnazší způsob aktualizace PodnikAppky. Aplikace automaticky kontroluje dostupnost nové verze v oficiálním GitHub repozitáři.
        </p>
        <p class="mt-3">
          Pokud je dostupná novější verze, zobrazí se v horní části této stránky. Kliknutím na <strong>Aktualizovat nyní</strong> PodnikAppka stáhne instalační balíček, vytvoří zálohu aplikačních souborů a provede aktualizaci. Lokální konfigurace, storage a nahrané soubory zůstanou zachovány.
        </p>
        <p class="mt-3">
          Po dokončení aktualizace zkontrolujte položku <strong>Čekající migrace</strong>. Pokud jsou k dispozici, spusťte je tlačítkem <strong>Spustit migrace</strong>.
        </p>
      </div>

      <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        <div class="font-semibold mb-2">Ruční aktualizace</div>
        <p style="color: var(--muted);">
          Záložní způsob pro případ, že aktualizaci na kliknutí nelze použít.
        </p>
        <ol class="list-decimal pl-5 mt-3 space-y-1">
          <li>Stáhněte instalační ZIP požadované verze z <a href="https://github.com/ViMa73/podnikappka/releases" target="_blank" rel="noopener noreferrer" class="underline">oficiálních GitHub Releases</a>.</li>
          <li>Před aktualizací zazálohujte databázi a soubory aplikace.</li>
          <li>Rozbalte balíček a jeho obsah nahrajte na server přes stávající instalaci PodnikAppky.</li>
          <li><strong>Zachovejte místní konfiguraci a uživatelská data, zejména <code>config/local.php</code>, <code>storage/</code> a <code>uploads/</code>.</strong></li>
          <li>Po nahrání souborů znovu otevřete <strong>Systém / Aktualizace</strong> a spusťte případné čekající migrace.</li>
        </ol>
      </div>
    </div>

    <div class="mt-4 p-4 rounded-xl text-sm" style="background: color-mix(in srgb, var(--error) 8%, var(--card)); border: 1px solid var(--border); color: var(--text);">
      <div class="font-semibold mb-2">Důležité před aktualizací</div>
      <p>
        Doporučujeme mít aktuální zálohu databáze i souborů aplikace. Pokud jste zdrojové soubory PodnikAppky sami upravovali, aktualizace může vaše změny přepsat. Při chybě aktualizace neprovádějte novou instalaci přes původní data; nejprve zjistěte příčinu nebo obnovte zálohu.
      </p>
    </div>
  </div>

  <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Čekající migrace</h2>

    <?php if (empty($pendingMigrations)): ?>
      <div class="text-sm italic" style="color: var(--muted);">
        Žádné čekající migrace.
      </div>
    <?php else: ?>
      <div class="space-y-2">
        <?php foreach ($pendingMigrations as $migration): ?>
          <div class="p-3 rounded-xl text-sm" style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <?= htmlspecialchars($migration['name']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Release notes</h2>

    <?php if (empty($releases)): ?>
      <div class="text-sm italic" style="color: var(--muted);">
        Žádné release notes.
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($releases as $index => $release): ?>
          <details <?= $index === 0 ? 'open' : '' ?> class="rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
            <summary class="cursor-pointer px-4 py-3 font-medium" style="color: var(--text);">
              <?= htmlspecialchars($release['version']) ?>
              — <?= htmlspecialchars($release['title'] ?? '') ?>
              <span class="text-sm ml-2" style="color: var(--muted);">
                (<?= htmlspecialchars($release['released_at'] ?? '') ?>)
              </span>
            </summary>

            <div class="px-4 pb-4">
              <?php if (!empty($release['changes']) && is_array($release['changes'])): ?>
                <ul class="list-disc pl-5 space-y-1 text-sm" style="color: var(--text);">
                  <?php foreach ($release['changes'] as $change): ?>
                    <li><?= htmlspecialchars($change) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div class="text-sm italic" style="color: var(--muted);">
                  Bez detailu změn.
                </div>
              <?php endif; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="rounded-2xl p-5 shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
    <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Historie migrací</h2>

    <?php if (empty($migrationHistory)): ?>
      <div class="text-sm italic" style="color: var(--muted);">
        Zatím nebyla spuštěna žádná migrace.
      </div>
    <?php else: ?>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr style="color: var(--muted);">
              <th class="text-left py-2 pr-4">Migrace</th>
              <th class="text-left py-2 pr-4">Stav</th>
              <th class="text-left py-2 pr-4">Kdy</th>
              <th class="text-left py-2 pr-4">Zpráva</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($migrationHistory as $row): ?>
              <tr style="border-top: 1px solid var(--border); color: var(--text);">
                <td class="py-2 pr-4"><?= htmlspecialchars($row['name']) ?></td>
                <td class="py-2 pr-4"><?= htmlspecialchars($row['status']) ?></td>
                <td class="py-2 pr-4"><?= htmlspecialchars($row['executed_at']) ?></td>
                <td class="py-2 pr-4"><?= htmlspecialchars((string) ($row['message'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
