<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow">
      <div class="card-body p-4">
        <h3>Ganti Password</h3>
        <form method="post" action="/users/password">
          <div class="mb-3"><label class="form-label">Password Lama</label><input name="oldPassword" type="password" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Password Baru (min 8)</label><input name="newPassword" type="password" class="form-control" required minlength="8"></div>
          <button class="btn btn-primary">Simpan</button>
          <a href="/users/profile" class="btn btn-outline-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
