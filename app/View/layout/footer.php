<?php if (($model['user'] ?? null)): ?>
  </div><!-- /app-content -->
</div><!-- /app-main -->
<?php else: ?>
</div><!-- /container -->
<?php endif; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
function escHtml(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

async function api(path, options = {}) {
  const res = await fetch(path, { credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, ...options });
  const ct = res.headers.get('content-type') || '';
  const data = ct.includes('json') ? await res.json() : await res.text();
  if (!res.ok) {
    const msg = (data && data.message) ? data.message : ('HTTP ' + res.status);
    throw new Error(msg);
  }
  return data;
}

/* Alert Bootstrap (fallback/damping SweetAlert) */
function bootstrapAlert(msg, type) {
  type = type || 'danger';
  const host = document.querySelector('.app-content') || document.querySelector('.container');
  if (!host) { console.error(msg); return; }
  const div = document.createElement('div');
  div.className = 'alert alert-' + type + ' alert-dismissible fade show';
  div.setAttribute('role', 'alert');
  div.innerHTML = escHtml(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>';
  host.prepend(div);
  setTimeout(() => { try { div.remove(); } catch (e) {} }, 8000);
}

/* SweetAlert dengan fallback Bootstrap bila CDN gagal */
function swalError(e) {
  const msg = (e && e.message) ? e.message : String(e);
  if (window.Swal) Swal.fire('Gagal', msg, 'error');
  else bootstrapAlert(msg, 'danger');
}
function swalOk(title, msg) {
  if (window.Swal) Swal.fire(title, msg, 'success');
  else bootstrapAlert(msg, 'success');
}
async function swalConfirm(opts) {
  if (window.Swal) return Swal.fire(Object.assign({ icon: 'warning', showCancelButton: true, cancelButtonText: 'Batal' }, opts));
  return { isConfirmed: window.confirm(opts.title + (opts.text ? '\n' + opts.text : '')) };
}

/* Inisialisasi aman (tahan bila CDN gagal dimuat) */
function initFlatpickr(sel) {
  if (typeof flatpickr === 'undefined') return;
  document.querySelectorAll(sel).forEach(elm => flatpickr(elm, { dateFormat: 'Y-m-d' }));
}
function safeDataTable(sel, prev) {
  if (prev) { try { prev.destroy(); } catch (e) {} }
  try {
    if (typeof DataTable !== 'undefined') return new DataTable(sel);
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) return window.jQuery(sel).DataTable();
  } catch (e) { console.error('DataTable:', e); }
  return null;
}
function openModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  if (window.bootstrap && window.bootstrap.Modal) new window.bootstrap.Modal(m).show();
  else m.style.display = 'block';
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  if (window.bootstrap && window.bootstrap.Modal) {
    const inst = window.bootstrap.Modal.getInstance(m);
    if (inst) inst.hide();
    else m.style.display = 'none';
  } else m.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
  // Alert dari redirect: ?ok=... (sukses) & ?logout=1 (baru logout)
  const q = new URLSearchParams(location.search);
  const ok = q.get('ok');
  if (ok) swalOk('Berhasil', ok);
  if (q.get('logout') === '1') {
    bootstrapAlert('Anda telah logout. Sampai jumpa!', 'info');
    if (window.Swal) Swal.fire('Logout', 'Anda telah logout.', 'info');
  }
  // Konfirmasi logout di sidebar
  document.querySelectorAll('a.js-logout').forEach(a => a.addEventListener('click', async (ev) => {
    ev.preventDefault();
    const c = await swalConfirm({ title: 'Yakin ingin logout?', text: 'Sesi Anda akan diakhiri dan harus login kembali.', confirmButtonText: 'Ya, logout' });
    if (c.isConfirmed) window.location.href = a.getAttribute('href');
  }));
});
</script>
</body>
</html>
