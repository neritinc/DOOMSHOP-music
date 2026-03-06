<template>
  <header class="header-shell">
    <div class="header-brand">
      <div class="brand-copy">
        <h1>Build Your Next Sound</h1>
        <p>DOOMSHOP RECORDS</p>
      </div>
      <img src="@/assets/DOOMSHOP.svg" alt="DOOMSHOP logo" class="header-logo" />
      <div class="right-space" aria-hidden="true"></div>
    </div>
  </header>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap");

.header-shell {
  position: relative;
  overflow: hidden;
  margin-bottom: 10px;
  padding: 18px 24px;
  border-radius: 18px;
  background: linear-gradient(130deg, #f8fbff 0%, #f1f7ff 48%, #eaf3ff 100%);
  background-size: 220% 220%;
  animation: gradientShift 12s ease-in-out infinite;
  border: 1px solid #d4e4fb;
  box-shadow:
    0 16px 36px rgba(37, 99, 235, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.85);
}

.header-shell::before {
  content: "";
  position: absolute;
  right: -90px;
  top: -70px;
  width: 250px;
  height: 250px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.22) 0%, rgba(59, 130, 246, 0) 40%);
  pointer-events: none;
  animation: glowFloat 9s ease-in-out infinite;
}

@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

@keyframes glowFloat {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.8; }
  50% { transform: translate(-14px, 10px) scale(1.06); opacity: 1; }
}

.header-brand {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 10px;
  position: relative;
  z-index: 1;
}

.header-logo {
  width: 180px;
  height: 180px;
  object-fit: contain;
  justify-self: center;
  filter: drop-shadow(0 10px 18px rgba(37, 99, 235, 0.2));
}

.brand-copy h1 {
  margin: 0;
  font-size: clamp(1.4rem, 2.6vw, 2.2rem);
  font-weight: 800;
  font-family: "Manrope", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  color: #0f172a;
  line-height: 1;
}

.brand-copy p {
  margin: 8px 0 0;
  font-size: clamp(0.7rem, 1vw, 0.84rem);
  font-weight: 700;
  font-family: "Manrope", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #1d4ed8;
  opacity: 0.88;
}

@media (max-width: 576px) {
  .header-shell { padding: 14px 12px; }
  .header-logo { width: 100px; height: 100px; }
  .brand-copy h1 { font-size: 1rem; }
  .brand-copy p { font-size: 0.62rem; letter-spacing: 0.1em; }
}
</style>
