<div class="d-flex justify-content-between align-items-center mb-3">
  <h2><i class="bi bi-box-arrow-up"></i> Barang Keluar</h2>
  <div>
    <button class="btn btn-success btn-sm" onclick="dl('xlsx')">Excel</button>
    <button class="btn btn-danger btn-sm" onclick="dl('pdf')">PDF</button>
    <button class="btn btn-secondary btn-sm" onclick="dl('csv')">CSV</button>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKeluar">+ Input Keluar</button>
  </div>
</div>
<div class="card shadow-sm mb-3"><div class="card-body row g-2">
  <div class="col-md-3"><input id="qStart" class="form-control date" placeholder="Dari tanggal"></div>
  <div class="col-md-3"><input id="qEnd" class="form-control date" placeholder="Sampai tanggal"></div>
  <div class="col-md-2 d-flex gap-1"><button class="btn btn-outline-primary" onclick="load()">Filter</button><button class="btn btn-outline-secondary" onclick="load()" title="Muat ulang data"><i class="bi bi-arrow-clockwise"></i></button></div>
</div></div>
<div class="card shadow-sm"><div class="card-body">
  <table class="table table-striped" id="tbl" style="width:100%">
    <thead><tr><th>Kode Trx</th><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Penerima</th><th>Aksi</th></tr></thead>
    <tbody></tbody>
  </table>
</div></div>

<div class="modal fade" id="modalKeluar"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Input Barang Keluar</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-2"><label class="form-label">Barang</label><select id="fBarang" class="form-select"></select>
      <div id="barangWarn" class="form-text text-danger d-none">Gagal memuat data barang. <a href="#" onclick="loadBarangOption();return false;">Coba lagi</a></div>
    </div>
    <div class="row"><div class="col mb-2"><label class="form-label">Jumlah</label><input id="fJumlah" type="number" class="form-control" value="1" min="1"></div>
    <div class="col mb-2"><label class="form-label">Tanggal Keluar</label><input id="fTgl" class="form-control date"></div></div>
    <div class="mb-2"><label class="form-label">Penerima</label><input id="fPenerima" class="form-control" placeholder="Divisi / nama penerima"></div>
    <div class="mb-2"><label class="form-label">Keterangan</label><input id="fKet" class="form-control"></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="simpan()">Simpan</button></div>
</div></div></div>

<script>
let dt;
const el = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
initFlatpickr('.date');
function dl(f) { window.open(`/api/v1/export/keluar?format=${f}&start_date=${el('qStart').value}&end_date=${el('qEnd').value}`, '_blank'); }
async function loadBarangOption() {
  const sel = el('fBarang'), warn = el('barangWarn');
  sel.disabled = true;
  sel.innerHTML = '<option value="">Memuat data barang...</option>';
  warn.classList.add('d-none');
  try {
    const b = await api('/api/v1/barang?per_page=100');
    if (!b.data || b.data.length === 0) {
      sel.innerHTML = '<option value="">-- Belum ada data barang --</option>';
      warn.innerHTML = 'Belum ada data barang. <a href="/barang">Tambah di menu Data Barang</a>';
      warn.classList.remove('d-none');
    } else {
      sel.innerHTML = b.data.map(x => `<option value="${x.id}">${esc(x.kode)} — ${esc(x.nama)} (stok ${x.stok})</option>`).join('');
    }
    sel.disabled = false;
  } catch (e) {
    sel.innerHTML = '<option value="">-- gagal memuat --</option>';
    warn.innerHTML = 'Gagal memuat data barang. <a href="#" onclick="loadBarangOption();return false;">Coba lagi</a>';
    warn.classList.remove('d-none');
    console.error(e);
  }
}
async function load() {
  try {
    const r = await api(`/api/v1/barang-keluar?start_date=${el('qStart').value}&end_date=${el('qEnd').value}`);
    const html = r.data.map(x => `<tr>
      <td>${esc(x.kode_transaksi)}</td><td>${esc(x.tanggal_keluar)}</td><td>${esc(x.barang_nama)}</td><td>${x.jumlah}</td><td>${esc(x.penerima ?? '-')}</td>
      <td><button class="btn btn-sm btn-danger" onclick="batal(${x.id})">Batalkan</button></td></tr>`).join('') || '<tr><td colspan="6" class="text-center text-muted">Belum ada transaksi barang keluar pada periode ini.</td></tr>';
    dt = refreshTable('#tbl', dt, html, 'barang-keluar');
  } catch (e) { swalError(e); }
}
async function simpan() {
  try {
    await api('/api/v1/barang-keluar', { method: 'POST', body: JSON.stringify({ barang_id: Number(el('fBarang').value), jumlah: Number(el('fJumlah').value), tanggal_keluar: el('fTgl').value || undefined, penerima: el('fPenerima').value, keterangan: el('fKet').value }) });
    closeModal('modalKeluar');
    swalOk('Berhasil', 'Barang keluar dicatat, stok berkurang'); load(); loadBarangOption();
  } catch (e) { swalError(e); }
}
async function batal(id) {
  const c = await swalConfirm({ title: 'Batalkan transaksi?', text: 'Stok barang akan dikembalikan.', confirmButtonText: 'Ya, batalkan' });
  if (!c.isConfirmed) return;
  try { await api('/api/v1/barang-keluar/' + id, { method: 'DELETE' }); swalOk('Dibatalkan', 'Transaksi dibatalkan, stok dikembalikan'); load(); }
  catch (e) { swalError(e); }
}
// Muat ulang opsi setiap modal dibuka agar selalu segar
el('modalKeluar').addEventListener('show.bs.modal', loadBarangOption);
loadBarangOption();
load().catch(swalError);
</script>
