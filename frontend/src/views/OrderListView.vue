<template>
  <div class="max-w-5xl mx-auto px-4 py-12 relative z-10">
    <!-- Header Design -->
    <div class="mb-12 relative">
      <div class="absolute -left-6 top-0 bottom-0 w-1.5 bg-gradient-to-b from-blue-600 to-indigo-600 rounded-full hidden md:block"></div>
      <h1 class="text-4xl font-bold text-slate-800 tracking-tight flex items-center gap-4">
        <span class="text-3xl filter drop-shadow-md">📦</span> {{ i18n.t('order.title') || 'Đơn hàng của tôi' }}
      </h1>
      <p class="text-slate-500 font-medium mt-2 text-[15px]">{{ i18n.t('order.manage_desc') || 'Theo dõi và quản lý lịch sử mua hàng của bạn' }}</p>
    </div>

    <!-- Loading State -->
    <div v-if="orderStore.loading" class="space-y-6">
      <div v-for="i in 3" :key="i" class="backdrop-blur-xl bg-white/40 border border-white/60 rounded-[2.5rem] p-8 animate-pulse">
        <div class="flex justify-between mb-6">
          <div class="h-6 bg-slate-200 rounded-full w-1/4"></div>
          <div class="h-6 bg-slate-200 rounded-full w-20"></div>
        </div>
        <div class="flex gap-4">
          <div class="w-20 h-20 bg-slate-200 rounded-2xl"></div>
          <div class="flex-1 space-y-3">
            <div class="h-4 bg-slate-200 rounded-full w-1/2"></div>
            <div class="h-4 bg-slate-200 rounded-full w-1/3"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="orderStore.orders.length === 0" class="text-center py-32 backdrop-blur-2xl bg-white/40 border border-white/60 rounded-[4rem] shadow-[0_20px_50px_rgba(0,0,0,0.04)]">
      <div class="w-32 h-32 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
        <span class="text-6xl">📭</span>
      </div>
      <h2 class="text-2xl font-bold text-slate-800 mb-4">{{ i18n.t('order.empty') || 'Bạn chưa có đơn hàng nào' }}</h2>
      <p class="text-slate-500 mb-10 font-medium">{{ i18n.t('order.empty_desc') || 'Hãy khám phá các sản phẩm tuyệt vời của chúng tôi!' }}</p>
      <router-link to="/products" class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold px-10 py-4 rounded-2xl shadow-lg shadow-blue-500/25 active:scale-95 transition-all tracking-wider uppercase text-sm group">
        {{ i18n.t('cart.go_shopping') || 'Tiếp tục mua sắm' }}
        <span class="group-hover:translate-x-1 transition-transform">➔</span>
      </router-link>
    </div>

    <!-- Order List -->
    <div v-else class="space-y-8">
      <div v-for="order in orderStore.orders" :key="order.id"
        class="backdrop-blur-xl bg-white/70 rounded-[2.5rem] overflow-hidden border border-white shadow-[0_10px_40px_rgba(0,0,0,0.03)] hover:shadow-[0_20px_60px_rgba(0,0,0,0.06)] hover:-translate-y-1 transition-all duration-500 group">
        
        <!-- Order Card Header -->
        <div class="p-6 sm:p-8 border-b border-slate-100/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gradient-to-r from-white/50 to-transparent">
          <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-slate-100 group-hover:scale-110 transition-transform">
              <span class="text-2xl">📦</span>
            </div>
            <div>
              <h3 class="font-bold text-slate-800 text-lg group-hover:text-blue-600 transition-colors">{{ i18n.t('order.id') || 'Đơn hàng' }} {{ order.order_code }}</h3>
              <p class="text-[13px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ formatDate(order.created_at) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-4 w-full sm:w-auto">
            <span class="px-5 py-2 rounded-xl text-[11px] font-bold uppercase tracking-widest shadow-sm border" :class="statusClass(order.status)">
              {{ statusLabel(order.status) }}
            </span>
          </div>
        </div>

        <!-- Order Card Body -->
        <div class="p-6 sm:p-8">
          <div class="flex flex-col lg:flex-row gap-8 items-start lg:items-center">
            <!-- Product Thumbnails -->
            <div class="flex -space-x-4 hover:space-x-2 transition-all duration-500 py-2 flex-1 overflow-x-auto scrollbar-hide">
              <div v-for="item in order.items?.slice(0, 4)" :key="item.id" class="relative group/img shrink-0">
                <div class="w-20 h-20 bg-white border-2 border-white shadow-md rounded-2xl overflow-hidden group-hover/img:z-10 group-hover/img:-translate-y-2 transition-all duration-300">
                  <img :src="getImageUrl(item.product?.hinh_anh)" class="w-full h-full object-contain p-2" />
                </div>
                <div class="absolute -top-2 -right-2 bg-slate-900 text-white text-[10px] font-bold w-6 h-6 flex items-center justify-center rounded-full border-2 border-white shadow-sm opacity-0 group-hover/img:opacity-100 transition-opacity">
                  x{{ item.quantity }}
                </div>
              </div>
              <div v-if="order.items?.length > 4" class="w-20 h-20 bg-slate-50 border-2 border-white shadow-md rounded-2xl flex items-center justify-center text-sm font-bold text-slate-500 backdrop-blur-sm shrink-0">
                +{{ order.items.length - 4 }}
              </div>
            </div>

            <!-- Price -->
            <div class="w-full lg:w-auto flex flex-col sm:flex-row lg:flex-col items-end gap-6 border-t lg:border-t-0 lg:border-l border-slate-100 pt-6 lg:pt-0 lg:pl-10">
              <div class="text-right flex-1">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ order.items?.length }} {{ i18n.t('order.items') || 'sản phẩm' }}</p>
                <p class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 drop-shadow-sm">{{ fmt(order.total_amount) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="orderStore.pagination && orderStore.pagination.last_page > 1" class="flex justify-center gap-4 mt-16 flex-wrap pb-12">
        <button
          @click="goPage(orderStore.pagination.current_page - 1)"
          :disabled="orderStore.pagination.current_page <= 1"
          class="w-14 h-14 flex items-center justify-center rounded-2xl text-lg font-bold transition-all disabled:opacity-30 disabled:grayscale backdrop-blur-md bg-white/60 border border-white/80 hover:bg-white text-slate-600 shadow-sm hover:shadow-md active:scale-90"
        >←</button>
        <button
          v-for="page in orderStore.pagination.last_page" :key="page"
          @click="goPage(page)"
          class="w-14 h-14 flex items-center justify-center rounded-2xl text-[15px] font-bold transition-all duration-300"
          :class="page === orderStore.pagination.current_page ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-xl shadow-blue-500/30 -translate-y-1 scale-110' : 'backdrop-blur-md bg-white/60 border border-white/80 hover:bg-white text-slate-600 shadow-sm hover:shadow-md hover:-translate-y-0.5'"
        >{{ page }}</button>
        <button
          @click="goPage(orderStore.pagination.current_page + 1)"
          :disabled="orderStore.pagination.current_page >= orderStore.pagination.last_page"
          class="w-14 h-14 flex items-center justify-center rounded-2xl text-lg font-bold transition-all disabled:opacity-30 disabled:grayscale backdrop-blur-md bg-white/60 border border-white/80 hover:bg-white text-slate-600 shadow-sm hover:shadow-md active:scale-90"
        >→</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useOrderStore } from '../stores/order'
import { useI18nStore } from '../stores/i18n'
import { useUtils } from '../composables/useUtils'

const orderStore = useOrderStore()
const i18n = useI18nStore()
const { fmtPrice: fmt, getImageUrl, fmtDate: formatDate } = useUtils()

onMounted(async () => {
  await orderStore.fetchOrders({ page: 1 })
})

function goPage(page) {
  orderStore.fetchOrders({ page })
  window.scrollTo({ top: 0, behavior: 'smooth' })
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
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
