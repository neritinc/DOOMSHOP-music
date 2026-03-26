<template>
  <div>
    <h1>This page could not be found</h1>
    <p>
      <RouterLink to="/">Home</RouterLink>
    </p>
  </div>
</template>

<script>
import { RouterLink } from "vue-router";
import { useNotFoundViewStore } from "@/stores/views/notFoundViewStore";

export default {
  components: { RouterLink },
  setup() {
    useNotFoundViewStore();
    return {};
  },
};
</script>

<style>
</style>
