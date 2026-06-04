import { ref } from 'vue'
import { defineStore } from 'pinia'
import { cartApi, vouchersApi } from '../api'
import { useI18nStore } from './i18n'
import { useToast } from '../composables/useToast'

/**
 * [Phan Đình Hạnh - 4.1.3 & 4.1.13] CartStore — Pinia store quản lý giỏ hàng
 * Vai trò trung gian: View gọi Store → Store gọi cartApi → cartApi gọi HTTP → CartController (backend)
 *
 * LUỒNG:
 *   addToCart()      → POST /cart          → CartController@store   → ghi vào bảng cart_items
 *   fetchCart()      → GET  /cart          → CartController@index   → trả danh sách + tổng tiền
 *   removeFromCart() → DELETE /cart/{id}   → CartController@destroy → xóa dòng khỏi cart_items
 *   updateQty()      → PUT  /cart/{id}     → CartController@update  → cập nhật số lượng
 *   applyVoucher()   → POST /vouchers/apply → VoucherController     → tính tiền giảm
 *   clearCart()      → DELETE /cart/clear  → CartController         → xóa toàn bộ giỏ
 */
export const useCartStore = defineStore('cart', () => {
  const items      = ref([])   // Mảng các CartItem: [{id, product: {name, price, hinh_anh}, quantity}]
  const pagination = ref(null) // Thông tin phân trang giỏ hàng {current_page, last_page, total}
  const tongTien   = ref(0)    // Tổng tiền hàng (chưa giảm) = Σ(price × quantity)
  const soLuong    = ref(0)    // Tổng số sản phẩm trong giỏ (hiển thị badge trên icon giỏ)
  const loading    = ref(false) // true khi đang gọi API → hiện spinner trên CartView

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

  // Voucher đã áp dụng — lưu vào localStorage để giữ khi user F5 trang
  const appliedVoucher = ref(safeParse('cart_voucher'))
  const tienGiam       = ref(Number(localStorage.getItem('cart_discount') || 0)) // Số tiền được giảm

  // ===================== [4.1.3] LẤY GIỎ HÀNG TỪ SERVER =====================
  // GET /cart → CartController@index → trả items + tổng tiền
  async function fetchCart(page = 1) {
    loading.value = true
    try {
      const res = await cartApi.get({ page })
      items.value      = res.data.items.data           // Mảng sản phẩm trong giỏ
      pagination.value = {
        current_page: res.data.items.current_page,
        last_page:    res.data.items.last_page,
        total:        res.data.items.total
      }
      tongTien.value = res.data.total_amount   // Tổng tiền từ server (chính xác hơn tính local)
      soLuong.value  = res.data.total_quantity // Tổng số lượng SP
      
      // Nếu đã có voucher → tính lại tiền giảm theo tổng tiền mới
      if (appliedVoucher.value) {
        await applyVoucher(appliedVoucher.value.code)
      }

      if (items.value.length === 0) {
        tongTien.value = 0
        soLuong.value = 0
        pagination.value = null
        appliedVoucher.value = null
        tienGiam.value = 0
        localStorage.removeItem('cart_voucher')
        localStorage.removeItem('cart_discount')
      }
    } catch (e) {
      useToast().error(e.response?.data?.message || useI18nStore().t('common.error'))
    } finally {
      loading.value = false
    }
  }

  // ===================== THÊM SẢN PHẨM VÀO GIỎ =====================
  // POST /cart → CartController@store → upsert bảng cart_items (tạo mới hoặc cộng thêm số lượng)
  async function addToCart(productId, quantity = 1) {
    try {
      const res = await cartApi.add(productId, quantity)
      await fetchCart() // Tải lại giỏ để cập nhật tổng tiền + số lượng
      return { success: true, message: res.data.message }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message || useI18nStore().t('common.error'),
        stock: e.response?.data?.stock
      }
    }
  }

  // ===================== XÓA 1 SẢN PHẨM KHỎI GIỎ =====================
  // DELETE /cart/{cartItemId} → CartController@destroy → xóa dòng khỏi cart_items
  async function removeFromCart(cartItemId) {
    try {
      await cartApi.remove(cartItemId)
      items.value = items.value.filter(item => item.id !== cartItemId)
      if (items.value.length === 0) {
        tongTien.value = 0
        soLuong.value = 0
        pagination.value = null
        appliedVoucher.value = null
        tienGiam.value = 0
        localStorage.removeItem('cart_voucher')
        localStorage.removeItem('cart_discount')
      } else {
        await fetchCart(pagination.value?.current_page || 1) // Tải lại trang hiện tại
      }
      return { success: true }
    } catch (e) {
      return { success: false }
    }
  }

  // ===================== CẬP NHẬT SỐ LƯỢNG =====================
  // PUT /cart/{cartItemId} → CartController@update → cập nhật quantity trong cart_items
  async function updateQty(cartItemId, qty) {
    try {
      await cartApi.update(cartItemId, qty)
      await fetchCart()
      return { success: true }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message,
        stock: e.response?.data?.stock
      }
    }
  }

  // ===================== XÓA TOÀN BỘ GIỎ HÀNG =====================
  // Gọi sau khi đặt hàng thành công → xóa cả DB lẫn state local
  async function clearCart() {
    try { await cartApi.clear() } catch {}
    $reset()
  }

  function $reset() {
    items.value      = []   // Xóa danh sách sản phẩm trên UI
    tongTien.value   = 0
    soLuong.value    = 0
    pagination.value = null
    appliedVoucher.value = null
    tienGiam.value   = 0
    localStorage.removeItem('cart_voucher')   // Xóa voucher đã lưu
    localStorage.removeItem('cart_discount')  // Xóa tiền giảm đã lưu
  }

  // ===================== [4.1.13] ÁP DỤNG VOUCHER =====================
  // POST /vouchers/apply → VoucherController → kiểm tra mã hợp lệ, tính tiền giảm
  async function applyVoucher(code) {
    try {
      const res = await vouchersApi.apply(code)
      appliedVoucher.value = res.data.voucher           // Lưu thông tin voucher
      tienGiam.value       = res.data.discount          // Lưu số tiền được giảm
      localStorage.setItem('cart_voucher', JSON.stringify(res.data.voucher)) // Giữ qua F5
      localStorage.setItem('cart_discount', res.data.discount.toString())
      return { success: true, data: res.data }
    } catch (e) {
      appliedVoucher.value = null
      tienGiam.value       = 0
      localStorage.removeItem('cart_voucher')
      localStorage.removeItem('cart_discount')
      return { success: false, message: e.response?.data?.message || useI18nStore().t('common.error') }
    }
  }

  // Tính lại tongTien và soLuong từ mảng items local (dùng khi không muốn gọi API)
  function recalcTotal() {
    tongTien.value = items.value.reduce((sum, i) => sum + (i.product?.price || 0) * i.quantity, 0)
    soLuong.value  = items.value.reduce((sum, i) => sum + i.quantity, 0)
  }

  // Số tiền thực cần thanh toán = tổng tiền - tiền giảm (không âm)
  const thanhToan = () => Math.max(0, tongTien.value - tienGiam.value)

  return {
    items, pagination, tongTien, soLuong, loading, appliedVoucher, tienGiam, thanhToan,
    fetchCart, addToCart, removeFromCart, updateQty, clearCart, applyVoucher, $reset
  }
})
