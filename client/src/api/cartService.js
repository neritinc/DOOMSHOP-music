import apiClient from "./axiosClient";

export default {
  myCarts() {
    return apiClient.get("/my-carts");
  },
  createMyCart(payload) {
    return apiClient.post("/my-carts", payload);
  },
  addMyCartItem(payload) {
    return apiClient.post("/my-cart-items", payload);
  },
  myCartItems() {
    return apiClient.get("/my-cart-items");
  },
  allCarts() {
    return apiClient.get("/carts");
  },
};
