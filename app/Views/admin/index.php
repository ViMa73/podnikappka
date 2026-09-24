<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold" style="color: var(--text);">Administrace</h1>
    <p class="text-sm mt-1" style="color: var(--muted);">
      Přehled systémové administrace aplikace.
    </p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php
      $trendStyles = [
          'up' => ['color' => '#16a34a', 'icon' => '↗'],
          'down' => ['color' => '#dc2626', 'icon' => '↘'],
          'same' => ['color' => 'var(--muted)', 'icon' => '→'],
      ];
    ?>

    <div class="rounded-2xl shadow p-5"
         style="background: var(--card); border: 1px solid var(--border);">
      <div class="text-sm mb-3" style="color: var(--muted);">Firem</div>

      <div class="grid grid-cols-[1fr_auto] gap-5 items-start">
        <div>
          <div class="text-4xl font-bold leading-none" style="color: var(--text);">
            <?= (int)$companiesCount ?>
          </div>
          <div class="text-sm mt-2" style="color: var(--muted);">
            Aktuální stav
          </div>
        </div>

        <div class="min-w-[170px] space-y-2">
          <?php $c30 = $trendStyles[$companiesTrend30['direction']] ?? $trendStyles['same']; ?>
          <div class="rounded-xl px-3 py-2"
               style="background: var(--bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between gap-3 text-sm">
              <span style="color: var(--muted);">Před 30 dny</span>
              <span class="font-semibold" style="color: var(--text);">
                <?= (int)$companiesTrend30['past'] ?>
              </span>
            </div>
            <div class="text-xs mt-1 font-semibold" style="color: <?= $c30['color'] ?>;">
              <?= $c30['icon'] ?> <?= htmlspecialchars($companiesTrend30['label']) ?>
            </div>
          </div>

          <?php $cYear = $trendStyles[$companiesTrendYear['direction']] ?? $trendStyles['same']; ?>
          <div class="rounded-xl px-3 py-2"
               style="background: var(--bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between gap-3 text-sm">
              <span style="color: var(--muted);">Před 1 rokem</span>
              <span class="font-semibold" style="color: var(--text);">
                <?= (int)$companiesTrendYear['past'] ?>
              </span>
            </div>
            <div class="text-xs mt-1 font-semibold" style="color: <?= $cYear['color'] ?>;">
              <?= $cYear['icon'] ?> <?= htmlspecialchars($companiesTrendYear['label']) ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-2xl shadow p-5"
         style="background: var(--card); border: 1px solid var(--border);">
      <div class="text-sm mb-3" style="color: var(--muted);">Uživatelů</div>

      <div class="grid grid-cols-[1fr_auto] gap-5 items-start">
        <div>
          <div class="text-4xl font-bold leading-none" style="color: var(--text);">
            <?= (int)$usersCount ?>
          </div>
          <div class="text-sm mt-2" style="color: var(--muted);">
            Aktuální stav
          </div>
        </div>

        <div class="min-w-[170px] space-y-2">
          <?php $u30 = $trendStyles[$usersTrend30['direction']] ?? $trendStyles['same']; ?>
          <div class="rounded-xl px-3 py-2"
               style="background: var(--bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between gap-3 text-sm">
              <span style="color: var(--muted);">Před 30 dny</span>
              <span class="font-semibold" style="color: var(--text);">
                <?= (int)$usersTrend30['past'] ?>
              </span>
            </div>
            <div class="text-xs mt-1 font-semibold" style="color: <?= $u30['color'] ?>;">
              <?= $u30['icon'] ?> <?= htmlspecialchars($usersTrend30['label']) ?>
            </div>
          </div>

          <?php $uYear = $trendStyles[$usersTrendYear['direction']] ?? $trendStyles['same']; ?>
          <div class="rounded-xl px-3 py-2"
               style="background: var(--bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between gap-3 text-sm">
              <span style="color: var(--muted);">Před 1 rokem</span>
              <span class="font-semibold" style="color: var(--text);">
                <?= (int)$usersTrendYear['past'] ?>
              </span>
            </div>
            <div class="text-xs mt-1 font-semibold" style="color: <?= $uYear['color'] ?>;">
              <?= $uYear['icon'] ?> <?= htmlspecialchars($usersTrendYear['label']) ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-2xl shadow p-6"
         style="background: var(--card); border: 1px solid var(--border);">
      <div class="text-sm" style="color: var(--muted);">Čekající platby</div>
      <div class="text-3xl font-bold mt-2" style="color: var(--text);">
        <?= (int)$pendingBillingRequestsCount ?>
      </div>
    </div>
  </div>

  <div class="rounded-2xl shadow p-6"
       style="background: var(--card); border: 1px solid var(--border);">
    <div class="flex flex-wrap gap-3">
      <a href="/admin/billing/requests"
         class="px-4 py-2 rounded-lg font-semibold"
         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        Billing žádosti
      </a>

      <a href="/admin/billing/settings"
         class="px-4 py-2 rounded-lg font-semibold"
         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        Billing nastavení
      </a>
      <a href="/admin/admins"
         class="px-4 py-2 rounded-lg font-semibold"
         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        Správa adminů
      </a>

      <a href="/admin/companies"
         class="px-4 py-2 rounded-lg font-semibold"
         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        Firmy
      </a>

      <a href="/admin/system"
         class="px-4 py-2 rounded-lg font-semibold"
         style="background: var(--bg); border: 1px solid var(--border); color: var(--text);">
        Aktualizace systému
      </a>
    </div>
  </div>
</div>
