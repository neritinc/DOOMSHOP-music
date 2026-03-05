<template>
  <div>
    <RouterLink class="btn btn-outline-secondary btn-sm mb-3" to="/tracks">Back to tracks</RouterLink>

    <div v-if="loading" class="alert alert-info">Loading track...</div>
    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else-if="track" class="card shadow-sm">
      <div class="row g-0">
        <div class="col-md-4">
          <img class="detail-cover" :src="coverUrl(track.track_cover)" :alt="track.track_title" @error="onImgError" />
        </div>

        <div class="col-md-8">
          <div class="card-body">
            <h1 class="h4 mb-3">{{ track.track_title }}</h1>

            <p class="mb-1"><strong>Artists:</strong> {{ artistNames(track) }}</p>
            <p class="mb-1"><strong>Genre:</strong> {{ track.genre?.genre_name || "-" }}</p>
            <p class="mb-1"><strong>BPM:</strong> {{ track.bpm_value || "-" }}</p>
            <p class="mb-1"><strong>Release date:</strong> {{ track.release_date || "-" }}</p>
            <p class="mb-3"><strong>Length (sec):</strong> {{ track.track_length_sec || "-" }}</p>

            <audio
              v-if="isAdmin"
              class="w-100"
              controls
              controlslist="nodownload noplaybackrate"
              :src="audioUrl(track.track_path)"
              @contextmenu.prevent
            ></audio>

            <div v-else-if="isCustomer" class="d-flex align-items-center gap-2">
              <button class="btn btn-primary btn-sm" @click="togglePreview">
                {{ isPlaying ? "Pause Preview" : "Play Preview" }}
              </button>
              <span class="badge text-bg-secondary">
                Preview: {{ previewWindow(track).start }}s - {{ previewWindow(track).end }}s
              </span>
            </div>

            <small v-else class="text-muted">Login needed for playback preview.</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from "pinia";
import { RouterLink } from "vue-router";
import { Howl } from "howler";
import service from "@/api/trackService";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { storageUrl } from "@/utils/storageUrl";

export default {
  components: { RouterLink },
  data() {
    return {
      loading: true,
      error: "",
      track: null,
      previewHowl: null,
      isPlaying: false,
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin", "isCustomer", "token"]),
  },
  methods: {
    previewWindow(track) {
      const start = Number(track.preview_start_sec ?? 30);
      const end = Number(track.preview_end_sec ?? 60);
      return {
        start: Number.isFinite(start) ? start : 30,
        end: Number.isFinite(end) ? end : 60,
      };
    },
    coverUrl(file) {
      return file ? storageUrl(`track-covers/${file}`) : "https://placehold.co/700x700?text=Track";
    },
    audioUrl(path) {
      return path ? storageUrl(path) : "";
    },
    previewApiUrl(track) {
      const { start, end } = this.previewWindow(track);
      const apiBase = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
      return `${apiBase}/tracks/${this.trackId(track)}/preview?start=${start}&end=${end}`;
    },
    artistNames(track) {
      return (track.artists || []).map((a) => a.artist_name).join(", ") || "-";
    },
    trackId(track) {
      return track?.id ?? track?.track_id;
    },
    onImgError(e) {
      e.target.src = "https://placehold.co/700x700?text=No+Cover";
    },
    async load() {
      this.loading = true;
      this.error = "";
      try {
        const id = this.$route.params.id;
        const res = await service.show(id);
        this.track = res.data || null;
      } catch (err) {
        this.error = err?.response?.data?.message || "Track loading failed.";
      } finally {
        this.loading = false;
      }
    },
    ensurePreviewHowl() {
      if (!this.track) return null;
      if (this.previewHowl) return this.previewHowl;

      this.previewHowl = new Howl({
        src: [this.previewApiUrl(this.track)],
        html5: true,
        preload: true,
        xhr: {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        },
        onend: () => {
          this.isPlaying = false;
        },
      });

      return this.previewHowl;
    },
    togglePreview() {
      const howl = this.ensurePreviewHowl();
      if (!howl) return;

      if (howl.playing()) {
        howl.stop();
        this.isPlaying = false;
        return;
      }

      howl.play();
      this.isPlaying = true;
    },
  },
  async mounted() {
    await this.load();
  },
  beforeUnmount() {
    if (this.previewHowl) {
      this.previewHowl.stop();
      this.previewHowl.unload();
      this.previewHowl = null;
    }
  },
};
</script>

<style scoped>
.detail-cover {
  width: 100%;
  aspect-ratio: 1 / 1;
  height: auto;
  object-fit: cover;
}
</style>
