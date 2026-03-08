<template>
  <div>
    <h2 class="h5">My Cart</h2>

    <form class="d-flex gap-2 mb-3" @submit.prevent="createCart">
      <input v-model="date" class="form-control" type="date" required />
      <button class="btn btn-primary">Create cart</button>
    </form>

    <form class="card card-body mb-3" @submit.prevent="addItem">
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label mb-1">Cart</label>
          <select v-model.number="cartId" class="form-select" required>
            <option :value="null" disabled>Select cart</option>
            <option v-for="c in carts" :key="c.id" :value="c.id">Cart #{{ c.id }} ({{ c.date }})</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Item type</label>
          <select v-model="itemType" class="form-select">
            <option value="track">Track</option>
            <option value="album">Album</option>
          </select>
        </div>
        <div class="col-md-5" v-if="itemType === 'track'">
          <label class="form-label mb-1">Track</label>
          <select v-model.number="trackId" class="form-select" required>
            <option :value="null" disabled>Select track</option>
            <option v-for="t in tracks" :key="t.id || t.track_id" :value="t.id || t.track_id">{{ t.track_title }}</option>
          </select>
        </div>
        <div class="col-md-5" v-else>
          <label class="form-label mb-1">Album</label>
          <select v-model.number="albumId" class="form-select" required>
            <option :value="null" disabled>Select album</option>
            <option v-for="a in albums" :key="a.id" :value="a.id">{{ a.title }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Pieces</label>
          <input v-model.number="pcs" class="form-control" type="number" min="1" required />
        </div>
      </div>
      <button class="btn btn-outline-primary mt-2 align-self-start">Add item</button>
    </form>

    <pre class="bg-light p-2 border">{{ cartItems }}</pre>
  </div>
</template>

<script>
import service from "@/api/cartService";
import trackService from "@/api/trackService";
import albumService from "@/api/albumService";

export default {
  data() {
    return {
      carts: [],
      cartItems: [],
      tracks: [],
      albums: [],
      date: new Date().toISOString().slice(0, 10),
      cartId: null,
      itemType: "track",
      trackId: null,
      albumId: null,
      pcs: 1,
    };
  },
  methods: {
    async load() {
      const [cartsRes, itemsRes, tracksRes, albumsRes] = await Promise.all([
        service.myCarts(),
        service.myCartItems(),
        trackService.list(),
        albumService.list(),
      ]);
      this.carts = cartsRes.data || [];
      this.cartItems = itemsRes.data || [];
      this.tracks = tracksRes.data || [];
      this.albums = albumsRes.data || [];
      if (!this.cartId && this.carts.length > 0) this.cartId = this.carts[0].id;
    },
    async createCart() {
      await service.createMyCart({ date: this.date });
      await this.load();
    },
    async addItem() {
      const payload = {
        cart_id: this.cartId,
        pcs: this.pcs,
      };
      if (this.itemType === "track") {
        payload.track_id = this.trackId;
      } else {
        payload.album_id = this.albumId;
      }
      await service.addMyCartItem(payload);
      await this.load();
    },
  },
  async mounted() {
    await this.load();
  },
};
</script>
