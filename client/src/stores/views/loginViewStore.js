import { defineStore } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

export const useLoginViewStore = defineStore("loginView", {
  state: () => ({
    email: "admin@doomshoprecords.com",
    password: "admin123",
  }),
  actions: {
    async submit(router) {
      const userStore = useUserLoginLogoutStore();
      await userStore.login({ email: this.email, password: this.password });
      router.push("/");
    },
  },
});
