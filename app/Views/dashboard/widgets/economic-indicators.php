<?php
$structures = $economicIndicatorWidgetStructures ?? [];
$widgetId = 'economicIndicatorsWidget_' . substr(md5((string)microtime(true) . rand()), 0, 8);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div id="<?= htmlspecialchars($widgetId) ?>" class="space-y-4">
  <?php if (empty($structures)): ?>
    <div class="text-sm italic p-4 rounded-xl"
         style="background: var(--bg); border: 1px solid var(--border); color: var(--muted);">
      Nejsou definované žádné skupiny ani samostatné ukazatele prvního řádu.
    </div>
  <?php else: ?>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($structures as $index => $item): ?>
        <button type="button"
                class="js-economic-widget-tab px-4 py-2 rounded-lg text-sm font-semibold"
                data-id="<?= (int)$item['id'] ?>"
                style="<?= $index === 0
                  ? 'background: var(--primary); color: #fff; border: 1px solid var(--primary);'
                  : 'background: var(--bg); color: var(--text); border: 1px solid var(--border);' ?>">
          <?= htmlspecialchars($item['name']) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div>
      <div class="font-semibold" data-role="chart-title" style="color: var(--text);"></div>
      <div class="text-sm mt-1" data-role="chart-meta" style="color: var(--muted);"></div>
    </div>

    <div class="rounded-xl p-4"
         style="background: var(--bg); border: 1px solid var(--border); min-height: 360px;">
      <div style="position: relative; height: 320px;">
        <canvas data-role="chart-canvas"></canvas>
      </div>
    </div>

    <div class="flex justify-end">
      <a href="/economic-indicators"
         class="px-4 py-2 rounded-lg text-sm font-semibold"
         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        Otevřít detail
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
(function () {
  const root = document.getElementById(<?= json_encode($widgetId) ?>);
  if (!root) return;

  const tabButtons = Array.from(root.querySelectorAll('.js-economic-widget-tab'));
  const chartTitle = root.querySelector('[data-role="chart-title"]');
  const chartMeta = root.querySelector('[data-role="chart-meta"]');
  const chartCanvas = root.querySelector('[data-role="chart-canvas"]');

  let chart = null;

  async function loadChart(rootId) {
    const res = await fetch('/economic-indicators/chart-data?id=' + encodeURIComponent(rootId), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
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

    if (window.DashboardMasonryResize) {
      setTimeout(() => window.DashboardMasonryResize(), 50);
    }
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
})();
</script>
