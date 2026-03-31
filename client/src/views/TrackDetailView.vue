<template>
  <div>
    <RouterLink class="btn btn-outline-secondary btn-sm mb-3" to="/tracks">Back to tracks</RouterLink>

    <div v-if="loading" class="alert alert-info">Loading track...</div>
    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else-if="track" class="track-layout">
      <div class="layout-left">
        <img class="detail-cover" :src="coverUrl(track.track_cover)" :alt="track.track_title" @error="onImgError" />

        <div class="detail-facts">
          <div class="fact-row"><span class="fact-key">Artists</span><span class="fact-val">{{ artistNames(track) }}</span></div>
          <div class="fact-row"><span class="fact-key">Genres</span><span class="fact-val">{{ genreNames(track) }}</span></div>
          <div v-if="track.album?.id && track.album?.title" class="fact-row">
            <span class="fact-key">Album</span>
            <RouterLink class="fact-val album-link" :to="`/albums?album=${track.album.id}`">
              {{ track.album.title }}
            </RouterLink>
          </div>
          <div class="fact-row"><span class="fact-key">BPM</span><span class="fact-val">{{ track.bpm_value || "-" }}</span></div>
          <div class="fact-row"><span class="fact-key">Release</span><span class="fact-val">{{ track.release_date || "-" }}</span></div>
          <div class="fact-row"><span class="fact-key">Length</span><span class="fact-val">{{ formatLength(track.track_length_sec) }}</span></div>
          <div class="fact-row"><span class="fact-key">Price</span><span class="fact-val">{{ formatPrice(track.track_price_eur) }}</span></div>
        </div>
      </div>

      <div class="layout-right">
        <div class="right-top">
          <p class="crumb-line">Home / Tracks / {{ firstArtist(track) }} / {{ track.track_title }}</p>

          <div v-if="isCustomer" class="artist-chips">
            <span v-for="a in track.artists || []" :key="`chip-${a.artist_id}`" class="chip">{{ a.artist_name }}</span>
          </div>

          <h1 class="detail-title">{{ track.track_title }}</h1>
        </div>

        <div class="player-wrap right-player">
          <div v-if="isAdmin" class="admin-player">
            <div class="d-flex gap-2 align-items-center mb-2">
              <button class="btn btn-outline-dark btn-sm" type="button" @click="toggleAdminTrackPlay(adminTrackAudioRef)" :disabled="!adminSourceUrl(track)">
                {{ adminIsPlaying ? "Pause" : "Play" }}
              </button>
              <small class="text-muted">Full track (admin)</small>
            </div>
            <input
              class="form-range"
              type="range"
              min="0"
              :max="adminSeekMax"
              step="0.1"
              :value="adminCurrentTime"
              @input="onAdminSeekInput($event, adminTrackAudioRef)"
              :disabled="!adminSourceUrl(track)"
            />
            <div class="small text-muted d-flex justify-content-between">
              <span>{{ formatTime(adminCurrentTime) }}</span>
              <span>{{ formatTime(adminSeekMax) }}</span>
            </div>
            <small v-if="adminError" class="d-block mt-2 text-danger">{{ adminError }}</small>
            <audio
              ref="adminTrackAudioRef"
              :src="adminSourceUrl(track)"
              preload="auto"
              class="admin-audio-hidden"
              @loadedmetadata="onAdminLoadedMetadata"
              @timeupdate="onAdminTimeUpdate"
              @ended="onAdminEnded"
              @error="onAdminError"
            ></audio>
          </div>

          <NeonWavePlayer v-else-if="isCustomer" :track="track" />

          <small v-else class="text-muted">Login needed for playback preview.</small>
        </div>

        <div class="after-player right-actions">
          <RouterLink
            v-if="track.album?.id && track.album?.title"
            class="album-cta mt-2"
            :to="`/albums?album=${track.album.id}`"
          >
            BUY FULL ALBUM
          </RouterLink>
          <button
            v-if="isCustomer"
            class="cart-cta mt-2"
            type="button"
            :disabled="addingToCart || !trackId(track)"
            @click="addTrackToCart"
          >
            <span class="cart-cta__icon" aria-hidden="true">+</span>
            <span>{{ addingToCart ? "Adding..." : "Add to cart" }}</span>
          </button>
          <small v-if="cartMessage" class="d-block mt-2 text-success">{{ cartMessage }}</small>
          <small v-if="cartError" class="d-block mt-2 text-danger">{{ cartError }}</small>
          <small v-if="!isCustomer" class="text-muted d-block mt-2">Login as customer to add items to cart.</small>
        </div>

        <form v-if="isAdmin" class="card card-body mt-3" @submit.prevent="saveTrack">
          <h3 class="h6 mb-2">Edit track (Admin)</h3>
          <div v-if="editError" class="alert alert-danger py-2 mb-2">{{ editError }}</div>
          <div v-if="editSuccess" class="alert alert-success py-2 mb-2">{{ editSuccess }}</div>

          <div class="row g-2">
            <div class="col-md-6"><input v-model="edit.track_title" class="form-control" placeholder="Track title" required /></div>
            <div class="col-md-3">
              <label class="form-label mb-1">Genres</label>
              <div v-for="(genre, idx) in edit.genre_names" :key="`edit-genre-${idx}`" class="d-flex gap-2 mb-1">
                <input
                  v-model="edit.genre_names[idx]"
                  class="form-control"
                  :placeholder="`Genre ${idx + 1}`"
                  list="edit-genre-options"
                />
                <button v-if="edit.genre_names.length > 1" class="btn btn-outline-danger btn-sm" type="button" @click="removeGenreField(idx)">-</button>
              </div>
              <datalist id="edit-genre-options">
                <option v-for="g in genres" :key="g.genre_id" :value="g.genre_name"></option>
              </datalist>
              <button class="btn btn-outline-secondary btn-sm" type="button" @click="addGenreField">+ Add genre</button>
            </div>
            <div class="col-md-3"><input v-model.number="edit.bpm_value" class="form-control" type="number" min="1" max="999" placeholder="BPM" /></div>
            <div class="col-md-3"><input v-model.number="edit.track_price_eur" class="form-control" type="number" min="0" step="0.01" placeholder="Price (€)" /></div>
            <div class="col-md-3">
              <select v-model="edit.album_id" class="form-select">
                <option value="">No album</option>
                <option v-for="a in albums" :key="`edit-album-${a.id}`" :value="String(a.id)">{{ a.title }}</option>
              </select>
            </div>
            <div class="col-md-3"><input v-model="edit.album_title" class="form-control" placeholder="Or new album title" /></div>
          </div>

          <div class="row g-2 mt-1">
            <div class="col-md-3"><input v-model="edit.release_date" class="form-control" type="date" /></div>
            <div class="col-md-3"><input v-model.number="edit.track_length_sec" class="form-control" type="number" min="1" placeholder="Length sec" /></div>
            <div class="col-md-3">
              <input v-model.number="edit.preview_start_at" class="form-control" type="number" min="0" @input="onPreviewStartInput" />
              <small class="text-muted">Start: {{ formatTime(edit.preview_start_at) }}</small>
            </div>
            <div class="col-md-3">
              <input v-model.number="edit.preview_end_at" class="form-control" type="number" min="1" @input="onPreviewEndInput" />
              <small class="text-muted">End: {{ formatTime(edit.preview_end_at) }}</small>
            </div>
          </div>

          <div class="small text-muted mt-2">Preview duration: {{ previewDuration }} sec (max 30 sec)</div>
          <div class="d-flex gap-2 mt-2 flex-wrap">
            <button class="btn btn-outline-primary btn-sm" type="button" :disabled="!audioPreviewUrl" @click="playPreviewSegment">Preview segment</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="!audioPreviewUrl" @click="regeneratePreviewSegment">Regenerate preview</button>
            <button class="btn btn-outline-danger btn-sm" type="button" :disabled="!isPreviewPlaying" @click="stopPreviewSegment">Stop</button>
          </div>
          <div v-if="audioPreviewUrl" class="mt-2">
            <input class="form-range" type="range" min="0" :max="previewDuration" step="0.1" :value="previewSeekTime" @input="onPreviewSeekInput" />
            <div class="small text-muted d-flex justify-content-between">
              <span>{{ formatTime(previewSeekTime) }}</span>
              <span>{{ formatTime(previewDuration) }}</span>
            </div>
          </div>

          <div class="mt-2">
            <label class="form-label mb-1">Artists</label>
            <div v-for="(artist, idx) in edit.artist_names" :key="`edit-artist-${idx}`" class="d-flex gap-2 mb-1">
              <input
                v-model="edit.artist_names[idx]"
                class="form-control"
                :placeholder="`Artist ${idx + 1}`"
                list="edit-artist-options"
              />
              <button v-if="edit.artist_names.length > 1" class="btn btn-outline-danger btn-sm" type="button" @click="removeArtistField(idx)">-</button>
            </div>
            <datalist id="edit-artist-options">
              <option v-for="a in artists" :key="a.artist_id" :value="a.artist_name"></option>
            </datalist>
            <button class="btn btn-outline-secondary btn-sm" type="button" @click="addArtistField">+ Add artist</button>
          </div>

          <div class="row g-2 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Replace audio (optional)</label>
              <input class="form-control" type="file" accept=".mp3,.wav,.ogg,.m4a,.flac,audio/*" @change="onAudioChange" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Replace cover (optional)</label>
              <div class="cover-dropzone" :class="{ 'is-over': isDraggingCover }" @dragenter.prevent="isDraggingCover = true" @dragover.prevent="isDraggingCover = true" @dragleave.prevent="isDraggingCover = false" @drop.prevent="onCoverDrop" @click="openCoverPicker">
                <input ref="coverInputRef" class="d-none" type="file" accept="image/*" @change="onCoverChange" />
                <template v-if="coverPreviewUrl"><img :src="coverPreviewUrl" alt="Cover preview" class="cover-preview" /></template>
                <template v-else><p class="m-0 fw-semibold">Drop image or click</p></template>
              </div>
            </div>
          </div>

          <button class="btn btn-primary btn-sm mt-3 align-self-start" :disabled="saving">{{ saving ? "Saving..." : "Save changes" }}</button>
          <button class="btn btn-danger btn-sm mt-2 align-self-start" type="button" :disabled="deleting" @click="deleteTrack">
            {{ deleting ? "Deleting..." : "Delete track" }}
          </button>
          <audio ref="previewAudioRef" :src="audioPreviewUrl" preload="auto" class="d-none" @ended="stopPreviewSegment" @timeupdate="onPreviewTimeUpdate"></audio>
        </form>
      </div>
    </div>

    <div v-if="showDeleteModal" class="modal-backdrop-custom" @click.self="cancelDelete">
      <div class="delete-modal card shadow">
        <div class="card-body">
          <h3 class="h5 mb-2">Delete track?</h3>
          <p class="mb-2">
            You are about to delete: <strong>{{ track?.track_title || "this track" }}</strong>
          </p>
          <p class="mb-2">This action will remove:</p>
          <ul class="mb-3">
            <li>Track record from database</li>
            <li>Source audio file from storage</li>
            <li>Cover image from storage</li>
            <li>Generated preview file and CSV exports</li>
          </ul>
          <p class="text-danger mb-3">This cannot be undone.</p>

          <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="deleting" @click="cancelDelete">
              Cancel
            </button>
            <button class="btn btn-danger btn-sm" type="button" :disabled="deleting" @click="confirmDeleteTrack">
              {{ deleting ? "Deleting..." : "Yes, delete" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { useTrackDetailViewStore } from "@/stores/views/trackDetailViewStore";
import NeonWavePlayer from "@/components/AudioPlayer/NeonWavePlayer.vue";

export default {
  components: { RouterLink, NeonWavePlayer },
  setup() {
    const store = useTrackDetailViewStore();
    const route = useRoute();
    const router = useRouter();
    const { isAdmin, isCustomer } = storeToRefs(useUserLoginLogoutStore());
    const storeRefs = storeToRefs(store);
    const adminTrackAudioRef = ref(null);
    const previewAudioRef = ref(null);
    const coverInputRef = ref(null);

    store.bindRefs({ adminTrackAudioRef, previewAudioRef, coverInputRef });

    onMounted(async () => {
      await store.load(route.params.id);
    });

    onBeforeUnmount(() => {
      store.cleanup();
    });

    const confirmDeleteTrack = async () => {
      await store.confirmDeleteTrack(router);
    };

    const {
      coverUrl,
      adminSourceUrl,
      artistNames,
      genreNames,
      firstArtist,
      trackId,
      onImgError,
      formatLength,
      formatTime,
      formatPrice,
      toggleAdminTrackPlay,
      onAdminLoadedMetadata,
      onAdminTimeUpdate,
      onAdminEnded,
      onAdminError,
      onAdminSeekInput,
      addGenreField,
      removeGenreField,
      addArtistField,
      removeArtistField,
      onPreviewStartInput,
      onPreviewEndInput,
      playPreviewSegment,
      regeneratePreviewSegment,
      stopPreviewSegment,
      onPreviewSeekInput,
      onPreviewTimeUpdate,
      onAudioChange,
      openCoverPicker,
      onCoverChange,
      onCoverDrop,
      addTrackToCart,
      saveTrack,
      deleteTrack,
      cancelDelete,
    } = store;

    return {
      ...storeRefs,
      isAdmin,
      isCustomer,
      adminTrackAudioRef,
      previewAudioRef,
      coverInputRef,
      coverUrl,
      adminSourceUrl,
      artistNames,
      genreNames,
      firstArtist,
      trackId,
      onImgError,
      formatLength,
      formatTime,
      formatPrice,
      toggleAdminTrackPlay,
      onAdminLoadedMetadata,
      onAdminTimeUpdate,
      onAdminEnded,
      onAdminError,
      onAdminSeekInput,
      addGenreField,
      removeGenreField,
      addArtistField,
      removeArtistField,
      onPreviewStartInput,
      onPreviewEndInput,
      playPreviewSegment,
      regeneratePreviewSegment,
      stopPreviewSegment,
      onPreviewSeekInput,
      onPreviewTimeUpdate,
      onAudioChange,
      openCoverPicker,
      onCoverChange,
      onCoverDrop,
      addTrackToCart,
      saveTrack,
      deleteTrack,
      cancelDelete,
      confirmDeleteTrack,
    };
  },
};
</script>

<style scoped>
.track-layout {
  display: grid;
  grid-template-columns: minmax(340px, 520px) minmax(420px, 1fr);
  gap: 3.2rem;
  align-items: start;
  padding: 1.25rem 1rem 1rem;
  border: 1px solid #dbe8fb;
  border-radius: 18px;
  background: linear-gradient(180deg, #f8fbff 0%, #f1f6fd 100%);
  box-shadow: 0 10px 22px rgba(30, 58, 110, 0.08);
  max-width: 1180px;
  margin: 0 auto;
}

.layout-left {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.detail-cover {
  width: 100%;
  max-width: 480px;
  border-radius: 12px;
  border: 1px solid #c7d7ef;
  box-shadow: 0 14px 24px rgba(22, 42, 78, 0.1);
  aspect-ratio: 1 / 1;
  height: auto;
  object-fit: cover;
}

.detail-facts {
  max-width: 480px;
  display: grid;
  gap: 0.22rem;
  padding: 0.2rem 0.1rem 0.05rem;
}

.fact-row {
  display: grid;
  grid-template-columns: 86px 1fr;
  gap: 0.5rem;
  align-items: baseline;
  font-size: 0.95rem;
  color: #0f172a;
  padding: 0.1rem 0;
}

.fact-key { font-weight: 700; color: #334155; }
.fact-val { color: #0f172a; }
.album-link {
  text-decoration: none;
  font-weight: 600;
}

.album-link:hover {
  text-decoration: underline;
}

.layout-right {
  min-width: 0;
  padding: 0.45rem 0.6rem 0.6rem;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  gap: 1.1rem;
}

.right-top {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.crumb-line {
  margin: 0;
  color: #4b5563;
  font-size: 0.92rem;
}

.artist-chips { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 0.4rem; }
.chip {
  font-size: 0.76rem;
  font-weight: 700;
  color: #1e3a8a;
  background: #dbeafe;
  border: 1px solid #bfdbfe;
  border-radius: 999px;
  padding: 0.18rem 0.55rem;
}

.detail-title {
  margin: 0;
  font-size: clamp(1.5rem, 3vw, 2.6rem);
  line-height: 1.03;
  letter-spacing: 0.01em;
  color: #0f172a;
  font-weight: 800;
  max-width: 18ch;
}

.player-wrap {
  max-width: 560px;
  border: 0;
  border-radius: 0;
  background: transparent;
  padding: 0;
  box-shadow: none;
}

.right-player { margin-top: 0.1rem; }

.after-player { margin-top: 0; }

.right-actions { max-width: 560px; }
.album-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0.9rem;
  border-radius: 999px;
  border: 1px solid #1e293b;
  text-decoration: none;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  color: #0f172a;
  background: #ffffff;
  transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease;
}

.album-cta:hover {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
}

.cart-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  border: 1px solid #2d67e3;
  border-radius: 12px;
  padding: 0.66rem 1.15rem;
  min-height: 44px;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0;
  color: #ffffff;
  background: linear-gradient(135deg, #1f57d6 0%, #2f6fe9 100%);
  box-shadow: 0 6px 14px rgba(37, 99, 235, 0.22);
  transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
}

.cart-cta:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
  filter: brightness(1.02);
}

.cart-cta:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.22);
}

.cart-cta:focus-visible {
  outline: 3px solid rgba(96, 165, 250, 0.45);
  outline-offset: 3px;
}

.cart-cta:disabled {
  opacity: 0.72;
  cursor: not-allowed;
  box-shadow: none;
}

.cart-cta__icon {
  width: 20px;
  height: 20px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  font-weight: 700;
  line-height: 1;
  background: rgba(255, 255, 255, 0.18);
}

@media (max-width: 576px) {
  .cart-cta {
    width: 100%;
    justify-content: center;
  }
}

.card.card-body {
  border: 1px solid #d7e4f7;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 8px 22px rgba(16, 42, 85, 0.08);
}

.form-control {
  border-color: #c9d8ee;
  border-radius: 10px;
  min-height: 42px;
}

.form-control:focus {
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

.cover-dropzone {
  min-height: 120px;
  border: 2px dashed #93c5fd;
  border-radius: 12px;
  background: linear-gradient(180deg, #eff6ff 0%, #edf3fc 100%);
  color: #1e3a8a;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 0.75rem;
  cursor: pointer;
}

.cover-dropzone.is-over { border-color: #2563eb; background: #dbeafe; }
.cover-preview {
  max-width: 100%;
  max-height: 160px;
  object-fit: cover;
  border-radius: 10px;
}

.admin-audio-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
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
  width: min(560px, 96vw);
  border: 1px solid #fecaca;
  border-radius: 14px;
}

@media (max-width: 992px) {
  .track-layout {
    grid-template-columns: 1fr;
    padding: 0.9rem 0;
    gap: 1.8rem;
  }
  .detail-cover, .detail-facts, .player-wrap {
    max-width: 100%;
  }
  .detail-title {
    max-width: 100%;
  }
  .layout-right {
    gap: 0.9rem;
  }
}
</style>

