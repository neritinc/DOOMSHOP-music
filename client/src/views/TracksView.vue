<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 m-0">Tracks</h2>
      <span class="badge text-bg-dark">{{ filteredTracks.length }} items</span>
    </div>

    <form v-if="isAdmin" class="card card-body mb-3" @submit.prevent="createTrack">
      <div class="row g-2">
        <div class="col-md-4"><input v-model="form.track_title" class="form-control" placeholder="Track title" required /></div>
        <div class="col-md-3"><input v-model="form.genre_name" class="form-control" placeholder="Genre name" required /></div>
        <div class="col-md-3"><input v-model="form.artist_names" class="form-control" placeholder="Artist names, comma" required /></div>
        <div class="col-md-2"><input v-model="form.track_path" class="form-control" placeholder="tracks/file.mp3" required /></div>
      </div>
      <button class="btn btn-primary btn-sm mt-2 align-self-start">Create track</button>
    </form>

    <div class="row g-3">
      <div class="col-md-6 col-xl-4" v-for="t in filteredTracks" :key="trackId(t)">
        <div class="card h-100 shadow-sm">
          <img class="card-img-top track-cover" :src="coverUrl(t.track_cover)" :alt="t.track_title" @error="onImgError" />

          <div class="card-body d-flex flex-column">
            <h3 class="h6 card-title mb-1">{{ t.track_title }}</h3>
            <p class="small text-muted mb-1">Genre: {{ t.genre?.genre_name || '-' }}</p>
            <p class="small text-muted mb-2">Artists: {{ artistNames(t) }}</p>

            <div class="mt-auto">
              <audio
                v-if="isAdmin"
                class="w-100"
                controls
                controlslist="nodownload noplaybackrate"
                :src="audioUrl(t.track_path)"
                @play="stopOtherAdminPlayers($event)"
                @contextmenu.prevent
              ></audio>

              <div v-else-if="isCustomer" class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-primary" @click="togglePreview(t)">
                  {{ isPlaying(trackId(t)) ? 'Pause Preview' : 'Play Preview' }}
                </button>
                <span class="badge text-bg-secondary">
                  Preview: {{ previewWindow(t).start }}s - {{ previewWindow(t).end }}s
                </span>
              </div>

              <small v-else class="text-muted">Login needed for playback.</small>

              <RouterLink class="btn btn-sm btn-outline-secondary mt-2" :to="`/tracks/${trackId(t)}`">
                Open details
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import service from "@/api/trackService";
import { mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { useSearchStore } from "@/stores/searchStore";
import { storageUrl } from "@/utils/storageUrl";
import { Howl } from "howler";
import { RouterLink } from "vue-router";

export default {
  components: { RouterLink },
  data() {
    return {
      tracks: [],
      form: { track_title: "", genre_name: "", artist_names: "", track_path: "" },
      howls: {},
      activeTrackId: null,
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin", "isCustomer", "token"]),
    ...mapState(useSearchStore, ["searchWord"]),
    filteredTracks() {
      const word = (this.searchWord || "").toLowerCase().trim();
      if (!word) return this.tracks;
      return this.tracks.filter((t) => {
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
      return file ? storageUrl(`artists/${file}`) : "https://placehold.co/600x340?text=Track";
    },
    audioUrl(path) {
      return path ? storageUrl(path) : "";
    },
    previewApiUrl(track) {
      const { start, end } = this.previewWindow(track);
      const apiBase = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
      return `${apiBase}/tracks/${this.trackId(track)}/preview?start=${start}&end=${end}`;
    },
    trackId(track) {
      return track?.id ?? track?.track_id;
    },
    artistNames(track) {
      return (track.artists || []).map((a) => a.artist_name).join(", ") || "-";
    },
    onImgError(e) {
      e.target.src = "https://placehold.co/600x340?text=No+Cover";
    },
    stopOtherAdminPlayers(event) {
      const allPlayers = document.querySelectorAll("audio");
      allPlayers.forEach((player) => {
        if (player !== event.target) player.pause();
      });
      this.stopAllPreviews();
    },
    ensureHowl(track) {
      const id = this.trackId(track);
      if (this.howls[id]) return this.howls[id];

      const howl = new Howl({
        src: [this.previewApiUrl(track)],
        html5: true,
        preload: true,
        xhr: {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        },
        onend: () => {
          if (this.activeTrackId === id) this.activeTrackId = null;
        },
      });

      this.howls[id] = howl;
      return howl;
    },
    stopAllPreviews() {
      Object.values(this.howls).forEach((h) => h.stop());
      this.activeTrackId = null;
    },
    isPlaying(trackId) {
      return this.activeTrackId === trackId;
    },
    togglePreview(track) {
      const id = this.trackId(track);
      const howl = this.ensureHowl(track);

      if (this.activeTrackId === id && howl.playing()) {
        howl.stop();
        this.activeTrackId = null;
        return;
      }

      this.stopAllPreviews();
      const allPlayers = document.querySelectorAll("audio");
      allPlayers.forEach((player) => player.pause());

      howl.play();
      this.activeTrackId = id;
    },
    async load() {
      const res = await service.list();
      this.tracks = res.data || [];
    },
    async createTrack() {
      await service.create({
        track_title: this.form.track_title,
        genre_name: this.form.genre_name,
        artist_names: this.form.artist_names.split(",").map((x) => x.trim()).filter(Boolean),
        track_path: this.form.track_path,
      });
      this.form = { track_title: "", genre_name: "", artist_names: "", track_path: "" };
      await this.load();
    },
  },
  async mounted() {
    await this.load();
  },
  beforeUnmount() {
    this.stopAllPreviews();
    Object.values(this.howls).forEach((h) => h.unload());
  },
};
</script>

<style scoped>
.track-cover {
  width: 100%;
  height: 180px;
  object-fit: cover;
}
</style>
