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
import { storeToRefs } from "pinia";
import { useLoginViewStore } from "@/stores/views/loginViewStore";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import { RouterLink, useRouter } from "vue-router";

export default {
  components: { RouterLink },
  setup() {
    const store = useLoginViewStore();
    const router = useRouter();
    const { loading } = storeToRefs(useUserLoginLogoutStore());
    const storeRefs = storeToRefs(store);

    const submit = async () => {
      await store.submit(router);
    };

    return {
      ...storeRefs,
      loading,
      submit,
    };
  },
};
</script>

