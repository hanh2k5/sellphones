<template>
  <div class="max-w-7xl mx-auto px-4 lg:px-8 py-12 bg-[#f9f9f9] min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 md:mb-10 border-b border-slate-100 pb-4 md:pb-6 gap-3">
      <h1 class="text-2xl md:text-[40px] font-bold text-slate-900 leading-tight">{{ i18n.t('cart.title') }}.</h1>
      <button v-if="cartStore.items.length > 0" 
        @click="confirmClearCart" 
        class="text-rose-500 font-bold text-xs md:text-sm hover:text-rose-600 transition-colors flex items-center gap-2 group">
        <svg class="w-4 h-4 md:w-5 md:h-5 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        {{ i18n.t('common.clear_filter') }}
      </button>
    </div>

    <div v-if="!cartStore.loading && cartStore.items.length === 0" class="text-center py-12 md:py-24 bg-white rounded-[1.5rem] md:rounded-[2.5rem] border border-slate-100 shadow-sm px-6">
      <div class="text-5xl md:text-8xl mb-4 md:mb-6">🛒</div>
      <p class="text-lg md:text-2xl font-bold text-slate-400 mb-6 md:mb-8">{{ i18n.t('cart.empty') }}</p>
      <router-link to="/products" class="inline-block bg-blue-600 text-white px-6 md:px-10 py-3 md:py-4 rounded-xl md:rounded-2xl font-bold transition-all hover:bg-blue-700">
        {{ i18n.t('cart.go_shopping') }}
      </router-link>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-10">
      <!-- Item List -->
      <div class="lg:col-span-2 space-y-4 md:space-y-6">
        <div v-for="item in cartStore.items" :key="item.id" 
          class="bg-white rounded-[1.25rem] md:rounded-[2rem] p-4 md:p-8 border border-slate-100 flex flex-col md:flex-row gap-4 md:gap-8 relative group transition-all hover:shadow-md">
          
          <!-- Remove Icon -->
          <button @click="confirmRemove(item)" class="absolute top-3 right-3 md:top-4 md:right-4 text-slate-300 hover:text-rose-500 transition-colors z-10">
            <svg class="w-5 h-5 md:w-6 md:h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
          </button>

          <div class="w-full md:w-32 h-32 md:h-32 bg-slate-50 rounded-xl md:rounded-2xl p-2 shrink-0 flex items-center justify-center">
            <img :src="getImageUrl(item.product?.hinh_anh)" :alt="item.product?.name" class="w-full h-full object-contain" />
          </div>

          <div class="flex-1">
            <div class="flex flex-col md:flex-row justify-between items-start mb-3 md:mb-4 gap-1 md:gap-2">
              <div class="pr-8 md:pr-0">
                <h3 class="font-bold text-base md:text-xl text-slate-900 mb-0.5 md:mb-1 leading-tight">{{ item.product?.name }}</h3>
                <p class="text-[10px] md:text-[11px] text-slate-400 font-medium uppercase tracking-wider">{{ i18n.t('product.variant_default') }}</p>
              </div>
              <div class="text-left md:text-right">
                <p class="text-lg md:text-2xl font-bold text-slate-900">{{ fmt(item.product?.price * item.quantity) }}</p>
                <p class="text-[10px] md:text-[11px] text-slate-400 font-bold mt-0.5 md:mt-1">{{ i18n.t('product.price') }}: {{ fmt(item.product?.price) }}</p>
              </div>
            </div>

            <!-- Qty Selector Pill -->
            <div class="flex items-center bg-slate-100 rounded-full w-fit p-1">
              <button @click="changeQty(item, item.quantity - 1)" class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-900 font-bold">−</button>
              <span class="w-10 text-center text-sm font-bold text-slate-900">{{ item.quantity }}</span>
              <button @click="changeQty(item, item.quantity + 1)" class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-900 font-bold">+</button>
            </div>
          </div>
        </div>

      </div>

      <!-- Order Summary Card -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-sm h-fit sticky top-24">
          <h3 class="text-xl font-bold text-slate-900 mb-8">{{ i18n.t('cart.summary') }}</h3>
          
          <div class="space-y-5 mb-10 text-[15px] font-medium">
            <div class="flex justify-between text-slate-400">
              <span>{{ i18n.t('cart.subtotal') }}</span>
              <span class="text-slate-900 font-bold">{{ fmt(cartStore.tongTien) }}</span>
            </div>
            <div v-if="cartStore.tienGiam > 0" class="flex justify-between text-[#28a745]">
              <span>{{ i18n.t('cart.discount') }}</span>
              <span class="font-bold">-{{ fmt(cartStore.tienGiam) }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>{{ i18n.t('cart.shipping') }}</span>
              <span class="text-[#28a745] font-bold">{{ i18n.t('product.variant_default') === 'Standard' ? 'Free' : 'Miễn phí' }}</span>
            </div>
            <div class="h-px bg-slate-100 my-2"></div>
            
            <!-- Applied Voucher Indicator (Moved up) -->
            <div v-if="cartStore.appliedVoucher" class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex justify-between items-center animate-fade-in mb-4">
              <div class="flex items-center gap-2 text-emerald-700 text-sm font-bold">
                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>
                <span>{{ i18n.t('cart.voucher_applied') }}: {{ cartStore.appliedVoucher.code }}</span>
              </div>
              <button @click="removeVoucher" class="text-rose-500 text-xs font-bold hover:underline">{{ i18n.t('cart.remove') }}</button>
            </div>

            <div class="h-px bg-slate-100 my-4"></div>

            <div class="flex justify-between items-center">
              <span class="text-lg font-bold text-slate-900">{{ i18n.t('cart.total') }}</span>
              <span class="text-[32px] font-bold text-blue-600 tracking-tight">{{ fmt(cartStore.thanhToan()) }}</span>
            </div>
          </div>

          <router-link to="/checkout" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-5 rounded-[1.25rem] shadow-xl shadow-blue-500/20 transition-all hover:-translate-y-1 active:scale-95 text-lg mb-6">
            {{ i18n.t('checkout.place_order').toUpperCase() }}
          </router-link>

          <div class="flex items-center justify-center gap-2 text-slate-400 text-xs font-bold">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            {{ i18n.locale === 'vi' ? 'Thanh toán an toàn & bảo mật' : 'Secure & encrypted payment' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCartStore } from '../stores/cart'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'
import { useUtils } from '../composables/useUtils'
import Swal from 'sweetalert2'

const { fmtPrice: fmt, getImageUrl } = useUtils()
const cartStore = useCartStore()
const i18n = useI18nStore()
const toast = useToast()

onMounted(async () => {
  await cartStore.fetchCart()
})

async function changeQty(item, newQty) {
  if (newQty < 1) { confirmRemove(item); return }
  const result = await cartStore.updateQty(item.id, newQty)
  if (!result.success) toast.error(result.message)
}

async function confirmRemove(item) {
  const result = await Swal.fire({
    title: i18n.locale === 'vi' ? 'Xóa sản phẩm?' : 'Remove item?',
    text: i18n.locale === 'vi' ? 'Bạn có chắc muốn xóa sản phẩm này khỏi giỏ?' : 'Are you sure you want to remove this item?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: i18n.locale === 'vi' ? 'Đúng, xóa nó!' : 'Yes, remove it!',
    cancelButtonText: i18n.locale === 'vi' ? 'Bỏ qua' : 'Cancel'
  })

  if (result.isConfirmed) {
    const res = await cartStore.removeFromCart(item.id)
    if (res.success) {
      toast.info(i18n.locale === 'vi' ? 'Đã xóa khỏi giỏ hàng' : 'Removed from cart')
    } else {
      toast.error(i18n.locale === 'vi' ? 'Lỗi khi xóa' : 'Error removing item')
    }
  }
}

async function confirmClearCart() {
  const result = await Swal.fire({
    title: i18n.locale === 'vi' ? 'Dọn sạch túi hàng?' : 'Clear bag?',
    text: i18n.locale === 'vi' ? 'Bạn có chắc muốn xóa TOÀN BỘ sản phẩm?' : 'Are you sure you want to clear your entire bag?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: i18n.locale === 'vi' ? 'Dọn sạch ngay' : 'Clear now!',
    cancelButtonText: i18n.locale === 'vi' ? 'Bỏ qua' : 'Cancel'
  })

  if (result.isConfirmed) {
    await cartStore.clearCart()
    toast.success(i18n.locale === 'vi' ? 'Đã dọn sạch túi hàng' : 'Bag cleared')
  }
}

function removeVoucher() {
  cartStore.appliedVoucher = null
  cartStore.tienGiam = 0
  localStorage.removeItem('cart_voucher')
  localStorage.removeItem('cart_discount')
  toast.info(i18n.locale === 'vi' ? 'Đã hủy mã giảm giá' : 'Voucher removed')
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
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
