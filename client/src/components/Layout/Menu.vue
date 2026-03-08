<template>
  <div>
    <nav class="navbar navbar-expand-md nav-modern" data-bs-theme="light">
      <div class="container-fluid">
        <button
          class="navbar-toggler nav-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 nav-links-wrap">
            <li class="nav-item"><RouterLink class="nav-link" to="/">Home</RouterLink></li>
            <li class="nav-item"><RouterLink class="nav-link" to="/about">About</RouterLink></li>
            <li class="nav-item"><RouterLink class="nav-link" to="/tracks">Tracks</RouterLink></li>
            <li class="nav-item"><RouterLink class="nav-link" to="/genres">Genres</RouterLink></li>
            <li class="nav-item"><RouterLink class="nav-link" to="/artists">Artists</RouterLink></li>
            <li class="nav-item"><RouterLink class="nav-link" to="/albums">Albums</RouterLink></li>
            <li v-if="isLoggedIn" class="nav-item"><RouterLink class="nav-link" to="/my-cart">My Cart</RouterLink></li>
            <li v-if="isAdmin" class="nav-item"><RouterLink class="nav-link" to="/admin-carts">All Carts</RouterLink></li>
            <li class="nav-item">
              <RouterLink v-if="!isLoggedIn" class="nav-link" to="/login">Login</RouterLink>
              <div v-else class="d-flex align-items-center gap-2 px-2">
                <span class="small fw-semibold text-primary">{{ userNameWithRole }}</span>
                <button
                  type="button"
                  class="logout-btn"
                  aria-label="Logout"
                  @click="onClickLogout"
                >
                  <i class="bi bi-box-arrow-right tight-icon" aria-hidden="true"></i>
                </button>
              </div>
            </li>
          </ul>
          <form class="d-flex nav-search" role="search" @submit.prevent>
            <input
              id="search"
              class="form-control me-2 nav-search-input"
              type="search"
              placeholder="Search tracks"
              aria-label="Search"
              v-model="searchWordInput"
            />
            <label for="search" class="form-label m-0">
              <i class="bi bi-search fs-5 my-pointer nav-search-icon"></i>
            </label>
          </form>
        </div>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { useSearchStore } from "@/stores/searchStore";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";

const router = useRouter();

const searchStore = useSearchStore();
const userLoginLogoutStore = useUserLoginLogoutStore();

const { searchWord } = storeToRefs(searchStore);
const { isLoggedIn, userNameWithRole, isAdmin } = storeToRefs(userLoginLogoutStore);

const searchWordInput = computed({
  get: () => searchWord.value,
  set: (value) => {
    if (value) {
      searchStore.setSearchWord(value);
      return;
    }
    searchStore.resetSearchWord();
  },
});

const onClickLogout = async () => {
  await userLoginLogoutStore.logout();
  router.push("/");
};
</script>

<style scoped>
.nav-modern {
  position: relative;
  overflow: hidden;
  border-radius: 14px;
  margin: 6px 0 10px;
  padding: 10px 12px;
  background:
    radial-gradient(900px 220px at -8% -120%, rgba(37, 99, 235, 0.12), transparent 46%),
    linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
  border: 1px solid #d8e5f7;
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.09);
}

.nav-modern::after {
  content: "";
  position: absolute;
  right: -34px;
  top: -42px;
  width: 140px;
  height: 140px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0) 62%);
  pointer-events: none;
}

.nav-links-wrap .nav-link {
  position: relative;
  color: #0f172a;
  font-weight: 600;
  border-radius: 12px;
  padding: 8px 13px !important;
  transition: all 0.2s ease;
}

.nav-links-wrap .nav-link:hover {
  background: #eaf2ff;
  color: #1d4ed8;
  transform: translateY(-1px);
}

.nav-link.active,
.nav-link.router-link-exact-active {
  color: #1d4ed8 !important;
  font-weight: 700;
  background: linear-gradient(180deg, #eaf2ff 0%, #e4efff 100%);
  box-shadow: inset 0 0 0 1px #c6dbff;
}

.nav-search { align-items: center; }
.nav-search-input {
  border-radius: 999px;
  border: 1px solid #c9daef;
  background: #f8fbff;
  min-width: 210px;
}

.nav-search-input:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.18);
}

.nav-search-icon {
  color: #2563eb;
  transition: color 0.2s ease, transform 0.2s ease;
}

.nav-search-icon:hover {
  color: #1d4ed8;
  transform: scale(1.06);
}

.nav-toggler {
  border: 1px solid #c9daef;
  border-radius: 10px;
  padding: 0.35rem 0.5rem;
  background: #f8fbff;
}

.tight-icon { line-height: 1 !important; display: inline-flex; vertical-align: middle; font-size: 1.2rem; }

.logout-btn {
  border: 0;
  background: transparent;
  color: #1d4ed8;
  padding: 0;
  line-height: 1;
  transition: color 0.18s ease, transform 0.18s ease;
}

.logout-btn:hover {
  color: #1e40af;
  transform: translateY(-1px);
}

.small.fw-semibold.text-primary {
  background: #edf4ff;
  border: 1px solid #d6e6ff;
  color: #1d4ed8 !important;
  border-radius: 999px;
  padding: 0.18rem 0.5rem;
  font-size: 0.74rem;
}

@media (max-width: 767px) {
  .nav-modern {
    padding: 10px 8px;
  }

  .nav-links-wrap .nav-link {
    margin: 2px 0;
  }

  .nav-search {
    margin-top: 8px;
    width: 100%;
  }

  .nav-search-input {
    min-width: 100%;
  }
}
</style>
