<div class="d-flex justify-content-between align-items-center mb-3">
  <h2><i class="bi bi-file-earmark-text"></i> Laporan</h2>
  <div id="exportBtns">
    <button class="btn btn-success btn-sm" onclick="dl('xlsx')">Excel</button>
    <button class="btn btn-danger btn-sm" onclick="dl('pdf')">PDF</button>
    <button class="btn btn-secondary btn-sm" onclick="dl('csv')">CSV</button>
  </div>
</div>
<div class="card shadow-sm mb-3"><div class="card-body row g-2">
  <div class="col-md-3"><input id="qStart" class="form-control date" placeholder="Dari tanggal"></div>
  <div class="col-md-3"><input id="qEnd" class="form-control date" placeholder="Sampai tanggal"></div>
  <div class="col-md-2"><button class="btn btn-outline-primary" onclick="load()">Tampilkan</button></div>
</div></div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-bold">Barang Masuk <span id="sumMasuk" class="badge bg-primary"></span></div><div class="card-body"><table class="table table-sm" id="tblMasuk"><thead><tr><th>Tanggal</th><th>Barang</th><th>Qty</th></tr></thead><tbody></tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-bold">Barang Keluar <span id="sumKeluar" class="badge bg-warning"></span></div><div class="card-body"><table class="table table-sm" id="tblKeluar"><thead><tr><th>Tanggal</th><th>Barang</th><th>Qty</th></tr></thead><tbody></tbody></table></div></div></div>
  <div class="col-12"><div class="card shadow-sm"><div class="card-header fw-bold">Mutasi / Kartu Stok</div><div class="card-body"><table class="table table-sm" id="tblMutasi"><thead><tr><th>Tanggal</th><th>Tipe</th><th>Kode</th><th>Barang</th><th>Masuk</th><th>Keluar</th></tr></thead><tbody></tbody></table></div></div></div>
</div>
<script>
const el = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
initFlatpickr('.date');
function dl(f) { window.open(`/api/v1/export/laporan?format=${f}&start_date=${el('qStart').value}&end_date=${el('qEnd').value}`, '_blank'); }
async function load() {
  try {
    const q = `start_date=${el('qStart').value}&end_date=${el('qEnd').value}`;
    const [m, k, mu] = await Promise.all([api('/api/v1/laporan/masuk?' + q), api('/api/v1/laporan/keluar?' + q), api('/api/v1/laporan/mutasi?' + q)]);
    el('sumMasuk').textContent = 'Total qty: ' + (m.summary?.total_qty ?? 0);
    el('sumKeluar').textContent = 'Total qty: ' + (k.summary?.total_qty ?? 0);
    document.querySelector('#tblMasuk tbody').innerHTML = m.data.map(x => `<tr><td>${esc(x.tanggal_masuk)}</td><td>${esc(x.barang_nama)}</td><td>${x.jumlah}</td></tr>`).join('') || '<tr><td colspan=3 class=text-center>-</td></tr>';
    document.querySelector('#tblKeluar tbody').innerHTML = k.data.map(x => `<tr><td>${esc(x.tanggal_keluar)}</td><td>${esc(x.barang_nama)}</td><td>${x.jumlah}</td></tr>`).join('') || '<tr><td colspan=3 class=text-center>-</td></tr>';
    document.querySelector('#tblMutasi tbody').innerHTML = mu.data.map(x => `<tr><td>${esc(x.tanggal)}</td><td><span class="badge ${x.tipe === 'masuk' ? 'bg-success' : 'bg-warning'}">${esc(x.tipe)}</span></td><td>${esc(x.kode_transaksi)}</td><td>${esc(x.barang)}</td><td>${x.qty_masuk}</td><td>${x.qty_keluar}</td></tr>`).join('') || '<tr><td colspan=6 class=text-center>-</td></tr>';
  } catch (e) { swalError(e); }
}
load().catch(swalError);
</script>
