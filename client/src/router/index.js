import { createRouter, createWebHistory } from "vue-router";

const routes = [
  { path: "/", name: "home", component: () => import("@/views/HomeView.vue"), meta: { title: "Home", breadcrumb: "Home" } },
  { path: "/login", name: "login", component: () => import("@/views/LoginView.vue"), meta: { title: "Login", breadcrumb: "Login" } },
  { path: "/registration", name: "registration", component: () => import("@/views/RegistrationView.vue"), meta: { title: "Registration", breadcrumb: "Registration" } },
  { path: "/about", name: "about", component: () => import("@/views/AboutView.vue"), meta: { title: "About", breadcrumb: "About" } },
  { path: "/tracks", name: "tracks", component: () => import("@/views/TracksView.vue"), meta: { title: "Tracks", breadcrumb: "Tracks" } },
  { path: "/tracks/:id", name: "track-detail", component: () => import("@/views/TrackDetailView.vue"), meta: { title: "Track Details", breadcrumb: "Track Details" } },
  { path: "/genres", name: "genres", component: () => import("@/views/GenresView.vue"), meta: { title: "Genres", breadcrumb: "Genres", roles: [1] } },
  { path: "/artists", name: "artists", component: () => import("@/views/ArtistsView.vue"), meta: { title: "Artists", breadcrumb: "Artists", roles: [1] } },
  { path: "/my-cart", name: "my-cart", component: () => import("@/views/MyCartView.vue"), meta: { title: "My Cart", breadcrumb: "My Cart", roles: [1, 2] } },
  { path: "/admin-carts", name: "admin-carts", component: () => import("@/views/AdminCartsView.vue"), meta: { title: "All Carts", breadcrumb: "All Carts", roles: [1] } },
  { path: "/:pathMatch(.*)*", name: "not-found", component: () => import("@/views/404.vue"), meta: { title: "404", breadcrumb: "404" } },
];

const router = createRouter({ history: createWebHistory(import.meta.env.BASE_URL), routes });

router.beforeEach((to, from, next) => {
  document.title = `DOOMSHOP - ${to.meta.title || "App"}`;
  const raw = localStorage.getItem("user_data");
  const user = raw ? JSON.parse(raw) : null;
  const role = user?.role ?? null;

  if (to.meta.roles && !to.meta.roles.includes(role)) {
    return next(role ? "/" : "/login");
  }

  next();
});

export default router;
