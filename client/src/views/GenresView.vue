<template>
  <div>
    <h2 class="h5">Genres</h2>
    <form class="d-flex gap-2 mb-3" @submit.prevent="createOne">
      <input v-model="name" class="form-control" placeholder="Genre name" required />
      <button class="btn btn-primary">Add</button>
    </form>
    <ul class="list-group">
      <li v-for="g in items" :key="g.genre_id" class="list-group-item">{{ g.genre_id }} - {{ g.genre_name }}</li>
    </ul>
  </div>
</template>

<script>
import service from "@/api/genreService";

export default {
  data() {
    return { items: [], name: "" };
  },
  methods: {
    async load() {
      const res = await service.list();
      this.items = res.data || [];
    },
    async createOne() {
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
