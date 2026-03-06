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

    <div class="col-12">
      <section class="recommendation-item" aria-label="Recommendations">
        <div class="recommendation-copy">
          <p class="recommendation-kicker">Curated Picks</p>
          <p class="recommendation-text mb-0">Want to expand your music library? Check out our top recommendations!</p>
        </div>
        <RouterLink class="btn recommendation-btn" to="/recommendations">Open Recommendations</RouterLink>
      </section>
    </div>
  </div>
</template>

<script>
export default {};
</script>

<style scoped>
.recommendation-item {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  border-radius: 14px;
  padding: 1rem 1.1rem;
  border: 1px solid #d8e5f7;
  background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}

.recommendation-kicker {
  margin: 0 0 0.2rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #2563eb;
}

.recommendation-text {
  color: #0f172a;
  font-weight: 600;
  line-height: 1.35;
}

.recommendation-btn {
  border-radius: 999px;
  padding: 0.45rem 0.95rem;
  background: #2563eb;
  color: #fff;
  border: 1px solid #1d4ed8;
  font-weight: 700;
}

.recommendation-btn:hover {
  background: #1d4ed8;
  color: #fff;
}

@media (max-width: 767px) {
  .recommendation-item {
    align-items: flex-start;
  }
}
</style>
