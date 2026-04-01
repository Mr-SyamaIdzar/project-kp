@extends('layouts.bps')

@php
  $title = 'Dashboard BPS';
  $header = 'Dashboard BPS';
  $subheader = 'Ringkasan LKE OPD untuk proses penilaian.';
@endphp

@section('content')

{{-- Filter OPD (tidak mempengaruhi piechart) --}}
<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="min-w-0 flex-1">
      <div class="text-xs md:text-sm font-semibold text-(--text)">Filter OPD (untuk navigasi cepat)</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-0.5">Filter juga mempengaruhi pie chart di bawah.</div>
      <div class="mt-3 flex flex-col sm:flex-row gap-3">
        <div class="w-full sm:w-80">
          <select id="dashOpdSelectBps" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
            <option value="0">Semua OPD</option>
            @foreach(($opds ?? collect()) as $u)
              <option value="{{ (int) $u->id }}">{{ $u->nama ?? $u->username }} ({{ $u->username }})</option>
            @endforeach
          </select>
        </div>
        <div class="w-full sm:w-56">
          <select id="dashYearSelectBps" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
            <option value="0">Semua Tahun</option>
            @foreach(($years ?? collect()) as $y)
              <option value="{{ (int)$y }}">{{ (int)$y }}</option>
            @endforeach
          </select>
        </div>
        <div class="flex gap-2">
          <a id="dashOpdGoBps" href="{{ route('bps.penilaian.index') }}"
             class="px-3 py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm">
            <i class="bi bi-arrow-right-circle"></i> Buka Penilaian OPD
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">Total OPD</div>
      <i class="bi bi-building text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1" data-dash-bps-total-opd>{{ $totalOpd }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Akun OPD terdaftar</div>
  </div>

  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">Draft</div>
      <i class="bi bi-hourglass-split text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1" data-dash-bps-total-draft>{{ $totalDraft }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Belum final submit</div>
  </div>

  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">LKE Final</div>
      <i class="bi bi-check2-circle text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1" data-dash-bps-total-final>{{ $totalFinal }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Sudah dikumpulkan</div>
  </div>

  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">LKE Masuk Penilaian</div>
      <i class="bi bi-clipboard-check text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1" data-dash-bps-masuk-penilaian>{{ $masukPenilaian }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Siap dinilai</div>
  </div>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 md:p-6 mb-6">
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
      <div class="font-semibold text-base md:text-lg text-(--text)">Menu BPS</div>
      <div class="text-(--muted) text-xs md:text-sm mt-1">Akses cepat ke halaman penilaian.</div>
    </div>

    <div class="flex flex-wrap gap-3">
      <a href="{{ route('bps.penilaian.index') }}" class="px-4 md:px-5 py-2 md:py-2.5 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 font-medium">
        <i class="bi bi-clipboard-check text-base md:text-lg"></i>
        Penilaian OPD
      </a>
    </div>
  </div>
</div>

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
<div class="border rounded-2xl p-5 md:p-6 {{ $infoCardClass }}">
  <div class="font-semibold text-base md:text-lg mb-2 break-words {{ $infoTitleClass }}">{{ $informasi->judul }}</div>
  <div class="text-xs md:text-sm leading-relaxed break-words {{ $infoBodyClass }}">{{ $informasi->isi }}</div>

  <hr class="border-t border-(--border-strong) my-4">

  <div class="space-y-3 text-xs md:text-sm">
    <div class="flex items-center justify-between text-(--muted)">
      <span>Session timeout</span>
      <span class="font-semibold text-(--text)">15 menit idle</span>
    </div>
    <div class="flex items-center justify-between text-(--muted)">
      <span>Role</span>
      <span class="font-semibold text-(--text) capitalize">{{ Auth::user()->role }}</span>
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
        <canvas id="pieSubmitBps"></canvas>
      </div>
    </div>
    <div class="bg-(--content-bg) border border-(--border-strong) rounded-2xl p-4">
      <div class="font-semibold text-sm md:text-base text-(--text) mb-2">Jumlah Indikator Berdasarkan Pengisian</div>
      <div class="h-64">
        <canvas id="piePenjelasanBps"></canvas>
      </div>
    </div>
    <div class="bg-(--content-bg) border border-(--border-strong) rounded-2xl p-4">
      <div class="font-semibold text-sm md:text-base text-(--text) mb-2">Jumlah Indikator Berdasarkan Status Bukti Dukung</div>
      <div class="h-64">
        <canvas id="pieBuktiBps"></canvas>
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
       * Dashboard BPS (client-side):
       * - Filter OPD + Tahun (atas) mengatur navigasi Monitoring LKE
       * - stats URL memanggil angka kartu, pie-stats tersinkron dengan filter global
       *
       * Stabilitas render:
       * - Chart.js CDN kadang belum siap saat script jalan → tambah loader+retry.
       * - Resize+double update menghindari kasus chart blank saat layout belum settle.
       */
      const pieUrl = "/bps/dashboard/pie-stats";
      const statsUrl = "/bps/dashboard/stats";
      const penilaianIndexBase = "/bps/penilaian/index";

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

      const opdSelect = document.getElementById('dashOpdSelectBps');
      const yearNavSelect = document.getElementById('dashYearSelectBps');
      const goBtn = document.getElementById('dashOpdGoBps');
      if (opdSelect && yearNavSelect && goBtn) {
        const updateHref = () => {
          const uid = parseInt(opdSelect.value || '0', 10) || 0;
          const y = parseInt(yearNavSelect.value || '0', 10) || 0;
          const url = new URL(penilaianIndexBase, window.location.origin);
          if (uid > 0) url.searchParams.set('user_id', String(uid));
          if (y > 0) url.searchParams.set('export_year', String(y));
          goBtn.setAttribute('href', url.toString());
        };
        opdSelect.addEventListener('change', updateHref);
        yearNavSelect.addEventListener('change', updateHref);
        updateHref();
      }

      // Live update stat cards based on filter OPD/Tahun (bukan piechart)
      const elTotalOpd = document.querySelector('[data-dash-bps-total-opd]');
      const elTotalDraft = document.querySelector('[data-dash-bps-total-draft]');
      const elTotalFinal = document.querySelector('[data-dash-bps-total-final]');
      const elMasuk = document.querySelector('[data-dash-bps-masuk-penilaian]');
      let statsAborter = null;
      async function refreshStats() {
        if (!opdSelect || !yearNavSelect) return;
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
          if (elTotalOpd && typeof json.cards.total_opd === 'number') elTotalOpd.textContent = String(json.cards.total_opd);
          if (elTotalDraft && typeof json.cards.total_draft === 'number') elTotalDraft.textContent = String(json.cards.total_draft);
          if (elTotalFinal && typeof json.cards.total_final === 'number') elTotalFinal.textContent = String(json.cards.total_final);
          if (elMasuk && typeof json.cards.masuk_penilaian === 'number') elMasuk.textContent = String(json.cards.masuk_penilaian);
        } catch (e) {}
      }
      if (opdSelect && yearNavSelect) {
        opdSelect.addEventListener('change', () => { refreshStats(); refreshCharts(); });
        yearNavSelect.addEventListener('change', () => { refreshStats(); refreshCharts(); });
        refreshStats();
      }

      const ctxSubmit = document.getElementById('pieSubmitBps');
      const ctxPenjelasan = document.getElementById('piePenjelasanBps');
      const ctxBukti = document.getElementById('pieBuktiBps');

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
          data: { labels: ['Sudah', 'Draft', 'Belum'], datasets: [{ data: [0, 0, 0],backgroundColor: ['#16a34a', '#f59e0b', '#94a3b8']}] }
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
