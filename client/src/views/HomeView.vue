<template>
  <main class="home-shell">
    <section class="hero-card reveal-up">
      <div class="hero-noise" aria-hidden="true"></div>
      <div class="hero-orb hero-orb-a" aria-hidden="true"></div>
      <div class="hero-orb hero-orb-b" aria-hidden="true"></div>

      <p class="hero-label">DOOMSHOP Records Control Room</p>
      <h1 class="hero-title">Build Your Next Sound.</h1>
      <p class="hero-description">
        One colorful dashboard for tracks, genres, artists and cart flows.
        Jump in fast, edit faster, keep the catalog clean.
      </p>

      <div class="hero-cta">
        <RouterLink class="btn btn-primary-clean" to="/tracks">Open Tracks</RouterLink>
        <RouterLink class="btn btn-ghost-clean" to="/artists">Artists</RouterLink>
        <RouterLink class="btn btn-ghost-clean" to="/genres">Genres</RouterLink>
      </div>

      <div class="hero-metrics">
        <div class="metric-card">
          <p class="metric-label">Core Module</p>
          <p class="metric-value">Tracks + Preview</p>
        </div>
        <div class="metric-card">
          <p class="metric-label">Catalog Control</p>
          <p class="metric-value">Artists / Genres</p>
        </div>
        <div class="metric-card metric-wave">
          <p class="metric-label">Live Pulse</p>
          <div class="wave-bars" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span>
          </div>
        </div>
      </div>

      <div class="ticker" aria-hidden="true">
        <span>TRACK PREVIEWS</span>
        <span>ARTIST MANAGEMENT</span>
        <span>GENRE ROUTING</span>
        <span>CART FLOW</span>
        <span>TRACK PREVIEWS</span>
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
  padding: 0.4rem 0.1rem 0.8rem;
  display: grid;
  gap: 1.05rem;
}

.hero-card {
  position: relative;
  overflow: hidden;
  isolation: isolate;
  border-radius: 22px;
  padding: 2.2rem 2rem 1.5rem;
  background:
    radial-gradient(circle at 15% -15%, rgba(255, 160, 55, 0.35), transparent 40%),
    radial-gradient(circle at 88% 8%, rgba(56, 189, 248, 0.28), transparent 35%),
    linear-gradient(120deg, #2c0b4b 0%, #0b1c4f 46%, #024f65 100%);
  border: 1px solid rgba(186, 226, 255, 0.25);
  color: #f8fafc;
  box-shadow: 0 18px 38px rgba(8, 19, 43, 0.35);
}

.hero-noise {
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: 0.18;
  background-image: radial-gradient(rgba(255, 255, 255, 0.8) 0.55px, transparent 0.55px);
  background-size: 4px 4px;
}

.hero-orb {
  position: absolute;
  width: 260px;
  height: 260px;
  border-radius: 999px;
  filter: blur(40px);
  opacity: 0.4;
  pointer-events: none;
  animation: floatGlow 6s ease-in-out infinite;
  z-index: -1;
}

.hero-orb-a {
  top: -100px;
  left: -70px;
  background: #f59e0b;
}

.hero-orb-b {
  bottom: -130px;
  right: -80px;
  background: #22d3ee;
  animation-delay: 1.1s;
}

.hero-label {
  margin: 0 0 0.55rem;
  font-size: 0.79rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  font-weight: 800;
  color: #fbcfe8;
}

.hero-title {
  margin: 0;
  font-size: clamp(2.2rem, 4vw, 3.5rem);
  line-height: 1.03;
  font-weight: 900;
  max-width: 16ch;
}

.hero-description {
  margin: 0.8rem 0 0;
  max-width: 54ch;
  color: #dbeafe;
  line-height: 1.5;
  font-size: 1.03rem;
}

.hero-cta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-top: 1.2rem;
}

.btn-primary-clean,
.btn-ghost-clean,
.btn-outline-clean {
  text-decoration: none;
  transition: all 0.22s ease;
  border-radius: 12px;
  padding: 0.7rem 1.2rem;
  font-weight: 700;
  font-size: 0.95rem;
}

.btn-primary-clean:focus-visible,
.btn-ghost-clean:focus-visible,
.btn-outline-clean:focus-visible,
.quick-link:focus-visible {
  outline: 2px solid #60a5fa;
  outline-offset: 3px;
}

.btn-primary-clean {
  background: linear-gradient(130deg, #ffd8a8, #ffb36b);
  color: #3a1a00;
  border: 1px solid rgba(255, 228, 190, 0.7);
  box-shadow: 0 10px 20px rgba(255, 149, 0, 0.25);
}

.btn-primary-clean:hover {
  color: #3a1a00;
  transform: translateY(-2px);
}

.btn-ghost-clean {
  background: rgba(255, 255, 255, 0.08);
  color: #f8fafc;
  border: 1px solid rgba(255, 255, 255, 0.35);
}

.btn-ghost-clean:hover {
  background: rgba(226, 232, 240, 0.16);
  color: #f8fafc;
  transform: translateY(-2px);
}

.hero-metrics {
  margin-top: 1.05rem;
  display: grid;
  gap: 0.6rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.metric-card {
  border: 1px solid rgba(186, 226, 255, 0.32);
  background: rgba(10, 24, 54, 0.4);
  border-radius: 12px;
  padding: 0.65rem 0.75rem;
  backdrop-filter: blur(8px);
}

.metric-label {
  margin: 0;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #a5f3fc;
  font-weight: 700;
}

.metric-value {
  margin: 0.2rem 0 0;
  font-size: 1rem;
  color: #ecfeff;
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

.ticker {
  margin-top: 0.95rem;
  display: flex;
  gap: 1.4rem;
  overflow: hidden;
  white-space: nowrap;
  border-top: 1px solid rgba(186, 226, 255, 0.25);
  padding-top: 0.7rem;
}

.ticker span {
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.11em;
  color: #bae6fd;
  opacity: 0.9;
  animation: tickerMove 9s linear infinite;
}

.panel-card {
  border-radius: 18px;
  border: 1px solid rgba(140, 171, 214, 0.28);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 252, 255, 0.95));
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
  padding: 1.35rem;
  backdrop-filter: blur(10px);
}

.section-head {
  margin-bottom: 0.9rem;
}

.section-title {
  margin: 0;
  font-size: 1.3rem;
  color: #0f2145;
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
  border: 1px solid rgba(148, 180, 225, 0.45);
  background: linear-gradient(180deg, #fdfefe 0%, #edf6ff 100%);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.quick-link:hover {
  background: linear-gradient(180deg, #ffffff 0%, #eaf4ff 100%);
  color: #0f172a;
  border-color: #93c5fd;
  box-shadow: 0 12px 24px rgba(26, 64, 121, 0.14);
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
  border: 1px solid rgba(148, 180, 225, 0.4);
  background: linear-gradient(180deg, #ffffff 0%, #f1f8ff 100%);
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

@keyframes tickerMove {
  0% { transform: translateX(0); }
  100% { transform: translateX(-18px); }
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
    padding: 1.5rem 1.2rem 1.1rem;
  }

  .hero-metrics {
    grid-template-columns: 1fr;
  }
}
</style>
