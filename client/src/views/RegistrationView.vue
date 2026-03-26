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
import { storeToRefs } from "pinia";
import { useRegistrationViewStore } from "@/stores/views/registrationViewStore";
import { useRouter } from "vue-router";

export default {
  setup() {
    const store = useRegistrationViewStore();
    const router = useRouter();
    const storeRefs = storeToRefs(store);

    const submit = async () => {
      await store.submit(router);
    };

    return {
      ...storeRefs,
      submit,
    };
  },
};
</script>

