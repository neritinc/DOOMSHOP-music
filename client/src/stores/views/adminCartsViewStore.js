import { defineStore } from "pinia";
import service from "@/api/cartService";

export const useAdminCartsViewStore = defineStore("adminCartsView", {
  state: () => ({
    carts: [],
    loading: false,
    error: "",
  }),
  getters: {
    groupedCarts(state) {
      const map = new Map();
      (state.carts || []).forEach((cart) => {
        const user = cart?.user || {};
        const id = user?.id || cart?.user_id || `user-${cart?.id}`;
        if (!map.has(id)) {
          map.set(id, {
            userId: id,
            name: user?.name || "",
            email: user?.email || "",
            carts: [],
          });
        }
        map.get(id).carts.push(cart);
      });
      return Array.from(map.values());
    },
    totalUsers() {
      return this.groupedCarts.length;
    },
  },
  actions: {
    async load() {
      this.loading = true;
      this.error = "";
      try {
        const res = await service.allCarts();
        this.carts = res.data || [];
      } catch (err) {
        this.error = err?.response?.data?.message || "Failed to load carts.";
      } finally {
        this.loading = false;
      }
    },
  },
});
