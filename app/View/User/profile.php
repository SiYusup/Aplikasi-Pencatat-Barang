<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow">
      <div class="card-body p-4">
        <h3>Profile</h3>
        <form method="post" action="/users/profile">
          <div class="mb-3"><label class="form-label">Nama</label><input name="name" class="form-control" value="<?= htmlspecialchars($model['user']->name ?? '') ?>" required></div>
          <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?= htmlspecialchars($model['user']->email ?? '') ?>" required></div>
          <div class="mb-3"><label class="form-label">Role</label><input class="form-control" value="<?= htmlspecialchars($model['user']->role ?? '') ?>" disabled></div>
          <button class="btn btn-primary">Update Profile</button>
          <a href="/users/password" class="btn btn-outline-secondary">Ganti Password</a>
        </form>
      </div>
    </div>
  </div>
</div>
