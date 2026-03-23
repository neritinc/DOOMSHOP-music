import apiClient from "./axiosClient";

const route = "/genres";

export default {
  list() {
    return apiClient.get(route);
  },
  create(payload) {
    return apiClient.post(route, payload);
  },
  update(id, payload) {
    return apiClient.patch(`${route}/${id}`, payload);
  },
  destroy(id) {
    return apiClient.delete(`${route}/${id}`);
  },
};
