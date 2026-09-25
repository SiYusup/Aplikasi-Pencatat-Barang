<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0"><i class="bi bi-truck"></i> Data Supplier</h2>
  <div class="d-flex gap-2">
    <input id="qSearch" class="form-control form-control-sm" placeholder="Cari supplier..." style="width:200px">
    <button class="btn btn-outline-primary btn-sm" onclick="load()">Cari</button>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSupplier" onclick="resetForm()">+ Tambah</button>
  </div>
</div>
<div class="card shadow-sm"><div class="card-body">
  <table class="table table-striped" id="tbl" style="width:100%">
    <thead><tr><th style="width:60px">ID</th><th>Nama</th><th>Kontak</th><th>Alamat</th><th style="width:120px">Jml Transaksi</th><th style="width:170px">Aksi</th></tr></thead>
    <tbody></tbody>
  </table>
</div></div>

<div class="modal fade" id="modalSupplier"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Supplier</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="fId">
    <div class="mb-2"><label class="form-label">Nama <span class="text-danger">*</span></label><input id="fNama" class="form-control" placeholder="cth: PT Maju Jaya"></div>
    <div class="mb-2"><label class="form-label">Kontak</label><input id="fKontak" class="form-control" placeholder="cth: 081234567890"></div>
    <div class="mb-2"><label class="form-label">Alamat</label><textarea id="fAlamat" class="form-control" rows="2"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="simpan()">Simpan</button></div>
</div></div></div>

<script>
let dt = null;
let supplierCache = {};
const el = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
function resetForm() { el('fId').value = ''; el('fNama').value = ''; el('fKontak').value = ''; el('fAlamat').value = ''; }
async function load() {
  try {
    const r = await api('/api/v1/supplier?search=' + encodeURIComponent(el('qSearch').value));
    supplierCache = {};
    (r.data || []).forEach(s => { supplierCache[s.id] = s; });
    const html = (r.data || []).map(s => `<tr>
      <td>${s.id}</td><td class="fw-semibold">${esc(s.nama)}</td><td>${esc(s.kontak ?? '-')}</td><td>${esc(s.alamat ?? '-')}</td>
      <td><span class="badge bg-primary">${s.jumlah_transaksi ?? 0}</span></td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-warning" onclick="edit(${s.id})">Edit</button>
        <button class="btn btn-sm btn-danger" onclick="hapus(${s.id})">Hapus</button>
      </td></tr>`).join('') || '<tr><td colspan="6" class="text-center">Belum ada supplier</td></tr>';
    dt = refreshTable('#tbl', dt, html, 'supplier');
  } catch (e) { swalError(e); }
}
function edit(id) {
  const s = supplierCache[id];
  if (!s) return;
  el('fId').value = s.id; el('fNama').value = s.nama; el('fKontak').value = s.kontak ?? ''; el('fAlamat').value = s.alamat ?? '';
  openModal('modalSupplier');
}
async function simpan() {
  const body = { nama: el('fNama').value, kontak: el('fKontak').value || null, alamat: el('fAlamat').value || null };
  try {
    if (el('fId').value) await api('/api/v1/supplier/' + el('fId').value, { method: 'PUT', body: JSON.stringify(body) });
    else await api('/api/v1/supplier', { method: 'POST', body: JSON.stringify(body) });
    closeModal('modalSupplier');
    swalOk('Berhasil', 'Supplier tersimpan'); resetForm(); load();
  } catch (e) { swalError(e); }
}
async function hapus(id) {
  const s = supplierCache[id] || {};
  const dipakai = s.jumlah_transaksi ?? 0;
  const c = await swalConfirm({
    title: 'Hapus supplier "' + (s.nama ?? '') + '"?',
    text: dipakai > 0 ? dipakai + ' transaksi memakai supplier ini (supplier transaksi tsb jadi kosong).' : 'Supplier akan dihapus permanen.',
    confirmButtonText: 'Ya, hapus'
  });
  if (!c.isConfirmed) return;
  try { await api('/api/v1/supplier/' + id, { method: 'DELETE' }); swalOk('Terhapus', 'Supplier berhasil dihapus'); load(); }
  catch (e) { swalError(e); }
}
el('qSearch').addEventListener('keydown', e => { if (e.key === 'Enter') load(); });
load().catch(swalError);
</script>
