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
            <div>
              <h3 class="h6 m-0">{{ album.title }}</h3>
              <small class="text-muted">
                {{ album.release_date || "-" }} - EUR {{ Number(album.price_eur || 0).toFixed(2) }} - {{ (album.tracks || []).length }} tracks
              </small>
            </div>
            <img v-if="album.cover" :src="coverUrl(album.cover)" alt="Album cover" class="album-cover-thumb" />
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
            <select v-model="assignTrackIds" class="form-select" multiple size="8">
              <option v-for="track in tracks" :key="track.id || track.track_id" :value="String(track.id || track.track_id)">
                {{ track.track_title }}
              </option>
            </select>
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-primary btn-sm" @click="saveAssign(album)">Save</button>
              <button type="button" class="btn btn-outline-danger btn-sm" @click="cancelAssign">Cancel</button>
            </div>
          </div>

          <ul class="mt-2 mb-0 ps-3">
            <li v-for="track in album.tracks || []" :key="track.id || track.track_id">
              {{ track.track_title }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import albumService from "@/api/albumService";
import trackService from "@/api/trackService";
import { mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { storageUrl } from "@/utils/storageUrl";

export default {
  data() {
    return {
      albums: [],
      tracks: [],
      activeAssignAlbumId: null,
      assignTrackIds: [],
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
    ...mapState(useUserLoginLogoutStore, ["isAdmin"]),
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
      return storageUrl(path, "/images/placeholder-track.svg");
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
  },
  async mounted() {
    await this.load();
  },
  beforeUnmount() {
    if (this.coverPreviewUrl) {
      URL.revokeObjectURL(this.coverPreviewUrl);
    }
  },
};
</script>

<style scoped>
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
</style>
