import apiClient from "./axiosClient";

const route = "/artists";

export default {
  list() {
    return apiClient.get(route);
  },
  create(payload) {
    return apiClient.post(route, payload);
  },
  update(id, payload) {
    if (payload instanceof FormData) {
      payload.append("_method", "PATCH");
      return apiClient.post(`${route}/${id}`, payload);
    }
    return apiClient.patch(`${route}/${id}`, payload);
  },
  destroy(id) {
    return apiClient.delete(`${route}/${id}`);
  },
};
