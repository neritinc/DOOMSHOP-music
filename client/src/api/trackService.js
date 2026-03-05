import apiClient from "./axiosClient";

const route = "/tracks";

export default {
  list() {
    return apiClient.get(route);
  },
  create(payload) {
    return apiClient.post(route, payload);
  },
};
