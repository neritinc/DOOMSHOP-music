<template>
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h2 class="h5 mb-3">Registration</h2>
      <form class="card card-body" @submit.prevent="submit">
        <input v-model="form.name" class="form-control mb-2" placeholder="Name" required />
        <div v-if="errors.name" class="text-danger small mb-2">{{ errors.name }}</div>
        <input v-model="form.email" class="form-control mb-2" type="email" placeholder="Email" required />
        <div v-if="errors.email" class="text-danger small mb-2">{{ errors.email }}</div>
        <input
          v-model="form.password"
          class="form-control mb-2"
          type="password"
          placeholder="Password"
          required
        />
        <input
          v-model="passwordConfirm"
          class="form-control mb-3"
          type="password"
          placeholder="Confirm password"
          required
        />
        <div v-if="errors.password" class="text-danger small mb-3">{{ errors.password }}</div>
        <button class="btn btn-primary" :disabled="loading">Create account</button>
      </form>
    </div>
  </div>
</template>

<script>
import userService from "@/api/userService";
import { useToastStore } from "@/stores/toastStore";

export default {
  data() {
    return {
      loading: false,
      errors: {},
      passwordConfirm: "",
      form: {
        name: "",
        email: "",
        password: "",
        role: 2,
      },
    };
  },
  methods: {
    async submit() {
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
        this.$router.push("/login");
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
};
</script>
