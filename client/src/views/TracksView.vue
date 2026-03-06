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
      <div class="col-sm-6 col-lg-4 col-xl-3" v-for="t in filteredTracks" :key="trackId(t)">
        <div class="card h-100 shadow-sm track-card">
          <img class="card-img-top track-cover" :src="coverUrl(t.track_cover)" :alt="t.track_title" @error="onImgError" />

          <div class="card-body d-flex flex-column track-meta">
            <h3 class="h5 card-title mb-2 track-title">{{ t.track_title }}</h3>
            <p class="mb-2 track-artist">Artist: {{ artistNames(t) }}</p>

            <div class="mt-auto">
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
import { RouterLink } from "vue-router";

export default {
  components: { RouterLink },
  data() {
    return {
      tracks: [],
      form: { track_title: "", genre_name: "", artist_names: "", track_path: "" },
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin"]),
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
    coverUrl(file) {
      if (!file) return "https://placehold.co/600x340?text=Track";
      if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
      if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
      return storageUrl(`track-covers/${file}`);
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
};
</script>

<style scoped>
.track-cover {
  width: 72%;
  margin: 0.75rem auto 0;
  border-radius: 0.5rem;
  aspect-ratio: 1 / 1;
  height: auto;
  object-fit: cover;
}

.track-card {
  background: #ffffff;
  border: 1px solid #d9dee5;
}

.track-meta {
  color: #1f2937;
}

.track-title {
  font-size: 1.2rem;
  line-height: 1.1;
  letter-spacing: 0.01em;
}

.track-artist {
  color: #4b5563;
  font-weight: 600;
}
</style>
