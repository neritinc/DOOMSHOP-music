<template>
  <main class="home-shell">
    <section class="hero-card">
      <p class="hero-label">DOOMSHOP Records</p>
      <h1 class="hero-title">Music. Fast. Simple.</h1>
      <p class="hero-description">
        Quickly find tracks, artists, and genres. Everything important is in one clear place.
      </p>
      <div class="hero-cta">
        <RouterLink class="btn btn-primary-clean" to="/tracks">Browse Tracks</RouterLink>
        <RouterLink class="btn btn-ghost-clean" to="/about">About</RouterLink>
      </div>
    </section>

    <section class="panel-card">
      <div class="section-head">
        <h2 class="section-title">Quick Start</h2>
      </div>
      <div class="split-grid">
        <ol class="clean-list mb-0">
          <li>Log in from the <code>/login</code> page.</li>
          <li>Open tracks and play audio previews.</li>
          <li>As admin, manage artists, genres, and carts.</li>
        </ol>
        <div class="quick-links">
          <RouterLink class="quick-link" to="/tracks">Tracks</RouterLink>
          <RouterLink class="quick-link" to="/genres">Genres</RouterLink>
          <RouterLink class="quick-link" to="/artists">Artists</RouterLink>
          <RouterLink class="quick-link" to="/my-cart">My Cart</RouterLink>
        </div>
      </div>
    </section>

    <section class="panel-card" aria-label="Discover more">
      <div class="section-head">
        <h2 class="section-title">More to Explore</h2>
      </div>
      <div class="action-rows">
        <div class="action-row">
          <div>
            <p class="action-label">Curated Picks</p>
            <p class="action-text mb-0">Check out our recommended music on a dedicated page.</p>
          </div>
          <RouterLink class="btn btn-outline-clean" to="/recommendations">Open Recommendations</RouterLink>
        </div>

        <div class="row-divider"></div>

        <div class="action-row">
          <div>
            <p class="action-label">Video Section</p>
            <p class="action-text mb-0">Watch LIVESHOWS and Mixes YouTube videos in one place.</p>
          </div>
          <RouterLink class="btn btn-outline-clean" to="/liveshows-mixes">LIVESHOWS and Mixes</RouterLink>
        </div>
      </div>
    </section>
  </main>
</template>

<script>
export default {};
</script>

<style scoped>
.home-shell {
  max-width: 1160px;
  margin: 0 auto;
  padding: 0.5rem 0.15rem 0.6rem;
  display: grid;
  gap: 1.15rem;
}

.hero-card {
  border-radius: 18px;
  padding: 2rem 1.9rem;
  background:
    radial-gradient(circle at 88% 15%, rgba(30, 64, 175, 0.18), transparent 45%),
    linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
  border: 1px solid #1e293b;
  color: #f8fafc;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.28);
}

.hero-label {
  margin: 0 0 0.4rem;
  font-size: 0.86rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  font-weight: 700;
  color: #93c5fd;
}

.hero-title {
  margin: 0;
  font-size: clamp(2.2rem, 3.6vw, 3.1rem);
  line-height: 1.08;
  font-weight: 900;
}

.hero-description {
  margin: 0.75rem 0 0;
  max-width: 64ch;
  color: #dbeafe;
  line-height: 1.5;
  font-size: 1.15rem;
}

.hero-cta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  margin-top: 1.35rem;
}

.btn-primary-clean,
.btn-ghost-clean,
.btn-outline-clean {
  border-radius: 999px;
  padding: 0.72rem 1.3rem;
  font-weight: 700;
  font-size: 1.04rem;
}

.btn-primary-clean {
  background: #f8fafc;
  color: #0f172a;
  border: 1px solid #f8fafc;
}

.btn-primary-clean:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.btn-ghost-clean {
  background: transparent;
  color: #f8fafc;
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.btn-ghost-clean:hover {
  background: rgba(226, 232, 240, 0.12);
  color: #f8fafc;
}

.panel-card {
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
  padding: 1.35rem;
}

.section-head {
  margin-bottom: 0.8rem;
}

.section-title {
  margin: 0;
  font-size: 1.35rem;
  color: #0f172a;
  font-weight: 800;
}

.split-grid {
  display: grid;
  gap: 1.2rem;
  grid-template-columns: 1.2fr 1fr;
}

.clean-list {
  margin: 0;
  padding-left: 1.1rem;
  color: #1e293b;
  line-height: 1.7;
  font-size: 1.08rem;
}

.quick-links {
  display: grid;
  gap: 0.7rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.quick-link {
  text-decoration: none;
  border-radius: 12px;
  padding: 0.85rem 0.95rem;
  font-weight: 700;
  font-size: 1.02rem;
  color: #0f172a;
  border: 1px solid #dbe3ee;
  background: #f8fafc;
}

.quick-link:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.action-rows {
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
}

.action-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.row-divider {
  height: 1px;
  background: #e2e8f0;
}

.action-label {
  margin: 0 0 0.2rem;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #1d4ed8;
}

.action-text {
  color: #334155;
  font-weight: 500;
  line-height: 1.5;
  max-width: 64ch;
  font-size: 1.03rem;
}

.btn-outline-clean {
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #cfd9e6;
  min-width: 260px;
  text-align: center;
}

.btn-outline-clean:hover {
  background: #f8fafc;
  color: #0f172a;
}

@media (max-width: 767px) {
  .home-shell {
    padding-inline: 0;
  }

  .split-grid {
    grid-template-columns: 1fr;
  }

  .action-row {
    align-items: flex-start;
  }

  .quick-links {
    grid-template-columns: 1fr;
  }
}
</style>
