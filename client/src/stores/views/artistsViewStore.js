import { defineStore } from "pinia";
import service from "@/api/artistService";
import { storageUrl } from "@/utils/storageUrl";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

export const useArtistsViewStore = defineStore("artistsView", {
  state: () => ({
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
    refs: {},
  }),
  actions: {
    bindRefs(refs) {
      this.refs = { ...this.refs, ...refs };
    },
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
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isAdmin) return;
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
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isAdmin || !artist?.artist_id) return;
      const artistName = String(artist.artist_name || "").trim() || "this artist";
      const confirmed = window.confirm(`Delete artist \"${artistName}\"?`);
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
      this.refs?.artistPictureInputRef?.value?.click?.();
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
      this.refs?.updateArtistPictureInputRef?.value?.click?.();
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
      const userStore = useUserLoginLogoutStore();
      const artist = this.updateArtist;
      if (!userStore.isAdmin || !artist?.artist_id || !this.updatePictureFile) return;

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
    cleanup() {
      if (this.picturePreviewUrl) {
        URL.revokeObjectURL(this.picturePreviewUrl);
      }
      if (this.updatePicturePreviewUrl) {
        URL.revokeObjectURL(this.updatePicturePreviewUrl);
      }
    },
  },
});
