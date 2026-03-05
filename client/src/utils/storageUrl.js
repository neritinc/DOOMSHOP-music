function backendOrigin() {
  const apiUrl = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
  return apiUrl.replace(/\/api\/?$/, "");
}

function encodePath(path) {
  return String(path)
    .split("/")
    .map((segment) => encodeURIComponent(segment))
    .join("/");
}

export function storageUrl(relativePath) {
  if (!relativePath) return "";
  return `${backendOrigin()}/storage/${encodePath(relativePath)}`;
}

