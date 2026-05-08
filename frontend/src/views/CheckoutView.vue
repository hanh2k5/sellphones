<template>
  <div class="max-w-7xl mx-auto px-4 lg:px-8 py-12 bg-[#f9f9f9] min-h-screen">
    <div class="flex flex-col lg:flex-row gap-12">
      
      <!-- Checkout Form -->
      <div class="flex-1 space-y-10">
        <h1 class="text-3xl font-bold text-slate-900">{{ i18n.t('checkout.shipping_info') }}</h1>

        <div class="bg-white rounded-[2rem] p-10 border border-slate-100 shadow-sm">
          <form novalidate class="space-y-8">
            <div class="space-y-6">
              <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">{{ i18n.t('checkout.shipping_name') }}</label>
                <input 
                  type="text" 
                  v-model="form.name"
                  maxlength="50"
                  @input="errors && (errors.receiver_name = null)"
                  :placeholder="i18n.t('checkout.name_placeholder')"
                  class="w-full bg-[#f3f4f6] border-none rounded-xl px-6 py-4 text-[15px] focus:ring-2 focus:ring-blue-100 transition-all font-medium"
                  :class="{'input-error': errors?.receiver_name}"
                />
                <p v-if="errors?.receiver_name" class="form-error-label text-rose-500 text-xs mt-1">{{ errors.receiver_name[0] }}</p>
              </div>
              <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">{{ i18n.t('checkout.phone') }}</label>
                <input 
                  type="text" 
                  v-model="form.phone"
                  maxlength="10"
                  @input="errors && (errors.phone = null); form.phone = form.phone.replace(/[^0-9]/g, '')"
                  :placeholder="i18n.t('checkout.phone_placeholder')"
                  class="w-full bg-[#f3f4f6] border-none rounded-xl px-6 py-4 text-[15px] focus:ring-2 focus:ring-blue-100 transition-all font-medium"
                  :class="{'input-error': errors?.phone}"
                />
                <p v-if="errors?.phone" class="form-error-label text-rose-500 text-xs mt-1">{{ errors.phone[0] }}</p>
              </div>
              <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">{{ i18n.t('checkout.address') }}</label>
                <textarea 
                  v-model="form.shipping_address"
                  @input="errors && (errors.shipping_address = null)"
                  :placeholder="i18n.t('checkout.address_placeholder')"
                  rows="3"
                  class="w-full bg-[#f3f4f6] border-none rounded-xl px-6 py-4 text-[15px] focus:ring-2 focus:ring-blue-100 transition-all font-medium"
                  :class="{'input-error': errors?.shipping_address}"
                ></textarea>
                <p v-if="errors?.shipping_address" class="form-error-label text-rose-500 text-xs mt-1">{{ errors.shipping_address[0] }}</p>
              </div>
            </div>

            <div class="pt-6 border-t border-slate-100 space-y-4">
              <label class="flex items-center gap-4 p-6 bg-[#f3f4f6] rounded-2xl cursor-pointer transition-all border-2 border-transparent"
                :class="form.payment_method === 'cod' ? 'border-blue-600 bg-white shadow-md' : ''">
                <input type="radio" v-model="form.payment_method" value="cod" class="w-5 h-5 accent-blue-600" />
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
                <div>
                  <p class="font-bold text-slate-900">{{ i18n.t('checkout.cod') }}</p>
                  <p class="text-xs text-slate-500 font-medium mt-1">{{ i18n.t('checkout.cod_desc') }}</p>
                </div>
              </label>

              <label class="flex items-center gap-4 p-6 bg-[#f3f4f6] rounded-2xl cursor-pointer transition-all border-2 border-transparent"
                :class="form.payment_method === 'momo' ? 'border-[#a50064] bg-[#fff0f8] shadow-md' : ''">
                <input type="radio" v-model="form.payment_method" value="momo" class="w-5 h-5 accent-[#a50064]" />
                <div class="w-10 h-10 bg-[#a50064] rounded-xl flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                  <p class="font-bold text-[#a50064]">{{ i18n.t('checkout.momo') }}</p>
                  <p class="text-xs text-slate-500 font-medium mt-1">{{ i18n.t('checkout.momo_desc') }}</p>
                </div>
              </label>
            </div>
            
            <button @click.prevent="handleCheckout" :disabled="loading" class="w-full bg-[#1c1c1e] hover:bg-black text-white font-bold py-5 rounded-2xl transition-all active:scale-95 text-lg shadow-xl uppercase tracking-widest flex items-center justify-center gap-2">
              <span v-if="loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ loading ? i18n.t('common.processing').toUpperCase() : i18n.t('checkout.place_order').toUpperCase() }}
            </button>
          </form>
        </div>
      </div>

      <!-- Order Side Summary -->
      <div class="w-full lg:w-[450px]">
        <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-sm sticky top-24">
          <h2 class="text-xl font-bold text-slate-900 mb-8">{{ i18n.t('checkout.your_order') }}</h2>
          
          <div class="space-y-6 mb-10">
            <div v-for="item in cartStore.items" :key="item.id" class="flex gap-4 items-center">
              <div class="w-16 h-16 bg-slate-50 rounded-xl p-1 shrink-0">
                <img :src="getImageUrl(item.product?.hinh_anh)" class="w-full h-full object-contain" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-bold text-sm text-slate-800 truncate">{{ item.product?.name }}</p>
                <p class="text-xs text-slate-400 font-medium">x{{ item.quantity }}</p>
              </div>
              <p class="font-bold text-sm text-slate-900">{{ fmt(item.product?.price * item.quantity) }}</p>
            </div>
          </div>

          <div class="space-y-4 pt-6 border-t border-slate-100 text-[15px] font-medium">
            <div class="flex justify-between text-slate-400">
              <span>{{ i18n.t('cart.subtotal') }}:</span>
              <span class="text-slate-900 font-bold">{{ fmt(cartStore.tongTien) }}</span>
            </div>
            <div v-if="cartStore.tienGiam > 0" class="flex justify-between text-[#28a745]">
              <span>{{ i18n.t('admin.manage_vouchers') }}:</span>
              <span class="font-bold">-{{ fmt(cartStore.tienGiam) }}</span>
            </div>
            <div class="h-px bg-slate-100 my-2"></div>
            <div class="flex justify-between items-center">
              <span class="text-lg font-bold text-slate-900">{{ i18n.t('order.total') }}:</span>
              <span class="text-2xl font-bold text-red-600 tracking-tight">{{ fmt(cartStore.thanhToan()) }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useCartStore } from '../stores/cart'
import { useOrderStore } from '../stores/order'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'
import { useUtils } from '../composables/useUtils'

const { fmtPrice: fmt, getImageUrl } = useUtils()
const cartStore = useCartStore()
const orderStore = useOrderStore()
const authStore = useAuthStore()
const i18n = useI18nStore()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const errors = ref({})

onMounted(async () => {
  if (cartStore.items.length === 0) await cartStore.fetchCart()
})

const form = ref({ 
  name: authStore.user?.name || '',
  phone: authStore.user?.phone || '', 
  shipping_address: authStore.user?.address || '',
  payment_method: 'cod' 
})

async function handleCheckout() {
  errors.value = {}
  form.value.name = form.value.name?.trim() || ''
  
  let hasError = false
  if (!form.value.name) {
    errors.value.receiver_name = [i18n.t('checkout.name_error') || 'Họ tên là bắt buộc']
    hasError = true
  }
  if (!/^0[0-9]{9}$/.test(form.value.phone)) {
    errors.value.phone = [i18n.t('checkout.phone_error') || 'SĐT không hợp lệ']
    hasError = true
  }
  if (!form.value.shipping_address?.trim()) {
    errors.value.shipping_address = [i18n.t('checkout.address_error') || 'Địa chỉ là bắt buộc']
    hasError = true
  }

  if (hasError) return

  loading.value = true
  
  try {
    const res = await orderStore.checkout({
      receiver_name: form.value.name,
      phone: form.value.phone,
      shipping_address: form.value.shipping_address,
      payment_method: form.value.payment_method,
      voucher_code: cartStore.appliedVoucher?.code
    })
    loading.value = false

    if (res.success) {
      toast.success(i18n.t('checkout.success_title') || 'Đặt hàng thành công!')
      cartStore.clearCart()
      router.push({ name: 'home' }) // Temporary redirect to home until Order List is done
    } else {
      if (res.errors) errors.value = res.errors
      else toast.error(res.message)
    }
  } catch (e) {
    loading.value = false
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || e.response.data
    } else {
      toast.error(e.response?.data?.message || 'Có lỗi xảy ra!')
    }
  }
}
</script>

<style scoped>
.input-error {
  border: 1.5px solid #ef4444 !important;
  background-color: #fef2f2 !important;
}
</style>
