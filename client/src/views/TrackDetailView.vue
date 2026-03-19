<template>
  <div>
    <RouterLink class="btn btn-outline-secondary btn-sm mb-3" to="/tracks">Back to tracks</RouterLink>

    <div v-if="loading" class="alert alert-info">Loading track...</div>
    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else-if="track" class="track-layout card shadow-sm">
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
        <p class="crumb-line">Home / Tracks / {{ firstArtist(track) }} / {{ track.track_title }}</p>

        <div class="artist-stack">
          <p v-for="a in track.artists || []" :key="a.artist_id" class="artist-top">{{ a.artist_name }}</p>
        </div>
        <div v-if="isCustomer" class="artist-chips">
          <span v-for="a in track.artists || []" :key="`chip-${a.artist_id}`" class="chip">{{ a.artist_name }}</span>
        </div>

        <h1 class="detail-title">{{ track.track_title }}</h1>

        <div class="player-wrap">
          <div v-if="isAdmin" class="admin-player">
            <div class="d-flex gap-2 align-items-center mb-2">
              <button class="btn btn-outline-dark btn-sm" type="button" @click="toggleAdminTrackPlay" :disabled="!adminSourceUrl(track)">
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
              @input="onAdminSeekInput"
              :disabled="!adminSourceUrl(track)"
            />
            <div class="small text-muted d-flex justify-content-between">
              <span>{{ formatTime(adminCurrentTime) }}</span>
              <span>{{ formatTime(adminSeekMax) }}</span>
            </div>
            <audio
              ref="adminTrackAudioRef"
              :src="adminSourceUrl(track)"
              preload="metadata"
              class="d-none"
              @loadedmetadata="onAdminLoadedMetadata"
              @timeupdate="onAdminTimeUpdate"
              @ended="onAdminEnded"
            ></audio>
          </div>

          <NeonWavePlayer v-else-if="isCustomer" :track="track" />

          <small v-else class="text-muted">Login needed for playback preview.</small>
        </div>

        <div class="after-player">
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
              <input v-model="edit.genre_name" class="form-control" placeholder="Genre" list="genre-options" required />
              <datalist id="genre-options">
                <option v-for="g in genres" :key="g.genre_id" :value="g.genre_name"></option>
              </datalist>
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
import { mapState } from "pinia";
import { RouterLink } from "vue-router";
import service from "@/api/trackService";
import genreService from "@/api/genreService";
import artistService from "@/api/artistService";
import albumService from "@/api/albumService";
import cartService from "@/api/cartService";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { storageUrl } from "@/utils/storageUrl";
import NeonWavePlayer from "@/components/AudioPlayer/NeonWavePlayer.vue";

const emptyEdit = () => ({
  track_title: "",
  album_id: "",
  album_title: "",
  genre_name: "",
  bpm_value: null,
  track_price_eur: 1.99,
  release_date: "",
  track_length_sec: null,
  preview_start_at: 0,
  preview_end_at: 30,
  artist_names: [""],
});

export default {
  components: { RouterLink, NeonWavePlayer },
  data() {
    return {
      loading: true,
      error: "",
      track: null,
      genres: [],
      artists: [],
      albums: [],
      edit: emptyEdit(),
      saving: false,
      deleting: false,
      showDeleteModal: false,
      editError: "",
      editSuccess: "",
      audioFile: null,
      coverFile: null,
      coverPreviewUrl: "",
      isDraggingCover: false,
      audioPreviewUrl: "",
      isPreviewPlaying: false,
      previewStopTimer: null,
      previewSeekTime: 0,
      adminIsPlaying: false,
      adminDuration: 0,
      adminCurrentTime: 0,
      addingToCart: false,
      customerCartId: null,
      cartMessage: "",
      cartError: "",
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin", "isCustomer"]),
    previewDuration() {
      return Math.max(0, Number(this.edit.preview_end_at || 0) - Number(this.edit.preview_start_at || 0));
    },
    selectedGenre() {
      const input = String(this.edit.genre_name || "").trim().toLowerCase();
      if (!input) return null;
      return this.genres.find((g) => String(g.genre_name || "").trim().toLowerCase() === input) || null;
    },
    adminSeekMax() {
      const loaded = Number(this.adminDuration || 0);
      if (Number.isFinite(loaded) && loaded > 0) return loaded;

      const fallback = Number(this.track?.track_length_sec || 0);
      return Number.isFinite(fallback) && fallback > 0 ? fallback : 0;
    },
  },
  methods: {
    coverUrl(file) {
      if (!file) return "https://placehold.co/700x700?text=Track";
      if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
      if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
      return storageUrl(`track-covers/${file}`);
    },
    audioUrl(path) {
      return path ? storageUrl(path) : "";
    },
    adminSourceUrl(track) {
      const id = this.trackId(track);
      if (!id) return "";
      const apiBase = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
      const pathPart = track?.track_path || "";
      const cacheKey = encodeURIComponent(pathPart);
      return `${apiBase}/tracks/${id}/source?v=${cacheKey}`;
    },
    previewUrl(track) {
      const id = this.trackId(track);
      if (!id) return "";
      const apiBase = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
      const pathPart = track?.preview_path || "";
      const start = track?.preview_start_at ?? "";
      const end = track?.preview_end_at ?? "";
      const v = encodeURIComponent(`${pathPart}|${start}|${end}`);
      return `${apiBase}/tracks/${id}/preview?v=${v}`;
    },
    artistNames(track) {
      return (track.artists || []).map((a) => a.artist_name).join(", ") || "-";
    },
    genreNames(track) {
      const list = (track.genres || [])
        .map((g) => g?.genre_name)
        .filter((name) => String(name || "").trim() !== "");
      if (list.length === 0 && track?.genre?.genre_name) {
        list.push(track.genre.genre_name);
      }
      return list.length > 0 ? list.join(", ") : "-";
    },
    firstArtist(track) {
      return track?.artists?.[0]?.artist_name || "Artist";
    },
    trackId(track) {
      return track?.id ?? track?.track_id;
    },
    onImgError(e) {
      e.target.src = "https://placehold.co/700x700?text=No+Cover";
    },
    formatLength(value) {
      const total = Number(value);
      if (!Number.isFinite(total) || total <= 0) return "-";
      const seconds = Math.floor(total);
      const min = Math.floor(seconds / 60);
      const sec = seconds % 60;
      return `${min}:${String(sec).padStart(2, "0")}`;
    },
    formatTime(value) {
      const total = Math.max(0, Math.floor(Number(value) || 0));
      const min = Math.floor(total / 60);
      const sec = total % 60;
      return `${min}:${String(sec).padStart(2, "0")}`;
    },
    formatPrice(value) {
      const amount = Number(value);
      const safeAmount = Number.isFinite(amount) ? amount : 1.99;
      return `€${safeAmount.toFixed(2)}`;
    },
    initEditFromTrack() {
      const t = this.track || {};
      this.edit = {
        track_title: t.track_title || "",
        album_id: t.album?.id ? String(t.album.id) : "",
        album_title: "",
        genre_name: t.genre?.genre_name || "",
        bpm_value: t.bpm_value || null,
        track_price_eur: Number(t.track_price_eur ?? 1.99),
        release_date: t.release_date || "",
        track_length_sec: t.track_length_sec || null,
        preview_start_at: Number(t.preview_start_at ?? 0),
        preview_end_at: Number(t.preview_end_at ?? 30),
        artist_names: (t.artists || [])
          .map((a) => String(a?.artist_name || "").trim())
          .filter(Boolean),
      };
      if (this.edit.artist_names.length === 0) this.edit.artist_names = [""];
      this.audioPreviewUrl = this.previewUrl(t);
      this.previewSeekTime = 0;
      this.coverPreviewUrl = "";
      this.audioFile = null;
      this.coverFile = null;
      const fallback = Number(t.track_length_sec || 0);
      this.adminDuration = Number.isFinite(fallback) && fallback > 0 ? fallback : 0;
      this.resetAdminPlayer();
    },
    resetAdminPlayer() {
      const audio = this.$refs.adminTrackAudioRef;
      if (audio) {
        audio.pause();
        audio.currentTime = 0;
      }
      this.adminIsPlaying = false;
      this.adminCurrentTime = 0;
    },
    toggleAdminTrackPlay() {
      const audio = this.$refs.adminTrackAudioRef;
      if (!audio) return;
      if (this.adminIsPlaying) {
        audio.pause();
        this.adminIsPlaying = false;
        return;
      }
      audio.play();
      this.adminIsPlaying = true;
    },
    onAdminLoadedMetadata() {
      const audio = this.$refs.adminTrackAudioRef;
      const loaded = Number(audio?.duration || 0);
      const fallback = Number(this.track?.track_length_sec || 0);
      this.adminDuration = Number.isFinite(loaded) && loaded > 0
        ? loaded
        : (Number.isFinite(fallback) && fallback > 0 ? fallback : 0);
    },
    onAdminTimeUpdate() {
      const audio = this.$refs.adminTrackAudioRef;
      this.adminCurrentTime = Number(audio?.currentTime || 0);
    },
    onAdminEnded() {
      this.adminIsPlaying = false;
      this.adminCurrentTime = 0;
    },
    onAdminSeekInput(event) {
      const next = Number(event?.target?.value || 0);
      const audio = this.$refs.adminTrackAudioRef;
      if (!audio) return;
      const max = Number(this.adminSeekMax || 0);
      const clamped = Math.max(0, Math.min(max, next));
      audio.currentTime = clamped;
      this.adminCurrentTime = clamped;
    },
    addArtistField() { this.edit.artist_names.push(""); },
    removeArtistField(index) {
      this.edit.artist_names.splice(index, 1);
      if (this.edit.artist_names.length === 0) this.edit.artist_names = [""];
    },
    previewWindowSize() {
      const length = Number(this.edit.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0) return Math.min(30, Math.floor(length));
      return 30;
    },
    onPreviewStartInput() {
      let start = Number(this.edit.preview_start_at || 0);
      start = Number.isFinite(start) ? Math.max(0, Math.floor(start)) : 0;
      const windowSize = this.previewWindowSize();
      let end = start + windowSize;
      const length = Number(this.edit.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0 && end > length) {
        end = length;
        start = Math.max(0, end - windowSize);
      }
      this.edit.preview_start_at = start;
      this.edit.preview_end_at = end;
    },
    onPreviewEndInput() {
      let end = Number(this.edit.preview_end_at || 0);
      end = Number.isFinite(end) ? Math.max(1, Math.floor(end)) : 30;
      const length = Number(this.edit.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0) end = Math.min(end, length);
      const windowSize = this.previewWindowSize();
      let start = Math.max(0, end - windowSize);
      this.edit.preview_start_at = start;
      this.edit.preview_end_at = end;
    },
    clearPreviewTimer() {
      if (this.previewStopTimer) {
        clearTimeout(this.previewStopTimer);
        this.previewStopTimer = null;
      }
    },
    stopPreviewSegment() {
      this.clearPreviewTimer();
      const audio = this.$refs.previewAudioRef;
      if (audio) audio.pause();
      this.isPreviewPlaying = false;
      this.previewSeekTime = 0;
    },
    playPreviewSegment() {
      if (!this.audioPreviewUrl) return;
      const durationMs = Math.max(0, Number(this.previewDuration || 0) * 1000);
      if (durationMs <= 0) return;
      const audio = this.$refs.previewAudioRef;
      if (!audio) return;
      this.stopPreviewSegment();
      audio.currentTime = 0;
      this.previewSeekTime = 0;
      audio.play();
      this.isPreviewPlaying = true;
      this.previewStopTimer = setTimeout(() => this.stopPreviewSegment(), durationMs + 80);
    },
    async regeneratePreviewSegment() {
      if (!this.isAdmin || !this.track) return;
      this.editError = "";
      this.editSuccess = "";
      try {
        const res = await service.regeneratePreview(this.trackId(this.track), {
          preview_start_at: Number(this.edit.preview_start_at || 0),
          preview_end_at: Number(this.edit.preview_end_at || 0),
        });
        const refreshed = await service.show(this.trackId(this.track));
        this.track = refreshed.data || res.data || this.track;
        this.initEditFromTrack();
        this.editSuccess = "Preview regenerated successfully.";
      } catch (err) {
        this.editError = this.toValidationError(err);
      }
    },
    onPreviewSeekInput(event) {
      const next = Number(event?.target?.value || 0);
      const min = 0;
      const max = Number(this.previewDuration || 0);
      const clamped = Math.max(min, Math.min(max, next));
      this.previewSeekTime = clamped;
      const audio = this.$refs.previewAudioRef;
      if (audio) audio.currentTime = clamped;
    },
    onPreviewTimeUpdate() {
      const audio = this.$refs.previewAudioRef;
      if (!audio) return;
      this.previewSeekTime = Number(audio.currentTime || 0);
      if (this.previewSeekTime >= Number(this.previewDuration || 0)) this.stopPreviewSegment();
    },
    async onAudioChange(event) {
      const file = event.target.files?.[0] || null;
      this.audioFile = file;
      if (!file) return;
      this.audioPreviewUrl = URL.createObjectURL(file);
      this.previewSeekTime = Number(this.edit.preview_start_at || 0);
      const audio = document.createElement("audio");
      audio.preload = "metadata";
      audio.src = URL.createObjectURL(file);
      audio.onloadedmetadata = async () => {
        const duration = Math.floor(audio.duration || 0);
        if (duration > 0) {
          this.edit.track_length_sec = duration;
          this.onPreviewStartInput();
        }
        URL.revokeObjectURL(audio.src);
      };
      await this.analyzeAudioFile(file);
    },
    async analyzeAudioFile(file) {
      try {
        const res = await service.analyzeUpload(file);
        const data = res.data || {};
        if (data.bpm_value && !this.edit.bpm_value) this.edit.bpm_value = data.bpm_value;
      } catch (_err) {}
    },
    openCoverPicker() { this.$refs.coverInputRef?.click(); },
    setCoverFile(file) {
      this.coverFile = file;
      if (this.coverPreviewUrl) URL.revokeObjectURL(this.coverPreviewUrl);
      this.coverPreviewUrl = file ? URL.createObjectURL(file) : "";
    },
    onCoverChange(event) {
      const file = event.target.files?.[0] || null;
      this.setCoverFile(file);
    },
    onCoverDrop(event) {
      this.isDraggingCover = false;
      const file = event.dataTransfer?.files?.[0] || null;
      if (!file) return;
      if (!file.type.startsWith("image/")) {
        this.editError = "Only image files can be used as track cover.";
        return;
      }
      this.setCoverFile(file);
    },
    toValidationError(err) {
      const errors = err?.response?.data?.errors;
      if (!errors || typeof errors !== "object") return err?.response?.data?.message || "Save failed.";
      const firstKey = Object.keys(errors)[0];
      if (!firstKey) return "Validation error.";
      const firstValue = errors[firstKey];
      if (Array.isArray(firstValue) && firstValue.length > 0) return firstValue[0];
      return "Validation error.";
    },
    async load() {
      this.loading = true;
      this.error = "";
      try {
        const id = this.$route.params.id;
        const [trackRes, genresRes, artistsRes, albumsRes] = await Promise.all([
          service.show(id),
          genreService.list(),
          artistService.list(),
          albumService.list(),
        ]);
        this.track = trackRes.data || null;
        console.log("track",this.track);
        
        this.genres = genresRes.data || [];
        this.artists = artistsRes.data || [];
        this.albums = albumsRes.data || [];
        this.initEditFromTrack();
        if (this.isCustomer) {
          await this.ensureCustomerCart();
        }
      } catch (err) {
        this.error = err?.response?.data?.message || "Track loading failed.";
      } finally {
        this.loading = false;
      }
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
    async addTrackToCart() {
      if (!this.isCustomer || !this.track) return;
      this.addingToCart = true;
      this.cartMessage = "";
      this.cartError = "";
      try {
        if (!this.customerCartId) {
          await this.ensureCustomerCart();
        }
        const id = this.trackId(this.track);
        if (!id || !this.customerCartId) {
          throw new Error("Missing cart or track id.");
        }
        await cartService.addMyCartItem({
          cart_id: this.customerCartId,
          track_id: id,
          pcs: 1,
        });
        this.cartMessage = "Track added to cart.";
      } catch (err) {
        this.cartError = err?.response?.data?.message || "Could not add track to cart.";
      } finally {
        this.addingToCart = false;
      }
    },
    async saveTrack() {
      if (!this.isAdmin || !this.track) return;
      this.editError = "";
      this.editSuccess = "";
      this.saving = true;
      try {
        const payload = new FormData();
        payload.append("track_title", this.edit.track_title);
        if (this.edit.album_id) {
          payload.append("album_id", String(this.edit.album_id));
        } else if (String(this.edit.album_title || "").trim() !== "") {
          payload.append("album_title", String(this.edit.album_title).trim());
        }
        if (this.selectedGenre?.genre_id) payload.append("genre_id", String(this.selectedGenre.genre_id));
        else payload.append("genre_name", this.edit.genre_name);

        payload.append("preview_start_at", String(this.edit.preview_start_at));
        payload.append("preview_end_at", String(this.edit.preview_end_at));
        if (this.edit.bpm_value) payload.append("bpm_value", String(this.edit.bpm_value));
        payload.append("track_price_eur", String(Number(this.edit.track_price_eur ?? 1.99).toFixed(2)));
        if (this.edit.release_date) payload.append("release_date", this.edit.release_date);
        if (this.edit.track_length_sec) payload.append("track_length_sec", String(this.edit.track_length_sec));

        const artists = (this.edit.artist_names || []).map((x) => String(x || "").trim()).filter(Boolean);
        artists.forEach((name, index) => payload.append(`artist_names[${index}]`, name));

        if (this.audioFile) payload.append("track_audio", this.audioFile);
        if (this.coverFile) payload.append("track_cover_file", this.coverFile);

        const res = await service.update(this.trackId(this.track), payload);
        const refreshed = await service.show(this.trackId(this.track));
        this.track = refreshed.data || res.data || this.track;
        this.initEditFromTrack();
        this.editSuccess = "Track updated successfully.";
      } catch (err) {
        this.editError = this.toValidationError(err);
      } finally {
        this.saving = false;
      }
    },
    async deleteTrack() {
      if (!this.isAdmin || !this.track || this.deleting) return;
      this.showDeleteModal = true;
    },
    cancelDelete() {
      if (this.deleting) return;
      this.showDeleteModal = false;
    },
    async confirmDeleteTrack() {
      if (!this.isAdmin || !this.track) return;
      const id = this.trackId(this.track);
      if (!id) return;

      this.deleting = true;
      this.editError = "";
      this.editSuccess = "";

      try {
        await service.destroy(id);
        this.showDeleteModal = false;
        this.$router.push("/tracks");
      } catch (err) {
        this.editError = err?.response?.data?.message || "Delete failed.";
      } finally {
        this.deleting = false;
      }
    },
  },
  async mounted() {
    await this.load();
  },
  beforeUnmount() {
    this.resetAdminPlayer();
    this.stopPreviewSegment();
    if (this.coverPreviewUrl) URL.revokeObjectURL(this.coverPreviewUrl);
    if (this.audioFile && this.audioPreviewUrl && this.audioPreviewUrl.startsWith("blob:")) URL.revokeObjectURL(this.audioPreviewUrl);
  },
};
</script>

<style scoped>
.track-layout {
  display: grid;
  grid-template-columns: minmax(280px, 480px) minmax(320px, 1fr);
  gap: 3.4rem;
  align-items: start;
  padding: 1.35rem 1.35rem 1.5rem;
  border: 1px solid #d9e5f7;
  border-radius: 16px;
  background:
    radial-gradient(900px 320px at 0% -20%, rgba(59, 130, 246, 0.08), transparent 55%),
    linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
}

.layout-left {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
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
  padding: 0.45rem 0.1rem;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.crumb-line {
  margin: 0 0 1.05rem;
  color: #4b5563;
  font-size: 0.92rem;
}

.artist-stack { margin-bottom: 0.75rem; }
.artist-top { margin: 0; color: #2563eb; font-weight: 600; }
.artist-chips { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 0.9rem; }
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
  margin: 0 0 1.05rem;
  font-size: clamp(1.5rem, 3vw, 2.6rem);
  line-height: 1.03;
  letter-spacing: 0.01em;
  color: #0f172a;
  font-weight: 800;
  max-width: 20ch;
}

.player-wrap {
  max-width: 620px;
  border: 1px solid #dbe8fb;
  border-radius: 14px;
  background: #fbfdff;
  padding: 0.8rem 0.85rem;
}

.after-player { margin-top: 0.9rem; }
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
  gap: 0.5rem;
  border: none;
  border-radius: 999px;
  padding: 0.55rem 0.95rem;
  font-size: 0.86rem;
  font-weight: 700;
  color: #ffffff;
  background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
  box-shadow: 0 8px 18px rgba(29, 78, 216, 0.28);
  transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
}

.cart-cta:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(29, 78, 216, 0.35);
  filter: saturate(1.08);
}

.cart-cta:active:not(:disabled) {
  transform: translateY(0);
}

.cart-cta:focus-visible {
  outline: 3px solid rgba(59, 130, 246, 0.35);
  outline-offset: 2px;
}

.cart-cta:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.cart-cta__icon {
  width: 20px;
  height: 20px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  line-height: 1;
  background: rgba(255, 255, 255, 0.2);
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
    padding: 0.9rem;
    gap: 1.5rem;
  }
  .detail-cover, .detail-facts, .player-wrap {
    max-width: 100%;
  }
  .detail-title {
    max-width: 100%;
  }
}
</style>
