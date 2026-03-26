<template>
  <div>
    <h2 class="h5">Artists</h2>
    <form v-if="isAdmin" class="card card-body mb-3" @submit.prevent="createOne">
      <input v-model="form.artist_name" class="form-control mb-2" placeholder="Artist name" required />
      <div v-if="fieldError(formErrors, 'artist_name')" class="text-danger small mb-2">{{ fieldError(formErrors, 'artist_name') }}</div>
      <div
        class="artist-dropzone mb-2"
        :class="{ 'is-over': isDraggingPicture }"
        @dragenter.prevent="isDraggingPicture = true"
        @dragover.prevent="isDraggingPicture = true"
        @dragleave.prevent="isDraggingPicture = false"
        @drop.prevent="onPictureDrop"
        @click="openPicturePicker"
      >
        <input
          ref="artistPictureInputRef"
          class="d-none"
          type="file"
          accept="image/*"
          @change="onPictureChange"
        />
        <template v-if="picturePreviewUrl">
          <img :src="picturePreviewUrl" alt="Artist preview" class="artist-preview" />
        </template>
        <template v-else>
          <p class="m-0 fw-semibold">Drop artist image here or click to browse</p>
          <small class="text-muted">JPG, PNG, WEBP - max 5 MB</small>
        </template>
      </div>
      <div v-if="fieldError(formErrors, 'artist_picture_file')" class="text-danger small mb-2">{{ fieldError(formErrors, 'artist_picture_file') }}</div>
      <button class="btn btn-primary">Add artist</button>
    </form>
    <div v-if="actionError" class="alert alert-danger py-2 mb-3">{{ actionError }}</div>

    <div class="row g-3">
      <div v-for="a in items" :key="a.artist_id" class="col-sm-6 col-lg-4 col-xl-3">
        <div class="card h-100 shadow-sm artist-card">
          <RouterLink class="artist-link" :to="`/artists/${a.artist_id}/tracks`">
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
          </RouterLink>
          <div v-if="isAdmin" class="artist-card-actions">
            <button
              type="button"
              class="update-image-btn"
              @click="openUpdateModal(a)"
            >
              Update image
            </button>
            <button
              type="button"
              class="delete-artist-btn"
              :disabled="deletingArtistId === a.artist_id"
              @click="deleteArtist(a)"
            >
              {{ deletingArtistId === a.artist_id ? "Deleting..." : "Delete artist" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showUpdateModal" class="modal-backdrop-custom" @click.self="closeUpdateModal">
        <div class="update-modal card shadow">
          <div class="card-body">
            <h3 class="h6 mb-2">Update artist image</h3>
            <p class="mb-2">Artist: <strong>{{ updateArtist?.artist_name || "-" }}</strong></p>

            <div
              class="artist-dropzone mb-2"
              :class="{ 'is-over': isDraggingUpdatePicture }"
              @dragenter.prevent="isDraggingUpdatePicture = true"
              @dragover.prevent="isDraggingUpdatePicture = true"
              @dragleave.prevent="isDraggingUpdatePicture = false"
              @drop.prevent="onUpdatePictureDrop"
              @click="openUpdatePicturePicker"
            >
              <input
                ref="updateArtistPictureInputRef"
                class="d-none"
                type="file"
                accept="image/*"
                @change="onUpdatePictureChange"
              />
              <template v-if="updatePicturePreviewUrl">
                <img :src="updatePicturePreviewUrl" alt="Artist update preview" class="artist-preview" />
              </template>
              <template v-else>
                <p class="m-0 fw-semibold">Drop artist image here or click to browse</p>
                <small class="text-muted">JPG, PNG, WEBP - max 5 MB</small>
              </template>
            </div>

            <div v-if="updateError" class="alert alert-danger py-2 mb-2">{{ updateError }}</div>
            <div v-if="fieldError(updateErrors, 'artist_picture_file')" class="text-danger small mb-2">{{ fieldError(updateErrors, 'artist_picture_file') }}</div>
            <div class="d-flex gap-2 justify-content-end">
              <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="updating" @click="closeUpdateModal">
                Cancel
              </button>
              <button class="btn btn-primary btn-sm" type="button" :disabled="updating || !updatePictureFile" @click="saveUpdatedPicture">
                {{ updating ? "Saving..." : "Save image" }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import { useArtistsViewStore } from "@/stores/views/artistsViewStore";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { RouterLink } from "vue-router";

export default {
  components: { RouterLink },
  setup() {
    const store = useArtistsViewStore();
    const { isAdmin } = storeToRefs(useUserLoginLogoutStore());
    const storeRefs = storeToRefs(store);

    const artistPictureInputRef = ref(null);
    const updateArtistPictureInputRef = ref(null);

    store.bindRefs({ artistPictureInputRef, updateArtistPictureInputRef });

    onMounted(async () => {
      await store.load();
    });

    onBeforeUnmount(() => {
      store.cleanup();
    });

    return {
      ...storeRefs,
      ...store,
      isAdmin,
      artistPictureInputRef,
      updateArtistPictureInputRef,
    };
  },
};
</script>

<style scoped>
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.55);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.update-modal {
  width: min(520px, 96vw);
  border: 1px solid #d7e4f7;
  border-radius: 14px;
  background: #ffffff;
  position: relative;
  z-index: 3001;
}

.artist-dropzone {
  min-height: 120px;
  border: 2px dashed #93c5fd;
  border-radius: 12px;
  background: linear-gradient(180deg, #eff6ff 0%, #edf3fc 100%);
  color: #1e3a8a;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 0.75rem;
  cursor: pointer;
}

.artist-dropzone.is-over {
  border-color: #2563eb;
  background: #dbeafe;
}

.artist-preview {
  max-width: 100%;
  max-height: 180px;
  object-fit: cover;
  border-radius: 10px;
}

.artist-card {
  border: 1px solid #d9dee5;
  background: #ffffff;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.artist-link {
  text-decoration: none;
  color: inherit;
  display: block;
  flex: 1 1 auto;
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
  min-height: 104px;
  display: flex;
  flex-direction: column;
  justify-content: center;
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

.artist-card-actions {
  padding: 0 0.85rem 0.95rem;
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  justify-content: center;
}

.update-image-btn {
  width: min(210px, 100%);
  border: 1px solid #bfd2ee;
  border-radius: 999px;
  background: linear-gradient(180deg, #ffffff 0%, #f2f7ff 100%);
  color: #334155;
  font-size: 0.83rem;
  font-weight: 700;
  padding: 0.46rem 0.72rem;
  line-height: 1.2;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
}

.update-image-btn:hover {
  background: #eaf2ff;
  border-color: #9fbee8;
}

.delete-artist-btn {
  width: min(210px, 100%);
  border: 1px solid #f1b9b9;
  border-radius: 999px;
  background: linear-gradient(180deg, #fff7f7 0%, #ffeaea 100%);
  color: #b42318;
  font-size: 0.83rem;
  font-weight: 700;
  padding: 0.46rem 0.72rem;
  line-height: 1.2;
  box-shadow: 0 2px 6px rgba(180, 35, 24, 0.08);
}

.delete-artist-btn:hover:not(:disabled) {
  background: #ffe2e2;
  border-color: #e7a4a4;
}

.delete-artist-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .artist-card-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .update-image-btn,
  .delete-artist-btn {
    width: 100%;
  }

  .artist-cover {
    width: 82%;
  }

  .artist-meta {
    min-height: auto;
  }
}
</style>

