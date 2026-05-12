<template>
  <div class="checkout-container">
    <div class="checkout-grid">
      <!-- Checkout Form -->
      <div class="checkout-form-side">
        <!-- Back Button -->
        <button @click="$router.back()" class="btn-back-modern">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
          <span class="desktop-only">{{ i18n.t('common.back_to_cart') }}</span>
        </button>

        <h1 class="page-title">{{ i18n.t('checkout.shipping_info') }}</h1>

        <div class="checkout-card">
          <form @submit.prevent="handleCheckout" class="main-form">
            <div class="form-section">
              <div class="input-group">
                <label class="input-label">{{ i18n.t('checkout.shipping_name') }}</label>
                <input type="text" v-model="form.name" :placeholder="i18n.t('checkout.name_placeholder')"
                  class="modern-input" :class="{'input-error': errors?.receiver_name}" />
                <p v-if="errors?.receiver_name" class="error-text">{{ errors.receiver_name[0] }}</p>
              </div>

              <div class="input-group">
                <label class="input-label">{{ i18n.t('checkout.phone') }}</label>
                <input type="text" v-model="form.phone" inputmode="tel" maxlength="10" 
                  :placeholder="i18n.t('checkout.phone_placeholder')"
                  class="modern-input" :class="{'input-error': errors?.phone}" />
                <p v-if="errors?.phone" class="error-text">{{ errors.phone[0] }}</p>
              </div>

              <div class="input-group">
                <label class="input-label">{{ i18n.t('checkout.address') }}</label>
                <textarea v-model="form.shipping_address" :placeholder="i18n.t('checkout.address_placeholder')"
                  rows="2" class="modern-input" :class="{'input-error': errors?.shipping_address}"></textarea>
                <p v-if="errors?.shipping_address" class="error-text">{{ errors.shipping_address[0] }}</p>
              </div>
            </div>

            <div class="payment-section">
              <label class="payment-option" :class="{ active: form.payment_method === 'cod' }">
                <input type="radio" v-model="form.payment_method" value="cod" />
                <div class="payment-icon cod">💵</div>
                <div class="payment-text">
                  <p class="p-title">{{ i18n.t('checkout.cod') }}</p>
                  <p class="p-desc">{{ i18n.t('checkout.cod_desc') }}</p>
                </div>
              </label>

              <label class="payment-option" :class="{ active: form.payment_method === 'momo' }">
                <input type="radio" v-model="form.payment_method" value="momo" />
                <div class="payment-icon momo">💓</div>
                <div class="payment-text">
                  <p class="p-title">{{ i18n.t('checkout.momo') }}</p>
                  <p class="p-desc">{{ i18n.t('checkout.momo_desc') }}</p>
                </div>
              </label>
            </div>
            
            <button type="submit" :disabled="loading" class="btn-checkout-desktop">
              <span v-if="loading" class="spinner"></span>
              {{ loading ? i18n.t('common.processing').toUpperCase() : i18n.t('checkout.place_order').toUpperCase() }}
            </button>
          </form>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="checkout-summary-side">
        <div class="summary-card">
          <h2 class="summary-title">{{ i18n.t('checkout.your_order') }}</h2>
          
          <div class="order-items">
            <div v-for="item in cartStore.items" :key="item.id" class="order-item">
              <div class="item-img-box">
                <img :src="getImageUrl(item.product?.hinh_anh)" />
              </div>
              <div class="item-info">
                <p class="item-name">{{ item.product?.name }}</p>
                <p class="item-qty">x{{ item.quantity }}</p>
              </div>
              <p class="item-price">{{ fmt(item.product?.price * item.quantity) }}</p>
            </div>
          </div>

          <div class="summary-totals">
            <div class="total-row">
              <span>{{ i18n.t('cart.subtotal') }}</span>
              <span>{{ fmt(cartStore.tongTien) }}</span>
            </div>
            <div v-if="cartStore.tienGiam > 0" class="total-row discount">
              <span>{{ i18n.t('cart.discount') }}</span>
              <span>-{{ fmt(cartStore.tienGiam) }}</span>
            </div>
            <div class="divider"></div>
            <div class="total-row final">
              <span>{{ i18n.t('order.total') }}</span>
              <span class="price-big">{{ fmt(cartStore.thanhToan()) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sticky Mobile Checkout -->
    <div class="sticky-mobile-bar md:hidden">
      <div class="flex justify-between items-center mb-3">
        <span class="text-slate-500 font-bold text-sm">{{ i18n.t('order.total') }}</span>
        <span class="text-red-600 font-black text-xl">{{ fmt(cartStore.thanhToan()) }}</span>
      </div>
      <button @click="handleCheckout" :disabled="loading" class="btn-checkout-sm">
        {{ i18n.t('checkout.place_order').toUpperCase() }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
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

const form = ref({ 
  name: authStore.user?.name || '',
  phone: authStore.user?.phone || '', 
  shipping_address: authStore.user?.address || '',
  payment_method: 'cod' 
})

onMounted(async () => {
  if (cartStore.items.length === 0) await cartStore.fetchCart()
})

async function handleCheckout() {
  errors.value = {}
  let hasError = false
  if (!form.value.name?.trim()) { errors.value.receiver_name = [i18n.t('checkout.name_error')]; hasError = true }
  if (!/^0[0-9]{9}$/.test(form.value.phone)) { errors.value.phone = [i18n.t('checkout.phone_error')]; hasError = true }
  if (!form.value.shipping_address?.trim()) { errors.value.shipping_address = [i18n.t('checkout.address_error')]; hasError = true }
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
    if (res.success) {
      toast.success(i18n.t('checkout.success_title'))
      cartStore.clearCart()
      if (form.value.payment_method === 'momo') {
        router.push({ name: 'payment.momo', query: { order_id: res.order.id, amount: res.order.total_amount } })
      } else {
        router.push({ name: 'orders' })
      }
    } else {
      if (res.errors) errors.value = res.errors
      else toast.error(res.message)
    }
  } catch (e) {
    toast.error(i18n.t('common.error'))
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.checkout-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px 100px; }
.checkout-grid { display: grid; grid-template-columns: 1fr 400px; gap: 40px; }

.page-title { font-size: 28px; font-weight: 800; color: #1e293b; margin-bottom: 30px; }
.btn-back-modern {
  display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e2e8f0;
  padding: 10px 18px; border-radius: 14px; font-size: 14px; font-weight: 700; color: #64748b;
  cursor: pointer; transition: 0.2s; margin-bottom: 25px; width: fit-content;
}
.btn-back-modern:hover { color: #1e293b; border-color: #cbd5e1; background: #f8fafc; transform: translateX(-4px); }

.checkout-card { background: #fff; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }

.input-group { margin-bottom: 20px; }
.input-label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
.modern-input { width: 100%; background: #f8fafc; border: 2px solid transparent; border-radius: 12px; padding: 14px 18px; font-size: 15px; font-weight: 600; outline: none; transition: 0.2s; }
.modern-input:focus { background: #fff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.05); }
.input-error { border-color: #ef4444 !important; }
.error-text { color: #ef4444; font-size: 11px; font-weight: 700; margin-top: 4px; }

.payment-section { margin-top: 30px; display: grid; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 30px; }
.payment-option { display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 16px; border: 2px solid #f1f5f9; cursor: pointer; transition: 0.2s; position: relative; }
.payment-option input { position: absolute; opacity: 0; }
.payment-option.active { border-color: #3b82f6; background: #eff6ff; }
.payment-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.payment-icon.cod { background: #dcfce7; }
.payment-icon.momo { background: #fce7f3; }
.p-title { font-weight: 700; color: #1e293b; font-size: 15px; }
.p-desc { font-size: 12px; color: #64748b; font-weight: 500; }

.btn-checkout-desktop { width: 100%; background: #1e293b; color: #fff; font-weight: 800; border-radius: 16px; padding: 18px; border: none; cursor: pointer; margin-top: 30px; font-size: 15px; letter-spacing: 0.05em; transition: 0.3s; }
.btn-checkout-desktop:hover { background: #334155; transform: translateY(-2px); }

/* Summary */
.summary-card { background: #fff; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; position: sticky; top: 100px; }
.summary-title { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 25px; }
.order-items { display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px; }
.order-item { display: flex; align-items: center; gap: 12px; }
.item-img-box { width: 50px; height: 50px; border-radius: 10px; background: #f8fafc; padding: 5px; flex-shrink: 0; }
.item-img-box img { width: 100%; height: 100%; object-fit: contain; }
.item-info { flex: 1; }
.item-name { font-size: 13px; font-weight: 700; color: #1e293b; line-height: 1.3; }
.item-qty { font-size: 11px; color: #94a3b8; font-weight: 700; }
.item-price { font-size: 13px; font-weight: 800; color: #1e293b; }

.summary-totals { border-top: 1px solid #f1f5f9; padding-top: 20px; }
.total-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #64748b; font-weight: 600; }
.total-row.discount { color: #10b981; }
.divider { height: 1px; background: #f1f5f9; margin: 10px 0; }
.total-row.final { color: #1e293b; font-weight: 800; font-size: 16px; margin-top: 10px; }
.price-big { color: #ef4444; font-size: 24px; }

/* Sticky Mobile */
.sticky-mobile-bar { display: none; }

@media (max-width: 1024px) {
  .checkout-grid { grid-template-columns: 1fr; }
  .summary-card { position: static; margin-top: 30px; }
}

@media (max-width: 768px) {
  .checkout-container { padding: 15px 15px 100px; }
  .page-title { font-size: 22px; margin-bottom: 20px; }
  .checkout-card { padding: 20px; border-radius: 18px; }
  .btn-checkout-desktop { display: none; }
  .desktop-only { display: none; }
  
  .sticky-mobile-bar {
    display: flex; flex-direction: column; position: fixed; bottom: 0; left: 0; right: 0; 
    background: #fff; padding: 15px 20px; z-index: 1000;
    box-shadow: 0 -10px 30px rgba(0,0,0,0.08); border-top: 1px solid #f1f5f9;
  }
  .btn-checkout-sm {
    background: #1e293b; color: #fff; font-weight: 800; border-radius: 12px;
    padding: 14px; border: none; font-size: 15px;
  }
}

.spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
