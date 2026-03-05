<template>
  <div>
    <h2 class="h5">My Cart</h2>

    <form class="d-flex gap-2 mb-3" @submit.prevent="createCart">
      <input v-model="date" class="form-control" type="date" required />
      <button class="btn btn-primary">Create cart</button>
    </form>

    <form class="card card-body mb-3" @submit.prevent="addItem">
      <input v-model.number="cartId" class="form-control mb-2" placeholder="Cart ID" required />
      <input v-model.number="trackId" class="form-control mb-2" placeholder="Track ID" required />
      <input v-model.number="pcs" class="form-control mb-2" placeholder="Pieces" required />
      <button class="btn btn-outline-primary">Add item</button>
    </form>

    <pre class="bg-light p-2 border">{{ carts }}</pre>
  </div>
</template>

<script>
import service from "@/api/cartService";

export default {
  data() {
    return {
      carts: [],
      date: new Date().toISOString().slice(0, 10),
      cartId: null,
      trackId: null,
      pcs: 1,
    };
  },
  methods: {
    async load() {
      const res = await service.myCarts();
      this.carts = res.data || [];
    },
    async createCart() {
      await service.createMyCart({ date: this.date });
      await this.load();
    },
    async addItem() {
      await service.addMyCartItem({ cart_id: this.cartId, track_id: this.trackId, pcs: this.pcs });
      await this.load();
    },
  },
  async mounted() {
    await this.load();
  },
};
</script>
