import { defineStore } from "pinia";
import albumService from "@/api/albumService";
import trackService from "@/api/trackService";
import cartService from "@/api/cartService";
import { storageUrl } from "@/utils/storageUrl";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

export const useAlbumsViewStore = defineStore("albumsView", {
  state: () => ({
    albums: [],
    tracks: [],
    activeAssignAlbumId: null,
    assignTrackIds: [],
    customerCartId: null,
    addingAlbumId: null,
    cartMessageByAlbumId: {},
    cartErrorByAlbumId: {},
    coverFile: null,
    coverPreviewUrl: "",
    form: {
      title: "",
      price_eur: 0,
      release_date: "",
    },
  }),
  actions: {
    async load() {
      const [albumsRes, tracksRes] = await Promise.all([
        albumService.list(),
        trackService.list(),
      ]);
      this.albums = albumsRes.data || [];
      this.tracks = tracksRes.data || [];
    },
    coverUrl(path) {
      if (!path) return "";
      const normalized = String(path).replace(/\\/g, "/").trim();
      if (!normalized) return "";
      if (/^https?:\/\//i.test(normalized)) return normalized;
      if (normalized.includes("/")) {
        return storageUrl(normalized.replace(/^storage\//, ""));
      }
      return storageUrl(`track-covers/${normalized}`);
    },
    albumImage(album) {
      const directCover = this.coverUrl(album?.cover);
      if (directCover) return directCover;

      const firstTrackCover = this.coverUrl(album?.tracks?.[0]?.track_cover);
      if (firstTrackCover) return firstTrackCover;

      return "https://placehold.co/72x72?text=Album";
    },
    onAlbumImgError(event) {
      event.target.src = "https://placehold.co/72x72?text=Album";
    },
    onCoverChange(event) {
      const file = event?.target?.files?.[0] ?? null;
      this.coverFile = file;
      if (this.coverPreviewUrl) {
        URL.revokeObjectURL(this.coverPreviewUrl);
        this.coverPreviewUrl = "";
      }
      if (file) {
        this.coverPreviewUrl = URL.createObjectURL(file);
      }
    },
    async createAlbum() {
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isAdmin) return;
      const payload = new FormData();
      payload.append("title", this.form.title);
      payload.append("price_eur", String(Number(this.form.price_eur || 0)));
      if (this.form.release_date) payload.append("release_date", this.form.release_date);
      if (this.coverFile) payload.append("cover_file", this.coverFile);

      await albumService.create(payload);
      this.form = { title: "", price_eur: 0, release_date: "" };
      this.coverFile = null;
      if (this.coverPreviewUrl) {
        URL.revokeObjectURL(this.coverPreviewUrl);
        this.coverPreviewUrl = "";
      }
      await this.load();
    },
    isAssigning(album) {
      return Number(this.activeAssignAlbumId) === Number(album.id);
    },
    startAssign(album) {
      this.activeAssignAlbumId = album.id;
      this.assignTrackIds = (album.tracks || []).map((t) => String(t.id || t.track_id));
    },
    cancelAssign() {
      this.activeAssignAlbumId = null;
      this.assignTrackIds = [];
    },
    async saveAssign(album) {
      const ids = (this.assignTrackIds || []).map((x) => Number(x)).filter((x) => Number.isFinite(x) && x > 0);
      await albumService.syncTracks(album.id, ids);
      this.cancelAssign();
      await this.load();
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
    extractErrorMessage(err, fallback) {
      const errors = err?.response?.data?.errors;
      if (errors && typeof errors === "object") {
        const firstKey = Object.keys(errors)[0];
        const firstValue = firstKey ? errors[firstKey] : null;
        if (Array.isArray(firstValue) && firstValue.length > 0) return firstValue[0];
        if (typeof firstValue === "string" && firstValue.trim() !== "") return firstValue;
      }
      return err?.response?.data?.message || fallback;
    },
    async addAlbumToCart(album) {
      const userStore = useUserLoginLogoutStore();
      if (!userStore.isCustomer || !album?.id) return;
      this.addingAlbumId = album.id;
      this.cartMessageByAlbumId = { ...this.cartMessageByAlbumId, [album.id]: "" };
      this.cartErrorByAlbumId = { ...this.cartErrorByAlbumId, [album.id]: "" };
      try {
        if (!this.customerCartId) {
          await this.ensureCustomerCart();
        }
        if (!this.customerCartId) {
          throw new Error("Missing cart id.");
        }
        await cartService.addMyCartItem({
          cart_id: this.customerCartId,
          album_id: album.id,
          pcs: 1,
        });
        window.dispatchEvent(new Event("cart-updated"));
        this.cartMessageByAlbumId = {
          ...this.cartMessageByAlbumId,
          [album.id]: "Album added to cart.",
        };
      } catch (err) {
        this.cartErrorByAlbumId = {
          ...this.cartErrorByAlbumId,
          [album.id]: this.extractErrorMessage(err, "Could not add album to cart."),
        };
      } finally {
        this.addingAlbumId = null;
      }
    },
    cleanup() {
      if (this.coverPreviewUrl) {
        URL.revokeObjectURL(this.coverPreviewUrl);
      }
    },
  },
});
