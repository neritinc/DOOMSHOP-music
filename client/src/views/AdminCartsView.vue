<template>
  <div>
    <h2 class="h5">All Carts (Admin)</h2>
    <pre class="bg-light p-2 border">{{ carts }}</pre>
  </div>
</template>

<script>
import service from "@/api/cartService";

export default {
  data() {
    return { carts: [] };
  },
  async mounted() {
    const res = await service.allCarts();
    this.carts = res.data || [];
  },
};
</script>
