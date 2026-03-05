<template>
  <div>
    <h2 class="h5">Artists</h2>
    <form class="card card-body mb-3" @submit.prevent="createOne">
      <input v-model="form.artist_name" class="form-control mb-2" placeholder="Artist name" required />
      <input v-model="form.artist_picture" class="form-control mb-2" placeholder="artists/file.jpg" />
      <button class="btn btn-primary">Add artist</button>
    </form>
    <ul class="list-group">
      <li v-for="a in items" :key="a.artist_id" class="list-group-item">
        <div class="d-flex align-items-center gap-2">
          <img
            :src="coverUrl(a.artist_picture)"
            alt=""
            width="36"
            height="36"
            class="rounded object-fit-cover border"
            @error="onImgError"
          />
          <span>{{ a.artist_id }} - {{ a.artist_name }} ({{ a.artist_picture }})</span>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>
import service from "@/api/artistService";
import { storageUrl } from "@/utils/storageUrl";

export default {
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
      await service.create(this.form);
      this.form = { artist_name: "", artist_picture: "" };
      await this.load();
    },
  },
  async mounted() {
    await this.load();
  },
};
</script>
