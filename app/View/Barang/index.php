<div class="d-flex justify-content-between align-items-center mb-3">
  <h2><i class="bi bi-boxes"></i> Data Barang</h2>
  <div>
    <button class="btn btn-success btn-sm" onclick="window.open('/api/v1/export/barang?format=xlsx','_blank')">Excel</button>
    <button class="btn btn-danger btn-sm" onclick="window.open('/api/v1/export/barang?format=pdf','_blank')">PDF</button>
    <button class="btn btn-secondary btn-sm" onclick="window.open('/api/v1/export/barang?format=csv','_blank')">CSV</button>
    <button class="btn btn-primary btn-sm" onclick="tambahBarang()">+ Tambah</button>
  </div>
</div>
<div class="card shadow-sm"><div class="card-body">
  <table class="table table-striped" id="tblBarang" style="width:100%">
    <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Satuan</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
    <tbody></tbody>
  </table>
</div></div>

<div class="modal fade" id="modalBarang"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Barang</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="fId">
    <div class="mb-2"><label class="form-label">Kode (kosong = auto)</label><input id="fKode" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Nama</label><input id="fNama" class="form-control"></div>
    <div class="row">
      <div class="col mb-2"><label class="form-label">Kategori</label><select id="fKategori" class="form-select"></select>
        <div id="kategoriWarn" class="form-text text-danger d-none">Gagal memuat kategori. <a href="#" onclick="loadKategori();return false;">Coba lagi</a></div>
      </div>
      <div class="col mb-2"><label class="form-label">Satuan</label><input id="fSatuan" class="form-control" value="pcs"></div>
    </div>
    <div class="row">
      <div class="col mb-2"><label class="form-label">Harga</label><input id="fHarga" type="number" class="form-control" value="0"></div>
      <div class="col mb-2"><label class="form-label">Stok Awal</label><input id="fStok" type="number" class="form-control" value="0"></div>
      <div class="col mb-2"><label class="form-label">Stok Min</label><input id="fStokMin" type="number" class="form-control" value="5"></div>
    </div>
    <div class="mb-2"><label class="form-label">Deskripsi</label><textarea id="fDeskripsi" class="form-control"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="simpan()">Simpan</button></div>
</div></div></div>

<div class="modal fade" id="modalOpname"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Stok Opname</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="oId">
    <div class="mb-2"><label class="form-label">Stok Fisik</label><input id="oStok" type="number" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Keterangan</label><input id="oKet" class="form-control"></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-warning" onclick="simpanOpname()">Sesuaikan</button></div>
</div></div></div>

<script>
let dt = null;
let barangCache = {};
const el = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

async function loadKategori(selectedId) {
  const sel = el('fKategori'), warn = el('kategoriWarn');
  sel.disabled = true;
  sel.innerHTML = '<option value="">Memuat kategori...</option>';
  warn.classList.add('d-none');
  try {
    const r = await api('/api/v1/kategori');
    sel.innerHTML = '<option value="">- Pilih kategori -</option>' +
      (r.data || []).map(k => `<option value="${k.id}">${esc(k.nama)}</option>`).join('');
    sel.disabled = false;
    if (selectedId) sel.value = selectedId;
  } catch (e) {
    sel.innerHTML = '<option value="">-- gagal memuat --</option>';
    warn.classList.remove('d-none');
    console.error('loadKategori:', e);
  }
}

async function load() {
  try {
    const r = await api('/api/v1/barang?per_page=100');
    barangCache = {};
    (r.data || []).forEach(b => { barangCache[b.id] = b; });
    document.querySelector('#tblBarang tbody').innerHTML = (r.data || []).map(b => `<tr>
      <td>${esc(b.kode)}</td><td>${esc(b.nama)}</td><td>${esc(b.kategori?.nama ?? '-')}</td><td>${esc(b.satuan)}</td>
      <td>${Number(b.harga).toLocaleString('id-ID')}</td>
      <td>${b.is_low_stock ? `<span class="badge bg-danger">${b.stok}</span>` : b.stok}</td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-warning" onclick="edit(${b.id})">Edit</button>
        <button class="btn btn-sm btn-info" onclick="opname(${b.id})">Opname</button>
        <button class="btn btn-sm btn-danger" onclick="hapus(${b.id})">Hapus</button>
      </td></tr>`).join('');
    dt = safeDataTable('#tblBarang', dt);
  } catch (e) { swalError(e); }
}

function tambahBarang() {
  el('fId').value = ''; el('fKode').value = ''; el('fNama').value = '';
  el('fSatuan').value = 'pcs'; el('fHarga').value = 0; el('fStok').value = 0;
  el('fStokMin').value = 5; el('fDeskripsi').value = '';
  loadKategori();
  openModal('modalBarang');
}

function edit(id) {
  const b = barangCache[id];
  if (!b) return;
  el('fId').value = b.id; el('fKode').value = b.kode; el('fNama').value = b.nama;
  el('fSatuan').value = b.satuan; el('fHarga').value = b.harga;
  el('fStokMin').value = b.stok_minimum; el('fDeskripsi').value = b.deskripsi ?? '';
  loadKategori(b.kategori_id ?? '');
  openModal('modalBarang');
}

function opname(id) { el('oId').value = id; openModal('modalOpname'); }

async function simpan() {
  const kat = el('fKategori').value;
  const body = { kode: el('fKode').value || undefined, nama: el('fNama').value, kategori_id: kat === '' ? undefined : Number(kat), satuan: el('fSatuan').value, harga: Number(el('fHarga').value), stok_awal: Number(el('fStok').value || 0), stok_minimum: Number(el('fStokMin').value), deskripsi: el('fDeskripsi').value };
  try {
    if (el('fId').value) await api('/api/v1/barang/' + el('fId').value, { method: 'PUT', body: JSON.stringify(body) });
    else await api('/api/v1/barang', { method: 'POST', body: JSON.stringify(body) });
    closeModal('modalBarang');
    swalOk('Berhasil', 'Data barang tersimpan');
    load();
  } catch (e) { swalError(e); }
}

async function simpanOpname() {
  const c = await swalConfirm({ title: 'Sesuaikan stok?', text: 'Stok sistem akan diubah mengikuti hasil opname.', confirmButtonText: 'Ya, sesuaikan' });
  if (!c.isConfirmed) return;
  try {
    await api('/api/v1/barang/' + el('oId').value + '/opname', { method: 'POST', body: JSON.stringify({ stok_fisik: Number(el('oStok').value), keterangan: el('oKet').value }) });
    closeModal('modalOpname');
    swalOk('Berhasil', 'Stok disesuaikan'); load();
  } catch (e) { swalError(e); }
}

async function hapus(id) {
  const c = await swalConfirm({ title: 'Hapus barang?', text: 'Data yang dihapus tidak dapat dikembalikan.', confirmButtonText: 'Ya, hapus' });
  if (!c.isConfirmed) return;
  try { await api('/api/v1/barang/' + id, { method: 'DELETE' }); swalOk('Terhapus', 'Barang berhasil dihapus'); load(); }
  catch (e) { swalError(e); }
}

// Dropdown kategori & tabel dimuat independen agar satu gagal tidak memblokir lainnya
loadKategori();
load();
</script>
