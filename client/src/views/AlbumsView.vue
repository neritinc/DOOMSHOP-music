<template>
  <div class="albums-page">
    <div class="albums-hero">
      <div>
        <p class="albums-kicker">Albums</p>
        <h2 class="albums-title">Browse full releases</h2>
        <p class="albums-subtitle">Complete albums with tracklists and full-length downloads.</p>
      </div>
      <div class="albums-count">
        <span>{{ albums.length }}</span>
        <small>albums</small>
      </div>
    </div>

    <form v-if="isAdmin" class="card card-body mb-3 albums-form" @submit.prevent="createAlbum">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Album title</label>
          <input v-model="form.title" class="form-control" placeholder="Album title" required />
        </div>
        <div class="col-md-2">
          <label class="form-label">Price (EUR)</label>
          <input v-model.number="form.price_eur" class="form-control" type="number" min="0" step="0.01" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Release date</label>
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

    <div v-if="albums.length === 0" class="empty-state">
      <p class="mb-0">No albums yet. Check back later.</p>
    </div>

    <div class="albums-grid">
      <div v-for="album in albums" :key="album.id" class="album-card">
        <div class="album-card-top">
          <img :src="albumImage(album)" alt="Album cover" class="album-cover-thumb" @error="onAlbumImgError" />
          <div class="album-header-main">
            <h3 class="album-title">{{ album.title }}</h3>
            <div class="album-meta">
              <span>{{ album.release_date || "Release date TBA" }}</span>
              <span>€{{ Number(album.price_eur || 0).toFixed(2) }}</span>
              <span>{{ (album.tracks || []).length }} tracks</span>
            </div>
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
</template>

<script>
import { onMounted, onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import { useAlbumsViewStore } from "@/stores/views/albumsViewStore";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { RouterLink } from "vue-router";

export default {
  components: { RouterLink },
  setup() {
    const store = useAlbumsViewStore();
    const { isAdmin, isCustomer } = storeToRefs(useUserLoginLogoutStore());
    const storeRefs = storeToRefs(store);

    onMounted(async () => {
      await store.load();
      if (isCustomer.value) {
        await store.ensureCustomerCart();
      }
    });

    onBeforeUnmount(() => {
      store.cleanup();
    });

    const {
      load,
      ensureCustomerCart,
      coverUrl,
      albumImage,
      onAlbumImgError,
      onCoverChange,
      createAlbum,
      isAssigning,
      startAssign,
      cancelAssign,
      saveAssign,
      addAlbumToCart,
      cleanup,
    } = store;

    return {
      ...storeRefs,
      isAdmin,
      isCustomer,
      load,
      ensureCustomerCart,
      coverUrl,
      albumImage,
      onAlbumImgError,
      onCoverChange,
      createAlbum,
      isAssigning,
      startAssign,
      cancelAssign,
      saveAssign,
      addAlbumToCart,
      cleanup,
    };
  },
};
</script>

<style scoped>
.albums-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.albums-hero {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  background: linear-gradient(130deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.78));
  box-shadow: var(--shadow-md);
}

.albums-kicker {
  font-size: 0.75rem;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: var(--text-1);
  margin: 0 0 0.35rem;
}

.albums-title {
  font-size: clamp(1.6rem, 2vw, 2rem);
  margin: 0 0 0.4rem;
}

.albums-subtitle {
  margin: 0;
  color: var(--text-1);
}

.albums-count {
  min-width: 120px;
  padding: 0.65rem 1rem;
  border-radius: 16px;
  background: linear-gradient(140deg, rgba(255, 122, 24, 0.16), rgba(0, 163, 255, 0.16));
  border: 1px solid rgba(255, 255, 255, 0.6);
  display: grid;
  place-items: center;
  text-align: center;
  color: var(--text-0);
}

.albums-count span {
  font-size: 1.6rem;
  font-weight: 700;
}

.albums-count small {
  color: var(--text-1);
}

.albums-form {
  border-radius: var(--radius-lg);
}

.albums-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.album-card {
  background: var(--surface-strong);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 1rem 1.1rem;
  box-shadow: var(--shadow-md);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.album-card-top {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.9rem;
  align-items: start;
}

.album-header-main {
  min-width: 0;
}

.album-cover-thumb {
  width: 84px;
  height: 84px;
  object-fit: cover;
  border-radius: 14px;
  border: 1px solid var(--border);
  box-shadow: 0 10px 18px rgba(10, 20, 40, 0.12);
}

.album-cover-preview {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid var(--border);
}

.assign-list {
  max-height: 260px;
  overflow: auto;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.5rem;
  background: rgba(255, 255, 255, 0.85);
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
  border: 1px solid var(--border);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  color: inherit;
  transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.album-track-row:hover {
  transform: translateY(-1px);
  border-color: rgba(0, 163, 255, 0.4);
  box-shadow: 0 10px 22px rgba(20, 37, 63, 0.08);
}

.album-track-row strong {
  color: #0f172a;
}

.album-title {
  font-size: 1.05rem;
  margin: 0 0 0.35rem;
}

.album-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  color: var(--text-1);
  font-size: 0.85rem;
}

.empty-state {
  padding: 1rem 1.25rem;
  border-radius: var(--radius-md);
  border: 1px dashed var(--border);
  background: rgba(255, 255, 255, 0.75);
  color: var(--text-1);
}

@media (max-width: 768px) {
  .album-card-top {
    grid-template-columns: 1fr;
  }
}
</style>

