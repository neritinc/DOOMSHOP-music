import { defineStore } from "pinia";
import service from "@/api/userLoginLogoutService";
import { useToastStore } from "./toastStore";

export const useUserLoginLogoutStore = defineStore("userLoginLogout", {
  state: () => ({
    item: JSON.parse(localStorage.getItem("user_data")) || null,
    loading: false,
    error: null,
  }),
  getters: {
    token: (state) => state.item?.token || null,
    role: (state) => state.item?.role || null,
    userName: (state) => state.item?.name || null,
    userNameWithRole: (state) => {
      if (!state.item) return null;
      const roleLabel = state.item.role === 1 ? "Admin" : "Customer";
      return `${state.item.name} (${roleLabel})`;
    },
    isLoggedIn: (state) => !!state.item,
    isAdmin: (state) => state.item?.role === 1,
    isCustomer: (state) => state.item?.role === 2,
  },
  actions: {
    async login(data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await service.login(data);
        this.item = response.data;
        localStorage.setItem("user_data", JSON.stringify(response.data));
        return true;
      } catch (err) {
        this.error = err;
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async logout() {
      this.loading = true;
      this.error = null;
      try {
        await service.logout();
      } catch (_err) {
      } finally {
        this.item = null;
        localStorage.removeItem("user_data");
        this.loading = false;
        const toastStore = useToastStore();
        toastStore.messages.push("Logged out");
        toastStore.show("Success");
      }
    },
  },
});
