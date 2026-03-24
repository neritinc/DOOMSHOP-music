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
      pictureFile: null,
      picturePreviewUrl: "",
      isDraggingPicture: false,
      showUpdateModal: false,
      updateArtist: null,
      updatePictureFile: null,
      updatePicturePreviewUrl: "",
      isDraggingUpdatePicture: false,
      updating: false,
      updateError: "",
      updateErrors: {},
      deletingArtistId: null,
      actionError: "",
      formErrors: {},
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
      this.actionError = "";
      this.formErrors = {};
      const payload = new FormData();
      payload.append("artist_name", this.form.artist_name);
      if (this.form.artist_picture) payload.append("artist_picture", this.form.artist_picture);
      if (this.pictureFile) payload.append("artist_picture_file", this.pictureFile);
      try {
        await service.create(payload);
        this.form = { artist_name: "", artist_picture: "" };
        this.setPictureFile(null);
        await this.load();
      } catch (err) {
        this.formErrors = this.extractValidationErrors(err);
        this.actionError = this.extractErrorMessage(err, "Artist creation failed.");
      }
    },
    async deleteArtist(artist) {
      if (!this.isAdmin || !artist?.artist_id) return;
      const artistName = String(artist.artist_name || "").trim() || "this artist";
      const confirmed = window.confirm(`Delete artist "${artistName}"?`);
      if (!confirmed) return;

      this.deletingArtistId = artist.artist_id;
      this.actionError = "";
      this.formErrors = {};

      try {
        await service.destroy(artist.artist_id);
        await this.load();
        if (this.updateArtist?.artist_id === artist.artist_id) {
          this.closeUpdateModal();
        }
      } catch (err) {
        this.actionError = this.extractErrorMessage(err, "Artist deletion failed.");
      } finally {
        this.deletingArtistId = null;
      }
    },
    openPicturePicker() {
      this.$refs.artistPictureInputRef?.click();
    },
    setPictureFile(file) {
      this.pictureFile = file;
      if (this.picturePreviewUrl) {
        URL.revokeObjectURL(this.picturePreviewUrl);
      }
      this.picturePreviewUrl = file ? URL.createObjectURL(file) : "";
    },
    onPictureChange(event) {
      const file = event?.target?.files?.[0] || null;
      this.setPictureFile(file);
    },
    onPictureDrop(event) {
      this.isDraggingPicture = false;
      const file = event?.dataTransfer?.files?.[0] || null;
      if (!file || !String(file.type || "").startsWith("image/")) return;
      this.setPictureFile(file);
    },
    openUpdateModal(artist) {
      this.updateArtist = artist || null;
      this.updateError = "";
      this.updateErrors = {};
      this.setUpdatePictureFile(null);
      this.showUpdateModal = true;
    },
    closeUpdateModal() {
      if (this.updating) return;
      this.showUpdateModal = false;
      this.updateArtist = null;
      this.updateError = "";
      this.updateErrors = {};
      this.setUpdatePictureFile(null);
    },
    openUpdatePicturePicker() {
      this.$refs.updateArtistPictureInputRef?.click();
    },
    setUpdatePictureFile(file) {
      this.updatePictureFile = file;
      if (this.updatePicturePreviewUrl) {
        URL.revokeObjectURL(this.updatePicturePreviewUrl);
      }
      this.updatePicturePreviewUrl = file ? URL.createObjectURL(file) : "";
    },
    onUpdatePictureChange(event) {
      const file = event?.target?.files?.[0] || null;
      this.setUpdatePictureFile(file);
    },
    onUpdatePictureDrop(event) {
      this.isDraggingUpdatePicture = false;
      const file = event?.dataTransfer?.files?.[0] || null;
      if (!file || !String(file.type || "").startsWith("image/")) return;
      this.setUpdatePictureFile(file);
    },
    async saveUpdatedPicture() {
      const artist = this.updateArtist;
      if (!this.isAdmin || !artist?.artist_id || !this.updatePictureFile) return;

      this.updating = true;
      this.updateError = "";
      this.updateErrors = {};
      try {
        const payload = new FormData();
        payload.append("artist_name", String(artist.artist_name || "").trim());
        payload.append("artist_picture_file", this.updatePictureFile);
        await service.update(artist.artist_id, payload);
        await this.load();
        this.closeUpdateModal();
      } catch (err) {
        this.updateErrors = this.extractValidationErrors(err);
        this.updateError = this.extractErrorMessage(err, "Artist image update failed.");
      } finally {
        this.updating = false;
      }
    },
    extractValidationErrors(err) {
      const errors = err?.response?.data?.errors;
      if (!errors || typeof errors !== "object") return {};
      return Object.fromEntries(
        Object.entries(errors).map(([key, value]) => [
          key,
          Array.isArray(value) ? value[0] : String(value || ""),
        ]),
      );
    },
    extractErrorMessage(err, fallback) {
      const mapped = this.extractValidationErrors(err);
      const firstKey = Object.keys(mapped)[0];
      if (firstKey && mapped[firstKey]) return mapped[firstKey];
      return err?.response?.data?.message || fallback;
    },
    fieldError(source, key) {
      return source?.[key] || "";
    },
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin"]),
  },
  async mounted() {
    await this.load();
  },
  beforeUnmount() {
    if (this.picturePreviewUrl) {
      URL.revokeObjectURL(this.picturePreviewUrl);
    }
    if (this.updatePicturePreviewUrl) {
      URL.revokeObjectURL(this.updatePicturePreviewUrl);
    }
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
