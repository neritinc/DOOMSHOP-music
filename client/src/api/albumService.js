import apiClient from "./axiosClient";

const route = "/albums";

export default {
  list() {
    return apiClient.get(route);
  },
  show(id) {
    return apiClient.get(`${route}/${id}`);
  },
  create(payload) {
    if (payload instanceof FormData) {
      return apiClient.post(route, payload, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    }
    return apiClient.post(route, payload);
  },
  update(id, payload) {
    if (payload instanceof FormData) {
      payload.append("_method", "PATCH");
      return apiClient.post(`${route}/${id}`, payload, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    }
    return apiClient.patch(`${route}/${id}`, payload);
  },
  remove(id) {
    return apiClient.delete(`${route}/${id}`);
  },
  syncTracks(id, trackIds) {
    return apiClient.patch(`${route}/${id}/tracks`, { track_ids: trackIds });
  },
};
