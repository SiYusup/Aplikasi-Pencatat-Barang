<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow">
      <div class="card-body p-4">
        <h3 class="mb-3">Registrasi</h3>
        <form method="post" action="/users/register" id="formRegister">
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password (min 8)</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="mb-3">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>
          <button class="btn btn-success w-100">Daftar</button>
        </form>
        <p class="mt-3 text-center">Sudah punya akun? <a href="/users/login">Login</a></p>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('formRegister').addEventListener('submit', (ev) => {
  const pwd = document.querySelector('#formRegister input[name="password"]').value;
  const konf = document.querySelector('#formRegister input[name="password_confirmation"]').value;
  if (pwd !== konf) {
    ev.preventDefault();
    if (window.Swal) Swal.fire('Gagal', 'Konfirmasi password tidak sama', 'error');
    else alert('Konfirmasi password tidak sama');
    return;
  }
  if (window.Swal) Swal.fire({ title: 'Mendaftarkan...', text: 'Mohon tunggu sebentar', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
});
<?php if (!empty($model['error'])): ?>
if (window.Swal) Swal.fire('Registrasi Gagal', <?= json_encode($model['error'], JSON_UNESCAPED_UNICODE) ?>, 'error');
<?php endif; ?>
</script>
