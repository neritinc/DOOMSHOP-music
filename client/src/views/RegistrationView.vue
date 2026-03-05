<template>
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h2 class="h5 mb-3">Registration</h2>
      <form class="card card-body" @submit.prevent="submit">
        <input v-model="form.name" class="form-control mb-2" placeholder="Name" required />
        <input v-model="form.email" class="form-control mb-2" type="email" placeholder="Email" required />
        <input v-model="form.password" class="form-control mb-3" type="password" placeholder="Password (min 8)" required />
        <button class="btn btn-primary" :disabled="loading">Create account</button>
      </form>
    </div>
  </div>
</template>

<script>
import userService from "@/api/userService";

export default {
  data() {
    return {
      loading: false,
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
      try {
        await userService.create(this.form);
        this.$router.push("/login");
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
