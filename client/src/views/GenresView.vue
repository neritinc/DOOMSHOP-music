<template>
  <div>
    <h2 class="h5">Genres</h2>
    <form v-if="isAdmin" class="card card-body mb-3" @submit.prevent="createOne">
      <input v-model="name" class="form-control mb-2" placeholder="Genre name" required />
      <div v-if="fieldError('genre_name')" class="text-danger small mb-2">{{ fieldError('genre_name') }}</div>
      <button class="btn btn-primary align-self-start">Add genre</button>
    </form>
    <div v-if="actionError" class="alert alert-danger py-2 mb-3">{{ actionError }}</div>

    <div class="row g-3">
      <div v-for="g in items" :key="g.genre_id" class="col-sm-6 col-lg-4 col-xl-3">
        <div class="card h-100 shadow-sm genre-card">
          <RouterLink
            v-if="editingGenreId !== g.genre_id"
            class="genre-card-link"
            :to="{ path: '/tracks', query: { genre: g.genre_name } }"
          >
            <div class="card-body genre-meta">
              <h3 class="genre-name">{{ g.genre_name }}</h3>
              <p v-if="isAdmin" class="genre-extra mb-0">ID: {{ g.genre_id }}</p>
            </div>
          </RouterLink>

          <div v-else class="card-body genre-meta">
            <label class="form-label text-start w-100 mb-1 fw-semibold" :for="`genre-name-${g.genre_id}`">
              Genre name
            </label>
            <input
              :id="`genre-name-${g.genre_id}`"
              v-model="editName"
              class="form-control"
              placeholder="Genre name"
              maxlength="255"
              @keyup.esc="cancelEdit"
            />
            <div v-if="fieldError('genre_name')" class="text-danger small mt-2 text-start w-100">{{ fieldError('genre_name') }}</div>
            <p class="genre-extra mt-2 mb-0 text-start">ID: {{ g.genre_id }}</p>
          </div>

          <div v-if="isAdmin" class="genre-card-actions">
            <template v-if="editingGenreId === g.genre_id">
              <button
                type="button"
                class="genre-action-btn genre-save-btn"
                :disabled="savingGenreId === g.genre_id"
                @click="saveEdit(g)"
              >
                {{ savingGenreId === g.genre_id ? "Saving..." : "Save" }}
              </button>
              <button
                type="button"
                class="genre-action-btn genre-cancel-btn"
                :disabled="savingGenreId === g.genre_id"
                @click="cancelEdit"
              >
                Cancel
              </button>
            </template>

            <template v-else>
              <button
                type="button"
                class="genre-action-btn genre-edit-btn"
                @click="startEdit(g)"
              >
                Edit
              </button>
              <button
                type="button"
                class="genre-action-btn genre-delete-btn"
                :disabled="deletingGenreId === g.genre_id"
                @click="askDelete(g)"
              >
                {{ deletingGenreId === g.genre_id ? "Deleting..." : "Delete" }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <ConfirmModal
      :isOpenConfirmModal="isDeleteModalOpen"
      title="Delete Genre"
      :message="deleteMessage"
      cancel="Cancel"
      confirm="Delete"
      @cancel="closeDeleteModal"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script>
import { onMounted } from "vue";
import { storeToRefs } from "pinia";
import { useGenresViewStore } from "@/stores/views/genresViewStore";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import ConfirmModal from "@/components/Confirm/ConfirmModal.vue";
import { RouterLink } from "vue-router";

export default {
  components: { ConfirmModal, RouterLink },
  setup() {
    const store = useGenresViewStore();
    const { isAdmin } = storeToRefs(useUserLoginLogoutStore());
    const storeRefs = storeToRefs(store);

    onMounted(async () => {
      await store.load();
    });

    const {
      load,
      createOne,
      startEdit,
      cancelEdit,
      saveEdit,
      askDelete,
      closeDeleteModal,
      confirmDelete,
      fieldError,
    } = store;

    return {
      ...storeRefs,
      isAdmin,
      load,
      createOne,
      startEdit,
      cancelEdit,
      saveEdit,
      askDelete,
      closeDeleteModal,
      confirmDelete,
      fieldError,
    };
  },
};
</script>

<style scoped>
.genre-card-link {
  display: block;
  text-decoration: none;
  color: inherit;
}

.genre-card {
  border: 1px solid #111111;
  border-radius: 18px;
  background: linear-gradient(180deg, #111111 0%, #3a3a3a 100%);
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.16);
}

.genre-card:hover {
  transform: translateY(-3px);
  border-color: #5a5a5a;
  box-shadow: 0 18px 34px rgba(0, 0, 0, 0.28) !important;
}

.genre-meta {
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 132px;
  padding: 1.2rem;
}

.genre-name {
  margin: 0;
  font-size: 1.08rem;
  line-height: 1.25;
  letter-spacing: 0.12em;
  color: #ffffff;
  font-weight: 700;
  text-transform: uppercase;
}

.genre-extra {
  margin-top: 0.7rem;
  font-size: 0.74rem;
  color: rgba(255, 255, 255, 0.68);
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.genre-card-actions {
  padding: 0 0.9rem 0.95rem;
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  justify-content: center;
}

.genre-action-btn {
  min-width: 110px;
  border-radius: 999px;
  font-size: 0.83rem;
  font-weight: 700;
  padding: 0.46rem 0.78rem;
  line-height: 1.2;
  transition: background 0.15s ease, border-color 0.15s ease, opacity 0.15s ease, color 0.15s ease;
}

.genre-action-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.genre-edit-btn,
.genre-save-btn {
  border: 1px solid rgba(255, 255, 255, 0.28);
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.genre-edit-btn:hover,
.genre-save-btn:hover {
  background: rgba(255, 255, 255, 0.16);
  border-color: rgba(255, 255, 255, 0.42);
}

.genre-cancel-btn {
  border: 1px solid rgba(255, 255, 255, 0.28);
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.genre-cancel-btn:hover {
  background: rgba(255, 255, 255, 0.16);
  border-color: rgba(255, 255, 255, 0.42);
}

.genre-delete-btn {
  border: 1px solid rgba(255, 255, 255, 0.28);
  background: rgba(255, 255, 255, 0.04);
  color: #ffffff;
}

.genre-delete-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.16);
  border-color: rgba(255, 255, 255, 0.42);
  color: #ffffff;
}

@media (max-width: 768px) {
  .genre-card-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .genre-action-btn {
    width: 100%;
  }

  .genre-meta {
    min-height: 110px;
    padding: 1rem;
  }
}
</style>


