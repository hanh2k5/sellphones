<template>
  <div class="flex flex-col min-h-[calc(100vh-60px)] bg-slate-50 md:bg-transparent">
    
    <!-- Sticky Header & Filters (Mobile First) -->
    <div class="sticky top-0 z-20 bg-slate-50/90 md:bg-white/90 backdrop-blur-md pb-3 border-b border-slate-200/60 md:border-none md:pb-0 md:mb-4 px-3 md:px-0 pt-3">
      
      <!-- Search -->
      <div class="relative w-full max-w-md mb-3 md:mb-4">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input v-model="search" @keyup.enter="fetchOrders()" type="text"
          :placeholder="i18n.t('admin.search_customers')"
          class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
      </div>

      <!-- Horizontal Scroll Tabs -->
      <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-1 snap-x">
        <button v-for="s in statusTabs" :key="s.val"
          @click="filterStatus = s.val; fetchOrders()"
          class="snap-start shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all border"
          :class="filterStatus === s.val 
            ? 'bg-slate-800 text-white border-slate-800 shadow-md' 
            : 'bg-white text-slate-600 border-slate-200 hover:border-blue-500 hover:text-blue-600'">
          {{ s.label }}
        </button>
      </div>
    </div>

    <!-- Báo cáo 4.1.8 STT 3: Giao diện cảnh báo tranh chấp dữ liệu -->
    <div v-if="showConflictAlert" class="mx-3 md:mx-0 mt-3 md:mt-0 mb-4 bg-red-50 border border-red-200 rounded-xl p-3 flex items-center justify-between shadow-sm animate-fade-in">
      <div class="flex items-center gap-2">
        <span class="text-lg">⚠️</span>
        <p class="text-xs font-bold text-red-700 m-0">{{ i18n.t('admin.data_conflict_full') }}</p>
      </div>
      <button @click="fetchOrders(); showConflictAlert = false" class="shrink-0 px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition-colors">
        {{ i18n.t('common.refresh') }}
      </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 px-3 md:px-0 pb-20 md:pb-6">
      
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 opacity-60">
        <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-3"></div>
        <p class="text-sm font-bold text-slate-500">{{ i18n.t('common.loading') }}</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!orders.length" class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border border-slate-100 shadow-sm mt-2">
        <div class="text-5xl mb-3 opacity-50">📋</div>
        <p class="text-sm font-bold text-slate-500">{{ i18n.t('common.no_data') }}</p>
      </div>

      <!-- Orders List (Mobile Cards & Desktop Table structure) -->
      <div v-else class="space-y-3 md:space-y-0 md:bg-white md:rounded-2xl md:border md:border-slate-100 md:shadow-sm md:overflow-hidden mt-2">
        
        <!-- Desktop Table Header -->
        <div class="hidden md:grid grid-cols-12 gap-4 p-4 bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
          <div class="col-span-2">{{ i18n.t('admin.order_code') }}</div>
          <div class="col-span-3">{{ i18n.t('admin.customer') }}</div>
          <div class="col-span-2">{{ i18n.t('admin.order_date') }}</div>
          <div class="col-span-2">{{ i18n.t('admin.total_amount') }}</div>
          <div class="col-span-1">{{ i18n.t('admin.status') }}</div>
          <div class="col-span-2 text-right">{{ i18n.t('admin.actions') }}</div>
        </div>

        <!-- Order Items -->
        <div v-for="order in orders" :key="order.id" 
          class="bg-white rounded-2xl md:rounded-none p-4 md:p-4 border border-slate-100 shadow-sm md:shadow-none md:border-b md:border-slate-50 md:last:border-0 hover:bg-slate-50/50 transition-colors">
          
          <!-- Desktop Row Layout -->
          <div class="hidden md:grid grid-cols-12 gap-4 items-center">
            <div class="col-span-2 text-xs font-mono font-bold text-slate-600">{{ order.order_code || `#ORD-${order.id}` }}</div>
            <div class="col-span-3 flex flex-col">
              <span class="text-sm font-bold text-slate-800">{{ order.user?.name }}</span>
              <span class="text-xs text-slate-500">{{ order.user?.email }}</span>
            </div>
            <div class="col-span-2 text-xs font-medium text-slate-500">{{ fmtDate(order.created_at) }}</div>
            <div class="col-span-2 text-sm font-bold text-red-600">{{ fmtPrice(order.total_amount) }}</div>
            <div class="col-span-1">
              <span :class="statusClass(order.status)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide whitespace-nowrap">
                {{ statusLabel(order.status) }}
              </span>
            </div>
            <div class="col-span-2 flex justify-end gap-2">
              <!-- Báo cáo 4.1.8: Duyệt đơn (Pending -> Shipping) -->
              <button v-if="order.status === 'pending'" @click="handleUpdateStatus(order)" 
                class="flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 hover:bg-green-600 hover:text-white rounded-lg transition-all font-bold text-[10px] uppercase border border-green-200 shadow-sm whitespace-nowrap">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ i18n.t('admin.approve_order') }}</span>
              </button>

              <!-- Hoàn tất đơn (Shipping -> Completed) -->
              <button v-if="order.status === 'shipping'" @click="handleUpdateStatus(order)" 
                class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white rounded-lg transition-all font-bold text-[10px] uppercase border border-blue-200 shadow-sm whitespace-nowrap">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ i18n.t('common.complete') || 'Hoàn tất' }}</span>
              </button>

              <!-- Báo cáo 4.1.9: Hủy đơn hàng (Admin/User) -->
              <button v-if="['pending', 'shipping'].includes(order.status)" @click="handleCancel(order)" 
                class="flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white rounded-lg transition-all font-bold text-[10px] uppercase border border-rose-200 shadow-sm whitespace-nowrap">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>{{ i18n.t('common.cancel') }}</span>
              </button>
              
              <span v-if="['completed', 'cancelled'].includes(order.status)" class="text-[10px] font-bold text-slate-400 uppercase bg-slate-100 px-2 py-1 rounded-md whitespace-nowrap">
                {{ i18n.t('admin.order_locked') }}
              </span>
            </div>
          </div>

          <!-- Mobile Card Layout -->
          <div class="flex flex-col md:hidden gap-3">
            <!-- Header: ID + Status -->
            <div class="flex justify-between items-start pb-3 border-b border-dashed border-slate-100">
              <div class="flex flex-col">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ i18n.t('admin.order_code') }}</span>
                <span class="text-sm font-mono font-bold text-slate-800 mt-0.5">{{ order.order_code || `#ORD-${order.id}` }}</span>
              </div>
              <span :class="statusClass(order.status)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                {{ statusLabel(order.status) }}
              </span>
            </div>

            <!-- Body: Info -->
            <div class="grid grid-cols-2 gap-3">
              <div class="flex flex-col">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ i18n.t('admin.customer') }}</span>
                <span class="text-xs font-bold text-slate-700 mt-1 line-clamp-1">{{ order.user?.name }}</span>
                <span class="text-[10px] text-slate-500 truncate">{{ order.user?.email }}</span>
              </div>
              <div class="flex flex-col items-end text-right">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ i18n.t('admin.order_date') }}</span>
                <span class="text-xs font-medium text-slate-600 mt-1">{{ fmtDate(order.created_at) }}</span>
              </div>
            </div>

            <!-- Footer: Price & Actions -->
            <div class="flex justify-between items-end pt-3 border-t border-slate-50 mt-1 gap-2">
              <div class="flex flex-col shrink-0">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ i18n.t('admin.total_amount') }}</span>
                <span class="text-base font-bold text-red-600 mt-0.5 whitespace-nowrap">{{ fmtPrice(order.total_amount) }}</span>
              </div>
              
              <div class="flex gap-1.5 flex-wrap justify-end">
                <!-- Duyệt đơn -->
                <button v-if="order.status === 'pending'" @click="handleUpdateStatus(order)" 
                  class="flex items-center gap-1 px-2.5 py-1.5 bg-green-600 text-white rounded-lg text-[10px] font-bold active:scale-95 transition-transform shadow-md shadow-green-800/20 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  {{ i18n.t('admin.approve_order') }}
                </button>

                <!-- Hoàn tất -->
                <button v-if="order.status === 'shipping'" @click="handleUpdateStatus(order)" 
                  class="flex items-center gap-1 px-2.5 py-1.5 bg-blue-600 text-white rounded-lg text-[10px] font-bold active:scale-95 transition-transform shadow-md shadow-blue-800/20 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  {{ i18n.t('common.complete') || 'Hoàn tất' }}
                </button>

                <!-- Hủy đơn -->
                <button v-if="['pending', 'shipping'].includes(order.status)" @click="handleCancel(order)" 
                  class="flex items-center gap-1 px-2.5 py-1.5 bg-rose-600 text-white rounded-lg text-[10px] font-bold active:scale-95 transition-transform shadow-md shadow-rose-800/20 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  {{ i18n.t('common.cancel') }}
                </button>

                <div v-if="['completed', 'cancelled'].includes(order.status)" class="flex items-center gap-1.5 px-2 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold uppercase whitespace-nowrap">
                  {{ i18n.t('admin.order_locked') }}
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Pagination (Apple-like Style) -->
      <div v-if="pagination.last_page > 1" class="flex justify-center mt-6 md:mt-8 pb-4">
        <div class="bg-white rounded-2xl border border-slate-100 p-1 shadow-sm flex items-center gap-1">
          <button v-if="pagination.current_page > 1" @click="goPage(1)" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-500 hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
          </button>
          
          <button v-for="p in pagination.last_page" :key="p" @click="goPage(p)"
            class="w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold transition-all"
            :class="p === pagination.current_page ? 'bg-slate-800 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'">
            {{ p }}
          </button>
          
          <button v-if="pagination.current_page < pagination.last_page" @click="goPage(pagination.last_page)" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-500 hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
          </button>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ordersApi } from '../../api'
import { useUtils } from '../../composables/useUtils'
import { useToast } from '../../composables/useToast'
import { useI18nStore } from '../../stores/i18n'

const { fmtPrice, fmtDate } = useUtils()
const toast = useToast()
const i18n = useI18nStore()
const orders = ref([])
const loading = ref(false)
const search = ref('')
const filterStatus = ref('')
const showConflictAlert = ref(false)
const pagination = ref({ current_page: 1, last_page: 1 })

const statusTabs = computed(() => [
  { val: '', label: i18n.t('admin.all_orders') },
  { val: 'pending', label: i18n.t('order.status_pending') },
  { val: 'shipping', label: i18n.t('order.status_shipping') },
  { val: 'completed', label: i18n.t('order.status_completed') },
  { val: 'cancelled', label: i18n.t('order.status_cancelled') },
])

onMounted(() => fetchOrders())

async function fetchOrders(page = 1) {
  loading.value = true
  try {
    const res = await ordersApi.adminList({ page, status: filterStatus.value || undefined, search: search.value || undefined })
    orders.value = res.data.data
    pagination.value = res.data.meta || { 
      current_page: res.data.current_page || 1, 
      last_page: res.data.last_page || 1, 
      total: res.data.total || orders.value.length 
    }
  } catch (e) { 
    toast.error(e.response?.data?.message || i18n.t('common.error')) 
  } finally { loading.value = false }
}

// Báo cáo 4.1.8 STT 1: Chức năng Duyệt đơn và Hoàn tất đơn hàng
async function handleUpdateStatus(order) {
  try {
    if (order.status === 'pending') {
      await ordersApi.adminConfirm(order.id, order.updated_at)
    } else if (order.status === 'shipping') {
      await ordersApi.adminComplete(order.id, order.updated_at)
    }
    
    toast.success(i18n.t('admin.order_updated_success'))
    fetchOrders()
  } catch (e) {
    if (e.response?.status === 409) {
      // Báo cáo 4.1.8 STT 3: Xử lý ngoại lệ, thông báo tranh chấp dữ liệu (Optimistic Locking)
      showConflictAlert.value = true
      toast.warning(e.response?.data?.message || i18n.t('admin.data_conflict'))
    } else {
      toast.error(e.response?.data?.message || i18n.t('common.error'))
    }
  }
}

// Báo cáo 4.1.9 STT 1: Chức năng Hủy đơn hàng và Hoàn tồn kho
async function handleCancel(order) {
  try {
    await ordersApi.cancel(order.id, order.updated_at)
    toast.success(i18n.t('admin.order_updated_success'))
    fetchOrders()
  } catch (e) {
    if (e.response?.status === 409) {
      // Báo cáo 4.1.9 STT 2: Xử lý tranh chấp dữ liệu khi hủy
      showConflictAlert.value = true
      toast.warning(e.response?.data?.message || i18n.t('admin.data_conflict'))
    } else {
      toast.error(e.response?.data?.message || i18n.t('common.error'))
    }
  }
}

function goPage(p) { fetchOrders(p) }
function statusLabel(s) {
  return {
    pending: i18n.t('order.status_pending'),
    shipping: i18n.t('order.status_shipping'),
    completed: i18n.t('order.status_completed'),
    cancelled: i18n.t('order.status_cancelled')
  }[s] || s
}
function statusClass(s) { 
  return { 
    pending: 'bg-amber-100 text-amber-700 border border-amber-200', 
    shipping: 'bg-blue-100 text-blue-700 border border-blue-200', 
    completed: 'bg-emerald-100 text-emerald-700 border border-emerald-200', 
    cancelled: 'bg-rose-100 text-rose-700 border border-rose-200' 
  }[s] || 'bg-slate-100 text-slate-600' 
}
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
@keyframes fade-in {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fade-in 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
