import apiClient from "./axiosClient";

const route = "/artists";

export default {
  list() {
    return apiClient.get(route);
  },
  create(payload) {
    return apiClient.post(route, payload);
  },
};
