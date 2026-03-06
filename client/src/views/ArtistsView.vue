<template>
  <div>
    <h2 class="h5">Artists</h2>
    <form v-if="isAdmin" class="card card-body mb-3" @submit.prevent="createOne">
      <input v-model="form.artist_name" class="form-control mb-2" placeholder="Artist name" required />
      <input v-model="form.artist_picture" class="form-control mb-2" placeholder="artists/file.jpg" />
      <button class="btn btn-primary">Add artist</button>
    </form>

    <div class="row g-3">
      <div v-for="a in items" :key="a.artist_id" class="col-sm-6 col-lg-4 col-xl-3">
        <RouterLink class="artist-link" :to="`/artists/${a.artist_id}/tracks`">
          <div class="card h-100 shadow-sm artist-card">
            <img
              :src="coverUrl(a.artist_picture)"
              :alt="a.artist_name"
              class="card-img-top artist-cover"
              @error="onImgError"
            />

            <div class="card-body artist-meta">
              <h3 class="artist-name">{{ a.artist_name }}</h3>
              <p v-if="isAdmin" class="artist-extra mb-0">ID: {{ a.artist_id }} | {{ a.artist_picture || "-" }}</p>
            </div>
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script>
import service from "@/api/artistService";
import { storageUrl } from "@/utils/storageUrl";
import { mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { RouterLink } from "vue-router";

export default {
  components: { RouterLink },
  data() {
    return {
      items: [],
      form: { artist_name: "", artist_picture: "" },
    };
  },
  methods: {
    coverUrl(file) {
      if (!file) return "https://placehold.co/80x80?text=A";
      const normalized = String(file).replace(/\\/g, "/").trim();
      if (/^https?:\/\//i.test(normalized)) return normalized;
      const relative = normalized.startsWith("artists/") ? normalized : `artists/${normalized}`;
      return storageUrl(relative);
    },
    onImgError(e) {
      const img = e.target;
      const current = img.getAttribute("src") || "";
      const alreadyRetried = img.dataset.retriedExt === "1";

      if (!alreadyRetried && /\.png($|\?)/i.test(current)) {
        img.dataset.retriedExt = "1";
        img.src = current.replace(/\.png($|\?)/i, ".jpg$1");
        return;
      }

      img.src = "https://placehold.co/80x80?text=A";
    },
    async load() {
      const res = await service.list();
      this.items = res.data || [];
    },
    async createOne() {
      if (!this.isAdmin) return;
      await service.create(this.form);
      this.form = { artist_name: "", artist_picture: "" };
      await this.load();
    },
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin"]),
  },
  async mounted() {
    await this.load();
  },
};
</script>

<style scoped>
.artist-card {
  border: 1px solid #d9dee5;
  background: #ffffff;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.artist-link {
  text-decoration: none;
  color: inherit;
}

.artist-link:hover .artist-card {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12) !important;
}

.artist-cover {
  width: 72%;
  margin: 0.85rem auto 0;
  border-radius: 0.5rem;
  aspect-ratio: 1 / 1;
  height: auto;
  object-fit: cover;
}

.artist-meta {
  text-align: center;
}

.artist-name {
  margin: 0;
  font-size: 1.2rem;
  line-height: 1.1;
  letter-spacing: 0.01em;
  color: #1f2937;
  font-weight: 700;
}

.artist-extra {
  margin-top: 0.55rem;
  font-size: 0.82rem;
  color: #6b7280;
  word-break: break-word;
}
</style>
