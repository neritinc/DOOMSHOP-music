import apiClient from "./axiosClient";

const route = "/tracks";

export default {
  list() {
    return apiClient.get(route);
  },
  show(id) {
    return apiClient.get(`${route}/${id}`);
  },
  create(payload) {
    return apiClient.post(route, payload);
  },
};
