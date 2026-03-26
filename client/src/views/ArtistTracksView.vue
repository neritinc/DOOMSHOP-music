<template>
  <div>
    <RouterLink class="btn btn-outline-secondary btn-sm mb-3" to="/artists">Back to artists</RouterLink>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 m-0">{{ artistName }}</h2>
      <span class="badge text-bg-dark">{{ filteredTracks.length }} tracks</span>
    </div>

    <div v-if="loading" class="alert alert-info">Loading tracks...</div>
    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else class="row g-3">
      <div class="col-sm-6 col-lg-4 col-xl-3" v-for="t in filteredTracks" :key="trackId(t)">
        <div class="card h-100 shadow-sm track-card">
          <img class="card-img-top track-cover" :src="coverUrl(t.track_cover)" :alt="t.track_title" @error="onImgError" />
          <div class="card-body d-flex flex-column track-meta">
            <h3 class="h6 card-title mb-2 track-title">{{ t.track_title }}</h3>
            <p class="mb-2 track-artist">Artist: {{ artistNames(t) }}</p>
            <div class="mt-auto">
              <RouterLink class="btn btn-sm btn-outline-secondary mt-2" :to="`/tracks/${trackId(t)}`">Open details</RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, onMounted } from "vue";
import { storeToRefs } from "pinia";
import { RouterLink, useRoute } from "vue-router";
import { useArtistTracksViewStore } from "@/stores/views/artistTracksViewStore";

export default {
  components: { RouterLink },
  setup() {
    const store = useArtistTracksViewStore();
    const route = useRoute();
    const storeRefs = storeToRefs(store);
    const artistId = computed(() => Number(route.params.id));
    const filteredTracks = computed(() => store.filteredTracks(artistId.value));

    onMounted(async () => {
      await store.load(artistId.value);
    });

    return {
      ...storeRefs,
      artistId,
      filteredTracks,
      load: store.load,
      coverUrl: store.coverUrl,
      onImgError: store.onImgError,
      trackId: store.trackId,
      artistNames: store.artistNames,
    };
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
  line-height: 1.2;
}

.track-artist {
  color: #4b5563;
  font-weight: 600;
}
</style>

