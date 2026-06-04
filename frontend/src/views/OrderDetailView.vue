<template>
  <div class="max-w-7xl mx-auto px-4 lg:px-8 py-12 relative z-10">
    <div v-if="orderStore.loading && !orderStore.current" class="text-center py-20">
      <div class="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>

    <div v-else-if="orderStore.current" class="space-y-8 animate-fade-in">
      <div>
        <router-link to="/profile?tab=orders" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 font-bold text-[13px] rounded-xl border border-slate-200 shadow-sm transition-all active:scale-95 mb-4">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
          {{ i18n.t('product.go_back') }}
        </router-link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Timeline & Shipping Info -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm order-card-main relative">
            <div class="flex flex-wrap justify-between items-start mb-12 gap-4">
              <div>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 leading-tight">
                  #{{ orderStore.current.order_code }}
                </h1>
                <p class="text-slate-400 font-medium text-xs md:text-sm mt-1 flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  {{ formatDate(orderStore.current.created_at) }}
                </p>
              </div>
              <span class="px-5 py-2 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-widest shadow-sm border" :class="statusClass(orderStore.current.status)">
                {{ statusLabel(orderStore.current.status) }}
              </span>
            </div>

            <!-- Timeline -->
            <div v-if="orderStore.current.status !== 'cancelled'" class="relative mb-16 px-2 md:px-12">
              <!-- Line -->
              <div class="absolute left-[15%] right-[15%] top-[1.35rem] h-1 bg-slate-100 rounded-full">
                <div class="h-full bg-blue-600 rounded-full transition-all duration-1000" :style="{ width: progressWidth }"></div>
              </div>
              <!-- Steps -->
              <div class="relative z-10 flex justify-between">
                <!-- Step 1 -->
                <div class="flex flex-col items-center gap-2 md:gap-3">
                  <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center text-white bg-blue-600 border-4 border-white shadow-sm ring-4 ring-slate-50 transition-colors">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                  </div>
                  <span class="text-[9px] md:text-[11px] font-bold text-blue-600 uppercase tracking-widest text-center">{{ i18n.t('order.step_placed') }}</span>
                </div>
                <!-- Step 2 -->
                <div class="flex flex-col items-center gap-2 md:gap-3">
                  <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center border-4 border-white shadow-sm ring-4 ring-slate-50 transition-colors" :class="stepStatus >= 2 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-400'">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                  </div>
                  <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-widest text-center" :class="stepStatus >= 2 ? 'text-blue-600' : 'text-slate-400'">{{ i18n.t('order.step_shipping') }}</span>
                </div>
                <!-- Step 3 -->
                <div class="flex flex-col items-center gap-2 md:gap-3">
                  <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center border-4 border-white shadow-sm ring-4 ring-slate-50 transition-colors" :class="stepStatus >= 3 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-400'">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  </div>
                  <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-widest text-center" :class="stepStatus >= 3 ? 'text-blue-600' : 'text-slate-400'">{{ i18n.t('order.step_completed') }}</span>
                </div>
              </div>
            </div>

            <!-- Shipping Info -->
            <div class="bg-slate-50 rounded-[1.5rem] p-5 md:p-6">
              <h3 class="text-sm md:text-base font-bold text-slate-900 mb-5 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                {{ i18n.t('order.shipping_detail') }}
              </h3>
              <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                  <div class="sm:w-32 shrink-0"><p class="text-[12px] sm:text-[13px] font-medium text-slate-500">{{ i18n.t('order.receiver') }}:</p></div>
                  <p class="text-[13px] sm:text-[14px] font-bold text-slate-900">{{ orderStore.current.receiver_name }}</p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                  <div class="sm:w-32 shrink-0"><p class="text-[12px] sm:text-[13px] font-medium text-slate-500">{{ i18n.t('auth.phone') }}:</p></div>
                  <p class="text-[13px] sm:text-[14px] font-bold text-slate-900">{{ orderStore.current.phone }}</p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                  <div class="sm:w-32 shrink-0"><p class="text-[12px] sm:text-[13px] font-medium text-slate-500">{{ i18n.t('order.address') }}:</p></div>
                  <p class="text-[13px] sm:text-[14px] font-bold text-slate-900 leading-snug">{{ orderStore.current.shipping_address }}</p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                  <div class="sm:w-32 shrink-0"><p class="text-[12px] sm:text-[13px] font-medium text-slate-500">{{ i18n.t('order.payment_method') }}:</p></div>
                  <div class="flex flex-wrap items-center gap-2 mt-1 sm:mt-0">
                    <span class="px-3 py-1 bg-slate-800 text-white rounded-lg text-[9px] sm:text-[10px] font-bold uppercase tracking-widest">
                      {{ orderStore.current.payment_method === 'cod' ? i18n.t('order.cod_short') : orderStore.current.payment_method }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Sidebar: Items & Total -->
        <div class="space-y-6">
          <div class="bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm summary-card-main">
            <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
              <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" /></svg>
              {{ i18n.t('order.items_purchased') }}
            </h3>
            
            <div class="divide-y divide-slate-100 mb-6">
              <div v-for="item in orderStore.current.items" :key="item.id" class="py-4 flex gap-4 items-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-slate-50 rounded-xl p-1.5 shrink-0">
                  <img :src="getImageUrl(item.product?.hinh_anh)" class="w-full h-full object-contain" @error="onImgError" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-bold text-slate-900 text-[13px] md:text-sm truncate">{{ item.product?.name }}</h4>
                  <p class="text-slate-500 font-medium text-[10px] md:text-[11px] mt-1">{{ i18n.t('order.quantity') }}: <span class="font-bold text-slate-800">{{ item.quantity }}</span></p>
                </div>
                <div class="text-right flex flex-col items-end shrink-0">
                  <p class="font-bold text-rose-600 text-[13px] md:text-sm">{{ fmt(item.price_at_purchase * item.quantity) }}</p>
                  <p class="text-[9px] md:text-[10px] text-slate-400 font-bold mt-0.5">{{ fmt(item.price_at_purchase) }}/{{ i18n.t('order.item_unit') }}</p>
                  
                  <router-link 
                    v-if="orderStore.current.status === 'completed'" 
                    :to="`/products/${item.product_id}`" 
                    class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/60 font-bold text-[9px] md:text-[10px] uppercase tracking-widest rounded-full transition-all active:scale-95 shadow-sm"
                  >
                    <span class="text-sm">★</span> {{ i18n.t('product.reviews') }}
                  </router-link>
                </div>
              </div>
            </div>

            <div class="space-y-3 text-[13px] md:text-[14px] font-medium border-t border-slate-100 pt-5">
              <div class="flex justify-between text-slate-500">
                <span>{{ i18n.t('cart.subtotal') }}</span>
                <span class="text-slate-900 font-bold">{{ fmt(calculateSubtotal()) }}</span>
              </div>
              <div v-if="orderStore.current.discount_amount > 0" class="flex justify-between text-emerald-600">
                <span>{{ i18n.t('cart.discount') }}</span>
                <span class="font-bold">-{{ fmt(orderStore.current.discount_amount) }}</span>
              </div>
              <div class="h-px bg-slate-100 my-2"></div>
              <div class="flex justify-between items-center py-2 w-full gap-4">
                <span class="text-[15px] md:text-base font-bold text-slate-900 whitespace-nowrap">{{ i18n.t('order.total_payment') }}</span>
                <span class="text-xl md:text-2xl font-black text-rose-600 tracking-tighter text-right final-total">{{ fmt(orderStore.current.total_amount) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 shadow-sm">
      <p class="text-slate-500 font-bold text-lg">Không tìm thấy đơn hàng hoặc đơn hàng không tồn tại.</p>
    </div>
  </div>
</template>

<script setup>
// =====================================================================
// [Phan Đình Hạnh - 4.1.8] OrderDetailView — Chi tiết đơn hàng
// LUỒNG:
//   Trang load (route /orders/:id) → fetchOrder(id) → GET /orders/{id}
//   → OrderController@show → kiểm tra quyền sở hữu → trả dữ liệu đơn + items + voucher
//   Hiển thị: timeline tiến độ đơn, thông tin giao hàng, danh sách sản phẩm, tổng tiền
//   Nếu đơn = 'completed' → hiện nút "Đánh giá" bên cạnh từng sản phẩm
// =====================================================================
import { onMounted, onUnmounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useOrderStore } from '../stores/order'
import { useI18nStore } from '../stores/i18n'
import { useUtils } from '../composables/useUtils'

const route      = useRoute()      // Lấy id đơn hàng từ URL (vd: /orders/42 → route.params.id = 42)
const orderStore = useOrderStore() // Lưu đơn hàng hiện tại vào orderStore.current
const i18n = useI18nStore()
const { fmtPrice: fmt, getImageUrl, fmtDate: formatDate } = useUtils()

// BƯỚC 1: Load trang → gọi API lấy chi tiết đơn hàng
// GET /orders/{id} → OrderController@show → kiểm tra quyền (403 nếu không phải chủ đơn)
onMounted(async () => {
  await orderStore.fetchOrder(route.params.id) // Lưu vào orderStore.current
})

// Dọn dữ liệu khi rời trang → tránh hiển thị đơn cũ khi vào trang chi tiết đơn khác
onUnmounted(() => {
  orderStore.current = null
})

// Tính bước hiện tại trên timeline (1=Đã đặt, 2=Đang giao, 3=Hoàn tất, -1=Đã hủy)
const stepStatus = computed(() => {
  const status = orderStore.current?.status
  if (['cancelled'].includes(status)) return -1
  if (['pending', 'confirmed'].includes(status)) return 1  // Chờ duyệt
  if (['processing', 'shipping'].includes(status)) return 2 // Đang giao
  if (['shipped', 'completed'].includes(status)) return 3   // Hoàn tất
  return 0
})

// Tính % thanh tiến trình (dùng để vẽ thanh progress bar trên timeline)
const progressWidth = computed(() => {
  if (stepStatus.value <= 1) return '0%'
  if (stepStatus.value === 2) return '50%'
  if (stepStatus.value >= 3) return '100%'
  return '0%'
})

// Tính tổng tiền hàng (chưa giảm) = Σ(price_at_purchase × quantity)
// Dùng giá lúc đặt hàng (price_at_purchase), không bị ảnh hưởng bởi thay đổi giá sau này
function calculateSubtotal() {
  return orderStore.current?.items?.reduce((sum, item) => sum + (item.price_at_purchase * item.quantity), 0) || 0
}

// Map status key sang tên hiển thị (vi/en tùy ngôn ngữ đã chọn)
function statusLabel(s) {
  return { 
    pending:    i18n.t('order.status_pending') || 'Chờ duyệt', 
    confirmed:  i18n.locale === 'vi' ? 'Đã xác nhận' : 'Confirmed',
    processing: i18n.t('order.status_processing') || 'Đang xử lý',
    shipping:   i18n.t('order.status_shipping') || 'Đang giao hàng',
    shipped:    i18n.locale === 'vi' ? 'Đã bàn giao vận chuyển' : 'Shipped',
    completed:  i18n.t('order.status_completed') || 'Hoàn tất', 
    cancelled:  i18n.t('order.status_cancelled') || 'Đã hủy' 
  }[s] || s
}

// Map status key sang class CSS màu sắc cho badge trạng thái
function statusClass(s) {
  return {
    pending:    'bg-amber-400 text-slate-900 border-amber-500',
    confirmed:  'bg-indigo-50 text-indigo-600 border-indigo-100',
    processing: 'bg-indigo-50 text-indigo-600 border-indigo-100',
    shipping:   'bg-blue-50 text-blue-600 border-blue-100',
    shipped:    'bg-cyan-50 text-cyan-600 border-cyan-100',
    completed:  'bg-emerald-500 text-white border-emerald-600',
    cancelled:  'bg-rose-50 text-rose-600 border-rose-100',
  }[s] || 'bg-slate-50 text-slate-600 border-slate-100'
}

function onImgError(e) {
  e.target.src = 'https://via.placeholder.com/400'
}
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
  .py-12 { padding-top: 20px; }
  .text-3xl { font-size: 1.5rem; }
  .order-card-main, .shipping-card-main, .summary-card-main { border-radius: 1.25rem; padding: 20px !important; }
  .item-row { gap: 12px; padding: 15px 0; }
  .img-box { width: 60px; height: 60px; }
  .item-name { font-size: 1rem; }
  .item-price { font-size: 1rem; }
  .info-grid { gap: 20px; }
  .final-total { font-size: 1.5rem; }
}
</style>
