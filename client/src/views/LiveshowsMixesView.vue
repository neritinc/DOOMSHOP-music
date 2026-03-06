<template>
  <section class="liveshows-page">
    <div class="header-box">
      <h2 class="mb-1">LIVESHOWS and Mixes</h2>
      <p class="mb-0">Official YouTube videos</p>
    </div>

    <div class="video-grid">
      <article class="video-card">
        <div class="video-wrap">
          <iframe
            src="https://www.youtube.com/embed/3BrZlwqYCIc?si=ATvbleQDqUGmfbwu"
            title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
          ></iframe>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
export default {};
</script>

<style scoped>
.liveshows-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.header-box {
  border-radius: 14px;
  padding: 1rem 1.1rem;
  border: 1px solid #d8e5f7;
  background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}

.video-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.video-card {
  border-radius: 14px;
  border: 1px solid #d8e5f7;
  background: #fff;
  padding: 0.8rem;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}

.video-wrap {
  position: relative;
  width: 100%;
  padding-top: 56.25%;
}

.video-wrap iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border-radius: 10px;
}
</style>
