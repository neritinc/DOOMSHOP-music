<template>
  <div>
    <h2 class="h5">Genres</h2>
    <form v-if="isAdmin" class="card card-body mb-3" @submit.prevent="createOne">
      <input v-model="name" class="form-control mb-2" placeholder="Genre name" required />
      <button class="btn btn-primary align-self-start">Add genre</button>
    </form>

    <div class="row g-3">
      <div v-for="g in items" :key="g.genre_id" class="col-sm-6 col-lg-4 col-xl-3">
        <div class="card h-100 shadow-sm genre-card">
          <div class="card-body genre-meta">
            <h3 class="genre-name">{{ g.genre_name }}</h3>
            <p v-if="isAdmin" class="genre-extra mb-0">ID: {{ g.genre_id }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import service from "@/api/genreService";
import { mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

export default {
  data() {
    return { items: [], name: "" };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin"]),
  },
  methods: {
    async load() {
      const res = await service.list();
      this.items = res.data || [];
    },
    async createOne() {
      if (!this.isAdmin) return;
      await service.create({ genre_name: this.name });
      this.name = "";
      await this.load();
    },
  },
  async mounted() {
    await this.load();
  },
};
</script>

<style scoped>
.genre-card {
  border: 1px solid #d9dee5;
  background: #ffffff;
}

.genre-meta {
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 120px;
}

.genre-name {
  margin: 0;
  font-size: 1.2rem;
  line-height: 1.1;
  letter-spacing: 0.01em;
  color: #1f2937;
  font-weight: 700;
}

.genre-extra {
  margin-top: 0.55rem;
  font-size: 0.82rem;
  color: #6b7280;
}
</style>
