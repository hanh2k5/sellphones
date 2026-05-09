<template>
  <div class="max-w-4xl mx-auto px-4 py-12 relative z-10">
    <div v-if="orderStore.loading && !orderStore.current" class="text-center py-20">
      <div class="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>

    <div v-else-if="orderStore.current" class="space-y-8 animate-fade-in">
      <!-- Top Bar -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
          <router-link to="/orders" class="text-blue-600 font-bold text-sm hover:underline flex items-center gap-2 mb-4">
            ← {{ i18n.locale === 'vi' ? 'Quay lại danh sách' : 'Back to List' }}
          </router-link>
          <h1 class="text-3xl font-bold text-slate-900 leading-tight">
            {{ i18n.t('order.id') || 'Đơn hàng' }} {{ orderStore.current.order_code }}
          </h1>
          <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-1">
            {{ formatDate(orderStore.current.created_at) }}
          </p>
        </div>
        <div class="flex items-center gap-4">
          <span class="px-6 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-widest border shadow-sm" :class="statusClass(orderStore.current.status)">
            {{ statusLabel(orderStore.current.status) }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content: Items -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
            <h3 class="text-xl font-bold text-slate-900 mb-6">{{ i18n.t('order.items') || 'Sản phẩm đã mua' }}</h3>
            <div class="divide-y divide-slate-100">
              <div v-for="item in orderStore.current.items" :key="item.id" class="py-6 flex gap-6 items-center">
                <div class="w-24 h-24 bg-slate-50 rounded-2xl p-2 shrink-0">
                  <img :src="getImageUrl(item.product?.hinh_anh)" class="w-full h-full object-contain" />
                </div>
                <div class="flex-1">
                  <h4 class="font-bold text-slate-900 text-lg">{{ item.product?.name }}</h4>
                  <p class="text-slate-400 font-medium text-sm">x{{ item.quantity }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-slate-900 text-lg">{{ fmt(item.price_at_purchase * item.quantity) }}</p>
                  <p class="text-xs text-slate-400 font-bold mt-1">{{ i18n.t('product.price') }}: {{ fmt(item.price_at_purchase) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Shipping Info -->
          <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
            <h3 class="text-xl font-bold text-slate-900 mb-6">{{ i18n.t('checkout.shipping_info') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ i18n.t('checkout.shipping_name') }}</p>
                <p class="text-slate-800 font-bold">{{ orderStore.current.receiver_name }}</p>
              </div>
              <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ i18n.t('checkout.phone') }}</p>
                <p class="text-slate-800 font-bold">{{ orderStore.current.phone }}</p>
              </div>
              <div class="sm:col-span-2">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ i18n.t('checkout.address') }}</p>
                <p class="text-slate-800 font-bold leading-relaxed">{{ orderStore.current.shipping_address }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar: Summary & Payment -->
        <div class="space-y-6">
          <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm h-fit">
            <h3 class="text-xl font-bold text-slate-900 mb-6">{{ i18n.t('cart.summary') }}</h3>
            <div class="space-y-4 text-[15px] font-medium">
              <div class="flex justify-between text-slate-400">
                <span>{{ i18n.t('cart.subtotal') }}</span>
                <span class="text-slate-900 font-bold">{{ fmt(calculateSubtotal()) }}</span>
              </div>
              <div v-if="orderStore.current.discount_amount > 0" class="flex justify-between text-emerald-600">
                <span>{{ i18n.t('cart.discount') }}</span>
                <span class="font-bold">-{{ fmt(orderStore.current.discount_amount) }}</span>
              </div>
              <div class="h-px bg-slate-100 my-2"></div>
              <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-slate-900">{{ i18n.t('order.total') }}</span>
                <span class="text-2xl font-bold text-blue-600">{{ fmt(orderStore.current.total_amount) }}</span>
              </div>
            </div>

            <div class="mt-8 pt-8 border-t border-slate-100">
              <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">{{ i18n.t('checkout.payment_method') }}</p>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center">
                  <span v-if="orderStore.current.payment_method === 'cod'">💵</span>
                  <span v-else>💳</span>
                </div>
                <div>
                  <p class="font-bold text-slate-900 text-sm uppercase">{{ orderStore.current.payment_method }}</p>
                  <p class="text-[11px] font-bold" :class="orderStore.current.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'">
                    {{ orderStore.current.payment_status === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán' }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOrderStore } from '../stores/order'
import { useI18nStore } from '../stores/i18n'
import { useUtils } from '../composables/useUtils'

const route = useRoute()
const orderStore = useOrderStore()
const i18n = useI18nStore()
const { fmtPrice: fmt, getImageUrl, fmtDate: formatDate } = useUtils()

onMounted(async () => {
  await orderStore.fetchOrder(route.params.id)
})

onUnmounted(() => {
  orderStore.current = null
})

function calculateSubtotal() {
  return orderStore.current?.items?.reduce((sum, item) => sum + (item.price_at_purchase * item.quantity), 0) || 0
}

function statusLabel(s) {
  return { 
    pending:    i18n.t('order.status_pending') || 'Chờ duyệt', 
    shipping:   i18n.t('order.status_shipping') || 'Đang giao',
    completed:  i18n.t('order.status_completed') || 'Hoàn thành', 
    cancelled:  i18n.t('order.status_cancelled') || 'Đã hủy' 
  }[s] || s
}

function statusClass(s) {
  return {
    pending:    'bg-amber-50 text-amber-600 border-amber-100',
    shipping:   'bg-blue-50 text-blue-600 border-blue-100',
    completed:  'bg-emerald-50 text-emerald-600 border-emerald-100',
    cancelled:  'bg-rose-50 text-rose-600 border-rose-100',
  }[s] || 'bg-slate-50 text-slate-600 border-slate-100'
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
