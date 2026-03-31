@extends('layouts.admin')

@php
  $title = 'Admin Dashboard';
  $header = 'Dashboard Admin';
  $subheader = 'Kelola user dan master data, serta monitoring lembar kerja evaluasi.';
@endphp

@section('content')
  <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div>
      <div class="font-semibold text-lg md:text-xl">Selamat datang, {{ Auth::user()->nama ?? Auth::user()->username }}</div>
      <div class="text-(--muted) text-xs md:text-sm">Gunakan menu di sidebar untuk mengelola data.</div>
    </div>

    <div class="flex gap-3">
      <a href="{{ route('users.create') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-white text-gray-800 rounded-xl hover:bg-gray-50 flex items-center gap-2 font-medium transition-colors">
        <i class="bi bi-person-plus text-base md:text-lg"></i> Tambah User
      </a>

      <a href="{{ route('lke.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 font-medium transition-opacity">
        <i class="bi bi-file-earmark-text text-base md:text-lg"></i> Lihat Lembar Kerja
      </a>
    </div>
  </div>

  <hr class="border-t border-(--border-strong) my-6">

  {{-- Filter OPD (tidak mempengaruhi piechart) --}}
  <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div class="min-w-0 flex-1">
        <div class="text-xs md:text-sm font-semibold text-(--text)">Filter OPD (untuk navigasi cepat)</div>
        <div class="text-[10px] md:text-xs text-(--muted) mt-0.5">Filter juga mempengaruhi pie chart di bawah.</div>
        <div class="mt-3 flex flex-col sm:flex-row gap-3">
          <div class="w-full sm:w-80">
            <select id="dashOpdSelectAdmin" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
              <option value="0">Semua OPD</option>
              @foreach(($opds ?? collect()) as $u)
                <option value="{{ (int) $u->id }}">{{ $u->nama ?? $u->username }} ({{ $u->username }})</option>
              @endforeach
            </select>
          </div>
          <div class="w-full sm:w-56">
            <select id="dashYearSelectAdmin" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
              <option value="0">Semua Tahun</option>
              @foreach(($years ?? collect()) as $y)
                <option value="{{ (int)$y }}">{{ (int)$y }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex gap-2">
            <a id="dashOpdGoAdmin" href="{{ route('lke.index') }}"
               class="px-3 py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm">
              <i class="bi bi-arrow-right-circle"></i> Buka Monitoring LKE
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <!-- Stat 1 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Total User</div>
        <i class="bi bi-people text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalUsers ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Admin / OPD / BPS</div>
    </div>

    <!-- Stat 2 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Tahun</div>
        <i class="bi bi-calendar3 text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalTahun ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Master data tahun</div>
    </div>

    <!-- Stat 2.5 (Domain) -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Jumlah Domain</div>
        <i class="bi bi-box text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalDomains ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Pengelompokan domain</div>
    </div>

    <!-- Stat 3 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Indikator</div>
        <i class="bi bi-diagram-3 text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalIndikator ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Master indikator</div>
    </div>

    <!-- Stat 4 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Lembar Kerja</div>
        <i class="bi bi-file-earmark-text text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)" data-dash-admin-total-lke>{{ $totalLke ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1" data-dash-admin-total-lke-subtitle>Isian OPD (monitoring)</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mt-4">
    <div class="lg:col-span-7">
      <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm h-full">
        <div class="font-semibold text-base md:text-lg mb-1">Aksi Cepat</div>
        <div class="text-(--muted) text-xs md:text-sm mb-4">Shortcut untuk kerja harian admin.</div>

        <div class="flex flex-wrap gap-3">
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('users.index') }}">
            <i class="bi bi-people"></i> Kelola User
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('tahun.index') }}">
            <i class="bi bi-calendar3"></i> Kelola Tahun
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('domains.index') }}">
            <i class="bi bi-diagram-3"></i> Kelola Domain
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('kriterias.index') }}">
            <i class="bi bi-list-check"></i> Kelola Kriteria
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('lke.index') }}">
            <i class="bi bi-file-earmark-text"></i> Monitoring LKE
          </a>
        </div>
      </div>
    </div>

    <div class="lg:col-span-5">
      @php
        $w = $informasi->warna ?? 'neutral';
        $infoCardClass = match ($w) {
          'red' => 'border-red-500/30 bg-red-500/5',
          'blue' => 'border-blue-500/30 bg-blue-500/5',
          'amber' => 'border-amber-500/30 bg-amber-500/5',
          'emerald' => 'border-emerald-500/30 bg-emerald-500/5',
          default => 'border-(--border-strong) bg-(--panel)',
        };
        $infoTitleClass = match ($w) {
          'red' => 'text-red-700 dark:text-red-200',
          'blue' => 'text-blue-700 dark:text-blue-200',
          'amber' => 'text-amber-700 dark:text-amber-200',
          'emerald' => 'text-emerald-700 dark:text-emerald-200',
          default => 'text-(--text)',
        };
        $infoBodyClass = match ($w) {
          'red' => 'text-red-700/90 dark:text-red-200/90',
          'blue' => 'text-blue-700/90 dark:text-blue-200/90',
          'amber' => 'text-amber-700/90 dark:text-amber-200/90',
          'emerald' => 'text-emerald-700/90 dark:text-emerald-200/90',
          default => 'text-(--muted)',
        };
      @endphp
      <div class="border rounded-2xl p-5 shadow-sm h-full {{ $infoCardClass }}">
        <div class="font-semibold text-base md:text-lg mb-1 break-all {{ $infoTitleClass }}">{{ $informasi->judul }}</div>
        <div class="text-xs md:text-sm mb-4 break-all {{ $infoBodyClass }}">{{ $informasi->isi }}</div>

        <hr class="border-t border-(--border-strong) my-4">

        <div class="text-xs md:text-sm text-(--muted) space-y-3">
          <div class="flex items-center justify-between">
            <span>Session timeout</span>
            <span class="font-semibold text-(--text)">15 menit idle</span>
          </div>
          <div class="flex items-center justify-between">
            <span>Role</span>
            <span class="font-semibold capitalize text-(--text)">{{ Auth::user()->role }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Piecharts --}}
  <div class="mt-6 bg-(--panel) border border-(--border-strong) rounded-2xl p-5 md:p-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-4">
      <div>
        <div class="font-semibold text-base md:text-lg text-(--text)">Ringkasan</div>
        <div class="text-(--muted) text-xs md:text-sm mt-1">Pie chart sinkron dengan <b class="text-(--text)">Filter OPD</b> dan <b class="text-(--text)">Filter Tahun</b> di atas.</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="bg-(--content-bg) border border-(--border-strong) rounded-2xl p-4">
        <div class="font-semibold text-sm md:text-base text-(--text) mb-2">Jumlah OPD Berdasarkan Status Submit LKE</div>
        <div class="h-64">
          <canvas id="pieSubmitAdmin"></canvas>
        </div>
      </div>
      <div class="bg-(--content-bg) border border-(--border-strong) rounded-2xl p-4">
        <div class="font-semibold text-sm md:text-base text-(--text) mb-2">Jumlah Indikator Berdasarkan Pengisian</div>
        <div class="h-64">
          <canvas id="piePenjelasanAdmin"></canvas>
        </div>
      </div>
      <div class="bg-(--content-bg) border border-(--border-strong) rounded-2xl p-4">
        <div class="font-semibold text-sm md:text-base text-(--text) mb-2">Jumlah Indikator Berdasarkan Status Bukti Dukung</div>
        <div class="h-64">
          <canvas id="pieBuktiAdmin"></canvas>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    (function () {
      /**
       * Dashboard Admin (client-side):
       * - Filter OPD + Tahun (atas) mengatur navigasi Monitoring LKE
       * - Memanggil stats() untuk angka kartu
       * - Memanggil pieStats() untuk angka pie chart
       */
      const pieUrl = "{{ route('admin.dashboard.pie-stats') }}";
      const statsUrl = "{{ route('admin.dashboard.stats') }}";
      const lkeIndexBase = "{{ route('lke.index') }}";

      /**
       * Chart.js CDN kadang terlambat/blocked sehingga `window.Chart` belum ada saat script dieksekusi.
       * Untuk menghindari piechart blank (baru muncul setelah refresh), kita:
       * - cek `window.Chart`
       * - kalau belum ada, coba load ulang dari CDN alternatif
       * - retry beberapa kali sebelum menyerah
       */
      function loadScriptOnce(src) {
        return new Promise((resolve, reject) => {
          if (document.querySelector(`script[data-kp-src="${src}"]`)) return resolve(true);
          const s = document.createElement('script');
          s.src = src;
          s.async = true;
          s.defer = true;
          s.setAttribute('data-kp-src', src);
          s.onload = () => resolve(true);
          s.onerror = () => reject(new Error('failed to load: ' + src));
          document.head.appendChild(s);
        });
      }

      async function ensureChartJs(maxTries = 3) {
        if (typeof window.Chart !== 'undefined') return true;
        const fallbacks = [
          'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
          'https://unpkg.com/chart.js@4.4.1/dist/chart.umd.min.js',
        ];
        for (let i = 0; i < maxTries; i++) {
          for (const src of fallbacks) {
            try {
              await loadScriptOnce(src);
              if (typeof window.Chart !== 'undefined') return true;
            } catch (e) {}
          }
          await new Promise(r => setTimeout(r, 250));
        }
        return typeof window.Chart !== 'undefined';
      }

      const opdSelect = document.getElementById('dashOpdSelectAdmin');
      const yearNavSelect = document.getElementById('dashYearSelectAdmin');
      const goBtn = document.getElementById('dashOpdGoAdmin');
      if (opdSelect && yearNavSelect && goBtn) {
        const updateHref = () => {
          const uid = parseInt(opdSelect.value || '0', 10) || 0;
          const y = parseInt(yearNavSelect.value || '0', 10) || 0;
          const url = new URL(lkeIndexBase, window.location.origin);
          if (uid > 0) url.searchParams.set('user_id', String(uid));
          if (y > 0) url.searchParams.set('export_year', String(y));
          goBtn.setAttribute('href', url.toString());
        };
        opdSelect.addEventListener('change', updateHref);
        yearNavSelect.addEventListener('change', updateHref);
        updateHref();
      }

      // Live update stat card(s) based on filter OPD/Tahun (bukan piechart)
      const elTotalLke = document.querySelector('[data-dash-admin-total-lke]');
      const elTotalLkeSub = document.querySelector('[data-dash-admin-total-lke-subtitle]');
      let statsAborter = null;
      async function refreshStats() {
        if (!opdSelect || !yearNavSelect || !elTotalLke) return;
        try {
          if (statsAborter) statsAborter.abort();
          statsAborter = new AbortController();
          const uid = parseInt(opdSelect.value || '0', 10) || 0;
          const y = parseInt(yearNavSelect.value || '0', 10) || 0;
          const url = new URL(statsUrl, window.location.origin);
          if (uid > 0) url.searchParams.set('user_id', String(uid));
          if (y > 0) url.searchParams.set('year', String(y));
          const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, signal: statsAborter.signal });
          const json = await res.json();
          if (!json || !json.ok || !json.cards) return;
          if (typeof json.cards.total_lke === 'number') elTotalLke.textContent = String(json.cards.total_lke);
          if (elTotalLkeSub) {
            const parts = [];
            if (uid > 0) parts.push('OPD terfilter');
            if (y > 0) parts.push('tahun ' + y);
            elTotalLkeSub.textContent = parts.length ? ('Isian OPD (monitoring) • ' + parts.join(' • ')) : 'Isian OPD (monitoring)';
          }
        } catch (e) {}
      }
      if (opdSelect && yearNavSelect) {
        opdSelect.addEventListener('change', () => { refreshStats(); refreshCharts(); });
        yearNavSelect.addEventListener('change', () => { refreshStats(); refreshCharts(); });
        refreshStats();
      }

      const ctxSubmit = document.getElementById('pieSubmitAdmin');
      const ctxPenjelasan = document.getElementById('piePenjelasanAdmin');
      const ctxBukti = document.getElementById('pieBuktiAdmin');

      if (!ctxSubmit || !ctxPenjelasan || !ctxBukti) return;

      let chartSubmit = null;
      let chartPenjelasan = null;
      let chartBukti = null;
      let aborter = null;

      function createCharts() {
        const baseOptions = {
          type: 'pie',
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom', labels: { color: getComputedStyle(document.documentElement).getPropertyValue('--text').trim() || (document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b') } },
              tooltip: { enabled: true }
            }
          }
        };
        chartSubmit = new window.Chart(ctxSubmit, {
          ...baseOptions,
          data: { labels: ['Sudah', 'Draft', 'Belum'], datasets: [{ data: [0, 0, 0], backgroundColor: ['#16a34a', '#f59e0b', '#94a3b8'] }] }
        });
        chartPenjelasan = new window.Chart(ctxPenjelasan, {
          ...baseOptions,
          data: { labels: ['Lengkap', 'Revisi', 'Kosong'], datasets: [{ data: [0, 0, 0], backgroundColor: ['#16a34a', '#f59e0b', '#94a3b8'] }] }
        });
        chartBukti = new window.Chart(ctxBukti, {
          ...baseOptions,
          data: { labels: ['Lengkap', 'Kosong'], datasets: [{ data: [0, 0], backgroundColor: ['#16a34a', '#94a3b8'] }] }
        });

        // Pantau perubahan mode agar warna legend terupdate live
        const observer = new MutationObserver(() => {
          if (chartSubmit && chartPenjelasan && chartBukti) {
            const tc = getComputedStyle(document.documentElement).getPropertyValue('--text').trim() || (document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b');
            chartSubmit.options.plugins.legend.labels.color = tc;
            chartPenjelasan.options.plugins.legend.labels.color = tc;
            chartBukti.options.plugins.legend.labels.color = tc;
            chartSubmit.update();
            chartPenjelasan.update();
            chartBukti.update();
          }
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
      }

      async function refreshCharts() {
        if (!chartSubmit || !chartPenjelasan || !chartBukti) return;
        try {
          if (aborter) aborter.abort();
          aborter = new AbortController();
          const uid = parseInt(opdSelect?.value || '0', 10) || 0;
          const y = parseInt(yearNavSelect?.value || '0', 10) || 0;
          const url = new URL(pieUrl, window.location.origin);
          if (uid > 0) url.searchParams.set('user_id', String(uid));
          if (y > 0) url.searchParams.set('year', String(y));
          const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, signal: aborter.signal });
          const json = await res.json();
          if (!json || !json.ok || !json.charts) return;

          const s = json.charts.submit;
          const p = json.charts.penjelasan;
          const b = json.charts.bukti;

          chartSubmit.data.labels = s.labels || chartSubmit.data.labels;
          chartSubmit.data.datasets[0].data = s.data || chartSubmit.data.datasets[0].data;
          chartPenjelasan.data.labels = p.labels || chartPenjelasan.data.labels;
          chartPenjelasan.data.datasets[0].data = p.data || chartPenjelasan.data.datasets[0].data;
          chartBukti.data.labels = b.labels || chartBukti.data.labels;
          chartBukti.data.datasets[0].data = b.data || chartBukti.data.datasets[0].data;

          // chart kadang dibuat saat layout belum settle; resize+double update bikin lebih stabil
          chartSubmit.resize(); chartPenjelasan.resize(); chartBukti.resize();
          chartSubmit.update(); chartPenjelasan.update(); chartBukti.update();
          requestAnimationFrame(() => {
            chartSubmit.resize(); chartPenjelasan.resize(); chartBukti.resize();
            chartSubmit.update(); chartPenjelasan.update(); chartBukti.update();
          });
        } catch (e) {}
      }

      async function initChartsWithRetry() {
        const ok = await ensureChartJs(4);
        if (!ok) return;
        if (!chartSubmit) createCharts();
        await refreshCharts();
      }

      // init pertama: DOM ready + window load (layout lebih stabil)
      initChartsWithRetry();
      window.addEventListener('load', () => setTimeout(initChartsWithRetry, 0));
      document.addEventListener('visibilitychange', () => { if (!document.hidden) initChartsWithRetry(); });
      setInterval(() => { if (!document.hidden) initChartsWithRetry(); }, 30000);
    })();
  </script>
@endpush
