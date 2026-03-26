import { defineStore } from "pinia";
import { markRaw } from "vue";
import service from "@/api/trackService";
import genreService from "@/api/genreService";
import artistService from "@/api/artistService";
import albumService from "@/api/albumService";
import cartService from "@/api/cartService";
import { storageUrl } from "@/utils/storageUrl";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

const emptyEdit = () => ({
  track_title: "",
  album_id: "",
  album_title: "",
  genre_names: [""],
  bpm_value: null,
  track_price_eur: 1.99,
  release_date: "",
  track_length_sec: null,
  preview_start_at: 0,
  preview_end_at: 30,
  artist_names: [""],
});

export const useTrackDetailViewStore = defineStore("trackDetailView", {
  state: () => ({
    loading: true,
    error: "",
    track: null,
    genres: [],
    artists: [],
    albums: [],
    edit: emptyEdit(),
    saving: false,
    deleting: false,
    showDeleteModal: false,
    editError: "",
    editSuccess: "",
    audioFile: null,
    coverFile: null,
    coverPreviewUrl: "",
    isDraggingCover: false,
    audioPreviewUrl: "",
    isPreviewPlaying: false,
    previewStopTimer: null,
    previewSeekTime: 0,
    adminIsPlaying: false,
    adminDuration: 0,
    adminCurrentTime: 0,
    adminError: "",
    addingToCart: false,
    customerCartId: null,
    cartMessage: "",
    cartError: "",
    refs: {},
  }),
  getters: {
    previewDuration(state) {
      return Math.max(0, Number(state.edit.preview_end_at || 0) - Number(state.edit.preview_start_at || 0));
    },
    adminSeekMax(state) {
      const loaded = Number(state.adminDuration || 0);
      if (Number.isFinite(loaded) && loaded > 0) return loaded;
      const fallback = Number(state.track?.track_length_sec || 0);
      return Number.isFinite(fallback) && fallback > 0 ? fallback : 0;
    },
  },
  actions: {
    resolveDomRef(refOrElement) {
      if (refOrElement && typeof refOrElement === "object" && "value" in refOrElement) {
        return refOrElement.value || null;
      }
      return refOrElement || null;
    },
    bindRefs(refs) {
      this.refs = markRaw({ ...this.refs, ...refs });
    },
    coverUrl(file) {
      if (!file) return "https://placehold.co/700x700?text=Track";
      if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
      if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
      return storageUrl(`track-covers/${String(file).replace(/\\/g, "/").trim()}`);
    },
    audioUrl(path) {
      return path ? storageUrl(path) : "";
    },
    adminSourceUrl(track) {
      const id = this.trackId(track);
      if (!id) return "";
      const apiBase = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
      const pathPart = track?.track_path || "";
      const cacheKey = encodeURIComponent(pathPart);
      return `${apiBase}/tracks/${id}/source?v=${cacheKey}`;
    },
    previewUrl(track) {
      const id = this.trackId(track);
      if (!id) return "";
      const apiBase = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
      const pathPart = track?.preview_path || "";
      const start = track?.preview_start_at ?? "";
      const end = track?.preview_end_at ?? "";
      const v = encodeURIComponent(`${pathPart}|${start}|${end}`);
      return `${apiBase}/tracks/${id}/preview?v=${v}`;
    },
    artistNames(track) {
      return (track.artists || []).map((a) => a.artist_name).join(", ") || "-";
    },
    genreNames(track) {
      const list = (track.genres || [])
        .map((g) => g?.genre_name)
        .filter((name) => String(name || "").trim() !== "");
      if (list.length === 0 && track?.genre?.genre_name) {
        list.push(track.genre.genre_name);
      }
      return list.length > 0 ? list.join(", ") : "-";
    },
    firstArtist(track) {
      return track?.artists?.[0]?.artist_name || "Artist";
    },
    trackId(track) {
      return track?.id ?? track?.track_id;
    },
    onImgError(e) {
      e.target.src = "https://placehold.co/700x700?text=No+Cover";
    },
    formatLength(value) {
      const total = Number(value);
      if (!Number.isFinite(total) || total <= 0) return "-";
      const seconds = Math.floor(total);
      const min = Math.floor(seconds / 60);
      const sec = seconds % 60;
      return `${min}:${String(sec).padStart(2, "0")}`;
    },
    formatTime(value) {
      const total = Math.max(0, Math.floor(Number(value) || 0));
      const min = Math.floor(total / 60);
      const sec = total % 60;
      return `${min}:${String(sec).padStart(2, "0")}`;
    },
    formatPrice(value) {
      const amount = Number(value);
      const safeAmount = Number.isFinite(amount) ? amount : 1.99;
      return `€${safeAmount.toFixed(2)}`;
    },
    initEditFromTrack() {
      const t = this.track || {};
      this.edit = {
        track_title: t.track_title || "",
        album_id: t.album?.id ? String(t.album.id) : "",
        album_title: "",
        genre_names: this.extractTrackGenreNames(t),
        bpm_value: t.bpm_value || null,
        track_price_eur: Number(t.track_price_eur ?? 1.99),
        release_date: t.release_date || "",
        track_length_sec: t.track_length_sec || null,
        preview_start_at: Number(t.preview_start_at ?? 0),
        preview_end_at: Number(t.preview_end_at ?? 30),
        artist_names: (t.artists || []).map((a) => String(a?.artist_name || "").trim()).filter(Boolean),
      };
      if (this.edit.genre_names.length === 0) this.edit.genre_names = [""];
      if (this.edit.artist_names.length === 0) this.edit.artist_names = [""];
      this.audioPreviewUrl = this.previewUrl(t);
      this.previewSeekTime = 0;
      this.coverPreviewUrl = "";
      this.audioFile = null;
      this.coverFile = null;
      const fallback = Number(t.track_length_sec || 0);
      this.adminDuration = Number.isFinite(fallback) && fallback > 0 ? fallback : 0;
      this.resetAdminPlayer();
    },
    resetAdminPlayer(audioElement = null) {
      const audio = audioElement || this.resolveDomRef(this.refs?.adminTrackAudioRef);
      if (audio) {
        audio.pause();
        audio.currentTime = 0;
      }
      this.adminIsPlaying = false;
      this.adminCurrentTime = 0;
      this.adminError = "";
    },
    async toggleAdminTrackPlay(audioElement = null) {
      const audio = audioElement || this.resolveDomRef(this.refs?.adminTrackAudioRef);
      if (!audio) {
        this.adminError = "The admin player could not be initialized.";
        return;
      }
      this.adminError = "";
      if (this.adminIsPlaying) {
        audio.pause();
        this.adminIsPlaying = false;
        return;
      }
      try {
        if (!audio.src) {
          this.adminError = "The full track source URL is missing.";
          return;
        }
        if (Number(audio.readyState || 0) === 0) {
          audio.load();
        }
        if (Number.isFinite(this.adminCurrentTime) && this.adminCurrentTime > 0) {
          audio.currentTime = this.adminCurrentTime;
        }
        await audio.play();
        this.adminIsPlaying = true;
      } catch (error) {
        this.adminIsPlaying = false;
        this.adminError = "The full track could not be played.";
        console.error("Admin track playback failed", error);
      }
    },
    onAdminLoadedMetadata() {
      this.adminError = "";
      const audio = this.resolveDomRef(this.refs?.adminTrackAudioRef);
      const loaded = Number(audio?.duration || 0);
      const fallback = Number(this.track?.track_length_sec || 0);
      this.adminDuration = Number.isFinite(loaded) && loaded > 0
        ? loaded
        : (Number.isFinite(fallback) && fallback > 0 ? fallback : 0);
    },
    onAdminTimeUpdate() {
      const audio = this.resolveDomRef(this.refs?.adminTrackAudioRef);
      this.adminCurrentTime = Number(audio?.currentTime || 0);
    },
    onAdminEnded() {
      this.adminIsPlaying = false;
      this.adminCurrentTime = 0;
    },
    onAdminError() {
      this.adminIsPlaying = false;
      this.adminError = "The full track file could not be loaded.";
    },
    onAdminSeekInput(event, audioElement = null) {
      const next = Number(event?.target?.value || 0);
      const audio = audioElement || this.resolveDomRef(this.refs?.adminTrackAudioRef);
      if (!audio) return;
      const max = Number(this.adminSeekMax || 0);
      const clamped = Math.max(0, Math.min(max, next));
      audio.currentTime = clamped;
      this.adminCurrentTime = clamped;
    },
    extractTrackGenreNames(track) {
      const names = (track?.genres || [])
        .map((g) => String(g?.genre_name || "").trim())
        .filter(Boolean);
      if (names.length === 0) {
        const fallback = String(track?.genre?.genre_name || "").trim();
        if (fallback) names.push(fallback);
      }
      return names;
    },
    addGenreField() {
      this.edit.genre_names.push("");
    },
    removeGenreField(index) {
      this.edit.genre_names.splice(index, 1);
      if (this.edit.genre_names.length === 0) this.edit.genre_names = [""];
    },
    addArtistField() {
      this.edit.artist_names.push("");
    },
    removeArtistField(index) {
      this.edit.artist_names.splice(index, 1);
      if (this.edit.artist_names.length === 0) this.edit.artist_names = [""];
    },
    previewWindowSize() {
      const length = Number(this.edit.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0) return Math.min(30, Math.floor(length));
      return 30;
    },
    onPreviewStartInput() {
      let start = Number(this.edit.preview_start_at || 0);
      start = Number.isFinite(start) ? Math.max(0, Math.floor(start)) : 0;
      const windowSize = this.previewWindowSize();
      let end = start + windowSize;
      const length = Number(this.edit.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0 && end > length) {
        end = length;
        start = Math.max(0, end - windowSize);
      }
      this.edit.preview_start_at = start;
      this.edit.preview_end_at = end;
    },
    onPreviewEndInput() {
      let end = Number(this.edit.preview_end_at || 0);
      end = Number.isFinite(end) ? Math.max(1, Math.floor(end)) : 30;
      const length = Number(this.edit.track_length_sec || 0);
      if (Number.isFinite(length) && length > 0) end = Math.min(end, length);
      const windowSize = this.previewWindowSize();
      const start = Math.max(0, end - windowSize);
      this.edit.preview_start_at = start;
      this.edit.preview_end_at = end;
    },
    clearPreviewTimer() {
      if (this.previewStopTimer) {
        clearTimeout(this.previewStopTimer);
        this.previewStopTimer = null;
      }
    },
    stopPreviewSegment() {
      this.clearPreviewTimer();
      const audio = this.resolveDomRef(this.refs?.previewAudioRef);
      if (audio) audio.pause();
      this.isPreviewPlaying = false;
      this.previewSeekTime = 0;
    },
    playPreviewSegment() {
      if (!this.audioPreviewUrl) return;
      const durationMs = Math.max(0, Number(this.previewDuration || 0) * 1000);
      if (durationMs <= 0) return;
      const audio = this.resolveDomRef(this.refs?.previewAudioRef);
      if (!audio) return;
      this.stopPreviewSegment();
      audio.currentTime = 0;
      this.previewSeekTime = 0;
      audio.play();
      this.isPreviewPlaying = true;
      this.previewStopTimer = setTimeout(() => this.stopPreviewSegment(), durationMs + 80);
    },
    async regeneratePreviewSegment() {
      const user = useUserLoginLogoutStore();
      if (!user.isAdmin || !this.track) return;
      this.editError = "";
      this.editSuccess = "";
      try {
        const res = await service.regeneratePreview(this.trackId(this.track), {
          preview_start_at: Number(this.edit.preview_start_at || 0),
          preview_end_at: Number(this.edit.preview_end_at || 0),
        });
        const refreshed = await service.show(this.trackId(this.track));
        this.track = refreshed.data || res.data || this.track;
        this.initEditFromTrack();
        this.editSuccess = "Preview regenerated successfully.";
      } catch (err) {
        this.editError = this.toValidationError(err);
      }
    },
    onPreviewSeekInput(event) {
      const next = Number(event?.target?.value || 0);
      const max = Number(this.previewDuration || 0);
      const clamped = Math.max(0, Math.min(max, next));
      this.previewSeekTime = clamped;
      const audio = this.resolveDomRef(this.refs?.previewAudioRef);
      if (audio) audio.currentTime = clamped;
    },
    onPreviewTimeUpdate() {
      const audio = this.resolveDomRef(this.refs?.previewAudioRef);
      if (!audio) return;
      this.previewSeekTime = Number(audio.currentTime || 0);
      if (this.previewSeekTime >= Number(this.previewDuration || 0)) this.stopPreviewSegment();
    },
    async onAudioChange(event) {
      const file = event.target.files?.[0] || null;
      this.audioFile = file;
      if (!file) return;
      this.audioPreviewUrl = URL.createObjectURL(file);
      this.previewSeekTime = Number(this.edit.preview_start_at || 0);
      const audio = document.createElement("audio");
      audio.preload = "metadata";
      audio.src = URL.createObjectURL(file);
      audio.onloadedmetadata = async () => {
        const duration = Math.floor(audio.duration || 0);
        if (duration > 0) {
          this.edit.track_length_sec = duration;
          this.onPreviewStartInput();
        }
        URL.revokeObjectURL(audio.src);
      };
      await this.analyzeAudioFile(file);
    },
    async analyzeAudioFile(file) {
      try {
        const res = await service.analyzeUpload(file);
        const data = res.data || {};
        if (data.bpm_value && !this.edit.bpm_value) this.edit.bpm_value = data.bpm_value;
      } catch (_err) {
      }
    },
    openCoverPicker() {
      this.resolveDomRef(this.refs?.coverInputRef)?.click?.();
    },
    syncCoverInputFile(file) {
      const input = this.resolveDomRef(this.refs?.coverInputRef);
      if (!input || !file) return;
      try {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
      } catch (_err) {
      }
    },
    setCoverFile(file) {
      this.coverFile = file;
      if (this.coverPreviewUrl) URL.revokeObjectURL(this.coverPreviewUrl);
      this.coverPreviewUrl = file ? URL.createObjectURL(file) : "";
    },
    onCoverChange(event) {
      const file = event.target.files?.[0] || null;
      this.setCoverFile(file);
    },
    onCoverDrop(event) {
      event?.preventDefault?.();
      event?.stopPropagation?.();
      this.isDraggingCover = false;
      const itemFile = event.dataTransfer?.items?.[0]?.getAsFile?.() || null;
      const file = itemFile || event.dataTransfer?.files?.[0] || null;
      if (!file) return;
      if (!file.type.startsWith("image/")) {
        this.editError = "Only image files can be used as track cover.";
        return;
      }
      this.editError = "";
      this.syncCoverInputFile(file);
      this.setCoverFile(file);
    },
    toValidationError(err) {
      const errors = err?.response?.data?.errors;
      if (!errors || typeof errors !== "object") return err?.response?.data?.message || "Save failed.";
      const firstKey = Object.keys(errors)[0];
      if (!firstKey) return "Validation error.";
      const firstValue = errors[firstKey];
      if (Array.isArray(firstValue) && firstValue.length > 0) return firstValue[0];
      return "Validation error.";
    },
    async load(id) {
      const user = useUserLoginLogoutStore();
      this.loading = true;
      this.error = "";
      try {
        const [trackRes, genresRes, artistsRes, albumsRes] = await Promise.all([
          service.show(id),
          genreService.list(),
          artistService.list(),
          albumService.list(),
        ]);
        this.track = trackRes.data || null;
        this.genres = genresRes.data || [];
        this.artists = artistsRes.data || [];
        this.albums = albumsRes.data || [];
        this.initEditFromTrack();
        if (user.isCustomer) {
          await this.ensureCustomerCart();
        }
      } catch (err) {
        this.error = err?.response?.data?.message || "Track loading failed.";
      } finally {
        this.loading = false;
      }
    },
    async ensureCustomerCart() {
      const cartsRes = await cartService.myCarts();
      const carts = cartsRes?.data || [];
      if (carts.length > 0) {
        this.customerCartId = carts[0].id;
        return;
      }
      const today = new Date().toISOString().slice(0, 10);
      const created = await cartService.createMyCart({ date: today });
      this.customerCartId = created?.data?.id || null;
    },
    async addTrackToCart() {
      const user = useUserLoginLogoutStore();
      if (!user.isCustomer || !this.track) return;
      this.addingToCart = true;
      this.cartMessage = "";
      this.cartError = "";
      try {
        if (!this.customerCartId) {
          await this.ensureCustomerCart();
        }
        const id = this.trackId(this.track);
        if (!id || !this.customerCartId) {
          throw new Error("Missing cart or track id.");
        }
        await cartService.addMyCartItem({
          cart_id: this.customerCartId,
          track_id: id,
          pcs: 1,
        });
        this.cartMessage = "Track added to cart.";
      } catch (err) {
        this.cartError = err?.response?.data?.message || "Could not add track to cart.";
      } finally {
        this.addingToCart = false;
      }
    },
    async saveTrack() {
      const user = useUserLoginLogoutStore();
      if (!user.isAdmin || !this.track) return;
      this.editError = "";
      this.editSuccess = "";
      this.saving = true;
      try {
        const payload = new FormData();
        payload.append("track_title", this.edit.track_title);
        if (this.edit.album_id) {
          payload.append("album_id", String(this.edit.album_id));
        } else if (String(this.edit.album_title || "").trim() !== "") {
          payload.append("album_title", String(this.edit.album_title).trim());
        }
        const genres = [...new Set((this.edit.genre_names || []).map((x) => String(x || "").trim()).filter(Boolean))];
        genres.forEach((name, index) => payload.append(`genre_names[${index}]`, name));
        payload.append("preview_start_at", String(this.edit.preview_start_at));
        payload.append("preview_end_at", String(this.edit.preview_end_at));
        if (this.edit.bpm_value) payload.append("bpm_value", String(this.edit.bpm_value));
        payload.append("track_price_eur", String(Number(this.edit.track_price_eur ?? 1.99).toFixed(2)));
        if (this.edit.release_date) payload.append("release_date", this.edit.release_date);
        if (this.edit.track_length_sec) payload.append("track_length_sec", String(this.edit.track_length_sec));
        const artists = (this.edit.artist_names || []).map((x) => String(x || "").trim()).filter(Boolean);
        artists.forEach((name, index) => payload.append(`artist_names[${index}]`, name));
        if (this.audioFile) payload.append("track_audio", this.audioFile);
        if (this.coverFile) payload.append("track_cover_file", this.coverFile);
        const res = await service.update(this.trackId(this.track), payload);
        const refreshed = await service.show(this.trackId(this.track));
        this.track = refreshed.data || res.data || this.track;
        this.initEditFromTrack();
        this.editSuccess = "Track updated successfully.";
      } catch (err) {
        this.editError = this.toValidationError(err);
      } finally {
        this.saving = false;
      }
    },
    deleteTrack() {
      const user = useUserLoginLogoutStore();
      if (!user.isAdmin || !this.track || this.deleting) return;
      this.showDeleteModal = true;
    },
    cancelDelete() {
      if (this.deleting) return;
      this.showDeleteModal = false;
    },
    async confirmDeleteTrack(router) {
      const user = useUserLoginLogoutStore();
      if (!user.isAdmin || !this.track) return;
      const id = this.trackId(this.track);
      if (!id) return;
      this.deleting = true;
      this.editError = "";
      this.editSuccess = "";
      try {
        await service.destroy(id);
        this.showDeleteModal = false;
        router.push("/tracks");
      } catch (err) {
        this.editError = err?.response?.data?.message || "Delete failed.";
      } finally {
        this.deleting = false;
      }
    },
    cleanup() {
      this.resetAdminPlayer();
      this.stopPreviewSegment();
      if (this.coverPreviewUrl) URL.revokeObjectURL(this.coverPreviewUrl);
      if (this.audioFile && this.audioPreviewUrl && this.audioPreviewUrl.startsWith("blob:")) {
        URL.revokeObjectURL(this.audioPreviewUrl);
      }
    },
  },
});


