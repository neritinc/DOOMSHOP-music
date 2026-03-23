<template>
  <div class="row justify-content-center">
    <div class="col-md-5">
      <h2 class="h5 mb-3">Login</h2>
      <form class="card card-body" @submit.prevent="submit">
        <input v-model="email" class="form-control mb-2" type="email" placeholder="Email" required />
        <input v-model="password" class="form-control mb-3" type="password" placeholder="Password" required />
        <button class="btn btn-primary" :disabled="loading">Sign in</button>
        <RouterLink class="small mt-3 d-inline-block" to="/registration">Create a new account</RouterLink>
      </form>
    </div>
  </div>
</template>

<script>
import { mapActions, mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

export default {
  data() {
    return {
      email: "admin@doomshoprecords.com",
      password: "admin123",
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["loading"]),
  },
  methods: {
    ...mapActions(useUserLoginLogoutStore, ["login"]),
    async submit() {
      await this.login({ email: this.email, password: this.password });
      this.$router.push("/");
    },
  },
};
</script>
