<template>
  <nav aria-label="breadcrumb" class="crumb-shell">
    <ol class="breadcrumb m-0">
      <li class="breadcrumb-item"><router-link to="/">Home</router-link></li>
      <li
        v-for="(crumb, index) in breadcrumbs"
        :key="index"
        class="breadcrumb-item"
        :class="{ active: index === breadcrumbs.length - 1 }"
      >
        <router-link v-if="index < breadcrumbs.length - 1 && !crumb.disabled" :to="crumb.path">
          {{ crumb.label }}
        </router-link>
        <span v-else :class="{ 'text-muted': crumb.disabled }">{{ crumb.label }}</span>
      </li>
    </ol>
  </nav>
</template>

<script>
export default {
  computed: {
    breadcrumbs() {
      const matchedRoutes = this.$route.matched.filter((route) => route.meta && route.meta.breadcrumb);
      if (matchedRoutes.length > 0 && (matchedRoutes[0].path === "/" || matchedRoutes[0].path === "")) {
        matchedRoutes.shift();
      }
      return matchedRoutes.map((route) => ({
        label: route.meta.breadcrumb,
        path: route.path,
        disabled: route.meta.disabled,
      }));
    },
  },
};
</script>

<style scoped>
.crumb-shell {
  border: 1px solid #d8e5f7;
  background: #ffffff;
  border-radius: 12px;
  padding: 8px 12px;
  margin-bottom: 10px;
}

.breadcrumb {
  background-color: transparent;
}
</style>
