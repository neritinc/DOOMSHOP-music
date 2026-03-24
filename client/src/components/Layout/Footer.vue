<template>
  <footer class="footer-shell mt-4">
    <div class="footer-glow"></div>
    <div class="footer-grid">
      <div class="footer-brand">
        <p class="footer-kicker">DOOMSHOP RECORDS</p>
        <h2 class="footer-title">Underground sounds, curated fast.</h2>
        <p class="footer-copy">
          Fresh releases, curated mixes, and full downloads. No noise, just music.
        </p>
      </div>

      <div class="footer-links">
        <p class="footer-label">Quick links</p>
        <RouterLink class="footer-link" to="/tracks">Tracks</RouterLink>
        <RouterLink class="footer-link" to="/albums">Albums</RouterLink>
        <RouterLink class="footer-link" to="/artists">Artists</RouterLink>
        <RouterLink class="footer-link" to="/genres">Genres</RouterLink>
      </div>

      <div class="footer-links">
        <p class="footer-label">Community</p>
        <RouterLink class="footer-link" to="/liveshows-mixes">Liveshows</RouterLink>
        <RouterLink class="footer-link" to="/recommendations">Recommendations</RouterLink>
        <RouterLink class="footer-link" to="/about">About</RouterLink>
        <a class="footer-link" href="mailto:info@doomshoprecords.com">Contact</a>
      </div>

      <div class="footer-card">
        <p class="footer-label">Newsletter</p>
        <p class="footer-copy small">
          One email per month. New releases and exclusive downloads.
        </p>
        <div class="footer-input">
          <input type="email" placeholder="email@domain.com" aria-label="Newsletter email" />
          <button type="button">Subscribe</button>
        </div>
        <p class="footer-meta">No spam. Unsubscribe anytime.</p>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© 2026 Doomshop Records</span>
      <span class="footer-meta">Built for fast downloads and clean listening.</span>
    </div>
    <slot></slot>
  </footer>
</template>

<script>
export default {};
</script>

<style scoped>
.footer-shell {
  --footer-accent: var(--brand-a);
  --footer-accent-2: var(--brand-b);
  position: relative;
  overflow: hidden;
  border-radius: var(--radius-lg);
  padding: 26px;
  color: var(--text-0);
  background:
    radial-gradient(900px 420px at 0% 0%, rgba(255, 122, 24, 0.18), transparent 55%),
    radial-gradient(820px 420px at 100% 18%, rgba(0, 163, 255, 0.16), transparent 60%),
    linear-gradient(140deg, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.78));
  border: 1px solid var(--border);
  box-shadow: var(--shadow-md);
  backdrop-filter: blur(8px);
}

.footer-glow {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(120deg, transparent 35%, rgba(255, 255, 255, 0.45), transparent 65%);
  opacity: 0.35;
  pointer-events: none;
}

.footer-grid {
  position: relative;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 22px;
  z-index: 1;
}

.footer-brand {
  max-width: 340px;
}

.footer-kicker {
  letter-spacing: 0.25em;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--text-1);
  margin: 0 0 8px;
}

.footer-title {
  font-size: 1.6rem;
  font-weight: 700;
  margin: 0 0 10px;
}

.footer-copy {
  font-size: 0.95rem;
  color: var(--text-1);
  margin: 0 0 12px;
}

.footer-label {
  font-size: 0.82rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: var(--text-1);
  margin: 0 0 12px;
}

.footer-links {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.footer-link {
  color: var(--text-0);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s ease, transform 0.2s ease;
}

.footer-link:hover {
  color: var(--footer-accent);
  transform: translateX(4px);
}

.footer-card {
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 16px;
  box-shadow: var(--shadow-md);
}

.footer-input {
  display: flex;
  gap: 8px;
  margin: 10px 0 6px;
}

.footer-input input {
  flex: 1;
  border-radius: 10px;
  border: 1px solid rgba(17, 53, 102, 0.18);
  background: rgba(255, 255, 255, 0.95);
  color: var(--text-0);
  padding: 8px 10px;
  outline: none;
}

.footer-input input::placeholder {
  color: rgba(40, 66, 102, 0.6);
}

.footer-input button {
  border: none;
  border-radius: 10px;
  padding: 8px 12px;
  font-weight: 600;
  color: #ffffff;
  background: linear-gradient(120deg, var(--footer-accent), var(--footer-accent-2));
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.footer-input button:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(58, 213, 255, 0.25);
}

.footer-bottom {
  position: relative;
  margin-top: 22px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 6px;
  color: var(--text-1);
  font-size: 0.85rem;
  z-index: 1;
}

.footer-meta {
  color: rgba(40, 66, 102, 0.7);
  font-size: 0.8rem;
  margin: 0;
}

@media (min-width: 768px) {
  .footer-bottom {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

@media (max-width: 640px) {
  .footer-shell {
    padding: 20px;
  }

  .footer-grid {
    grid-template-columns: 1fr;
  }

  .footer-card {
    padding: 14px;
  }

  .footer-input {
    flex-direction: column;
    align-items: stretch;
  }

  .footer-input button {
    width: 100%;
  }

  .footer-title {
    font-size: 1.35rem;
  }
}
</style>
