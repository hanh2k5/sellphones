export function getApiBaseUrl() {
  // Ưu tiên lấy từ biến môi trường, nếu không có thì mặc định localhost:8000
  return import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
}

export function getAppBaseUrl() {
  return window.location.origin
}
