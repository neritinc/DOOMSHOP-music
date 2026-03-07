<template>
  <main class="home-shell">
    <section class="hero-card reveal-up">
      <div class="hero-glow hero-glow-left" aria-hidden="true"></div>
      <div class="hero-glow hero-glow-right" aria-hidden="true"></div>

      <p class="hero-label">DOOMSHOP Records</p>
      <h1 class="hero-title">Home</h1>
      <p class="hero-description">Use this page to quickly access core features: tracks, genres, artists, cart, and admin sections.</p>
      <div class="hero-cta">
        <RouterLink class="btn btn-primary-clean" to="/tracks">Browse Tracks</RouterLink>
        <RouterLink class="btn btn-ghost-clean" to="/genres">Genres</RouterLink>
      </div>
      <div class="hero-strip">
        <span class="hero-chip">Track previews</span>
        <span class="hero-chip">Artist / Genre lists</span>
        <span class="hero-chip">Cart management</span>
      </div>

      <div class="hero-metrics">
        <div class="metric-card">
          <p class="metric-label">Quick Route</p>
          <p class="metric-value">Tracks</p>
        </div>
        <div class="metric-card">
          <p class="metric-label">Management</p>
          <p class="metric-value">Artists / Genres</p>
        </div>
        <div class="metric-card metric-wave">
          <p class="metric-label">Playback</p>
          <div class="wave-bars" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span>
          </div>
        </div>
      </div>
    </section>

    <section class="panel-card reveal-up reveal-delay-1">
      <div class="section-head">
        <h2 class="section-title">Quick Start</h2>
        <p class="section-subtitle mb-0">How to get started in under a minute.</p>
      </div>
      <div class="split-grid">
        <ol class="clean-list mb-0">
          <li>Sign in from the <code>/login</code> page.</li>
          <li>Use <code>/tracks</code> to search and play previews.</li>
          <li>As admin, manage artists, genres, and tracks.</li>
        </ol>
        <div class="quick-links">
          <RouterLink class="quick-link" to="/tracks">
            <span class="quick-link-title">Tracks</span>
            <span class="quick-link-text">List, search, preview</span>
          </RouterLink>
          <RouterLink class="quick-link" to="/genres">
            <span class="quick-link-title">Genres</span>
            <span class="quick-link-text">Filter by style</span>
          </RouterLink>
          <RouterLink class="quick-link" to="/artists">
            <span class="quick-link-title">Artists</span>
            <span class="quick-link-text">Browse artists</span>
          </RouterLink>
          <RouterLink class="quick-link" to="/my-cart">
            <span class="quick-link-title">My Cart</span>
            <span class="quick-link-text">Selected items</span>
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="panel-card reveal-up reveal-delay-2" aria-label="Discover more">
      <div class="section-head">
        <h2 class="section-title">More to Explore</h2>
        <p class="section-subtitle mb-0">Additional pages you may use regularly.</p>
      </div>
      <div class="feature-grid">
        <div class="feature-card">
          <p class="action-label">Recommendations</p>
          <p class="action-text">Open the recommendations page to view curated picks.</p>
          <RouterLink class="btn btn-outline-clean" to="/recommendations">Recommendations</RouterLink>
        </div>
        <div class="feature-card">
          <p class="action-label">Videos</p>
          <p class="action-text">Watch liveshow and mix content from a single page.</p>
          <RouterLink class="btn btn-outline-clean" to="/liveshows-mixes">LIVESHOWS / Mixes</RouterLink>
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
  padding: 0.5rem 0.15rem 0.8rem;
  display: grid;
  gap: 1rem;
}

.hero-card {
  position: relative;
  overflow: hidden;
  border-radius: 18px;
  padding: 2rem 1.9rem;
  background:
    radial-gradient(circle at 16% -12%, rgba(56, 189, 248, 0.2), transparent 42%),
    radial-gradient(circle at 95% 12%, rgba(99, 102, 241, 0.2), transparent 35%),
    linear-gradient(118deg, #0b1736 0%, #101d3f 52%, #1b2b55 100%);
  border: 1px solid #1e293b;
  color: #f8fafc;
  box-shadow: 0 14px 36px rgba(15, 23, 42, 0.28);
}

.hero-glow {
  position: absolute;
  width: 250px;
  height: 250px;
  border-radius: 999px;
  filter: blur(32px);
  opacity: 0.34;
  pointer-events: none;
  animation: floatGlow 6s ease-in-out infinite;
}

.hero-glow-left {
  top: -90px;
  left: -40px;
  background: #22d3ee;
}

.hero-glow-right {
  bottom: -115px;
  right: -65px;
  background: #818cf8;
  animation-delay: 1.3s;
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
  position: relative;
}

.hero-description {
  margin: 0.75rem 0 0;
  max-width: 58ch;
  color: #dbeafe;
  line-height: 1.5;
  font-size: 1.08rem;
  position: relative;
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
  text-decoration: none;
  transition: all 0.22s ease;
  border-radius: 999px;
  padding: 0.72rem 1.3rem;
  font-weight: 700;
  font-size: 1rem;
}

.btn-primary-clean:focus-visible,
.btn-ghost-clean:focus-visible,
.btn-outline-clean:focus-visible,
.quick-link:focus-visible {
  outline: 2px solid #60a5fa;
  outline-offset: 3px;
}

.btn-primary-clean {
  background: #f8fafc;
  color: #0f172a;
  border: 1px solid #f8fafc;
}

.btn-primary-clean:hover {
  background: #e2e8f0;
  color: #0f172a;
  transform: translateY(-2px);
}

.btn-ghost-clean {
  background: transparent;
  color: #f8fafc;
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.btn-ghost-clean:hover {
  background: rgba(226, 232, 240, 0.12);
  color: #f8fafc;
  transform: translateY(-2px);
}

.hero-strip {
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  position: relative;
}

.hero-chip {
  border: 1px solid rgba(191, 219, 254, 0.3);
  background: rgba(15, 23, 42, 0.35);
  color: #e2e8f0;
  border-radius: 999px;
  padding: 0.32rem 0.8rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.hero-metrics {
  margin-top: 1rem;
  display: grid;
  gap: 0.6rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.metric-card {
  border: 1px solid rgba(148, 163, 184, 0.28);
  background: rgba(15, 23, 42, 0.36);
  border-radius: 12px;
  padding: 0.65rem 0.75rem;
}

.metric-label {
  margin: 0;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #93c5fd;
  font-weight: 700;
}

.metric-value {
  margin: 0.2rem 0 0;
  font-size: 1rem;
  color: #f8fafc;
  font-weight: 800;
}

.metric-wave {
  display: grid;
  gap: 0.35rem;
}

.wave-bars {
  height: 24px;
  display: flex;
  align-items: flex-end;
  gap: 4px;
}

.wave-bars span {
  width: 5px;
  border-radius: 999px;
  background: linear-gradient(180deg, #67e8f9 0%, #a5b4fc 100%);
  animation: wavePulse 1s ease-in-out infinite;
}

.wave-bars span:nth-child(1) { height: 8px; animation-delay: 0s; }
.wave-bars span:nth-child(2) { height: 16px; animation-delay: 0.1s; }
.wave-bars span:nth-child(3) { height: 22px; animation-delay: 0.2s; }
.wave-bars span:nth-child(4) { height: 13px; animation-delay: 0.3s; }
.wave-bars span:nth-child(5) { height: 18px; animation-delay: 0.4s; }

.panel-card {
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
  padding: 1.35rem;
}

.section-head {
  margin-bottom: 0.9rem;
}

.section-title {
  margin: 0;
  font-size: 1.35rem;
  color: #0f172a;
  font-weight: 800;
}

.section-subtitle {
  margin-top: 0.25rem;
  color: #64748b;
  font-weight: 500;
  font-size: 0.95rem;
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
  display: block;
  text-decoration: none;
  border-radius: 12px;
  padding: 0.85rem 0.95rem;
  font-weight: 600;
  font-size: 1rem;
  color: #0f172a;
  border: 1px solid #dbe3ee;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.quick-link:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #bfd5ff;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
  transform: translateY(-2px);
}

.quick-link-title {
  display: block;
  font-size: 1rem;
  font-weight: 800;
}

.quick-link-text {
  display: block;
  margin-top: 0.2rem;
  font-size: 0.85rem;
  color: #475569;
}

.feature-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.feature-card {
  border: 1px solid #e2e8f0;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 14px;
  padding: 1rem;
  display: grid;
  gap: 0.75rem;
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
  max-width: 58ch;
  font-size: 0.98rem;
  margin: 0;
}

.btn-outline-clean {
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #cfd9e6;
  min-width: 220px;
  text-align: center;
  justify-self: flex-start;
}

.btn-outline-clean:hover {
  background: #f8fafc;
  color: #0f172a;
  transform: translateY(-2px);
}

.reveal-up {
  animation: revealUp 0.55s ease both;
}

.reveal-delay-1 {
  animation-delay: 0.08s;
}

.reveal-delay-2 {
  animation-delay: 0.16s;
}

@keyframes revealUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes floatGlow {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(8px);
  }
}

@keyframes wavePulse {
  0%,
  100% {
    opacity: 0.65;
    transform: scaleY(0.75);
  }
  50% {
    opacity: 1;
    transform: scaleY(1.08);
  }
}

@media (max-width: 767px) {
  .home-shell {
    padding-inline: 0;
  }

  .split-grid {
    grid-template-columns: 1fr;
  }

  .quick-links {
    grid-template-columns: 1fr;
  }

  .feature-grid {
    grid-template-columns: 1fr;
  }

  .hero-card {
    padding: 1.5rem 1.2rem;
  }

  .hero-metrics {
    grid-template-columns: 1fr;
  }
}
</style>
