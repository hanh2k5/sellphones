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
              <h3 class="font-bold text-slate-800 text-base md:text-lg group-hover:text-blue-600 transition-colors">{{ i18n.t('order.id') || 'Đơn hàng' }} {{ order.order_code }}</h3>
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

            <!-- Price & Actions -->
            <div class="w-full lg:w-auto flex flex-col sm:flex-row lg:flex-col items-end gap-6 border-t lg:border-t-0 lg:border-l border-slate-100 pt-6 lg:pt-0 lg:pl-10">
              <div class="text-right flex-1">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ order.items?.length }} {{ i18n.t('order.items') || 'sản phẩm' }}</p>
                <p class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 drop-shadow-sm">{{ fmt(order.total_amount) }}</p>
              </div>
              <div class="flex gap-4 w-full sm:w-auto">
                <router-link :to="`/orders/${order.id}`" class="flex-1 sm:flex-none text-center px-8 py-4 bg-white hover:bg-slate-50 text-blue-600 shadow-[0_4px_12px_rgba(0,0,0,0.05)] border border-slate-100 rounded-2xl text-[13px] font-bold uppercase tracking-widest hover:shadow-[0_8px_20px_rgba(0,0,0,0.08)] transition-all duration-300 active:scale-95">
                  {{ i18n.t('common.view_details') }}
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination (Chuẩn như trang Sản phẩm - 10 đơn/trang) -->
      <div v-if="orderStore.pagination && orderStore.pagination.last_page > 1" class="flex justify-center mt-12 pb-12">
        <div class="pagination-apple-wrapper">
          <ul class="pagination-apple">
            <!-- Back -->
            <li v-if="orderStore.pagination.current_page > 1" class="page-item">
              <button class="page-link" @click="goPage(orderStore.pagination.current_page - 1)">«</button>
            </li>

            <!-- Dynamic Numbers -->
            <li v-for="p in visiblePages" :key="p" class="page-item"
              :class="{ active: p === orderStore.pagination.current_page }">
              <button v-if="p !== '...'" class="page-link" @click="goPage(p)">{{ p }}</button>
              <span v-else class="page-link-text">...</span>
            </li>

            <!-- Next -->
            <li v-if="orderStore.pagination.current_page < orderStore.pagination.last_page" class="page-item">
              <button class="page-link" @click="goPage(orderStore.pagination.current_page + 1)">»</button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * SV THỰC HIỆN: PHAN ĐÌNH HẠNH
 * MỤC: 4.1.7 - HIỂN THỊ DANH SÁCH ĐƠN HÀNG (USER)
 */
import { onMounted, computed } from 'vue'
import { useOrderStore } from '../stores/order'
import { useI18nStore } from '../stores/i18n'
import { useUtils } from '../composables/useUtils'

const orderStore = useOrderStore()
const i18n = useI18nStore()
const { fmtPrice: fmt, getImageUrl, fmtDate: formatDate } = useUtils()

// Logic hiển thị trang thông minh
const visiblePages = computed(() => {
  const current = orderStore.pagination.current_page
  const last = orderStore.pagination.last_page
  const range = 2
  const pages = []

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - range && i <= current + range)) {
      pages.push(i)
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...')
    }
  }
  return pages
})

onMounted(async () => {
  await orderStore.fetchOrders({ page: 1, per_page: 10 })
})

function goPage(page) {
  orderStore.fetchOrders({ page, per_page: 10 })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function statusLabel(s) {
  return { 
    pending:    i18n.t('order.status_pending'), 
    processing: i18n.t('order.status_processing'),
    shipping:   i18n.t('order.status_shipping'),
    completed:  i18n.t('order.status_completed'), 
    cancelled:  i18n.t('order.status_cancelled') 
  }[s] || s
}

function statusClass(s) {
  return {
    pending:    'bg-amber-50 text-amber-600 border-amber-100',
    processing: 'bg-indigo-50 text-indigo-600 border-indigo-100',
    shipping:   'bg-blue-50 text-blue-600 border-blue-100',
    completed:  'bg-emerald-50 text-emerald-600 border-emerald-100',
    cancelled:  'bg-rose-50 text-rose-600 border-rose-100',
  }[s] || 'bg-slate-50 text-slate-600 border-slate-100'
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
@media (max-width: 768px) {
  .max-w-5xl { padding-top: 15px; padding-bottom: 30px; }
  .mb-12 { margin-bottom: 1rem !important; }
  .text-4xl { font-size: 1.3rem !important; }
  .text-slate-500.mt-2 { font-size: 13px; }
  .backdrop-blur-xl { border-radius: 1.25rem !important; }
  .p-6, .p-8 { padding: 14px; }
  .w-14 { width: 36px; height: 36px; }
  .w-14 .text-2xl { font-size: 1.25rem; }
  h3 { font-size: 15px !important; }
  .text-xs { font-size: 11px !important; }
  .px-5.py-2 { padding: 6px 12px; font-size: 9px; border-radius: 8px; }
  .flex.-space-x-4 { gap: 6px; margin-left: 0; }
  .w-20 { width: 52px; height: 52px; border-radius: 12px; }
  .text-3xl { font-size: 1.25rem !important; }
  .px-8.py-4 { padding: 10px 16px; font-size: 11px; border-radius: 12px; }
  .gap-8 { gap: 1rem; }
  .pl-10 { padding-left: 0; border-left: none; }
  .pt-6 { padding-top: 1rem; }
}

/* Pagination Chuẩn như trang Sản phẩm */
.pagination-apple-wrapper { 
  background: #fff; border-radius: 50px; padding: 6px; 
  border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
}
.pagination-apple { display: flex; align-items: center; gap: 4px; list-style: none; padding: 0; margin: 0; }
.page-link { 
  min-width: 44px; height: 44px; border-radius: 50%; 
  display: flex; align-items: center; justify-content: center; 
  font-weight: 800; font-size: 14px; color: #64748b; 
  cursor: pointer; transition: 0.3s; background: transparent; border: none; 
}
.page-link-text {
  min-width: 30px; text-align: center; color: #94a3b8; font-weight: bold;
}
.page-link:hover { background: #f1f5f9; color: #1e293b; }
.page-item.active .page-link { background: #1e293b; color: #fff; box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
</style>
