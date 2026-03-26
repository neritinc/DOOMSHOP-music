import { defineStore } from "pinia";
import userService from "@/api/userService";
import { useToastStore } from "@/stores/toastStore";

export const useRegistrationViewStore = defineStore("registrationView", {
  state: () => ({
    loading: false,
    errors: {},
    passwordConfirm: "",
    form: {
      name: "",
      email: "",
      password: "",
      role: 2,
    },
  }),
  actions: {
    async submit(router) {
      this.loading = true;
      this.errors = {};
      const toastStore = useToastStore();
      if (this.form.password !== this.passwordConfirm) {
        this.errors.password = "Passwords do not match.";
        toastStore.messages.push("Passwords do not match.");
        toastStore.show("Error");
        this.loading = false;
        return;
      }
      try {
        await userService.create(this.form);
        toastStore.messages.push("Account created. Please log in.");
        toastStore.show("Success");
        router.push("/login");
      } catch (err) {
        const apiErrors = err?.response?.data?.errors;
        if (apiErrors) {
          this.errors = {
            name: apiErrors.name?.[0],
            email: apiErrors.email?.[0],
            password: apiErrors.password?.[0],
          };
        }
        const message = err?.response?.data?.message || "Registration failed.";
        toastStore.messages.push(message);
        toastStore.show("Error");
      } finally {
        this.loading = false;
      }
    },
  },
});

