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
        </div>
      </div>

      <div class="layout-right">
        <p class="crumb-line">
          Home / Tracks / {{ firstArtist(track) }} / {{ track.track_title }}
        </p>

        <div class="artist-stack">
          <p v-for="a in track.artists || []" :key="a.artist_id" class="artist-top">{{ a.artist_name }}</p>
        </div>
        <div v-if="isCustomer" class="artist-chips">
          <span v-for="a in track.artists || []" :key="`chip-${a.artist_id}`" class="chip">{{ a.artist_name }}</span>
        </div>

        <h1 class="detail-title">{{ track.track_title }}</h1>

        <div class="player-wrap">
          <audio
            v-if="isAdmin"
            class="w-100"
            controls
            controlslist="nodownload noplaybackrate"
            :src="audioUrl(track.track_path)"
            @contextmenu.prevent
          ></audio>

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
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from "pinia";
import { RouterLink } from "vue-router";
import service from "@/api/trackService";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { storageUrl } from "@/utils/storageUrl";
import NeonWavePlayer from "@/components/AudioPlayer/NeonWavePlayer.vue";

export default {
  components: { RouterLink, NeonWavePlayer },
  data() {
    return {
      loading: true,
      error: "",
      track: null,
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin", "isCustomer"]),
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
  },
  async mounted() {
    await this.load();
  },
};
</script>

<style scoped>
.track-layout {
  display: grid;
  grid-template-columns: minmax(280px, 420px) 1fr;
  gap: 2rem;
  padding: 1rem 1rem 1.25rem;
}

.layout-left {
  min-width: 0;
}

.detail-cover {
  width: 100%;
  max-width: 420px;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  aspect-ratio: 1 / 1;
  height: auto;
  object-fit: cover;
}

.meta-box {
  margin-top: 0.75rem;
  max-width: 420px;
  border: 1px solid #dbe7fb;
  border-radius: 0.5rem;
  background: #f8fbff;
  padding: 0.6rem 0.75rem;
}

.meta-row {
  display: grid;
  grid-template-columns: 72px 1fr;
  gap: 0.5rem;
  font-size: 0.95rem;
  color: #0f172a;
  padding: 0.2rem 0;
}

.meta-key {
  font-weight: 700;
  color: #334155;
}

.layout-right {
  min-width: 0;
  padding: 0.6rem 0.25rem;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.crumb-line {
  margin: 0 0 1.1rem;
  color: #334155;
  font-size: 0.95rem;
}

.artist-stack {
  margin-bottom: 0.7rem;
}

.artist-top {
  margin: 0;
  color: #2563eb;
  font-weight: 600;
}

.artist-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-bottom: 0.8rem;
}

.chip {
  font-size: 0.78rem;
  font-weight: 700;
  color: #1e3a8a;
  background: #dbeafe;
  border: 1px solid #bfdbfe;
  border-radius: 999px;
  padding: 0.18rem 0.55rem;
}

.detail-title {
  margin: 0 0 1.15rem;
  font-size: clamp(1.5rem, 3vw, 2.6rem);
  line-height: 1.04;
  letter-spacing: 0.01em;
  color: #0f172a;
  font-weight: 800;
  max-width: 18ch;
}

.player-wrap {
  max-width: 560px;
}

.after-player {
  margin-top: 1rem;
}

.quality-row {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.quality-btn {
  min-width: 180px;
}

@media (max-width: 992px) {
  .track-layout {
    grid-template-columns: 1fr;
  }

  .detail-cover,
  .meta-box,
  .player-wrap {
    max-width: 100%;
  }
}
</style>
