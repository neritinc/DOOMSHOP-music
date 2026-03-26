import { defineStore } from "pinia";
import service from "@/api/cartService";
import trackService from "@/api/trackService";
import albumService from "@/api/albumService";
import { storageUrl } from "@/utils/storageUrl";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

export const useMyCartViewStore = defineStore("myCartView", {
  state: () => ({
    carts: [],
    cartItems: [],
    tracks: [],
    albums: [],
    cartId: null,
    showDeleteModal: false,
    itemToDelete: null,
    deletingItem: false,
    deleteError: "",
    checkingOut: false,
    checkoutError: "",
    checkoutResult: null,
    showCheckoutModal: false,
    checkoutEmail: "",
    checkoutEmailError: "",
  }),
  getters: {
    selectedCartItems(state) {
      if (!state.cartId) return [];
      return (state.cartItems || []).filter((item) => Number(item.cart_id) === Number(state.cartId));
    },
  },
  actions: {
    async load() {
      const [cartsRes, itemsRes, tracksRes, albumsRes] = await Promise.all([
        service.myCarts(),
        service.myCartItems(),
        trackService.list(),
        albumService.list(),
      ]);
      this.carts = cartsRes.data || [];
      this.cartItems = itemsRes.data || [];
      this.tracks = tracksRes.data || [];
      this.albums = albumsRes.data || [];
      const cartIds = new Set((this.carts || []).map((c) => Number(c.id)));
      if (!this.cartId || !cartIds.has(Number(this.cartId))) {
        this.cartId = this.carts.length > 0 ? this.carts[0].id : null;
      }
    },
    itemLabel(item) {
      if (item.track_id) {
        const found = this.findTrack(item.track_id);
        return found?.track_title || `Track #${item.track_id}`;
      }
      if (item.album_id) {
        const found = this.findAlbum(item.album_id);
        return found?.title || `Album #${item.album_id}`;
      }
      return "Unknown item";
    },
    findTrack(trackId) {
      return (this.tracks || []).find((t) => Number(t.id || t.track_id) === Number(trackId)) || null;
    },
    findAlbum(albumId) {
      return (this.albums || []).find((a) => Number(a.id) === Number(albumId)) || null;
    },
    albumTracks(item) {
      if (!item?.album_id) return [];
      const album = this.findAlbum(item.album_id);
      return Array.isArray(album?.tracks) ? album.tracks : [];
    },
    itemUnitPrice(item) {
      if (item?.track_id) {
        const track = this.findTrack(item.track_id);
        return Number(track?.track_price_eur || 0);
      }
      if (item?.album_id) {
        const album = this.findAlbum(item.album_id);
        return Number(album?.price_eur || 0);
      }
      return 0;
    },
    itemTotalPrice(item) {
      return this.itemUnitPrice(item) * Number(item?.pcs || 1);
    },
    formatPrice(value) {
      return `€${Number(value || 0).toFixed(2)}`;
    },
    itemCover(item) {
      const fallback = "https://placehold.co/96x96?text=Cover";
      if (item.track_id) {
        const track = this.findTrack(item.track_id);
        const file = track?.track_cover;
        if (!file) return fallback;
        if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
        if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
        return storageUrl(`track-covers/${String(file).replace(/\\/g, "/").trim()}`);
      }
      if (item.album_id) {
        const album = this.findAlbum(item.album_id);
        const directCover = this.normalizeStorageAsset(album?.cover);
        if (directCover) return directCover;
        const firstTrackCover = this.normalizeStorageAsset(album?.tracks?.[0]?.track_cover);
        if (firstTrackCover) return firstTrackCover;
        return "https://placehold.co/96x96?text=Album";
      }
      return fallback;
    },
    normalizeStorageAsset(file) {
      if (!file) return "";
      const normalized = String(file).replace(/\\/g, "/").trim();
      if (!normalized) return "";
      if (normalized.startsWith("http://") || normalized.startsWith("https://")) return normalized;
      if (normalized.includes("/")) return storageUrl(normalized.replace(/^storage\//, ""));
      return storageUrl(`track-covers/${normalized}`);
    },
    onItemImgError(event) {
      event.target.src = "https://placehold.co/96x96?text=Cover";
    },
    openDeleteModal(item) {
      this.itemToDelete = item || null;
      this.deleteError = "";
      this.showDeleteModal = true;
    },
    closeDeleteModal() {
      if (this.deletingItem) return;
      this.showDeleteModal = false;
      this.itemToDelete = null;
      this.deleteError = "";
    },
    openCheckoutModal() {
      if (!this.cartId || this.selectedCartItems.length === 0) return;
      const defaultEmail = useUserLoginLogoutStore()?.item?.email || "";
      this.checkoutEmail = defaultEmail;
      this.checkoutEmailError = "";
      this.showCheckoutModal = true;
    },
    closeCheckoutModal() {
      if (this.checkingOut) return;
      this.showCheckoutModal = false;
      this.checkoutEmailError = "";
    },
    async confirmDeleteItem() {
      const id = Number(this.itemToDelete?.id || 0);
      if (!id) {
        this.deleteError = "Invalid cart item.";
        return;
      }
      this.deletingItem = true;
      this.deleteError = "";
      try {
        await service.deleteMyCartItem(id);
        await this.load();
        this.closeDeleteModal();
      } catch (err) {
        this.deleteError = this.extractErrorMessage(err, "Could not delete cart item.");
      } finally {
        this.deletingItem = false;
      }
    },
    async checkoutCart() {
      if (!this.cartId || this.selectedCartItems.length === 0) return;
      if (!this.checkoutEmail || !String(this.checkoutEmail).includes("@")) {
        this.checkoutEmailError = "Please enter a valid email address.";
        return;
      }
      this.checkingOut = true;
      this.checkoutError = "";
      this.checkoutResult = null;
      try {
        const res = await service.checkoutMyCart(this.cartId, { email: this.checkoutEmail });
        this.checkoutResult = res?.data || null;
        await this.load();
        this.showCheckoutModal = false;
      } catch (err) {
        this.checkoutError = this.extractErrorMessage(err, "Could not send the email.");
      } finally {
        this.checkingOut = false;
      }
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
  },
});
