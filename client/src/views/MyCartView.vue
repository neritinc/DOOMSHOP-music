<template>
  <div class="cart-page">
    <h2 class="h5 m-0 page-title">My Cart</h2>

    <div class="layout-grid">
      <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="section-title m-0">Cart Items</div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge text-bg-dark">{{ selectedCartItems.length }} items</span>
          <button
            class="btn btn-primary btn-sm"
            type="button"
            :disabled="!cartId || selectedCartItems.length === 0 || checkingOut"
            @click="checkoutCart"
          >
            {{ checkingOut ? "Sending..." : "Vasarlas" }}
          </button>
        </div>
      </div>

      <div v-if="checkoutError" class="alert alert-danger py-2 mb-2">{{ checkoutError }}</div>
      <div v-if="checkoutResult" class="alert alert-success py-2 mb-2">
        Email elkuldve. Letoltesi linkek:
        <div class="download-links">
          <a
            v-for="(item, idx) in checkoutResult.download_items"
            :key="idx"
            class="download-link"
            :href="item.url"
            target="_blank"
            rel="noopener"
          >
            {{ item.type }}: {{ item.title }}
          </a>
        </div>
        <div class="small text-muted">Ervenyes: {{ checkoutResult.expires_at }}</div>
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
            <div v-if="item.album_id && albumTracks(item).length > 0" class="album-track-summary">
              <small
                v-for="track in albumTracks(item)"
                :key="track.id || track.track_id"
                class="album-track-line"
              >
                {{ track.track_title }}
              </small>
            </div>
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
      checkingOut: false,
      checkoutError: "",
      checkoutResult: null,
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
      const cartIds = new Set((this.carts || []).map((c) => Number(c.id)));
      if (!this.cartId || !cartIds.has(Number(this.cartId))) {
        this.cartId = this.carts.length > 0 ? this.carts[0].id : null;
      }
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
    albumTracks(item) {
      if (!item?.album_id) return [];
      const album = this.findAlbum(item.album_id);
      return Array.isArray(album?.tracks) ? album.tracks : [];
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
        const directCover = this.normalizeStorageAsset(album?.cover);
        if (directCover) return directCover;

        const firstTrackCover = this.normalizeStorageAsset(album?.tracks?.[0]?.track_cover);
        if (firstTrackCover) return firstTrackCover;

        return "https://placehold.co/96x96?text=Album";
      }

      return fallback;
    },
    normalizeStorageAsset(file) {
      if (!file) return "";
      const normalized = String(file).replace(/\\/g, "/").trim();
      if (!normalized) return "";
      if (normalized.startsWith("http://") || normalized.startsWith("https://")) return normalized;
      if (normalized.includes("/")) return storageUrl(normalized.replace(/^storage\//, ""));
      return storageUrl(normalized);
    },
    onItemImgError(event) {
      event.target.src = "https://placehold.co/96x96?text=Cover";
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
        this.deleteError = this.extractErrorMessage(err, "Could not delete cart item.");
      } finally {
        this.deletingItem = false;
      }
    },
    async checkoutCart() {
      if (!this.cartId || this.selectedCartItems.length === 0) return;
      this.checkingOut = true;
      this.checkoutError = "";
      this.checkoutResult = null;
      try {
        const res = await service.checkoutMyCart(this.cartId);
        this.checkoutResult = res?.data || null;
        await this.load();
      } catch (err) {
        this.checkoutError = this.extractErrorMessage(err, "Nem sikerult elkuldeni az emailt.");
      } finally {
        this.checkingOut = false;
      }
    },
    extractErrorMessage(err, fallback) {
      const errors = err?.response?.data?.errors;
      if (errors && typeof errors === "object") {
        const firstKey = Object.keys(errors)[0];
        const firstValue = firstKey ? errors[firstKey] : null;
        if (Array.isArray(firstValue) && firstValue.length > 0) {
          return firstValue[0];
        }
        if (typeof firstValue === "string" && firstValue.trim() !== "") {
          return firstValue;
        }
      }
      return err?.response?.data?.message || fallback;
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
  align-items: flex-start;
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

.album-track-summary {
  display: grid;
  gap: 0.15rem;
  margin-top: 0.35rem;
}

.album-track-line {
  color: #64748b;
  line-height: 1.35;
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

.download-links {
  display: grid;
  gap: 0.35rem;
  margin-top: 0.35rem;
}

.download-link {
  color: #1d4ed8;
  text-decoration: underline;
  font-weight: 600;
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
