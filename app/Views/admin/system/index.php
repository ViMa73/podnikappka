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
    <h2 class="text-lg font-semibold mb-4" style="color: var(--text);">Ruční aktualizace</h2>

    <div class="space-y-4 text-sm" style="color: var(--text);">
      <div class="p-4 rounded-xl" style="background: var(--bg); border: 1px solid var(--border);">
        <div class="font-semibold mb-2">Pokud vzdálená aktualizace není dostupná</div>
        <ol class="list-decimal pl-5 space-y-1">
          <li>Stáhněte si novou verzi PodnikAppky z oficiálního zdroje.</li>
          <li>Před aktualizací si zazálohujte databázi a soubory aplikace.</li>
          <li>Rozbalte stažený balíček a nahrajte nové soubory na server přes stávající instalaci.</li>
          <li><strong>Nemažte ani nepřepisujte soubor <code>config/local.php</code> a soubor <code>storage/installed.lock</code>.</strong> Obsahují údaje potřebné pro vaši konkrétní instalaci.</li>
          <li>Po nahrání souborů se přihlaste do PodnikAppky a otevřete <strong>Systém / Aktualizace</strong>.</li>
          <li>Pokud jsou uvedeny čekající migrace, klikněte na <strong>Spustit migrace</strong>.</li>
          <li>Po dokončení zkontrolujte, že aplikace funguje správně a že stránka zobrazuje novou verzi.</li>
        </ol>
      </div>

      <div class="p-4 rounded-xl" style="background: color-mix(in srgb, var(--primary) 8%, var(--card)); border: 1px solid var(--border);">
        <div class="font-semibold mb-2">Co jsou databázové migrace?</div>
        <p>
          Některé aktualizace mění také strukturu databáze. Pokud je po nahrání nové verze u položky „Čekající migrace“ číslo vyšší než 0, spusťte je tlačítkem výše. Pokud žádné čekající migrace nejsou, není potřeba nic dalšího dělat.
        </p>
      </div>

      <div class="p-4 rounded-xl" style="background: color-mix(in srgb, var(--error) 8%, var(--card)); border: 1px solid var(--border);">
        <div class="font-semibold mb-2">Důležité před aktualizací</div>
        <ul class="list-disc pl-5 space-y-1">
          <li>Vždy mějte aktuální zálohu databáze a souborů aplikace.</li>
          <li>Při ručním nahrávání nové verze zachovejte lokální konfiguraci své instalace.</li>
          <li>Pokud jste si zdrojové soubory PodnikAppky sami upravovali, aktualizace může vaše změny přepsat. Před aktualizací si je proto zazálohujte.</li>
          <li>Pokud aktualizace skončí chybou, neprovádějte novou instalaci přes původní data. Obnovte zálohu nebo nejprve zjistěte příčinu chyby.</li>
        </ul>
      </div>
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
