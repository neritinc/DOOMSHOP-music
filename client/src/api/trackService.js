import apiClient from "./axiosClient";

const route = "/tracks";

export default {
  list() {
    return apiClient.get(route);
  },
  show(id) {
    return apiClient.get(`${route}/${id}`);
  },
  analyzeUpload(file) {
    const payload = new FormData();
    payload.append("track_audio", file);
    return apiClient.post(`${route}/analyze-upload`, payload);
  },
  create(payload) {
    if (payload instanceof FormData) {
      return apiClient.post(route, payload);
    }
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
  regeneratePreview(id, payload) {
    return apiClient.post(`${route}/${id}/regenerate-preview`, payload);
  },
};
