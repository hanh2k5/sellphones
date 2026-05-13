<template>
  <div class="min-h-screen bg-[#f3f4f6] flex items-center justify-center p-4 md:p-8 font-['Inter',sans-serif]">
    <transition name="fade" appear>
      <div class="w-full max-w-[380px] bg-white rounded-[2rem] shadow-2xl shadow-slate-200/60 overflow-hidden relative border border-slate-100">
        
        <!-- Header MoMo -->
        <div class="bg-[#ae146d] p-3 md:p-4 text-white relative">
          <div class="flex justify-between items-center mb-1.5 md:mb-2">
            <div class="flex items-center gap-1.5">
              <svg width="24" height="24" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="100" height="100" rx="20" fill="white"/>
                <path d="M78 30H22C19.7909 30 18 31.7909 18 34V66C18 68.2091 19.7909 70 22 70H78C80.2091 70 82 68.2091 82 66V34C82 31.7909 80.2091 30 78 30Z" fill="#AE146D"/>
                <path d="M35 42V58M35 42L42 50L49 42V58M58 42C54 42 54 46 54 50C54 54 54 58 58 58C62 58 62 54 62 50C62 46 62 42 58 42ZM70 42V58M70 42L77 50L84 42V58" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span class="font-black text-lg tracking-tighter">MoMo</span>
            </div>
            <div class="text-right">
              <p class="text-[7px] font-bold uppercase tracking-widest opacity-70">
                {{ i18n.t('checkout.momo_timer') }}
              </p>
              <p class="text-xs md:text-sm font-mono font-bold tracking-tighter">{{ formatTime(timeLeft) }}</p>
            </div>
          </div>
          <h2 class="text-sm md:text-base font-black tracking-tight leading-tight">
            {{ i18n.t('checkout.momo_order_payment') }}
          </h2>
          <p class="text-[9px] font-medium opacity-80">{{ orderCode }}</p>
        </div>

        <!-- QR Content -->
        <div class="p-4 md:p-6 text-center space-y-3 md:space-y-5">
          <div class="relative inline-block group">
            <div class="relative bg-white p-3 md:p-4 rounded-2xl md:rounded-[1.5rem] border-[3px] border-[#ae146d] shadow-lg overflow-hidden">
              <div class="qr-scan-line"></div>
              <img :src="qrCodeUrl" alt="Momo QR" class="w-32 h-32 md:w-40 md:h-40 object-contain" />
            </div>
          </div>

          <div class="space-y-1">
            <p class="text-slate-400 text-[8px] md:text-[9px] font-bold uppercase tracking-widest">
              {{ i18n.t('checkout.momo_total_amount') }}
            </p>
            <p class="text-2xl md:text-3xl font-black text-slate-900 tracking-tighter leading-none">{{ fmtPrice(amount) }}</p>
          </div>

          <div class="bg-slate-50 rounded-xl p-3 md:p-4 border border-slate-100 flex items-center gap-3 text-left">
            <div class="w-8 h-8 md:w-9 md:h-9 bg-white rounded-lg shadow-sm flex items-center justify-center shrink-0">
              <span class="text-base">📱</span>
            </div>
            <div class="min-w-0">
              <p class="text-[10px] md:text-[11px] font-bold text-slate-900 truncate">
                {{ i18n.t('checkout.momo_scan_instruction') }}
              </p>
              <p class="text-[8px] md:text-[9px] text-slate-400 font-medium leading-tight">
                {{ i18n.t('checkout.momo_scan_desc') }}
              </p>
            </div>
          </div>

          <!-- Buttons -->
          <div class="space-y-2 pt-1">
            <button 
              @click="confirmPayment" 
              :disabled="loading"
              class="w-full bg-[#ae146d] hover:bg-[#8e1059] text-white font-bold py-3 md:py-3.5 rounded-xl shadow-lg shadow-[#ae146d]/20 transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50 text-[12px] md:text-[13px]"
            >
              <span v-if="loading" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ loading ? i18n.t('common.processing').toUpperCase() : i18n.t('checkout.momo_confirm_btn') }}
            </button>
            
            <button @click="cancelPayment" class="w-full text-slate-400 hover:text-slate-900 font-bold text-[8px] md:text-[9px] uppercase tracking-widest transition-colors py-1">
              {{ i18n.t('checkout.momo_cancel_btn') }}
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 p-2 md:p-3 text-center border-t border-slate-100 flex items-center justify-center gap-1.5">
          <svg class="w-3 h-3 text-slate-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
          <span class="text-[7px] md:text-[8px] font-bold text-slate-300 uppercase tracking-widest">
            {{ i18n.t('checkout.momo_security_note') }}
          </span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
/**
 * SV THỰC HIỆN: PHAN ĐÌNH HẠNH
 * MỤC: 4.1.14 - THANH TOÁN QUA CỔNG VÍ ĐIỆN TỬ (FAKE MOMO UI)
 * Chức năng: Giả lập quy trình quét mã QR và xác nhận giao dịch MoMo.
 */
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ordersApi } from '../api'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'
import { useUtils } from '../composables/useUtils'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const i18n = useI18nStore()
const toast = useToast()
const { fmtPrice } = useUtils()

// State quản lý giao dịch
const loading = ref(false)
const orderId = ref(route.query.order_id)
const orderCode = ref('...')
const amount = ref(route.query.amount || 0)
const timeLeft = ref(900) // 15 phút đếm ngược
const qrCodeUrl = ref('https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=MOMO_PAYMENT_' + orderId.value)

let timer = null

/**
 * Khởi tạo dữ liệu khi component được mount
 */
onMounted(async () => {
  if (!orderId.value) {
    toast.error(i18n.t('checkout.momo_order_not_found'))
    router.push('/orders')
    return
  }

  await fetchOrderDetails()
  
  // Bắt đầu bộ đếm ngược thời gian thanh toán
  timer = setInterval(() => {
    if (timeLeft.value > 0) timeLeft.value--
    else handleTimeout()
  }, 1000)
})

/**
 * Dọn dẹp timer khi rời khỏi trang
 */
onUnmounted(() => {
  if (timer) clearInterval(timer)
})

/**
 * Lấy thông tin chi tiết đơn hàng từ API để hiển thị đúng số tiền
 */
async function fetchOrderDetails() {
  try {
    const res = await ordersApi.show(orderId.value)
    // Chặn nếu đơn hàng đã được thanh toán
    if (res.data.payment_status === 'paid') {
      toast.success(i18n.t('checkout.momo_already_paid'))
      router.push('/orders')
      return
    }
    orderCode.value = res.data.order_code
    amount.value = res.data.total_amount
  } catch (e) {
    toast.error(i18n.t('checkout.momo_access_denied'))
    router.push('/orders')
  }
}

async function confirmPayment() {
  loading.value = true
  try {
    const res = await ordersApi.confirmPayment(orderId.value)
    if (res.data.success) {
      await Swal.fire({
        title: i18n.t('common.success'),
        text: i18n.t('checkout.momo_success_alert'),
        icon: 'success',
        confirmButtonColor: '#ae146d',
      })
      router.push('/orders')
    }
  } catch (e) {
    toast.error(e.response?.data?.message || i18n.t('common.error'))
  } finally {
    loading.value = false
  }
}

async function cancelPayment() {
  const result = await Swal.fire({
    title: i18n.t('checkout.momo_cancel_confirm_title'),
    text: i18n.t('checkout.momo_cancel_confirm_text'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ae146d',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: i18n.t('common.confirm'),
    cancelButtonText: i18n.t('common.cancel')
  })

  if (result.isConfirmed) {
    router.push('/orders')
  }
}

function handleTimeout() {
  Swal.fire({
    title: i18n.t('checkout.momo_timeout_title'),
    text: i18n.t('checkout.momo_timeout_text'),
    icon: 'error',
    confirmButtonColor: '#ae146d',
  }).then(() => {
    router.push('/orders')
  })
}

function formatTime(seconds) {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.qr-scan-line {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(to right, transparent, #ae146d, transparent);
  box-shadow: 0 0 15px #ae146d;
  animation: scan 3s ease-in-out infinite;
  z-index: 10;
}

@keyframes scan {
  0% { top: 0%; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 100%; opacity: 0; }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
</style>
