<template>
  <div class="cart-page">
    <h2 class="h5 m-0 page-title">My Cart</h2>

    <div class="layout-grid">
      <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="section-title m-0">Cart Items</div>
        <span class="badge text-bg-dark">{{ selectedCartItems.length }} items</span>
      </div>

      <div v-if="!cartId" class="empty-state">Select a cart to see its items.</div>
      <div v-else-if="selectedCartItems.length === 0" class="empty-state">This cart is currently empty.</div>
      <div v-else class="item-list">
        <div v-for="(item, idx) in selectedCartItems" :key="item.id || idx" class="item-row">
          <img
            class="item-cover"
            :src="itemCover(item)"
            :alt="itemLabel(item)"
            @error="onItemImgError"
          />
          <div class="item-main">
            <strong>{{ itemLabel(item) }}</strong>
            <small class="text-muted">Type: {{ item.track_id ? "Track" : "Album" }}</small>
          </div>
          <div class="item-actions">
            <div class="price-block">
              <span class="item-price">{{ formatPrice(itemUnitPrice(item)) }}</span>
              <small class="item-price-total">Total: {{ formatPrice(itemTotalPrice(item)) }}</small>
            </div>
            <span class="qty-chip">x{{ Number(item.pcs || 1) }}</span>
            <button class="btn btn-outline-danger btn-sm" type="button" @click="openDeleteModal(item)">Delete</button>
          </div>
        </div>
      </div>
      </div>
    </div>

    <div v-if="showDeleteModal" class="modal-backdrop-custom" @click.self="closeDeleteModal">
      <div class="delete-modal card shadow">
        <div class="card-body">
          <h3 class="h6 mb-2">Delete cart item?</h3>
          <p class="mb-3">
            Remove <strong>{{ itemLabel(itemToDelete) }}</strong> from your cart?
          </p>
          <div v-if="deleteError" class="alert alert-danger py-2 mb-2">{{ deleteError }}</div>
          <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="deletingItem" @click="closeDeleteModal">
              Cancel
            </button>
            <button class="btn btn-danger btn-sm" type="button" :disabled="deletingItem" @click="confirmDeleteItem">
              {{ deletingItem ? "Deleting..." : "Yes, delete" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import service from "@/api/cartService";
import trackService from "@/api/trackService";
import albumService from "@/api/albumService";
import { storageUrl } from "@/utils/storageUrl";

export default {
  data() {
    return {
      carts: [],
      cartItems: [],
      tracks: [],
      albums: [],
      cartId: null,
      showDeleteModal: false,
      itemToDelete: null,
      deletingItem: false,
      deleteError: "",
    };
  },
  computed: {
    selectedCartItems() {
      if (!this.cartId) return [];
      return (this.cartItems || []).filter((item) => Number(item.cart_id) === Number(this.cartId));
    },
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
    itemLabel(item) {
      if (item.track_id) {
        const found = this.findTrack(item.track_id);
        return found?.track_title || `Track #${item.track_id}`;
      }
      if (item.album_id) {
        const found = this.findAlbum(item.album_id);
        return found?.title || `Album #${item.album_id}`;
      }
      return "Unknown item";
    },
    findTrack(trackId) {
      return (this.tracks || []).find((t) => Number(t.id || t.track_id) === Number(trackId)) || null;
    },
    findAlbum(albumId) {
      return (this.albums || []).find((a) => Number(a.id) === Number(albumId)) || null;
    },
    itemUnitPrice(item) {
      if (item?.track_id) {
        const track = this.findTrack(item.track_id);
        return Number(track?.track_price_eur || 0);
      }
      if (item?.album_id) {
        const album = this.findAlbum(item.album_id);
        return Number(album?.price_eur || 0);
      }
      return 0;
    },
    itemTotalPrice(item) {
      return this.itemUnitPrice(item) * Number(item?.pcs || 1);
    },
    formatPrice(value) {
      return `EUR ${Number(value || 0).toFixed(2)}`;
    },
    itemCover(item) {
      const fallback = "https://placehold.co/96x96?text=Cover";
      if (item.track_id) {
        const track = this.findTrack(item.track_id);
        const file = track?.track_cover;
        if (!file) return fallback;
        if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
        if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
        return storageUrl(`track-covers/${file}`);
      }

      if (item.album_id) {
        const album = this.findAlbum(item.album_id);
        const file = album?.cover;
        if (!file) return fallback;
        if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
        if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
        return storageUrl(file);
      }

      return fallback;
    },
    onItemImgError(event) {
      event.target.src = "https://placehold.co/96x96?text=No+Cover";
    },
    openDeleteModal(item) {
      this.itemToDelete = item || null;
      this.deleteError = "";
      this.showDeleteModal = true;
    },
    closeDeleteModal() {
      if (this.deletingItem) return;
      this.showDeleteModal = false;
      this.itemToDelete = null;
      this.deleteError = "";
    },
    async confirmDeleteItem() {
      const id = Number(this.itemToDelete?.id || 0);
      if (!id) {
        this.deleteError = "Invalid cart item.";
        return;
      }
      this.deletingItem = true;
      this.deleteError = "";
      try {
        await service.deleteMyCartItem(id);
        await this.load();
        this.closeDeleteModal();
      } catch (err) {
        this.deleteError = err?.response?.data?.message || "Could not delete cart item.";
      } finally {
        this.deletingItem = false;
      }
    },
  },
  async mounted() {
    await this.load();
  },
};
</script>

<style scoped>
.cart-page {
  display: grid;
  gap: 0.9rem;
}

.page-title {
  color: #0f172a;
  font-weight: 700;
}

.layout-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  align-items: start;
}

.panel {
  border: 1px solid #d7e4f7;
  border-radius: 12px;
  background: #ffffff;
  padding: 0.9rem;
}

.section-title {
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.7rem;
}

.form-control,
.form-select {
  border-color: #c9d8ee;
  border-radius: 10px;
  min-height: 42px;
}

.form-control:focus,
.form-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 0.16rem rgba(59, 130, 246, 0.2);
}

.btn {
  border-radius: 10px;
}

.btn-primary {
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
  border-color: #1d4ed8;
  font-weight: 600;
}

.badge.text-bg-dark {
  background: #1e293b !important;
  border-radius: 999px;
}

.item-list {
  display: grid;
  gap: 0.55rem;
}

.item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  padding: 0.65rem 0.75rem;
}

.item-main {
  display: grid;
  min-width: 0;
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.item-cover {
  width: 52px;
  height: 52px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #dbe3ef;
  flex: 0 0 auto;
}

.price-block {
  display: grid;
  justify-items: end;
  min-width: 90px;
}

.item-price {
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
}

.item-price-total {
  color: #64748b;
  white-space: nowrap;
}

.qty-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 42px;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1e3a8a;
  font-weight: 700;
}

.empty-state {
  border: 1px dashed #d1d5db;
  border-radius: 10px;
  padding: 0.8rem;
  color: #64748b;
  background: #f8fafc;
}

.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.55);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.delete-modal {
  width: min(460px, 95vw);
  border: 1px solid #fecaca;
  border-radius: 14px;
}

@media (max-width: 992px) {
  .layout-grid {
    grid-template-columns: 1fr;
  }
}
</style>
