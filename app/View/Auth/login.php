<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow">
      <div class="card-body p-4">
        <h3 class="mb-3">Login</h3>
        <form method="post" action="/users/login" id="formLogin">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Masuk</button>
        </form>
        <p class="mt-3 text-center">Belum punya akun? <a href="/users/register">Registrasi</a></p>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('formLogin').addEventListener('submit', () => {
  if (window.Swal) Swal.fire({ title: 'Memeriksa...', text: 'Mohon tunggu sebentar', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
});
<?php if (!empty($model['error'])): ?>
if (window.Swal) Swal.fire('Login Gagal', <?= json_encode($model['error'], JSON_UNESCAPED_UNICODE) ?>, 'error');
<?php endif; ?>
</script>
