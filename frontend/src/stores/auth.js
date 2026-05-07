import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { useRouter } from 'vue-router'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const safeParse = (key) => {
    try {
      const val = localStorage.getItem(key)
      if (!val || val === 'undefined') return null
      return JSON.parse(val)
    } catch {
      return null
    }
  }

  const user = ref(safeParse('auth_user'))
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  const error = ref(null)

  const isLoggedIn = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  // Đăng nhập
  async function login(email, password) {
    loading.value = true
    error.value = null
    try {
      const res = await api.post('/login', { email, password })
      user.value = res.data.user
      token.value = res.data.token
      localStorage.setItem('auth_token', res.data.token)
      localStorage.setItem('auth_user', JSON.stringify(res.data.user))
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Đăng nhập thất bại!'
      return { success: false, message: error.value, data: err.response?.data }
    } finally {
      loading.value = false
    }
  }

  // Đăng ký - Chỉ gọi API, KHÔNG lưu token (redirect về /login theo báo cáo 4.2.5 STT 8)
  async function register(name, email, address, password, password_confirmation) {
    loading.value = true
    error.value = null
    try {
      await api.post('/register', { name, email, address, password, password_confirmation })
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Đăng ký thất bại!'
      return { success: false, message: error.value, errors: err.response?.data?.errors }
    } finally {
      loading.value = false
    }
  }

  // Đăng xuất
  async function logout() {
    try {
      await api.post('/logout')
    } catch {}
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('cart_voucher')
    localStorage.removeItem('cart_discount')
  }

  // Lấy thông tin user mới nhất
  async function fetchMe() {
    try {
      const res = await api.get('/me')
      user.value = res.data
      localStorage.setItem('auth_user', JSON.stringify(res.data))
    } catch {}
  }

  return { user, token, loading, error, isLoggedIn, isAdmin, login, register, logout, fetchMe }
})