import axios from 'axios'
import { getApiBaseUrl } from '../utils/url'

const api = axios.create({
  baseURL: getApiBaseUrl() + '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true
})

// Request interceptor: Gắn Token vào Header
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api