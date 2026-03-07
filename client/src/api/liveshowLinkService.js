import apiClient from "./axiosClient";

const route = "/liveshow-links";

export default {
  list() {
    return apiClient.get(route);
  },
  create(payload) {
    return apiClient.post(route, payload);
  },
  destroy(id) {
    return apiClient.delete(`${route}/${id}`);
  },
};

