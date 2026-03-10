<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 m-0">Tracks</h2>
      <span class="badge text-bg-dark">{{ filteredTracks.length }} items</span>
    </div>

    <div class="card card-body mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Filter by genre</label>
          <select v-model="selectedGenreFilter" class="form-select">
            <option value="">All genres</option>
            <option v-for="g in genres" :key="`filter-genre-${g.genre_id}`" :value="g.genre_name">{{ g.genre_name }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Filter by artist</label>
          <select v-model="selectedArtistFilter" class="form-select">
            <option value="">All artists</option>
            <option v-for="a in artists" :key="`filter-artist-${a.artist_id}`" :value="a.artist_name">{{ a.artist_name }}</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">Filter by BPM</label>
          <select v-model="selectedBpmFilter" class="form-select">
            <option value="">All BPM</option>
            <option v-for="bpm in bpmFilterOptions" :key="`filter-bpm-${bpm}`" :value="String(bpm)">{{ bpm }}</option>
          </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button class="btn btn-outline-secondary" type="button" @click="shuffleTracks">
            Shuffle order
          </button>
          <button class="btn btn-outline-danger" type="button" @click="resetTrackFilters">
            Reset filters
          </button>
        </div>
      </div>
    </div>

    <form v-if="isAdmin" class="card card-body mb-3" @submit.prevent="createTrack">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="h6 m-0">Create new track (Admin)</h3>
        <span class="section-chip">Structured editor</span>
      </div>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-outline-secondary btn-sm" type="button" @click="scrollToSection('basic')">Basic</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" @click="scrollToSection('album')">Album</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" @click="scrollToSection('meta')">Artists and genres</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" @click="scrollToSection('timing')">Timing</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" @click="scrollToSection('media')">Media files</button>
      </div>

      <div v-if="formError" class="alert alert-danger py-2 mb-3">{{ formError }}</div>

      <div ref="basicSection" class="editor-section mb-3">
        <div class="section-title">Basic info</div>
        <div class="row g-2">
          <div class="col-lg-6">
            <label class="form-label mb-1">Track title</label>
            <input v-model="form.track_title" class="form-control" placeholder="Track title" required />
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1">BPM</label>
            <input v-model.number="form.bpm_value" class="form-control" type="number" min="1" max="999" placeholder="BPM" />
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1">Price (EUR)</label>
            <input v-model.number="form.track_price_eur" class="form-control" type="number" min="0" step="0.01" placeholder="1.99" />
          </div>
        </div>
      </div>

      <div ref="albumSection" class="editor-section mb-3">
        <div class="section-title">Album</div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label mb-1">Choose existing album</label>
            <select v-model="form.album_id" class="form-select">
              <option value="">No album</option>
              <option v-for="album in albums" :key="`album-${album.id}`" :value="String(album.id)">{{ album.title }}</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label mb-1">Or create new album</label>
            <input v-model="form.album_title" class="form-control" placeholder="New album title" />
          </div>
        </div>
      </div>

      <div ref="metaSection" class="editor-section mb-3">
        <div class="section-title">Genres and artists</div>
        <div class="row g-3">
          <div class="col-lg-6">
            <label class="form-label mb-1">Genres</label>
            <div v-for="(genre, idx) in form.genre_names" :key="`genre-${idx}`" class="d-flex gap-2 mb-1">
              <input
                v-model="form.genre_names[idx]"
                class="form-control"
                placeholder="Genre name"
                list="genre-options"
              />
              <button
                v-if="form.genre_names.length > 1"
                class="btn btn-outline-danger btn-sm"
                type="button"
                @click="removeGenreField(idx)"
              >
                -
              </button>
            </div>
            <datalist id="genre-options">
              <option v-for="genre in genres" :key="genre.genre_id" :value="genre.genre_name"></option>
            </datalist>
            <small class="text-muted d-block">Select existing genres or type new ones.</small>
            <button class="btn btn-outline-secondary btn-sm mt-1" type="button" @click="addGenreField">+ Add genre</button>
          </div>

          <div class="col-lg-6">
            <label class="form-label mb-1">Artists</label>
            <div v-for="(artist, idx) in form.artist_names" :key="`artist-${idx}`" class="d-flex gap-2 mb-1">
              <input
                v-model="form.artist_names[idx]"
                class="form-control"
                :placeholder="`Artist ${idx + 1}`"
                list="artist-options"
              />
              <button
                v-if="form.artist_names.length > 1"
                class="btn btn-outline-danger btn-sm"
                type="button"
                @click="removeArtistField(idx)"
              >
                -
              </button>
            </div>
            <datalist id="artist-options">
              <option v-for="artist in artists" :key="artist.artist_id" :value="artist.artist_name"></option>
            </datalist>
            <small class="text-muted d-block">Select existing artists or type new ones.</small>
            <button class="btn btn-outline-secondary btn-sm mt-1" type="button" @click="addArtistField">+ Add artist</button>
          </div>
        </div>
      </div>

      <div ref="timingSection" class="editor-section mb-3">
        <div class="section-title">Timing and preview</div>
        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label mb-1">Release date</label>
            <input v-model="form.release_date" class="form-control" type="date" />
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1">Track length (sec)</label>
            <input
              v-model.number="form.track_length_sec"
              class="form-control"
              type="number"
              min="1"
              placeholder="Track length (sec)"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1">Preview start (sec)</label>
            <input
              v-model.number="form.preview_start_at"
              class="form-control"
              type="number"
              min="0"
              :max="maxPreviewStart"
              placeholder="Preview start (sec)"
              @input="onPreviewStartInput"
            />
            <small class="text-muted">Format: {{ formatTime(form.preview_start_at) }}</small>
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1">Preview end (sec)</label>
            <input
              v-model.number="form.preview_end_at"
              class="form-control"
              type="number"
              min="1"
              :max="maxPreviewEnd"
              placeholder="Preview end (sec)"
              @input="onPreviewEndInput"
            />
            <small class="text-muted">Format: {{ formatTime(form.preview_end_at) }}</small>
          </div>
        </div>

        <div class="small text-muted mt-2">
          Preview duration: {{ previewDuration }} sec (max 30 sec)
        </div>
        <div class="d-flex gap-2 align-items-center mt-2 flex-wrap">
          <button
            class="btn btn-outline-primary btn-sm"
            type="button"
            :disabled="!audioPreviewUrl || previewDuration <= 0"
            @click="playPreviewSegment"
          >
            Preview segment
          </button>
          <button
            class="btn btn-outline-secondary btn-sm"
            type="button"
            :disabled="!audioPreviewUrl || previewDuration <= 0"
            @click="regeneratePreviewSegment"
          >
            Regenerate preview
          </button>
          <button
            class="btn btn-outline-danger btn-sm"
            type="button"
            :disabled="!isPreviewPlaying"
            @click="stopPreviewSegment"
          >
            Stop
          </button>
          <small class="text-muted">{{ previewHint }}</small>
        </div>
        <div v-if="audioPreviewUrl" class="preview-seek-wrap mt-2">
          <input
            class="form-range"
            type="range"
            :min="form.preview_start_at"
            :max="form.preview_end_at"
            step="0.1"
            :value="previewSeekTime"
            @input="onPreviewSeekInput"
          />
          <div class="small text-muted d-flex justify-content-between">
            <span>{{ formatTime(previewSeekTime) }}</span>
            <span>{{ formatTime(form.preview_end_at) }}</span>
          </div>
        </div>
        <div v-if="audioPreviewUrl" class="preview-seek-wrap mt-3">
          <div class="d-flex align-items-center gap-2 mb-1">
            <button class="btn btn-outline-dark btn-sm" type="button" @click="toggleFullTrackPlay">
              {{ isFullTrackPlaying ? "Pause full track" : "Play full track" }}
            </button>
            <small class="text-muted">Full upload preview</small>
          </div>
          <input
            class="form-range"
            type="range"
            min="0"
            :max="fullTrackDuration"
            step="0.1"
            :value="fullTrackCurrentTime"
            @input="onFullTrackSeekInput"
          />
          <div class="small text-muted d-flex justify-content-between">
            <span>{{ formatTime(fullTrackCurrentTime) }}</span>
            <span>{{ formatTime(fullTrackDuration) }}</span>
          </div>
        </div>
      </div>

      <div ref="mediaSection" class="editor-section mb-3">
        <div class="section-title">Media files</div>
        <div class="row g-2 mt-1">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Audio file</label>
            <div
              class="audio-dropzone mb-2"
              :class="{ 'is-over': isDraggingAudio }"
              @dragenter.prevent="isDraggingAudio = true"
              @dragover.prevent="isDraggingAudio = true"
              @dragleave.prevent="isDraggingAudio = false"
              @drop.prevent="onAudioDrop"
            >
              Drop audio here
            </div>
            <input
              ref="audioInputRef"
              class="form-control"
              type="file"
              accept=".mp3,.wav,.ogg,.m4a,.flac,audio/*"
              @change="onAudioChange"
              required
            />
            <small class="text-muted">Supported: mp3, wav, ogg, m4a, flac (max 50 MB)</small>
            <small v-if="analyzing" class="d-block text-primary mt-1">Analyzing audio metadata...</small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Cover image (drag and drop)</label>
            <div
              class="cover-dropzone"
              :class="{ 'is-over': isDraggingCover }"
              @dragenter.prevent="isDraggingCover = true"
              @dragover.prevent="isDraggingCover = true"
              @dragleave.prevent="isDraggingCover = false"
              @drop.prevent="onCoverDrop"
              @click="openCoverPicker"
            >
              <input
                ref="coverInputRef"
                class="d-none"
                type="file"
                accept="image/*"
                @change="onCoverChange"
              />

              <template v-if="coverPreviewUrl">
                <img :src="coverPreviewUrl" alt="Cover preview" class="cover-preview" />
              </template>
              <template v-else>
                <p class="m-0 fw-semibold">Drop image here or click to browse</p>
                <small class="text-muted">JPG, PNG, WEBP - max 5 MB</small>
              </template>
            </div>
          </div>
        </div>
      </div>

      <div class="track-submit-sticky mt-2">
        <button :disabled="submitting" class="btn btn-primary btn-sm">
          {{ submitting ? "Saving..." : "Create track" }}
        </button>
        <button class="btn btn-outline-secondary btn-sm" type="button" @click="scrollToSection('basic')">
          Back to top
        </button>
      </div>

      <audio
        ref="previewAudioRef"
        :src="audioPreviewUrl"
        preload="auto"
        class="d-none"
        @ended="stopPreviewSegment"
        @timeupdate="onPreviewTimeUpdate"
      ></audio>
      <audio
        ref="fullAudioRef"
        :src="audioPreviewUrl"
        preload="metadata"
        class="d-none"
        @loadedmetadata="onFullTrackLoadedMetadata"
        @timeupdate="onFullTrackTimeUpdate"
        @ended="onFullTrackEnded"
      ></audio>
    </form>

    <div class="row g-3">
      <div class="col-sm-6 col-lg-4 col-xl-3" v-for="t in filteredTracks" :key="trackId(t)">
        <RouterLink class="track-card-link" :to="`/tracks/${trackId(t)}`">
          <div class="card h-100 shadow-sm track-card">
          <img class="card-img-top track-cover" :src="coverUrl(t.track_cover)" :alt="t.track_title" @error="onImgError" />

          <div class="card-body d-flex flex-column track-meta">
            <h3 class="h5 card-title mb-2 track-title">{{ t.track_title }}</h3>
            <p class="mb-2 track-artist">Artist: {{ artistNames(t) }}</p>
            <p class="mb-2 track-artist">Price: {{ formatPrice(t.track_price_eur) }}</p>
          </div>
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script>
import service from "@/api/trackService";
import genreService from "@/api/genreService";
import artistService from "@/api/artistService";
import albumService from "@/api/albumService";
import { mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { useSearchStore } from "@/stores/searchStore";
import { storageUrl } from "@/utils/storageUrl";
import { RouterLink } from "vue-router";

const initialForm = () => ({
  track_title: "",
  album_id: "",
  album_title: "",
  genre_names: [""],
  artist_names: [""],
  bpm_value: null,
  track_price_eur: 1.99,
  release_date: "",
  track_length_sec: null,
  preview_start_at: 0,
  preview_end_at: 30,
});

export default {
  components: { RouterLink },
  data() {
    return {
      tracks: [],
      genres: [],
      artists: [],
      albums: [],
      form: initialForm(),
      submitting: false,
      analyzing: false,
      formError: "",
      audioFile: null,
      coverFile: null,
      coverPreviewUrl: "",
      isDraggingCover: false,
      audioPreviewUrl: "",
      isPreviewPlaying: false,
      previewStopTimer: null,
      previewHint: "",
      previewSeekTime: 0,
      isDraggingAudio: false,
      isFullTrackPlaying: false,
      fullTrackDuration: 0,
      fullTrackCurrentTime: 0,
      selectedGenreFilter: "",
      selectedArtistFilter: "",
      selectedBpmFilter: "",
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin"]),
    ...mapState(useSearchStore, ["searchWord"]),
    filteredTracks() {
      const word = (this.searchWord || "").toLowerCase().trim();
      return this.tracks.filter((t) => {
        const genreFilter = String(this.selectedGenreFilter || "").trim().toLowerCase();
        if (genreFilter) {
          const hasGenre =
            String(t.genre?.genre_name || "").trim().toLowerCase() === genreFilter
            || (t.genres || []).some((g) => String(g?.genre_name || "").trim().toLowerCase() === genreFilter);
          if (!hasGenre) return false;
        }

        const artistFilter = String(this.selectedArtistFilter || "").trim().toLowerCase();
        if (artistFilter) {
          const hasArtist = (t.artists || []).some((a) => String(a?.artist_name || "").trim().toLowerCase() === artistFilter);
          if (!hasArtist) return false;
        }

        const bpmFilter = Number(this.selectedBpmFilter || 0);
        if (bpmFilter > 0 && Number(t?.bpm_value || 0) !== bpmFilter) {
          return false;
        }

        if (!word) return true;
        const hay = [
          t.track_title,
          t.genre?.genre_name,
          ...(t.artists || []).map((a) => a.artist_name),
        ]
          .join(" ")
          .toLowerCase();
        return hay.includes(word);
      });
    },
    bpmFilterOptions() {
      const values = (this.tracks || [])
        .map((t) => Number(t?.bpm_value || 0))
        .filter((bpm) => Number.isFinite(bpm) && bpm > 0);
      return Array.from(new Set(values)).sort((a, b) => a - b);
    },
    previewDuration() {
      return Math.max(0, Number(this.form.preview_end_at || 0) - Number(this.form.preview_start_at || 0));
    },
    maxPreviewStart() {
      const length = Number(this.form.track_length_sec || 0);
      if (!Number.isFinite(length) || length <= 0) return 999999;
      return Math.max(0, length - 1);
    },
    maxPreviewEnd() {
      const length = Number(this.form.track_length_sec || 0);
      if (!Number.isFinite(length) || length <= 0) return 999999;
      return length;
    },
  },
  methods: {
    coverUrl(file) {
      if (!file) return "https://placehold.co/600x340?text=Track";
      if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
      if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
      return storageUrl(`track-covers/${file}`);
    },
    trackId(track) {
      return track?.id ?? track?.track_id;
    },
    shuffleArray(items) {
      const arr = [...items];
      for (let i = arr.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
      }
      return arr;
    },
    shuffleTracks() {
      this.tracks = this.shuffleArray(this.tracks || []);
    },
    resetTrackFilters() {
      this.selectedGenreFilter = "";
      this.selectedArtistFilter = "";
      this.selectedBpmFilter = "";
    },
    applyGenreFromRouteQuery() {
      const queryGenre = String(this.$route?.query?.genre || "").trim();
      if (!queryGenre) return;

      const target = queryGenre.toLowerCase();
      const matched = (this.genres || []).find((g) => String(g.genre_name || "").trim().toLowerCase() === target);
      this.selectedGenreFilter = matched?.genre_name || queryGenre;
    },
    scrollToSection(section) {
      const map = {
        basic: "basicSection",
        album: "albumSection",
        meta: "metaSection",
        timing: "timingSection",
        media: "mediaSection",
      };
      const key = map[section];
      if (!key) return;
      const target = this.$refs[key];
      if (target && typeof target.scrollIntoView === "function") {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    },
    addArtistField() {
      this.form.artist_names.push("");
    },
    addGenreField() {
      this.form.genre_names.push("");
    },
    removeArtistField(index) {
      this.form.artist_names.splice(index, 1);
      if (this.form.artist_names.length === 0) {
        this.form.artist_names = [""];
      }
    },
    removeGenreField(index) {
      this.form.genre_names.splice(index, 1);
      if (this.form.genre_names.length === 0) {
        this.form.genre_names = [""];
      }
    },
    applyAnalyzedMetadata(data) {
      if (!data || typeof data !== "object") return;

      if (data.track_title && !this.form.track_title) {
        this.form.track_title = data.track_title;
      }

      if (data.genre_name) {
        const hasExistingGenres = this.form.genre_names.some((name) => String(name || "").trim() !== "");
        if (!hasExistingGenres) {
          this.form.genre_names = [data.genre_name];
        }
      }

      if (Array.isArray(data.artist_names) && data.artist_names.length > 0) {
        const hasExistingArtists = this.form.artist_names.some((name) => String(name || "").trim() !== "");
        if (!hasExistingArtists) {
          this.form.artist_names = data.artist_names;
        }
      }

      if (data.release_date && !this.form.release_date) {
        this.form.release_date = data.release_date;
      }

      if (data.track_length_sec) {
        this.form.track_length_sec = Number(data.track_length_sec) || this.form.track_length_sec;
      }

      if (data.bpm_value && !this.form.bpm_value) {
        this.form.bpm_value = data.bpm_value;
      }
    },
    artistNames(track) {
      return (track.artists || []).map((a) => a.artist_name).join(", ") || "-";
    },
    onImgError(e) {
      e.target.src = "https://placehold.co/600x340?text=No+Cover";
    },
    previewWindowSize() {
      const length = Number(this.form.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0) {
        return Math.min(30, Math.floor(length));
      }
      return 30;
    },
    onPreviewStartInput() {
      let start = Number(this.form.preview_start_at || 0);
      start = Number.isFinite(start) ? Math.max(0, Math.floor(start)) : 0;

      const windowSize = this.previewWindowSize();
      let end = start + windowSize;

      const length = Number(this.form.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0 && end > length) {
        end = length;
        start = Math.max(0, end - windowSize);
      }

      this.form.preview_start_at = start;
      this.form.preview_end_at = end;
    },
    onPreviewEndInput() {
      let end = Number(this.form.preview_end_at || 0);
      end = Number.isFinite(end) ? Math.max(1, Math.floor(end)) : 30;

      const length = Number(this.form.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0) {
        end = Math.min(end, length);
      }

      const windowSize = this.previewWindowSize();
      let start = Math.max(0, end - windowSize);

      if (Number.isFinite(length) && length > 0 && (end - start) < windowSize) {
        end = Math.min(length, start + windowSize);
        start = Math.max(0, end - windowSize);
      }

      this.form.preview_start_at = start;
      this.form.preview_end_at = end;
    },
    normalizePreviewWindow() {
      this.onPreviewStartInput();
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
      if (audio) {
        audio.pause();
      }
      this.isPreviewPlaying = false;
      this.previewSeekTime = Number(this.form.preview_start_at || 0);
    },
    stopFullTrack() {
      const audio = this.$refs.fullAudioRef;
      if (audio) {
        audio.pause();
      }
      this.isFullTrackPlaying = false;
    },
    toggleFullTrackPlay() {
      const audio = this.$refs.fullAudioRef;
      if (!audio || !this.audioPreviewUrl) return;

      if (this.isFullTrackPlaying) {
        audio.pause();
        this.isFullTrackPlaying = false;
        return;
      }

      this.stopPreviewSegment();
      audio.play();
      this.isFullTrackPlaying = true;
    },
    onFullTrackLoadedMetadata() {
      const audio = this.$refs.fullAudioRef;
      this.fullTrackDuration = Number(audio?.duration || 0);
    },
    onFullTrackTimeUpdate() {
      const audio = this.$refs.fullAudioRef;
      this.fullTrackCurrentTime = Number(audio?.currentTime || 0);
    },
    onFullTrackEnded() {
      this.isFullTrackPlaying = false;
      this.fullTrackCurrentTime = 0;
    },
    onFullTrackSeekInput(event) {
      const next = Number(event?.target?.value || 0);
      const audio = this.$refs.fullAudioRef;
      if (!audio) return;
      audio.currentTime = next;
      this.fullTrackCurrentTime = next;
    },
    playPreviewSegment() {
      if (!this.audioPreviewUrl) return;

      this.normalizePreviewWindow();
      const start = Number(this.form.preview_start_at || 0);
      const end = Number(this.form.preview_end_at || 0);
      const durationMs = Math.max(0, (end - start) * 1000);
      if (durationMs <= 0) return;

      const audio = this.$refs.previewAudioRef;
      if (!audio) return;

      this.stopPreviewSegment();
      audio.currentTime = start;
      this.previewSeekTime = start;
      audio.play();
      this.isPreviewPlaying = true;
      this.previewHint = `Playing ${start}s-${end}s`;

      this.previewStopTimer = setTimeout(() => {
        this.stopPreviewSegment();
      }, durationMs + 80);
    },
    regeneratePreviewSegment() {
      this.playPreviewSegment();
      this.previewHint = "Preview regenerated with current start/end";
    },
    onPreviewSeekInput(event) {
      const next = Number(event?.target?.value || this.form.preview_start_at || 0);
      const min = Number(this.form.preview_start_at || 0);
      const max = Number(this.form.preview_end_at || 0);
      const clamped = Math.max(min, Math.min(max, next));
      this.previewSeekTime = clamped;
      const audio = this.$refs.previewAudioRef;
      if (audio) {
        audio.currentTime = clamped;
      }
    },
    onPreviewTimeUpdate() {
      const audio = this.$refs.previewAudioRef;
      if (!audio) return;
      this.previewSeekTime = Number(audio.currentTime || this.form.preview_start_at || 0);
      if (this.previewSeekTime >= Number(this.form.preview_end_at || 0)) {
        this.stopPreviewSegment();
      }
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
    onAudioChange(event) {
      const file = event.target.files?.[0] || null;
      this.handleAudioSelected(file);
    },
    onAudioDrop(event) {
      this.isDraggingAudio = false;
      const file = event.dataTransfer?.files?.[0] || null;
      this.syncAudioInputFile(file);
      this.handleAudioSelected(file);
    },
    syncAudioInputFile(file) {
      const input = this.$refs.audioInputRef;
      if (!input || !file) return;
      try {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
      } catch (_err) {
      }
    },
    handleAudioSelected(file) {
      this.stopPreviewSegment();
      this.stopFullTrack();
      this.previewHint = "";
      this.audioFile = file;
      if (this.audioPreviewUrl) {
        URL.revokeObjectURL(this.audioPreviewUrl);
        this.audioPreviewUrl = "";
      }
      if (!file) return;
      this.audioPreviewUrl = URL.createObjectURL(file);
      this.previewSeekTime = Number(this.form.preview_start_at || 0);
      this.fullTrackCurrentTime = 0;
      this.fullTrackDuration = 0;
      this.applyFilenameFallback(file.name);

      const audio = document.createElement("audio");
      audio.preload = "metadata";
      audio.src = URL.createObjectURL(file);
      audio.onloadedmetadata = () => {
        const duration = Math.floor(audio.duration || 0);
        if (duration > 0) {
          this.form.track_length_sec = duration;
          if (this.form.preview_end_at > duration) {
            this.form.preview_end_at = duration;
          }
          this.normalizePreviewWindow();
        }
        URL.revokeObjectURL(audio.src);
      };

      this.analyzeAudioFile(file);
    },
    applyFilenameFallback(fileName) {
      const base = String(fileName || "").replace(/\.[^/.]+$/, "");
      const clean = base.replace(/_/g, " ").replace(/\s+/g, " ").trim();
      if (!clean) return;

      const parts = clean.split(" - ");
      if (parts.length >= 2) {
        const artistRaw = parts[0].trim();
        const titleRaw = parts.slice(1).join(" - ").trim();

        if (!this.form.track_title && titleRaw) {
          this.form.track_title = titleRaw;
        }

        const artistNames = artistRaw
          .split(/,|&| feat\. | feat | ft\. | ft | featuring | x | and |;/i)
          .map((x) => x.trim())
          .filter(Boolean);

        const hasExistingArtists = this.form.artist_names.some((name) => String(name || "").trim() !== "");
        if (!hasExistingArtists && artistNames.length > 0) {
          this.form.artist_names = artistNames;
        }
        return;
      }

      if (!this.form.track_title) {
        this.form.track_title = clean;
      }
    },
    async analyzeAudioFile(file) {
      this.analyzing = true;
      try {
        const res = await service.analyzeUpload(file);
        const analyzed = res?.data && typeof res.data === "object" ? res.data : (res || {});
        this.applyAnalyzedMetadata(analyzed);
        if (analyzed?.track_length_sec) {
          this.form.track_length_sec = Number(analyzed.track_length_sec) || this.form.track_length_sec;
          this.normalizePreviewWindow();
        }
        if (analyzed?.cover_data_url && !this.coverFile) {
          this.setCoverFromDataUrl(analyzed.cover_data_url, file?.name);
        }
        if (!analyzed?.bpm_value && !this.form.bpm_value) {
          const fallbackBpm = await this.estimateBpmFromAudioFile(file);
          if (fallbackBpm) {
            this.form.bpm_value = fallbackBpm;
          }
        }
        this.normalizePreviewWindow();
      } catch (err) {
        this.formError = err?.response?.data?.message || "Audio analyze failed, fallback from filename used.";
      } finally {
        this.analyzing = false;
      }
    },
    setCoverFromDataUrl(dataUrl, originalAudioName) {
      const data = String(dataUrl || "");
      const match = data.match(/^data:(.*?);base64,(.*)$/);
      if (!match) return;

      const mime = match[1] || "image/jpeg";
      const b64 = match[2] || "";
      if (!b64) return;

      try {
        const binary = atob(b64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i += 1) {
          bytes[i] = binary.charCodeAt(i);
        }

        const baseName = String(originalAudioName || "cover").replace(/\.[^/.]+$/, "").trim() || "cover";
        const ext = mime.includes("png") ? "png" : (mime.includes("webp") ? "webp" : "jpg");
        const file = new File([bytes], `${baseName}-cover.${ext}`, { type: mime });
        this.setCoverFile(file);
      } catch (_err) {
      }
    },
    async estimateBpmFromAudioFile(file) {
      try {
        const arrayBuffer = await file.arrayBuffer();
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return null;

        const ctx = new AudioCtx();
        try {
          const buffer = await ctx.decodeAudioData(arrayBuffer.slice(0));
          const data = buffer.getChannelData(0);
          const frameSize = 2048;
          const hop = 512;
          const envelope = [];

          for (let i = 0; i + frameSize < data.length; i += hop) {
            let e = 0;
            for (let j = 0; j < frameSize; j += 1) {
              const s = data[i + j];
              e += s * s;
            }
            envelope.push(Math.sqrt(e / frameSize));
          }

          if (envelope.length < 16) return null;

          const mean = envelope.reduce((a, b) => a + b, 0) / envelope.length;
          const threshold = mean * 1.3;
          const peaks = [];

          for (let i = 1; i < envelope.length - 1; i += 1) {
            if (envelope[i] > threshold && envelope[i] > envelope[i - 1] && envelope[i] >= envelope[i + 1]) {
              peaks.push(i);
            }
          }

          if (peaks.length < 8) return null;

          const bpmBins = new Map();
          for (let i = 0; i < peaks.length; i += 1) {
            for (let j = i + 1; j < Math.min(i + 20, peaks.length); j += 1) {
              const deltaFrames = peaks[j] - peaks[i];
              if (deltaFrames <= 0) continue;
              let bpm = (60 * buffer.sampleRate) / (deltaFrames * hop);
              while (bpm < 70) bpm *= 2;
              while (bpm > 180) bpm /= 2;
              const rounded = Math.round(bpm);
              if (rounded >= 70 && rounded <= 180) {
                bpmBins.set(rounded, (bpmBins.get(rounded) || 0) + 1);
              }
            }
          }

          let best = null;
          let score = -1;
          bpmBins.forEach((v, k) => {
            if (v > score) {
              score = v;
              best = k;
            }
          });

          return best;
        } finally {
          await ctx.close();
        }
      } catch (_err) {
        return null;
      }
    },
    openCoverPicker() {
      this.$refs.coverInputRef?.click();
    },
    setCoverFile(file) {
      this.coverFile = file;
      if (this.coverPreviewUrl) {
        URL.revokeObjectURL(this.coverPreviewUrl);
      }
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
        this.formError = "Only image files can be used as track cover.";
        return;
      }
      this.formError = "";
      this.setCoverFile(file);
    },
    toValidationError(err) {
      const errors = err?.response?.data?.errors;
      if (!errors || typeof errors !== "object") {
        return err?.response?.data?.message || "Track creation failed.";
      }

      const firstKey = Object.keys(errors)[0];
      if (!firstKey) return "Validation error.";
      const firstValue = errors[firstKey];
      if (Array.isArray(firstValue) && firstValue.length > 0) return firstValue[0];
      return "Validation error.";
    },
    async load() {
      const [tracksRes, genresRes, artistsRes, albumsRes] = await Promise.allSettled([
        service.list(),
        genreService.list(),
        artistService.list(),
        albumService.list(),
      ]);

      this.tracks = tracksRes.status === "fulfilled" ? this.shuffleArray(tracksRes.value.data || []) : [];
      this.genres = genresRes.status === "fulfilled" ? (genresRes.value.data || []) : [];
      this.artists = artistsRes.status === "fulfilled" ? (artistsRes.value.data || []) : [];
      this.albums = albumsRes.status === "fulfilled" ? (albumsRes.value.data || []) : [];

      if (tracksRes.status !== "fulfilled") {
        throw tracksRes.reason;
      }
    },
    async createTrack() {
      this.formError = "";
      this.normalizePreviewWindow();
      this.stopPreviewSegment();

      if (!this.audioFile) {
        this.formError = "Audio file is required.";
        return;
      }

      const artistNames = (this.form.artist_names || [])
        .map((x) => String(x || "").trim())
        .filter(Boolean);

      if (artistNames.length === 0) {
        this.formError = "At least one artist is required.";
        return;
      }

      if (this.previewDuration > 30) {
        this.formError = "Preview duration cannot exceed 30 seconds.";
        return;
      }

      this.submitting = true;
      try {
        const payload = new FormData();
        payload.append("track_title", this.form.track_title);
        if (this.form.album_id) {
          payload.append("album_id", String(this.form.album_id));
        } else if (String(this.form.album_title || "").trim() !== "") {
          payload.append("album_title", String(this.form.album_title).trim());
        }

        const genreNames = (this.form.genre_names || [])
          .map((x) => String(x || "").trim())
          .filter(Boolean);
        if (genreNames.length === 0) {
          this.formError = "At least one genre is required.";
          this.submitting = false;
          return;
        }

        const existingByName = new Map(
          (this.genres || []).map((g) => [String(g.genre_name || "").trim().toLowerCase(), g])
        );

        const genreIds = [];
        const newGenreNames = [];
        for (const genreName of genreNames) {
          const existing = existingByName.get(genreName.toLowerCase());
          if (existing?.genre_id) {
            genreIds.push(existing.genre_id);
          } else {
            newGenreNames.push(genreName);
          }
        }

        genreIds.forEach((id, index) => payload.append(`genre_ids[${index}]`, String(id)));
        newGenreNames.forEach((name, index) => payload.append(`genre_names[${index}]`, name));

        payload.append("preview_start_at", String(this.form.preview_start_at));
        payload.append("preview_end_at", String(this.form.preview_end_at));
        payload.append("track_audio", this.audioFile);

        artistNames.forEach((name, index) => payload.append(`artist_names[${index}]`, name));

        if (this.form.bpm_value) payload.append("bpm_value", String(this.form.bpm_value));
        payload.append("track_price_eur", String(Number(this.form.track_price_eur ?? 1.99).toFixed(2)));
        if (this.form.release_date) payload.append("release_date", this.form.release_date);
        if (this.form.track_length_sec) payload.append("track_length_sec", String(this.form.track_length_sec));
        if (this.coverFile) payload.append("track_cover_file", this.coverFile);

        await service.create(payload);
        this.form = initialForm();
        this.audioFile = null;
        this.stopFullTrack();
        if (this.audioPreviewUrl) {
          URL.revokeObjectURL(this.audioPreviewUrl);
          this.audioPreviewUrl = "";
        }
        this.previewHint = "";
        this.setCoverFile(null);
        await this.load();
      } catch (err) {
        this.formError = this.toValidationError(err);
      } finally {
        this.submitting = false;
      }
    },
  },
  watch: {
    "$route.query.genre"() {
      this.applyGenreFromRouteQuery();
    },
  },
  async mounted() {
    await this.load();
    this.applyGenreFromRouteQuery();
  },
  beforeUnmount() {
    this.stopPreviewSegment();
    this.stopFullTrack();
    if (this.audioPreviewUrl) {
      URL.revokeObjectURL(this.audioPreviewUrl);
    }
    if (this.coverPreviewUrl) {
      URL.revokeObjectURL(this.coverPreviewUrl);
    }
  },
};
</script>

<style scoped>
:root {
  color-scheme: light;
}

.card.card-body {
  border: 1px solid #d8e3f3;
  border-radius: 14px;
  background:
    radial-gradient(1200px 420px at 0% -20%, rgba(59, 130, 246, 0.07), transparent 55%),
    linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
  box-shadow: 0 10px 26px rgba(26, 58, 112, 0.08);
}

.h6,
.h5 {
  color: #0f172a;
  font-weight: 700;
}

.badge.text-bg-dark {
  background: #1e293b !important;
  border-radius: 999px;
  padding: 0.35rem 0.55rem;
}

.form-control,
.form-select {
  border-color: #c8d6ea;
  background: #ffffff;
  border-radius: 10px;
  min-height: 42px;
  box-shadow: none;
}

.form-control:focus,
.form-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 0.16rem rgba(59, 130, 246, 0.2);
}

.form-label {
  margin-bottom: 0.35rem;
  color: #1e293b;
  font-weight: 600;
}

.text-muted {
  color: #64748b !important;
}

.section-chip {
  display: inline-flex;
  align-items: center;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1e3a8a;
  border-radius: 999px;
  padding: 0.2rem 0.6rem;
  font-size: 0.78rem;
  font-weight: 600;
}

.editor-section {
  border: 1px solid #dbe7f7;
  border-radius: 12px;
  background: #f8fbff;
  padding: 0.85rem;
}

.section-title {
  font-weight: 700;
  font-size: 0.94rem;
  color: #0f172a;
  margin-bottom: 0.65rem;
}

.track-submit-sticky {
  position: sticky;
  bottom: 10px;
  z-index: 5;
  display: flex;
  gap: 0.5rem;
  align-items: center;
  width: fit-content;
  padding: 0.45rem;
  border-radius: 12px;
  border: 1px solid #dbe7f7;
  background: rgba(248, 251, 255, 0.96);
  backdrop-filter: blur(3px);
}

.btn {
  border-radius: 10px;
}

.btn-primary {
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
  border-color: #1d4ed8;
  font-weight: 600;
}

.btn-outline-primary {
  border-color: #3b82f6;
  color: #1d4ed8;
}

.btn-outline-primary:hover {
  background: #dbeafe;
  color: #1e3a8a;
}

.btn-outline-secondary {
  border-color: #c8d6ea;
  color: #334155;
}

.btn-outline-danger {
  border-color: #fca5a5;
  color: #dc2626;
}

.btn-outline-danger:hover {
  background: #fee2e2;
}

.form-range {
  height: 1.2rem;
}

.form-range::-webkit-slider-runnable-track {
  height: 0.42rem;
  border-radius: 999px;
  background: linear-gradient(90deg, #dbeafe 0%, #bfdbfe 100%);
}

.form-range::-webkit-slider-thumb {
  margin-top: -5px;
  width: 14px;
  height: 14px;
  border-radius: 999px;
  background: #2563eb;
  border: 2px solid #ffffff;
}

.form-range::-moz-range-track {
  height: 0.42rem;
  border-radius: 999px;
  background: linear-gradient(90deg, #dbeafe 0%, #bfdbfe 100%);
}

.form-range::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  background: #2563eb;
  border: 2px solid #ffffff;
}

.track-cover {
  width: 72%;
  margin: 0.9rem auto 0;
  border-radius: 0.75rem;
  aspect-ratio: 1 / 1;
  height: auto;
  object-fit: cover;
}

.track-card-link {
  display: block;
  text-decoration: none;
  color: inherit;
}

.track-card {
  background: #ffffff;
  border: 1px solid #d9e3f1;
  border-radius: 14px;
  box-shadow: 0 8px 20px rgba(20, 37, 63, 0.06);
  transition: transform 0.16s ease, box-shadow 0.16s ease;
  overflow: hidden;
}

.track-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px rgba(20, 37, 63, 0.11);
}

.track-meta {
  color: #1f2937;
}

.track-title {
  font-size: 1.2rem;
  line-height: 1.1;
  letter-spacing: 0.01em;
  min-height: 2.65rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.track-artist {
  color: #4b5563;
  font-weight: 600;
  min-height: 1.55rem;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.preview-seek-wrap {
  padding: 0.35rem 0.15rem;
}

.cover-dropzone {
  min-height: 150px;
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
  transition: all 0.2s ease;
}

.audio-dropzone {
  border: 2px dashed #93c5fd;
  border-radius: 12px;
  padding: 0.65rem 0.8rem;
  text-align: center;
  color: #1e3a8a;
  background: linear-gradient(180deg, #eff6ff 0%, #edf3fc 100%);
}

.audio-dropzone.is-over {
  border-color: #2563eb;
  background: #dbeafe;
}

.cover-dropzone.is-over {
  border-color: #2563eb;
  background: #dbeafe;
}

.cover-preview {
  max-width: 100%;
  max-height: 200px;
  object-fit: cover;
  border-radius: 10px;
}

@media (max-width: 992px) {
  .card.card-body {
    padding: 0.9rem;
  }
}
</style>




