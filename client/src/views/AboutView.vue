<template>
  <section class="about-page">
    <header class="about-hero">
      <div class="about-hero__content">
        <p class="about-eyebrow">DOOMSHOP</p>
        <h2 class="about-title">Our Mission</h2>
        <p class="about-lead mb-0">
          Our Mission
        </p>
      </div>
    </header>

    <div class="about-card card card-body">
      <div class="about-badges">
        <span class="about-badge">Our Mission</span>
      </div>
      <p>
        Our mission is to connect music creators with those who appreciate and enjoy high-quality
        sound. We believe that music is more than just entertainment - it is a universal language
        that inspires, conveys emotions, and brings people together.
      </p>
      <p>
        Our platform aims to provide a simple and reliable way to discover and purchase music,
        while supporting artists in gaining the recognition they truly deserve. We are building an
        environment where creativity is valued, quality is a core principle, and every sound is
        driven by genuine passion.
      </p>
      <p class="mb-0">
        We strive to deliver an experience for every user - whether it is discovering new
        favorites or sharing their own musical world.
      </p>
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
