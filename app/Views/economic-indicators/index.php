<?php
  $definitionsById = [];
  foreach (($indicatorOptions ?? []) as $opt) {
      $definitionsById[(int)$opt['id']] = $opt;
  }

  $canManageEconomicIndicators = false;
  if (\Core\Auth::role() === 'owner') {
      $canManageEconomicIndicators = true;
  } elseif (\Core\Auth::role() === 'manager') {
      $canManageEconomicIndicators = (int)($company['economic_indicators_allow_manager'] ?? 0) === 1;
  }

  $topLevelStructures = $topLevelStructures ?? [];
  $indicatorOptions = $indicatorOptions ?? [];
  $records = $records ?? [];
  $page = $page ?? 1;
  $perPage = $perPage ?? 20;
  $totalRecords = $totalRecords ?? 0;
  $totalPages = $totalPages ?? 1;

  $periodLabels = [
      'daily' => 'Denní',
      'weekly' => 'Týdenní',
      'monthly' => 'Měsíční',
      'quarterly' => 'Kvartální',
      'yearly' => 'Roční',
  ];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

  <!-- 1. GRAF -->
  <div class="xl:col-span-3 rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <div class="flex items-start justify-between gap-4 mb-5">
      <div>
        <h1 class="text-2xl font-semibold" style="color: var(--text);">Ekonomické ukazatele</h1>
        <p class="text-sm mt-1" style="color: var(--muted);">
          Přehled vývoje hlavních ekonomických ukazatelů.
        </p>
      </div>
    </div>

    <?php if (empty($topLevelStructures)): ?>
      <div class="text-sm italic p-4 rounded-xl"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
        Nejsou definované žádné skupiny ani samostatné ukazatele prvního řádu.
      </div>
    <?php else: ?>
      <div class="flex flex-wrap gap-2 mb-5" id="economicIndicatorTabs">
        <?php foreach ($topLevelStructures as $index => $item): ?>
          <button type="button"
                  class="js-economic-tab px-4 py-2 rounded-lg text-sm font-semibold"
                  data-id="<?= (int)$item['id'] ?>"
                  style="<?= $index === 0
                    ? 'background: var(--primary); color: #fff; border: 1px solid var(--primary);'
                    : 'background: var(--bg); color: var(--text); border: 1px solid var(--border);' ?>">
            <?= htmlspecialchars($item['name']) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="mb-4">
        <div id="economicChartTitle" class="font-semibold" style="color: var(--text);"></div>
        <div id="economicChartMeta" class="text-sm mt-1" style="color: var(--muted);"></div>
      </div>

      <div class="rounded-xl p-4"
           style="background: var(--bg); border: 1px solid var(--border); min-height: 420px;">
        <canvas id="economicIndicatorsChart" height="130"></canvas>
      </div>
    <?php endif; ?>
  </div>

  <!-- 2. PŘIDÁNÍ ZÁZNAMU -->
  <div class="xl:col-span-1 rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <div class="mb-5">
      <h2 class="text-lg font-semibold" style="color: var(--text);">Nový záznam</h2>
      <p class="text-sm mt-1" style="color: var(--muted);">
        Zápis hodnoty vybraného ukazatele.
      </p>
    </div>

    <?php if (!$canManageEconomicIndicators): ?>
      <div class="text-sm italic p-4 rounded-xl"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
        Nemáš oprávnění přidávat nebo upravovat záznamy.
      </div>
    <?php elseif (empty($indicatorOptions)): ?>
      <div class="text-sm italic p-4 rounded-xl"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
        Nejsou dostupné žádné ukazatele pro zadávání hodnot.
      </div>
    <?php else: ?>
      <form method="POST" action="/economic-indicators/store" class="space-y-4" id="economicIndicatorCreateForm">
        <?= \Core\CSRF::field() ?>

        <div>
          <label class="text-sm" style="color: var(--text);">Ukazatel</label>
          <select name="definition_id"
                  id="economicIndicatorDefinitionSelect"
                  class="mt-1 w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <?php foreach ($indicatorOptions as $index => $opt): ?>
              <option value="<?= (int)$opt['id'] ?>"
                      data-period-type="<?= htmlspecialchars($opt['effective_period_type']) ?>"
                      <?= $index === 0 ? 'selected' : '' ?>>
                <?= htmlspecialchars($opt['select_label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="economicPeriodDailyWrap" class="economic-period-wrap">
          <label class="text-sm" style="color: var(--text);">Den</label>
          <input type="date"
                 name="period_date"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div id="economicPeriodWeeklyWrap" class="economic-period-wrap hidden">
          <label class="text-sm" style="color: var(--text);">Týden</label>
          <input type="week"
                 name="period_week"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div id="economicPeriodMonthlyWrap" class="economic-period-wrap hidden">
          <label class="text-sm" style="color: var(--text);">Měsíc</label>
          <input type="month"
                 name="period_month"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div id="economicPeriodQuarterlyWrap" class="economic-period-wrap hidden grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm" style="color: var(--text);">Rok</label>
            <input type="number"
                   name="period_quarter_year"
                   min="2000"
                   max="2100"
                   value="<?= date('Y') ?>"
                   class="mt-1 w-full px-4 py-3 rounded-lg"
                   style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          </div>
          <div>
            <label class="text-sm" style="color: var(--text);">Kvartál</label>
            <select name="period_quarter"
                    class="mt-1 w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              <option value="1">Q1</option>
              <option value="2">Q2</option>
              <option value="3">Q3</option>
              <option value="4">Q4</option>
            </select>
          </div>
        </div>

        <div id="economicPeriodYearlyWrap" class="economic-period-wrap hidden">
          <label class="text-sm" style="color: var(--text);">Rok</label>
          <input type="number"
                 name="period_year"
                 min="2000"
                 max="2100"
                 value="<?= date('Y') ?>"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Hodnota</label>
          <input type="text"
                 name="value"
                 placeholder="např. 125000"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Poznámka</label>
          <textarea name="note"
                    rows="3"
                    class="mt-1 w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"></textarea>
        </div>

        <button class="btn-primary w-full px-5 py-3 rounded-lg font-semibold">
          Uložit záznam
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- 3. VÝPIS -->
  <div class="xl:col-span-4 rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <div class="mb-5">
      <h2 class="text-lg font-semibold" style="color: var(--text);">Všechny záznamy</h2>
      <p class="text-sm mt-1" style="color: var(--muted);">
        Přehled uložených hodnot včetně historie zápisu a poslední změny.
      </p>
    </div>

    <?php if (empty($records)): ?>
      <div class="text-sm italic p-4 rounded-xl"
           style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
        Zatím nejsou uložené žádné záznamy.
      </div>
    <?php else: ?>
      <div class="overflow-auto">
        <table class="w-full text-sm">
          <thead>
            <tr style="color: var(--muted); border-bottom: 1px solid var(--border);">
              <th class="text-left py-3 pr-4">Ukazatel</th>
              <th class="text-left py-3 pr-4">Období</th>
              <th class="text-left py-3 pr-4">Hodnota</th>
              <th class="text-left py-3 pr-4">Poznámka</th>
              <th class="text-left py-3 pr-4">Vytvořil</th>
              <th class="text-left py-3 pr-4">Změnil</th>
              <?php if ($canManageEconomicIndicators): ?>
                <th class="text-left py-3">Akce</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $row): ?>
              <?php
                $recordLabel = trim(
                  (($row['parent_name'] ?? '') !== '' ? ($row['parent_name'] . ' / ') : '') .
                  ($row['definition_name'] ?? '')
                );

                $createdBy = trim(($row['created_by_first_name'] ?? '') . ' ' . ($row['created_by_last_name'] ?? ''));
                $updatedBy = trim(($row['updated_by_first_name'] ?? '') . ' ' . ($row['updated_by_last_name'] ?? ''));
              ?>
              <tr style="border-bottom: 1px solid var(--border); color: var(--text);">
                <td class="py-3 pr-4 align-top">
                  <div class="font-semibold"><?= htmlspecialchars($recordLabel) ?></div>
                </td>
                <td class="py-3 pr-4 align-top">
                  <div><?= htmlspecialchars($row['period_label']) ?></div>
                  <div class="text-xs mt-1" style="color: var(--muted);">
                    <?= htmlspecialchars($periodLabels[$row['period_type']] ?? $row['period_type']) ?>
                  </div>
                </td>
                <td class="py-3 pr-4 align-top">
                  <div><?= htmlspecialchars(number_format((float)$row['value'], 2, ',', ' ')) ?><?= !empty($row['unit']) ? ' ' . htmlspecialchars($row['unit']) : '' ?></div>
                </td>
                <td class="py-3 pr-4 align-top">
                  <div style="color: var(--muted);"><?= nl2br(htmlspecialchars((string)($row['note'] ?? ''))) ?></div>
                </td>
                <td class="py-3 pr-4 align-top">
                  <div><?= htmlspecialchars($createdBy ?: '—') ?></div>
                  <div class="text-xs mt-1" style="color: var(--muted);">
                    <?= htmlspecialchars((string)$row['created_at']) ?>
                  </div>
                </td>
                <td class="py-3 pr-4 align-top">
                  <div><?= htmlspecialchars($updatedBy ?: '—') ?></div>
                  <div class="text-xs mt-1" style="color: var(--muted);">
                    <?= htmlspecialchars((string)$row['updated_at']) ?>
                  </div>
                </td>

                <?php if ($canManageEconomicIndicators): ?>
                  <td class="py-3 align-top">
                    <div class="flex flex-wrap gap-2">
                      <button type="button"
                              class="js-open-economic-record-edit px-3 py-2 rounded-lg text-sm"
                              style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                              data-id="<?= (int)$row['id'] ?>"
                              data-definition-id="<?= (int)$row['definition_id'] ?>"
                              data-period-type="<?= htmlspecialchars($row['period_type']) ?>"
                              data-period-key="<?= htmlspecialchars($row['period_key']) ?>"
                              data-value="<?= htmlspecialchars((string)$row['value']) ?>"
                              data-note="<?= htmlspecialchars((string)($row['note'] ?? ''), ENT_QUOTES) ?>">
                        Upravit
                      </button>

                      <form method="POST" action="/economic-indicators/delete" onsubmit="return confirm('Opravdu chceš tento záznam smazat?');">
                        <?= \Core\CSRF::field() ?>
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="px-3 py-2 rounded-lg text-sm"
                                style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;">
                          Smazat
                        </button>
                      </form>
                    </div>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between gap-3 mt-5 pt-4" style="border-top: 1px solid var(--border);">
          <div class="text-sm" style="color: var(--muted);">
            Stránka <?= (int)$page ?> z <?= (int)$totalPages ?> • celkem <?= (int)$totalRecords ?> záznamů
          </div>

          <div class="flex items-center gap-2">
            <?php if ($page > 1): ?>
              <a href="/economic-indicators?page=<?= $page - 1 ?>"
                 class="px-4 py-2 rounded-lg text-sm font-semibold"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                Předchozí
              </a>
            <?php endif; ?>

            <?php
              $from = max(1, $page - 2);
              $to = min($totalPages, $page + 2);
              for ($p = $from; $p <= $to; $p++):
            ?>
              <a href="/economic-indicators?page=<?= $p ?>"
                 class="px-3 py-2 rounded-lg text-sm font-semibold"
                 style="<?= $p === $page
                   ? 'background: var(--primary); border: 1px solid var(--primary); color: #fff;'
                   : 'background: var(--bg); border: 1px solid var(--border); color: var(--text);' ?>">
                <?= $p ?>
              </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <a href="/economic-indicators?page=<?= $page + 1 ?>"
                 class="px-4 py-2 rounded-lg text-sm font-semibold"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                Další
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($canManageEconomicIndicators): ?>
  <div id="economicRecordEditModal"
       class="fixed inset-0 z-50 hidden items-center justify-center"
       aria-hidden="true">
    <div class="absolute inset-0"
         style="background: rgba(0,0,0,.45);"
         data-close-economic-record-edit="1"></div>

    <div class="relative w-full max-w-lg mx-4 rounded-2xl shadow-xl p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-lg font-semibold" style="color: var(--text);">Upravit záznam</h3>
          <p class="text-sm mt-1" style="color: var(--muted);">
            Úprava ukazatele, období a hodnoty.
          </p>
        </div>

        <button type="button"
                class="px-3 py-2 rounded-lg"
                style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                data-close-economic-record-edit="1">✕</button>
      </div>

      <form method="POST" action="/economic-indicators/update" class="mt-5 space-y-4" id="economicRecordEditForm">
        <?= \Core\CSRF::field() ?>

        <input type="hidden" name="id" id="economicRecordEditId">

        <div>
          <label class="text-sm" style="color: var(--text);">Ukazatel</label>
          <select name="definition_id"
                  id="economicRecordEditDefinition"
                  class="mt-1 w-full px-4 py-3 rounded-lg"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
            <?php foreach ($indicatorOptions as $opt): ?>
              <option value="<?= (int)$opt['id'] ?>"
                      data-period-type="<?= htmlspecialchars($opt['effective_period_type']) ?>">
                <?= htmlspecialchars($opt['select_label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="economicEditPeriodDailyWrap" class="economic-edit-period-wrap">
          <label class="text-sm" style="color: var(--text);">Den</label>
          <input type="date"
                 name="period_date"
                 id="economicEditPeriodDate"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div id="economicEditPeriodWeeklyWrap" class="economic-edit-period-wrap hidden">
          <label class="text-sm" style="color: var(--text);">Týden</label>
          <input type="week"
                 name="period_week"
                 id="economicEditPeriodWeek"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div id="economicEditPeriodMonthlyWrap" class="economic-edit-period-wrap hidden">
          <label class="text-sm" style="color: var(--text);">Měsíc</label>
          <input type="month"
                 name="period_month"
                 id="economicEditPeriodMonth"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div id="economicEditPeriodQuarterlyWrap" class="economic-edit-period-wrap hidden grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm" style="color: var(--text);">Rok</label>
            <input type="number"
                   name="period_quarter_year"
                   id="economicEditPeriodQuarterYear"
                   min="2000"
                   max="2100"
                   class="mt-1 w-full px-4 py-3 rounded-lg"
                   style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
          </div>
          <div>
            <label class="text-sm" style="color: var(--text);">Kvartál</label>
            <select name="period_quarter"
                    id="economicEditPeriodQuarter"
                    class="mt-1 w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
              <option value="1">Q1</option>
              <option value="2">Q2</option>
              <option value="3">Q3</option>
              <option value="4">Q4</option>
            </select>
          </div>
        </div>

        <div id="economicEditPeriodYearlyWrap" class="economic-edit-period-wrap hidden">
          <label class="text-sm" style="color: var(--text);">Rok</label>
          <input type="number"
                 name="period_year"
                 id="economicEditPeriodYear"
                 min="2000"
                 max="2100"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Hodnota</label>
          <input type="text"
                 name="value"
                 id="economicRecordEditValue"
                 class="mt-1 w-full px-4 py-3 rounded-lg"
                 style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                 required>
        </div>

        <div>
          <label class="text-sm" style="color: var(--text);">Poznámka</label>
          <textarea name="note"
                    id="economicRecordEditNote"
                    rows="3"
                    class="mt-1 w-full px-4 py-3 rounded-lg"
                    style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"></textarea>
        </div>

        <div class="flex gap-3 justify-end pt-2">
          <button type="button"
                  class="px-5 py-3 rounded-lg font-semibold"
                  style="background: var(--bg); border: 1px solid var(--border); color: var(--text);"
                  data-close-economic-record-edit="1">
            Zrušit
          </button>

          <button class="btn-primary px-5 py-3 rounded-lg font-semibold">
            Uložit změny
          </button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<script>
(function () {
  const tabButtons = Array.from(document.querySelectorAll('.js-economic-tab'));
  const chartTitle = document.getElementById('economicChartTitle');
  const chartMeta = document.getElementById('economicChartMeta');
  const chartCanvas = document.getElementById('economicIndicatorsChart');

  let chart = null;

  async function loadChart(rootId) {
    const res = await fetch('/economic-indicators/chart-data?id=' + encodeURIComponent(rootId), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });

    const data = await res.json();
    if (!res.ok || data.ok === false) {
      throw new Error(data.message || 'Graf se nepodařilo načíst.');
    }

    if (chartTitle) {
      chartTitle.textContent = data.title || '';
    }

    if (chartMeta) {
      const periodLabels = {
        daily: 'Denní vývoj za posledních 30 dní',
        weekly: 'Týdenní vývoj za posledních 12 týdnů',
        monthly: 'Měsíční vývoj za posledních 12 měsíců',
        quarterly: 'Kvartální vývoj za posledních 8 kvartálů',
        yearly: 'Roční vývoj za posledních 5 let'
      };
      chartMeta.textContent = periodLabels[data.period_type] || '';
    }

    if (!chartCanvas) return;

    if (chart) {
      chart.destroy();
    }

    const datasets = [];
    (data.datasets || []).forEach((dataset) => {
      datasets.push({
        label: dataset.label,
        data: dataset.data,
        borderWidth: 2,
        fill: false,
        tension: 0.25
      });

      datasets.push({
        label: dataset.label + ' – trend',
        data: dataset.trend_data || [],
        borderWidth: 2,
        fill: false,
        tension: 0.25,
        borderDash: [6, 6],
        pointRadius: 0
      });
    });

    chart = new Chart(chartCanvas, {
      type: 'line',
      data: {
        labels: data.labels || [],
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            display: true
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }

  tabButtons.forEach((btn) => {
    btn.addEventListener('click', async () => {
      tabButtons.forEach((b) => {
        b.style.background = 'var(--bg)';
        b.style.color = 'var(--text)';
        b.style.border = '1px solid var(--border)';
      });

      btn.style.background = 'var(--primary)';
      btn.style.color = '#fff';
      btn.style.border = '1px solid var(--primary)';

      try {
        await loadChart(btn.getAttribute('data-id'));
      } catch (err) {
        alert(err.message || 'Graf se nepodařilo načíst.');
      }
    });
  });

  if (tabButtons.length) {
    loadChart(tabButtons[0].getAttribute('data-id')).catch((err) => {
      alert(err.message || 'Graf se nepodařilo načíst.');
    });
  }

  const definitionSelect = document.getElementById('economicIndicatorDefinitionSelect');
  const periodWraps = {
    daily: document.getElementById('economicPeriodDailyWrap'),
    weekly: document.getElementById('economicPeriodWeeklyWrap'),
    monthly: document.getElementById('economicPeriodMonthlyWrap'),
    quarterly: document.getElementById('economicPeriodQuarterlyWrap'),
    yearly: document.getElementById('economicPeriodYearlyWrap')
  };

  function syncCreatePeriodInputs() {
    if (!definitionSelect) return;

    const option = definitionSelect.options[definitionSelect.selectedIndex];
    const type = option ? option.getAttribute('data-period-type') : 'monthly';

    Object.entries(periodWraps).forEach(([key, el]) => {
      if (!el) return;
      el.classList.toggle('hidden', key !== type);
    });
  }

  if (definitionSelect) {
    definitionSelect.addEventListener('change', syncCreatePeriodInputs);
    syncCreatePeriodInputs();
  }

  const editModal = document.getElementById('economicRecordEditModal');
  const editDefinition = document.getElementById('economicRecordEditDefinition');
  const editId = document.getElementById('economicRecordEditId');
  const editValue = document.getElementById('economicRecordEditValue');
  const editNote = document.getElementById('economicRecordEditNote');

  const editPeriodWraps = {
    daily: document.getElementById('economicEditPeriodDailyWrap'),
    weekly: document.getElementById('economicEditPeriodWeeklyWrap'),
    monthly: document.getElementById('economicEditPeriodMonthlyWrap'),
    quarterly: document.getElementById('economicEditPeriodQuarterlyWrap'),
    yearly: document.getElementById('economicEditPeriodYearlyWrap')
  };

  const editFields = {
    date: document.getElementById('economicEditPeriodDate'),
    week: document.getElementById('economicEditPeriodWeek'),
    month: document.getElementById('economicEditPeriodMonth'),
    quarterYear: document.getElementById('economicEditPeriodQuarterYear'),
    quarter: document.getElementById('economicEditPeriodQuarter'),
    year: document.getElementById('economicEditPeriodYear')
  };

  function syncEditPeriodInputs() {
    if (!editDefinition) return;

    const option = editDefinition.options[editDefinition.selectedIndex];
    const type = option ? option.getAttribute('data-period-type') : 'monthly';

    Object.entries(editPeriodWraps).forEach(([key, el]) => {
      if (!el) return;
      el.classList.toggle('hidden', key !== type);
    });
  }

  function openEditModal(data) {
    if (!editModal) return;

    editId.value = data.id || '';
    editDefinition.value = data.definitionId || '';
    editValue.value = data.value || '';
    editNote.value = data.note || '';

    syncEditPeriodInputs();

    editFields.date.value = '';
    editFields.week.value = '';
    editFields.month.value = '';
    editFields.quarterYear.value = '';
    editFields.quarter.value = '1';
    editFields.year.value = '';

    if (data.periodType === 'daily') {
      editFields.date.value = data.periodKey || '';
    } else if (data.periodType === 'weekly') {
      editFields.week.value = data.periodKey || '';
    } else if (data.periodType === 'monthly') {
      editFields.month.value = data.periodKey || '';
    } else if (data.periodType === 'quarterly') {
      const match = String(data.periodKey || '').match(/^(\d{4})-Q([1-4])$/);
      if (match) {
        editFields.quarterYear.value = match[1];
        editFields.quarter.value = match[2];
      }
    } else if (data.periodType === 'yearly') {
      editFields.year.value = data.periodKey || '';
    }

    editModal.classList.remove('hidden');
    editModal.classList.add('flex');
    editModal.setAttribute('aria-hidden', 'false');
  }

  function closeEditModal() {
    if (!editModal) return;
    editModal.classList.add('hidden');
    editModal.classList.remove('flex');
    editModal.setAttribute('aria-hidden', 'true');
  }

  if (editDefinition) {
    editDefinition.addEventListener('change', syncEditPeriodInputs);
  }

  document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('.js-open-economic-record-edit');
    if (editBtn) {
      e.preventDefault();
      openEditModal({
        id: editBtn.getAttribute('data-id') || '',
        definitionId: editBtn.getAttribute('data-definition-id') || '',
        periodType: editBtn.getAttribute('data-period-type') || '',
        periodKey: editBtn.getAttribute('data-period-key') || '',
        value: editBtn.getAttribute('data-value') || '',
        note: editBtn.getAttribute('data-note') || ''
      });
      return;
    }

    if (e.target && e.target.getAttribute('data-close-economic-record-edit') === '1') {
      e.preventDefault();
      closeEditModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeEditModal();
    }
  });
})();
</script>
