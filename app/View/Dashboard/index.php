<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard Barang</h2>
<div class="row g-3 mb-4" id="statCards">
  <div class="col-md-3"><div class="card stat-card shadow-sm"><div class="card-body"><small class="text-muted">Total Barang</small><h3 id="stTotalBarang">-</h3></div></div></div>
  <div class="col-md-3"><div class="card stat-card shadow-sm" style="border-color:#198754"><div class="card-body"><small class="text-muted">Total Stok</small><h3 id="stTotalStok">-</h3></div></div></div>
  <div class="col-md-3"><div class="card stat-card shadow-sm" style="border-color:#ffc107"><div class="card-body"><small class="text-muted">Nilai Persediaan (Rp)</small><h3 id="stNilai">-</h3></div></div></div>
  <div class="col-md-3"><div class="card stat-card shadow-sm" style="border-color:#dc3545"><div class="card-body"><small class="text-muted">Stok Kritis</small><h3 id="stKritis">-</h3></div></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card shadow-sm"><div class="card-header fw-bold">Arus Masuk vs Keluar (30 hari) — ApexCharts</div><div class="card-body"><div id="chartArus"></div></div></div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm"><div class="card-header fw-bold">Komposisi Stok per Kategori</div><div class="card-body"><div id="chartKategori"></div></div></div>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-sm"><div class="card-header fw-bold">Top 5 Stok Terbanyak</div><div class="card-body"><div id="chartTop"></div></div></div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm"><div class="card-header fw-bold text-danger">Stok Menipis</div><div class="card-body p-0">
      <table class="table table-sm mb-0" id="tblKritis"><thead><tr><th>Barang</th><th>Stok</th></tr></thead><tbody></tbody></table>
    </div></div>
  </div>
</div>
<script>
const el = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

function renderChart(target, options) {
  const node = document.querySelector(target);
  if (typeof ApexCharts === 'undefined') {
    node.innerHTML = '<div class="alert alert-warning mb-0">Grafik tidak dapat dimuat (CDN ApexCharts tidak tersedia / offline).</div>';
    return;
  }
  new ApexCharts(node, options).render();
}

(async function () {
  try {
    const s = await api('/api/v1/dashboard/summary');
    const d = s.data;
    el('stTotalBarang').textContent = d.total_barang;
    el('stTotalStok').textContent = d.total_stok;
    el('stNilai').textContent = Number(d.nilai_persediaan).toLocaleString('id-ID');
    el('stKritis').textContent = d.stok_kritis_count;
    document.querySelector('#tblKritis tbody').innerHTML = (d.stok_kritis || []).map(b =>
      `<tr><td>${esc(b.nama)}</td><td><span class="badge bg-danger">${b.stok}</span></td></tr>`).join('') || '<tr><td colspan=2 class=text-center>Aman</td></tr>';

    // 1. Area chart arus masuk vs keluar
    const c = await api('/api/v1/dashboard/chart?range=30d');
    renderChart('#chartArus', {
      chart: { type: 'area', height: 300, toolbar: { show: true } },
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth' },
      series: [
        { name: 'Masuk', data: c.data.map(x => Number(x.masuk)) },
        { name: 'Keluar', data: c.data.map(x => Number(x.keluar)) }
      ],
      xaxis: { categories: c.data.map(x => x.tanggal), tickAmount: 6 },
      noData: { text: 'Belum ada transaksi' }
    });

    // 2. Donut komposisi stok per kategori
    const stok = await api('/api/v1/laporan/stok');
    const perKat = {};
    (stok.data || []).forEach(b => {
      const nama = (b.kategori && b.kategori.nama) || 'Tanpa Kategori';
      perKat[nama] = (perKat[nama] || 0) + Number(b.stok);
    });
    renderChart('#chartKategori', {
      chart: { type: 'donut', height: 300 },
      series: Object.values(perKat),
      labels: Object.keys(perKat),
      legend: { position: 'bottom' },
      noData: { text: 'Belum ada barang' }
    });

    // 3. Bar top 5 stok terbanyak
    const brg = await api('/api/v1/barang?per_page=100&sort_by=stok&sort_dir=desc');
    const top = (brg.data || []).slice(0, 5);
    renderChart('#chartTop', {
      chart: { type: 'bar', height: 300 },
      plotOptions: { bar: { horizontal: true } },
      series: [{ name: 'Stok', data: top.map(b => Number(b.stok)) }],
      xaxis: { categories: top.map(b => b.nama) },
      noData: { text: 'Belum ada barang' }
    });
  } catch (e) { swalError(e); }
})();
</script>
