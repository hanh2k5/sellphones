import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '../api'
import { useI18nStore } from './i18n'

/**
 * [Nguyễn Duy Khang - 4.2.5 → 4.2.11] AuthStore — Pinia store xác thực người dùng
 * Vai trò trung gian: View → AuthStore → authApi → HTTP → AuthController (backend)
 *
 * LUỒNG XÁC THỰC:
 *   login()    → POST /login    → AuthController@login    → trả {user, token}
 *   register() → POST /register → AuthController@register → trả {user, token}
 *   logout()   → POST /logout   → AuthController@logout   → xóa token DB + localStorage
 *   fetchMe()  → GET  /me       → AuthController@me       → làm mới thông tin user (sau F5)
 *
 * LƯU TRỮ TOKEN: localStorage (tồn tại qua F5, mất khi logout hoặc user xóa browser data)
 * BẢO MẬT: Token gắn vào mọi request qua Header: Authorization: Bearer {token}
 *          (được cấu hình trong src/services/api.js - Axios interceptor)
 */
export const useAuthStore = defineStore('auth', () => {
  // Đọc an toàn từ localStorage (tránh crash nếu JSON bị hỏng)
  const safeParse = (key) => {
    try {
      const val = localStorage.getItem(key)
      if (!val || val === 'undefined') return null
      return JSON.parse(val)
    } catch {
      return null
    }
  }

  const user    = ref(safeParse('auth_user'))                    // Thông tin user: {id, name, email, role, ...}
  const token   = ref(localStorage.getItem('auth_token') || null) // Sanctum token để xác thực API
  const loading = ref(false) // true khi đang gọi API đăng nhập/đăng ký → disable nút
  const error   = ref(null)  // Thông báo lỗi cuối cùng (hiển thị dưới form)

  const isLoggedIn = computed(() => !!token.value)           // true nếu có token hợp lệ
  const isAdmin    = computed(() => user.value?.role === 'admin') // true nếu role = 'admin'

  // ===================== ĐĂNG NHẬP =====================
  // POST /login → nhận {user, token} → lưu vào state + localStorage
  // Trả về: {success, attemptsLeft (số lần còn lại nếu sai mật khẩu)}
  async function login(email, password) {
    loading.value = true
    error.value = null
    try {
      const res = await authApi.login({ email, password })
      user.value  = res.data.user
      token.value = res.data.token
      localStorage.setItem('auth_token', res.data.token)            // Lưu token để dùng lại sau F5
      localStorage.setItem('auth_user', JSON.stringify(res.data.user)) // Lưu thông tin user
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || useI18nStore().t('common.error')
      return {
        success: false,
        message: err.response?.data?.message || useI18nStore().t('common.error'),
        data: err.response?.data,
        attemptsLeft: err.response?.data?.attempts_left, // Số lần đăng nhập sai còn lại
      }
    } finally {
      loading.value = false
    }
  }

  // ===================== ĐĂNG KÝ =====================
  // POST /register → validate → tạo User → trả về thành công (không tự đăng nhập)
  // User phải đăng nhập lại sau khi đăng ký
  async function register(name, email, address, phone, password, password_confirmation) {
    loading.value = true
    error.value = null
    try {
      await authApi.register({ name, email, address, phone, password, password_confirmation })
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || useI18nStore().t('common.error')
      return { success: false, message: error.value, errors: err.response?.data?.errors }
    } finally {
      loading.value = false
    }
  }

  // ===================== ĐĂNG XUẤT =====================
  // POST /logout → xóa token trên server → xóa toàn bộ dữ liệu cục bộ (kể cả giỏ hàng)
  async function logout() {
    try {
      await authApi.logout() // Xóa token khỏi bảng personal_access_tokens trong DB
    } catch { }
    user.value  = null
    token.value = null
    localStorage.removeItem('auth_token')    // Xóa token
    localStorage.removeItem('auth_user')     // Xóa thông tin user
    localStorage.removeItem('cart_voucher')  // Xóa voucher đang áp dụng
    localStorage.removeItem('cart_discount') // Xóa tiền giảm đã lưu
  }

  // ===================== LÀM MỚI THÔNG TIN USER =====================
  // GET /me → lấy thông tin user mới nhất từ DB → cập nhật state + localStorage
  // Dùng khi: F5 trang, sau khi Admin sửa thông tin user, sau khi đổi email
  async function fetchMe() {
    try {
      const res = await authApi.me()
      user.value = res.data
      localStorage.setItem('auth_user', JSON.stringify(res.data)) // Đồng bộ lại localStorage
    } catch { }
  }

  return { user, token, loading, error, isLoggedIn, isAdmin, login, register, logout, fetchMe }
})
