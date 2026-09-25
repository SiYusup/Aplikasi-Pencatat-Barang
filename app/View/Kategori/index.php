<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0"><i class="bi bi-tags"></i> Data Kategori</h2>
  <div class="d-flex gap-2">
    <input id="qSearch" class="form-control form-control-sm" placeholder="Cari kategori..." style="width:200px">
    <button class="btn btn-outline-primary btn-sm" onclick="load()">Cari</button>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="resetForm()">+ Tambah</button>
  </div>
</div>
<div class="card shadow-sm"><div class="card-body">
  <table class="table table-striped" id="tbl" style="width:100%">
    <thead><tr><th style="width:60px">ID</th><th>Nama</th><th>Deskripsi</th><th style="width:120px">Jml Barang</th><th style="width:170px">Aksi</th></tr></thead>
    <tbody></tbody>
  </table>
</div></div>

<div class="modal fade" id="modalKategori"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Kategori</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="fId">
    <div class="mb-2"><label class="form-label">Nama <span class="text-danger">*</span></label><input id="fNama" class="form-control" placeholder="cth: Elektronik"></div>
    <div class="mb-2"><label class="form-label">Deskripsi</label><textarea id="fDeskripsi" class="form-control" rows="2"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="simpan()">Simpan</button></div>
</div></div></div>

<script>
let dt = null;
let kategoriCache = {};
const el = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
function resetForm() { el('fId').value = ''; el('fNama').value = ''; el('fDeskripsi').value = ''; }
async function load() {
  try {
    const r = await api('/api/v1/kategori?search=' + encodeURIComponent(el('qSearch').value));
    kategoriCache = {};
    (r.data || []).forEach(k => { kategoriCache[k.id] = k; });
    document.querySelector('#tbl tbody').innerHTML = (r.data || []).map(k => `<tr>
      <td>${k.id}</td><td class="fw-semibold">${esc(k.nama)}</td><td>${esc(k.deskripsi ?? '-')}</td>
      <td><span class="badge bg-primary">${k.jumlah_barang ?? 0}</span></td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-warning" onclick="edit(${k.id})">Edit</button>
        <button class="btn btn-sm btn-danger" onclick="hapus(${k.id})">Hapus</button>
      </td></tr>`).join('') || '<tr><td colspan="5" class="text-center">Belum ada kategori</td></tr>';
    dt = safeDataTable('#tbl', dt);
  } catch (e) { swalError(e); }
}
function edit(id) {
  const k = kategoriCache[id];
  if (!k) return;
  el('fId').value = k.id; el('fNama').value = k.nama; el('fDeskripsi').value = k.deskripsi ?? '';
  openModal('modalKategori');
}
async function simpan() {
  const body = { nama: el('fNama').value, deskripsi: el('fDeskripsi').value || null };
  try {
    if (el('fId').value) await api('/api/v1/kategori/' + el('fId').value, { method: 'PUT', body: JSON.stringify(body) });
    else await api('/api/v1/kategori', { method: 'POST', body: JSON.stringify(body) });
    closeModal('modalKategori');
    swalOk('Berhasil', 'Kategori tersimpan'); resetForm(); load();
  } catch (e) { swalError(e); }
}
async function hapus(id) {
  const k = kategoriCache[id] || {};
  const dipakai = k.jumlah_barang ?? 0;
  const c = await swalConfirm({
    title: 'Hapus kategori "' + (k.nama ?? '') + '"?',
    text: dipakai > 0 ? dipakai + ' barang memakai kategori ini (kategori barang tsb jadi kosong).' : 'Kategori akan dihapus permanen.',
    confirmButtonText: 'Ya, hapus'
  });
  if (!c.isConfirmed) return;
  try { await api('/api/v1/kategori/' + id, { method: 'DELETE' }); swalOk('Terhapus', 'Kategori berhasil dihapus'); load(); }
  catch (e) { swalError(e); }
}
el('qSearch').addEventListener('keydown', e => { if (e.key === 'Enter') load(); });
load().catch(swalError);
</script>
