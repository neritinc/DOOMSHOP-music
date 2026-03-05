<template>
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="p-4 rounded-4 hero-panel">
        <h2 class="display-6 fw-bold mb-2">DOOMSHOP Records</h2>
        <p class="mb-3">Hardstyle, riddim and rawstyle catalog connected to your Laravel backend.</p>
        <div class="d-flex gap-2">
          <RouterLink class="btn btn-light" to="/tracks">Browse Tracks</RouterLink>
          <RouterLink class="btn btn-outline-light" to="/about">About</RouterLink>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6">Quick Start</h3>
          <ol class="small mb-0">
            <li>Login with admin or customer from <code>/login</code>.</li>
            <li>Open tracks and test audio paths from storage.</li>
            <li>Admin can manage genres, artists, all carts.</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {};
</script>
