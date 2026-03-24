<template>
  <div>
    <h2 class="h5 mb-3">Albums</h2>

    <form v-if="isAdmin" class="card card-body mb-3" @submit.prevent="createAlbum">
      <div class="row g-2">
        <div class="col-md-4">
          <input v-model="form.title" class="form-control" placeholder="Album title" required />
        </div>
        <div class="col-md-2">
          <input v-model.number="form.price_eur" class="form-control" type="number" min="0" step="0.01" placeholder="Price (EUR)" />
        </div>
        <div class="col-md-3">
          <input v-model="form.release_date" class="form-control" type="date" />
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary w-100">Create album</button>
        </div>
        <div class="col-md-6">
          <input class="form-control" type="file" accept="image/*" @change="onCoverChange" />
          <small class="text-muted">Optional album cover (jpg/png/webp, max 5 MB)</small>
        </div>
        <div class="col-md-3 d-flex align-items-end" v-if="coverPreviewUrl">
          <img :src="coverPreviewUrl" alt="Album cover preview" class="album-cover-preview" />
        </div>
      </div>
    </form>

    <div class="row g-3">
      <div v-for="album in albums" :key="album.id" class="col-12">
        <div class="card card-body">
          <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="album-header-main">
              <h3 class="h6 m-0">{{ album.title }}</h3>
              <small class="text-muted">
                {{ album.release_date || "-" }} - EUR {{ Number(album.price_eur || 0).toFixed(2) }} - {{ (album.tracks || []).length }} tracks
              </small>
              <div v-if="isCustomer" class="mt-2">
                <button
                  type="button"
                  class="btn btn-dark btn-sm"
                  :disabled="addingAlbumId === album.id"
                  @click="addAlbumToCart(album)"
                >
                  {{ addingAlbumId === album.id ? "Adding..." : "Add album to cart" }}
                </button>
              </div>
              <small v-if="cartMessageByAlbumId[album.id]" class="d-block mt-2 text-success">
                {{ cartMessageByAlbumId[album.id] }}
              </small>
              <small v-if="cartErrorByAlbumId[album.id]" class="d-block mt-2 text-danger">
                {{ cartErrorByAlbumId[album.id] }}
              </small>
            </div>
            <img :src="albumImage(album)" alt="Album cover" class="album-cover-thumb" @error="onAlbumImgError" />
            <button
              v-if="isAdmin"
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="startAssign(album)"
            >
              Assign tracks
            </button>
          </div>

          <div v-if="isAssigning(album)" class="mt-3">
            <label class="form-label mb-1">Select tracks for this album</label>
            <div class="assign-list">
              <label v-for="track in tracks" :key="track.id || track.track_id" class="assign-item">
                <input v-model="assignTrackIds" type="checkbox" :value="String(track.id || track.track_id)" />
                <span>{{ track.track_title }}</span>
              </label>
            </div>
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-primary btn-sm" @click="saveAssign(album)">Save</button>
              <button type="button" class="btn btn-outline-danger btn-sm" @click="cancelAssign">Cancel</button>
            </div>
          </div>

          <div v-if="(album.tracks || []).length > 0" class="album-track-list mt-3">
            <RouterLink
              v-for="track in album.tracks || []"
              :key="track.id || track.track_id"
              class="album-track-row"
              :to="`/tracks/${track.id || track.track_id}`"
            >
              <strong>{{ track.track_title }}</strong>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import albumService from "@/api/albumService";
import trackService from "@/api/trackService";
import cartService from "@/api/cartService";
import { mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { storageUrl } from "@/utils/storageUrl";
import { RouterLink } from "vue-router";

export default {
  components: { RouterLink },
  data() {
    return {
      albums: [],
      tracks: [],
      activeAssignAlbumId: null,
      assignTrackIds: [],
      customerCartId: null,
      addingAlbumId: null,
      cartMessageByAlbumId: {},
      cartErrorByAlbumId: {},
      coverFile: null,
      coverPreviewUrl: "",
      form: {
        title: "",
        price_eur: 0,
        release_date: "",
      },
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin", "isCustomer"]),
  },
  methods: {
    async load() {
      const [albumsRes, tracksRes] = await Promise.all([
        albumService.list(),
        trackService.list(),
      ]);
      this.albums = albumsRes.data || [];
      this.tracks = tracksRes.data || [];
    },
    coverUrl(path) {
      if (!path) return "";
      const normalized = String(path).replace(/\\/g, "/").trim();
      if (!normalized) return "";
      if (/^https?:\/\//i.test(normalized)) return normalized;
      return storageUrl(normalized.replace(/^storage\//, ""));
    },
    albumImage(album) {
      const directCover = this.coverUrl(album?.cover);
      if (directCover) return directCover;

      const firstTrackCover = this.coverUrl(album?.tracks?.[0]?.track_cover);
      if (firstTrackCover) return firstTrackCover;

      return "https://placehold.co/72x72?text=Album";
    },
    onAlbumImgError(event) {
      event.target.src = "https://placehold.co/72x72?text=Album";
    },
    onCoverChange(event) {
      const file = event?.target?.files?.[0] ?? null;
      this.coverFile = file;
      if (this.coverPreviewUrl) {
        URL.revokeObjectURL(this.coverPreviewUrl);
        this.coverPreviewUrl = "";
      }
      if (file) {
        this.coverPreviewUrl = URL.createObjectURL(file);
      }
    },
    async createAlbum() {
      if (!this.isAdmin) return;
      const payload = new FormData();
      payload.append("title", this.form.title);
      payload.append("price_eur", String(Number(this.form.price_eur || 0)));
      if (this.form.release_date) payload.append("release_date", this.form.release_date);
      if (this.coverFile) payload.append("cover_file", this.coverFile);

      await albumService.create(payload);
      this.form = { title: "", price_eur: 0, release_date: "" };
      this.coverFile = null;
      if (this.coverPreviewUrl) {
        URL.revokeObjectURL(this.coverPreviewUrl);
        this.coverPreviewUrl = "";
      }
      await this.load();
    },
    isAssigning(album) {
      return Number(this.activeAssignAlbumId) === Number(album.id);
    },
    startAssign(album) {
      this.activeAssignAlbumId = album.id;
      this.assignTrackIds = (album.tracks || []).map((t) => String(t.id || t.track_id));
    },
    cancelAssign() {
      this.activeAssignAlbumId = null;
      this.assignTrackIds = [];
    },
    async saveAssign(album) {
      const ids = (this.assignTrackIds || []).map((x) => Number(x)).filter((x) => Number.isFinite(x) && x > 0);
      await albumService.syncTracks(album.id, ids);
      this.cancelAssign();
      await this.load();
    },
    async ensureCustomerCart() {
      const cartsRes = await cartService.myCarts();
      const carts = cartsRes?.data || [];
      if (carts.length > 0) {
        this.customerCartId = carts[0].id;
        return;
      }
      const today = new Date().toISOString().slice(0, 10);
      const created = await cartService.createMyCart({ date: today });
      this.customerCartId = created?.data?.id || null;
    },
    extractErrorMessage(err, fallback) {
      const errors = err?.response?.data?.errors;
      if (errors && typeof errors === "object") {
        const firstKey = Object.keys(errors)[0];
        const firstValue = firstKey ? errors[firstKey] : null;
        if (Array.isArray(firstValue) && firstValue.length > 0) return firstValue[0];
        if (typeof firstValue === "string" && firstValue.trim() !== "") return firstValue;
      }
      return err?.response?.data?.message || fallback;
    },
    async addAlbumToCart(album) {
      if (!this.isCustomer || !album?.id) return;
      this.addingAlbumId = album.id;
      this.cartMessageByAlbumId = { ...this.cartMessageByAlbumId, [album.id]: "" };
      this.cartErrorByAlbumId = { ...this.cartErrorByAlbumId, [album.id]: "" };
      try {
        if (!this.customerCartId) {
          await this.ensureCustomerCart();
        }
        if (!this.customerCartId) {
          throw new Error("Missing cart id.");
        }
        await cartService.addMyCartItem({
          cart_id: this.customerCartId,
          album_id: album.id,
          pcs: 1,
        });
        this.cartMessageByAlbumId = {
          ...this.cartMessageByAlbumId,
          [album.id]: "Album added to cart.",
        };
      } catch (err) {
        this.cartErrorByAlbumId = {
          ...this.cartErrorByAlbumId,
          [album.id]: this.extractErrorMessage(err, "Could not add album to cart."),
        };
      } finally {
        this.addingAlbumId = null;
      }
    },
  },
  async mounted() {
    await this.load();
    if (this.isCustomer) {
      await this.ensureCustomerCart();
    }
  },
  beforeUnmount() {
    if (this.coverPreviewUrl) {
      URL.revokeObjectURL(this.coverPreviewUrl);
    }
  },
};
</script>

<style scoped>
.album-header-main {
  min-width: 0;
}

.album-cover-thumb {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid #d7e2f0;
}

.album-cover-preview {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid #d7e2f0;
}

.assign-list {
  max-height: 260px;
  overflow: auto;
  border: 1px solid #d7e2f0;
  border-radius: 8px;
  padding: 0.5rem;
  background: #fff;
}

.assign-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.1rem;
}

.album-track-list {
  display: grid;
  gap: 0.55rem;
}

.album-track-row {
  display: block;
  padding: 0.55rem 0.65rem;
  border: 1px solid #d7e2f0;
  border-radius: 10px;
  background: #fbfdff;
  text-decoration: none;
  color: inherit;
  transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.album-track-row:hover {
  transform: translateY(-1px);
  border-color: #a9bddb;
  box-shadow: 0 10px 22px rgba(20, 37, 63, 0.08);
}

.album-track-row strong {
  color: #0f172a;
}
</style>
