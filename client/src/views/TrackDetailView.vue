<template>
  <div>
    <RouterLink class="btn btn-outline-secondary btn-sm mb-3" to="/tracks">Back to tracks</RouterLink>

    <div v-if="loading" class="alert alert-info">Loading track...</div>
    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else-if="track" class="track-layout card shadow-sm">
      <div class="layout-left">
        <img class="detail-cover" :src="coverUrl(track.track_cover)" :alt="track.track_title" @error="onImgError" />

        <div class="meta-box">
          <div class="meta-row"><span class="meta-key">Artists</span><span class="meta-val">{{ artistNames(track) }}</span></div>
          <div class="meta-row"><span class="meta-key">Genre</span><span class="meta-val">{{ track.genre?.genre_name || "-" }}</span></div>
          <div class="meta-row"><span class="meta-key">BPM</span><span class="meta-val">{{ track.bpm_value || "-" }}</span></div>
          <div class="meta-row"><span class="meta-key">Release</span><span class="meta-val">{{ track.release_date || "-" }}</span></div>
          <div class="meta-row"><span class="meta-key">Length</span><span class="meta-val">{{ formatLength(track.track_length_sec) }}</span></div>
          <div class="meta-row"><span class="meta-key">Price</span><span class="meta-val">{{ formatPrice(track.track_price_eur) }}</span></div>
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
              :max="adminDuration"
              step="0.1"
              :value="adminCurrentTime"
              @input="onAdminSeekInput"
              :disabled="!adminSourceUrl(track)"
            />
            <div class="small text-muted d-flex justify-content-between">
              <span>{{ formatTime(adminCurrentTime) }}</span>
              <span>{{ formatTime(adminDuration) }}</span>
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
          <div class="quality-row">
            <strong>Quality</strong>
            <button class="btn btn-dark btn-sm quality-btn" type="button">Choose an option</button>
          </div>
          <button class="btn btn-secondary btn-sm mt-2" type="button" disabled>Add to cart</button>
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
              <input v-model="edit.artist_names[idx]" class="form-control" :placeholder="`Artist ${idx + 1}`" />
              <button v-if="edit.artist_names.length > 1" class="btn btn-outline-danger btn-sm" type="button" @click="removeArtistField(idx)">-</button>
            </div>
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
          <audio ref="previewAudioRef" :src="audioPreviewUrl" preload="auto" class="d-none" @ended="stopPreviewSegment" @timeupdate="onPreviewTimeUpdate"></audio>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from "pinia";
import { RouterLink } from "vue-router";
import service from "@/api/trackService";
import genreService from "@/api/genreService";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { storageUrl } from "@/utils/storageUrl";
import NeonWavePlayer from "@/components/AudioPlayer/NeonWavePlayer.vue";

const emptyEdit = () => ({
  track_title: "",
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
      edit: emptyEdit(),
      saving: false,
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
        genre_name: t.genre?.genre_name || "",
        bpm_value: t.bpm_value || null,
        track_price_eur: Number(t.track_price_eur ?? 1.99),
        release_date: t.release_date || "",
        track_length_sec: t.track_length_sec || null,
        preview_start_at: Number(t.preview_start_at ?? 0),
        preview_end_at: Number(t.preview_end_at ?? 30),
        artist_names: (t.artists || []).map((a) => a.artist_name) || [""],
      };
      if (this.edit.artist_names.length === 0) this.edit.artist_names = [""];
      this.audioPreviewUrl = this.previewUrl(t);
      this.previewSeekTime = 0;
      this.coverPreviewUrl = "";
      this.audioFile = null;
      this.coverFile = null;
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
      this.adminDuration = 0;
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
      const max = Number(this.adminDuration || 0);
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
        const [trackRes, genresRes] = await Promise.all([service.show(id), genreService.list()]);
        this.track = trackRes.data || null;
        this.genres = genresRes.data || [];
        this.initEditFromTrack();
      } catch (err) {
        this.error = err?.response?.data?.message || "Track loading failed.";
      } finally {
        this.loading = false;
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
  grid-template-columns: minmax(280px, 420px) 1fr;
  gap: 3rem;
  padding: 1.1rem 1.1rem 1.35rem;
  border: 1px solid #d9e5f7;
  border-radius: 16px;
  background:
    radial-gradient(900px 320px at 0% -20%, rgba(59, 130, 246, 0.08), transparent 55%),
    linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
}

.layout-left { min-width: 0; }

.detail-cover {
  width: 100%;
  max-width: 420px;
  border-radius: 12px;
  border: 1px solid #c7d7ef;
  box-shadow: 0 14px 24px rgba(22, 42, 78, 0.1);
  aspect-ratio: 1 / 1;
  height: auto;
  object-fit: cover;
}

.meta-box {
  margin-top: 0.85rem;
  max-width: 420px;
  border: 1px solid #d6e5fc;
  border-radius: 12px;
  background: linear-gradient(180deg, #f8fbff 0%, #f3f8ff 100%);
  padding: 0.75rem 0.85rem;
}

.meta-row {
  display: grid;
  grid-template-columns: 76px 1fr;
  gap: 0.5rem;
  font-size: 0.94rem;
  color: #0f172a;
  padding: 0.25rem 0;
}

.meta-key { font-weight: 700; color: #334155; }

.layout-right {
  min-width: 0;
  padding: 0.7rem 0.6rem;
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
.quality-row { display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; }
.quality-btn { min-width: 180px; }

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

@media (max-width: 992px) {
  .track-layout {
    grid-template-columns: 1fr;
    padding: 0.9rem;
    gap: 1.5rem;
  }
  .detail-cover, .meta-box, .player-wrap {
    max-width: 100%;
  }
  .detail-title {
    max-width: 100%;
  }
}
</style>
