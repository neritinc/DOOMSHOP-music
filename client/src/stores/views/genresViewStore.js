import { defineStore } from "pinia";
import service from "@/api/genreService";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

export const useGenresViewStore = defineStore("genresView", {
  state: () => ({
    items: [],
    name: "",
    editName: "",
    editingGenreId: null,
    savingGenreId: null,
    deletingGenreId: null,
    isDeleteModalOpen: false,
    genreToDelete: null,
    actionError: "",
    validationErrors: {},
  }),
  getters: {
    deleteMessage(state) {
      const genreName = String(state.genreToDelete?.genre_name || "").trim();
      if (!genreName) {
        return "Are you sure you want to delete this genre?";
      }
      return `Are you sure you want to delete this genre: "${genreName}"?`;
    },
  },
  actions: {
    async load() {
      const res = await service.list();
      this.items = res.data || [];
    },
    async createOne() {
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isAdmin) return;
      this.actionError = "";
      this.validationErrors = {};
      try {
        await service.create({ genre_name: this.name });
        this.name = "";
        await this.load();
      } catch (err) {
        this.validationErrors = this.extractValidationErrors(err);
        this.actionError = this.extractErrorMessage(err, "Genre creation failed.");
      }
    },
    startEdit(genre) {
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isAdmin || !genre?.genre_id) return;
      this.actionError = "";
      this.validationErrors = {};
      this.editingGenreId = genre.genre_id;
      this.editName = String(genre.genre_name || "");
    },
    cancelEdit() {
      this.editingGenreId = null;
      this.editName = "";
      this.savingGenreId = null;
      this.validationErrors = {};
    },
    async saveEdit(genre) {
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isAdmin || !genre?.genre_id) return;

      const nextName = String(this.editName || "").trim();
      if (!nextName) {
        this.actionError = "Genre name is required.";
        return;
      }

      this.actionError = "";
      this.validationErrors = {};
      this.savingGenreId = genre.genre_id;

      try {
        await service.update(genre.genre_id, { genre_name: nextName });
        await this.load();
        this.cancelEdit();
      } catch (err) {
        this.validationErrors = this.extractValidationErrors(err);
        this.actionError = this.extractErrorMessage(err, "Genre update failed.");
      } finally {
        if (this.savingGenreId === genre.genre_id) {
          this.savingGenreId = null;
        }
      }
    },
    askDelete(genre) {
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isAdmin || !genre?.genre_id) return;
      this.actionError = "";
      this.validationErrors = {};
      this.genreToDelete = genre;
      this.isDeleteModalOpen = true;
    },
    closeDeleteModal() {
      this.isDeleteModalOpen = false;
      this.genreToDelete = null;
    },
    async confirmDelete() {
      const userStore = useUserLoginLogoutStore();
      const genre = this.genreToDelete;
      if (!userStore.isAdmin || !genre?.genre_id) {
        this.closeDeleteModal();
        return;
      }

      this.actionError = "";
      this.validationErrors = {};
      this.deletingGenreId = genre.genre_id;

      try {
        await service.destroy(genre.genre_id);
        if (this.editingGenreId === genre.genre_id) {
          this.cancelEdit();
        }
        await this.load();
        this.closeDeleteModal();
      } catch (err) {
        this.validationErrors = this.extractValidationErrors(err);
        this.actionError = this.extractErrorMessage(err, "Genre deletion failed.");
      } finally {
        this.deletingGenreId = null;
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
    fieldError(key) {
      return this.validationErrors?.[key] || "";
    },
  },
});

