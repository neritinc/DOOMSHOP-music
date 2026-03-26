<template>
  <div class="admin-carts-page">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="h5 m-0">Users and Their Carts</h2>
      <span class="badge text-bg-dark">{{ totalUsers }} users</span>
    </div>

    <div v-if="loading" class="alert alert-info py-2">Loading carts...</div>
    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>

    <div v-if="groupedCarts.length === 0 && !loading" class="empty-state">
      No carts found.
    </div>

    <div v-else class="user-cards">
      <div v-for="user in groupedCarts" :key="user.userId" class="card user-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <div class="user-name">{{ user.name || "Unknown user" }}</div>
              <div class="user-email">{{ user.email || "no-email" }}</div>
            </div>
            <span class="badge text-bg-dark">{{ user.carts.length }} carts</span>
          </div>

          <div class="cart-list mt-3">
            <div v-for="cart in user.carts" :key="cart.id" class="cart-row">
              <div>
                <strong>Cart #{{ cart.id }}</strong>
                <div class="text-muted small">Date: {{ cart.date || "-" }}</div>
              </div>
              <div class="cart-meta">
                <span class="pill">{{ cart.items?.length || 0 }} items</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted } from "vue";
import { storeToRefs } from "pinia";
import { useAdminCartsViewStore } from "@/stores/views/adminCartsViewStore";

export default {
  setup() {
    const store = useAdminCartsViewStore();
    const storeRefs = storeToRefs(store);

    onMounted(async () => {
      await store.load();
    });

    return {
      ...storeRefs,
      load: store.load,
    };
  },
};
</script>

<style scoped>
.admin-carts-page {
  display: grid;
  gap: 1rem;
}

.user-cards {
  display: grid;
  gap: 0.9rem;
}

.user-card {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 8px 20px rgba(20, 37, 63, 0.06);
}

.user-name {
  font-weight: 700;
  color: #0f172a;
}

.user-email {
  color: #64748b;
  font-size: 0.9rem;
}

.cart-list {
  display: grid;
  gap: 0.6rem;
}

.cart-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.65rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
}

.cart-meta {
  display: flex;
  gap: 0.4rem;
  align-items: center;
}

.pill {
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1e3a8a;
  font-weight: 700;
  font-size: 0.75rem;
}

.empty-state {
  border: 1px dashed #d1d5db;
  border-radius: 10px;
  padding: 0.8rem;
  color: #64748b;
  background: #f8fafc;
}
</style>

