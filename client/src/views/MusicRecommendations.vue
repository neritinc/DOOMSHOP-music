<template>
  <section class="reco-page">
    <div class="d-flex justify-content-between align-items-center">
      <h2 class="h5 m-0">Music Recommendations</h2>
      <span class="badge text-bg-dark">{{ recommendations.length }} items</span>
    </div>

    <form v-if="isAdmin" class="card card-body admin-add" @submit.prevent="addLink">
      <h3 class="admin-title">Add recommendation (Admin)</h3>
      <div v-if="formError" class="alert alert-danger py-2 mb-2">{{ formError }}</div>
      <div class="row g-2">
        <div class="col-md-5">
          <input v-model="form.title" class="form-control" placeholder="Title (optional)" />
        </div>
        <div class="col-md-5">
          <input v-model="form.media_url" class="form-control" placeholder="SoundCloud or YouTube URL" required />
        </div>
        <div class="col-md-2">
          <input v-model.number="form.sort_order" class="form-control" type="number" min="0" max="127" placeholder="Order" />
        </div>
      </div>
      <button class="btn btn-primary btn-sm mt-2 align-self-start" :disabled="saving">
        {{ saving ? "Saving..." : "Add recommendation" }}
      </button>
    </form>

    <div class="row g-3">
      <div class="col-12 col-lg-6" v-for="item in recommendations" :key="item.id">
        <article class="card h-100 shadow-sm reco-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h3 class="reco-title m-0">{{ item.title }}</h3>
              <button
                v-if="isAdmin"
                type="button"
                class="btn btn-outline-danger btn-sm"
                @click="removeLink(item.id)"
              >
                Delete
              </button>
            </div>
            <div class="embed-wrap" :class="{ 'embed-video': item.kind === 'youtube' }">
              <iframe
                class="player-iframe"
                :src="item.embed_url"
                :title="item.title"
                scrolling="no"
                frameborder="no"
                allow="autoplay; encrypted-media; clipboard-write; picture-in-picture; web-share"
                allowfullscreen
              ></iframe>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script>
import { onMounted } from "vue";
import { storeToRefs } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { useMusicRecommendationsViewStore } from "@/stores/views/musicRecommendationsViewStore";

export default {
  setup() {
    const store = useMusicRecommendationsViewStore();
    const { isAdmin } = storeToRefs(useUserLoginLogoutStore());
    const storeRefs = storeToRefs(store);

    onMounted(async () => {
      await store.load();
    });

    return {
      ...storeRefs,
      isAdmin,
      load: store.load,
      addLink: store.addLink,
      removeLink: store.removeLink,
    };
  },
};
</script>

<style scoped>
.reco-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.admin-add {
  border: 1px solid #d8e5f7;
  border-radius: 14px;
  background: #fff;
}

.admin-title {
  margin: 0 0 0.6rem;
  font-size: 1rem;
  font-weight: 700;
}

.reco-card {
  border: 1px solid #d9dee5;
}

.reco-title {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.embed-wrap {
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid #dbeafe;
  background: #eff6ff;
}

.embed-video {
  position: relative;
  width: 100%;
  padding-top: 56.25%;
}

.embed-video .player-iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.player-iframe {
  display: block;
  width: 100%;
  height: 170px;
}
</style>


