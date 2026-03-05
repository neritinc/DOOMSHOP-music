<template>
  <footer class="footer-shell mt-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span>DOOMSHOP Records</span>
      <span class="small text-secondary">Streaming-ready catalog frontend</span>
    </div>
    <slot></slot>
  </footer>
</template>

<script>
export default {};
</script>

<style scoped>
.footer-shell {
  border: 1px solid #d8e5f7;
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
}
</style>
