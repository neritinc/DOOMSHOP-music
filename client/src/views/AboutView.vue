<template>
  <section class="about-page">
    <header class="about-hero">
      <div class="about-hero__content">
        <p class="about-eyebrow">DOOMSHOP</p>
        <h2 class="about-title">About the client</h2>
        <p class="about-lead mb-0">
          The DOOMSHOP client is aligned with the new music backend domain and focuses on a clean,
          fast path from discovery to checkout.
        </p>
      </div>
    </header>

    <div class="about-card card card-body">
      <div class="about-badges">
        <span class="about-badge">Vue</span>
        <span class="about-badge">Pinia</span>
        <span class="about-badge">Axios</span>
        <span class="about-badge">Laravel API</span>
      </div>
      <p class="mb-0">
        Roles are separated clearly, with guarded routes and API services that map directly to
        backend endpoints.
      </p>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-12 col-lg-4">
        <div class="about-tile card card-body h-100">
          <p class="about-pill">Public</p>
          <h3 class="h6 mb-2">Browse tracks</h3>
          <p class="mb-0">Open listing with previews and fast discovery.</p>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="about-tile card card-body h-100">
          <p class="about-pill">Customer</p>
          <h3 class="h6 mb-2">Own profile + cart</h3>
          <p class="mb-0">Personal profile, cart management, and checkout flow.</p>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="about-tile card card-body h-100">
          <p class="about-pill">Admin</p>
          <h3 class="h6 mb-2">Full catalog control</h3>
          <p class="mb-0">Tracks create, genres/artists CRUD, and all carts.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
export default {};
</script>

<style scoped>
.about-page {
  display: grid;
  gap: 1.25rem;
}

.about-hero {
  padding: 1.5rem;
  border-radius: 0.75rem;
  background: linear-gradient(135deg, #141414, #2a1f1f 55%, #3a2323);
  color: #f2f2f2;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
}

.about-hero__content {
  max-width: 680px;
}

.about-eyebrow {
  letter-spacing: 0.3em;
  font-size: 0.7rem;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
  color: rgba(255, 255, 255, 0.7);
}

.about-title {
  margin-bottom: 0.75rem;
}

.about-lead {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.85);
}

.about-card {
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: #ffffff;
}

.about-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.about-badge {
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #f4f0f0;
  color: #4a2d2d;
}

.about-tile {
  border: 1px solid rgba(0, 0, 0, 0.08);
  transition: transform 140ms ease, box-shadow 140ms ease;
}

.about-tile:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.about-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem 0.7rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #1f1a1a;
  color: #f4f0f0;
  margin-bottom: 0.75rem;
}

@media (max-width: 576px) {
  .about-hero {
    padding: 1.25rem;
  }
}
</style>
