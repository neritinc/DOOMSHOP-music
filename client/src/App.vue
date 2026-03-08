<template>
  <div class="container-fluid mt-2 app-shell">
    <Header />
    <Menu />
    <Breadcrumb />
    <main class="app-main p-3">
      <RouterView />
    </main>
    <Footer />
    <ToastContanier />
  </div>
</template>

<script>
import Header from "@/components/Layout/Header.vue";
import Menu from "@/components/Layout/Menu.vue";
import Breadcrumb from "@/components/Layout/Breadcrumb.vue";
import Footer from "@/components/Layout/Footer.vue";
import ToastContanier from "@/components/Message/ToastContanier.vue";

export default {
  components: { Header, Menu, Breadcrumb, Footer, ToastContanier },
};
</script>

<style scoped>
.app-shell {
  max-width: 1600px;
}

.app-main {
  border-radius: 22px;
}
</style>
