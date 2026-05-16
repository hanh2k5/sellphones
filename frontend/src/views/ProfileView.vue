<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-[#f4f7f6] min-h-screen font-sans">
    
    <!-- Mobile Header (Hidden on Desktop) -->
    <header class="md:hidden flex items-center gap-4 p-4 border-b border-slate-100 bg-white sticky top-0 z-40 mb-4 rounded-3xl shadow-sm">
      <button @click="$router.back()" class="w-10 h-10 flex items-center justify-center text-slate-600 active:bg-slate-50 rounded-full transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800">{{ i18n.t('nav.profile') }}</h1>
    </header>

    <!-- 409 Conflict Floating Alert -->
    <Transition name="slide-up">
      <div v-if="realtimeNotice" class="bg-rose-50 border border-rose-200 p-4 lg:p-6 rounded-2xl shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 lg:mb-8">
        <div class="flex items-start sm:items-center gap-4">
          <div class="w-10 h-10 bg-rose-500 rounded-full flex items-center justify-center text-white shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-rose-500 mb-1">{{ i18n.t('profile.data_conflict') }}</p>
            <p class="text-sm font-bold text-rose-900 leading-snug">{{ realtimeNotice }}</p>
          </div>
        </div>
        <button @click="refreshProfile" class="bg-rose-500 hover:bg-rose-600 text-white px-6 py-3 rounded-xl text-[11px] font-bold uppercase tracking-widest transition-all whitespace-nowrap self-end sm:self-auto">{{ i18n.t('profile.reload_data') }}</button>
      </div>
    </Transition>

    <div v-if="authStore.user" class="flex flex-col lg:flex-row gap-6 lg:gap-8">
      
      <!-- Left Sidebar -->
      <aside class="w-full lg:w-[320px] shrink-0">
        <div class="bg-white rounded-[2rem] p-6 lg:p-8 shadow-sm border border-slate-100 flex flex-col items-center">
          <div class="w-20 h-20 lg:w-24 lg:h-24 bg-[#2b2b2b] text-white rounded-full flex items-center justify-center text-3xl lg:text-4xl font-medium mb-3 lg:mb-4 shadow-sm">
            {{ authStore.user.name.charAt(0).toUpperCase() }}
          </div>
          <h2 class="text-xl lg:text-2xl font-bold text-slate-800 mb-1">{{ authStore.user.name }}</h2>
          <p class="text-slate-400 text-xs lg:text-sm mb-6 lg:mb-8 font-medium">@{{ authStore.user.email.split('@')[0] }}</p>
          
          <div class="w-full h-px bg-slate-100 mb-6 hidden lg:block"></div>

          <nav class="w-full flex flex-row lg:flex-col gap-2 lg:gap-3 overflow-x-auto pb-2 lg:pb-0">
            <button @click="activeTab = 'profile'"
              class="flex-1 lg:w-full flex items-center justify-center lg:justify-start gap-2 lg:gap-4 px-4 py-3 lg:px-6 lg:py-4 rounded-xl lg:rounded-2xl transition-all duration-300 whitespace-nowrap"
              :class="activeTab === 'profile' ? 'bg-[#2b2b2b] text-white' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'">
              <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
              <span class="text-xs lg:text-sm font-bold">{{ i18n.t('nav.profile') }}</span>
            </button>
            <button @click="activeTab = 'favorites'"
              class="flex-1 lg:w-full flex items-center justify-center lg:justify-start gap-2 lg:gap-4 px-4 py-3 lg:px-6 lg:py-4 rounded-xl lg:rounded-2xl transition-all duration-300 whitespace-nowrap"
              :class="activeTab === 'favorites' ? 'bg-[#2b2b2b] text-white' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'">
              <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
              <span class="text-xs lg:text-sm font-bold">{{ i18n.t('profile.favorites') }}</span>
            </button>
          </nav>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="flex-1">
        <div v-if="activeTab === 'profile'" class="space-y-6 lg:space-y-8 pb-20 md:pb-0">
          
          <!-- Box: Cập nhật thông tin -->
          <div class="bg-white rounded-[2rem] p-6 lg:p-10 shadow-sm border border-slate-100">
            <div class="flex items-center gap-3 mb-6 lg:mb-8">
              <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              <h3 class="text-[17px] font-bold text-slate-800">{{ i18n.t('profile.update_info') }}</h3>
            </div>

            <form @submit.prevent="handleUpdateProfile" novalidate>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5 lg:gap-6 mb-6 lg:mb-8" :class="{'opacity-75': realtimeNotice}">
                <div>
                  <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ i18n.t('auth.email') }}</label>
                  <input :value="authStore.user.email" disabled type="text" class="w-full bg-slate-50/50 border-none rounded-2xl px-6 py-4 text-[14px] font-medium text-slate-500 outline-none cursor-not-allowed" />
                </div>
                <div>
                  <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ i18n.t('auth.name') }}</label>
                  <input v-model="form.name" @input="errors && (errors.name = null)" :disabled="!!realtimeNotice" type="text" class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-[14px] font-medium text-slate-800 focus:ring-2 focus:ring-blue-100 outline-none transition-all disabled:cursor-not-allowed" />
                  <p v-if="errors?.name" class="text-xs text-rose-500 mt-1 ml-1">{{ errors.name[0] }}</p>
                </div>
                <div class="col-span-full">
                  <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ i18n.t('auth.phone') }}</label>
                  <input v-model="form.phone" @input="form.phone = form.phone.replace(/\D/g, ''); errors && (errors.phone = null)" maxlength="10" :disabled="!!realtimeNotice" type="tel" class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-[14px] font-medium text-slate-800 focus:ring-2 focus:ring-blue-100 outline-none transition-all disabled:cursor-not-allowed" />
                  <p v-if="errors?.phone" class="text-xs text-rose-500 mt-1 ml-1">{{ errors.phone[0] }}</p>
                </div>
                <div class="col-span-full">
                  <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ i18n.t('auth.address') }}</label>
                  <textarea v-model="form.address" @input="errors && (errors.address = null)" :disabled="!!realtimeNotice" rows="3" class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-[14px] font-medium text-slate-800 focus:ring-2 focus:ring-blue-100 outline-none transition-all resize-none disabled:cursor-not-allowed"></textarea>
                  <p v-if="errors?.address" class="text-xs text-rose-500 mt-1 ml-1">{{ errors.address[0] }}</p>
                </div>
              </div>

              <!-- Nút Action có Sticky Footer trên Mobile -->
              <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-100 z-50 md:relative md:border-t-0 md:bg-transparent md:p-0 flex justify-end">
                <button v-if="realtimeNotice" @click.prevent="refreshProfile" type="button" class="w-full md:w-auto bg-rose-500 hover:bg-rose-600 text-white px-8 py-4 rounded-2xl font-bold text-[13px] md:text-[12px] uppercase tracking-widest shadow-xl shadow-rose-500/20 active:scale-95 transition-all flex items-center justify-center gap-2">
                  <svg class="w-5 h-5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  {{ i18n.t('profile.reload_data') }}
                </button>
                <button v-else type="submit" :disabled="saving" class="w-full md:w-auto bg-[#2b2b2b] text-white px-8 py-4 md:py-3 rounded-2xl md:rounded-full font-bold text-[13px] md:text-[12px] uppercase tracking-wider hover:bg-black transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                  <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                  {{ i18n.t('admin.save_changes') }}
                </button>
              </div>
            </form>
          </div>

          <!-- Title: Lịch sử mua hàng -->
          <div class="flex items-center gap-3 pt-4 px-2">
            <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
            <h3 class="text-[17px] font-bold text-slate-800">{{ i18n.t('order.history') }}</h3>
          </div>

          <!-- Box: Lịch sử mua hàng -->
          <div class="bg-white rounded-[2rem] p-6 lg:p-10 shadow-sm border border-slate-100 min-h-[150px] flex flex-col items-center justify-center">
            <p v-if="!orders.length" class="text-slate-500 text-[15px] font-medium py-8">{{ i18n.t('order.empty') }}</p>
            <div v-else class="w-full space-y-4">
              <div v-for="order in orders" :key="order.id" class="p-5 lg:p-8 bg-slate-50 rounded-[1.5rem] lg:rounded-[2rem] border border-slate-100 hover:border-blue-100 transition-all group">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 lg:gap-6">
                  <div class="flex items-center gap-4 lg:gap-5">
                    <div class="w-14 h-14 lg:w-16 lg:h-16 bg-white rounded-xl lg:rounded-2xl flex items-center justify-center text-slate-400 shadow-sm group-hover:scale-110 transition-all shrink-0 overflow-hidden relative border border-slate-50">
                      <img v-if="order.items?.length > 0 && order.items[0].product?.hinh_anh_url" :src="order.items[0].product.hinh_anh_url" class="w-full h-full object-cover" :alt="order.items[0].product_name" />
                      <svg v-else class="w-6 h-6 lg:w-8 lg:h-8 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                      
                      <!-- Hiển thị số lượng sản phẩm còn lại nếu có nhiều hơn 1 -->
                      <div v-if="order.items?.length > 1" class="absolute bottom-0 right-0 bg-[#1c1c1e] text-white text-[9px] font-bold px-1.5 py-0.5 rounded-tl-lg shadow-sm">
                        +{{ order.items.length - 1 }}
                      </div>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="font-bold text-slate-900 truncate text-sm lg:text-base">#{{ order.order_code }}</p>
                      <p class="text-[9px] lg:text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 truncate pr-4">
                        <span v-if="order.items?.length > 0">{{ order.items[0].product?.name || order.items[0].product_name }} &bull; </span>
                        {{ fmtDate(order.created_at) }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center justify-between md:justify-end gap-4 lg:gap-8 mt-2 md:mt-0 pt-4 md:pt-0 border-t border-slate-200/60 md:border-t-0">
                    <div :class="statusClass(order.status)" class="px-4 py-2 lg:px-5 rounded-full text-[9px] lg:text-[10px] font-bold uppercase tracking-widest shadow-sm">
                      {{ statusLabel(order.status) }}
                    </div>
                    <div class="text-right">
                      <p class="text-[9px] lg:text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5 lg:mb-1">{{ i18n.t('order.total') }}</p>
                      <p class="font-bold text-[#1d1d1f] text-base lg:text-lg">{{ fmtPrice(order.total_amount) }}</p>
                    </div>
                    <router-link :to="`/orders/${order.id}`" class="bg-white text-slate-900 border border-slate-200 px-4 py-2.5 lg:px-6 lg:py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-sm hover:bg-[#1c1c1e] hover:text-white hover:border-[#1c1c1e] transition-all whitespace-nowrap">{{ i18n.t('product.view_detail') }}</router-link>
                  </div>
                </div>
              </div>

              <!-- Pagination -->
              <div v-if="pagination.last_page > 1" class="pagination-wrapper mt-10">
                <div class="pagination-apple-wrapper">
                  <ul class="pagination-apple">
                    <li v-if="pagination.current_page > 1" class="page-item">
                      <button class="page-link" @click="goPage(1)">«</button>
                    </li>
                    <li v-if="pagination.current_page > 1" class="page-item">
                      <button class="page-link" @click="goPage(pagination.current_page - 1)">‹</button>
                    </li>
                    <li v-for="p in pagination.last_page" :key="p" class="page-item" :class="{ active: p === pagination.current_page }">
                      <button class="page-link" @click="goPage(p)">{{ p }}</button>
                    </li>
                    <li v-if="pagination.current_page < pagination.last_page" class="page-item">
                      <button class="page-link" @click="goPage(pagination.current_page + 1)">›</button>
                    </li>
                    <li v-if="pagination.current_page < pagination.last_page" class="page-item">
                      <button class="page-link" @click="goPage(pagination.last_page)">»</button>
                    </li>
                  </ul>
                </div>
              </div>

            </div>
          </div>

        </div>
        
        <div v-if="activeTab === 'favorites'" class="bg-white rounded-[2rem] p-10 shadow-sm border border-slate-100 flex items-center justify-center min-h-[300px]">
          <p class="text-slate-500 text-[15px] font-medium">{{ i18n.t('profile.no_favorites') }}</p>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'
import api from '../services/api'
import { useToast } from '../composables/useToast'
import { useUtils } from '../composables/useUtils'
import Swal from 'sweetalert2'

const route = useRoute()
const authStore = useAuthStore()
const i18n = useI18nStore()
const toast = useToast()
const { fmtPrice, fmtDate } = useUtils()

const activeTab = ref(route.query.tab || 'profile')
const saving = ref(false)
const errors = ref({})
const orders = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const realtimeNotice = ref(null)
let pollingTimer = null

const form = ref({
  name: authStore.user?.name || '',
  email: authStore.user?.email || '',
  address: authStore.user?.address || '',
  phone: authStore.user?.phone || ''
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const menu = [
  { 
    id: 'profile', 
    labelKey: 'nav.profile', 
    icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>` 
  },
  { 
    id: 'password', 
    labelKey: 'profile.password_tab', 
    icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>` 
  },
  { 
    id: 'orders', 
    labelKey: 'nav.orders', 
    icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>` 
  }
]

onMounted(async () => {
  fetchOrders()
  startPolling()
})

onUnmounted(() => stopPolling())

async function fetchOrders(page = 1) {
  try {
    const res = await api.get('/orders', { params: { page } })
    orders.value = res.data.data || res.data
    pagination.value = res.data.meta || { 
      current_page: res.data.current_page || 1, 
      last_page: res.data.last_page || 1, 
      total: res.data.total || orders.value.length 
    }
  } catch {}
}

function goPage(p) { fetchOrders(p) }

function statusLabel(status) {
  const map = {
    'pending': i18n.locale === 'vi' ? 'Đang xử lý' : 'Pending',
    'shipping': i18n.locale === 'vi' ? 'Đang giao hàng' : 'Shipping',
    'completed': i18n.locale === 'vi' ? 'Hoàn thành' : 'Completed',
    'cancelled': i18n.locale === 'vi' ? 'Đã hủy' : 'Cancelled'
  }
  return map[status] || status
}

function statusClass(status) {
  const map = {
    'pending': 'bg-amber-50 text-amber-600 border border-amber-100',
    'shipping': 'bg-blue-50 text-blue-600 border border-blue-100',
    'completed': 'bg-emerald-50 text-emerald-600 border border-emerald-100',
    'cancelled': 'bg-slate-100 text-slate-500 border border-slate-200'
  }
  return map[status] || 'bg-slate-50 text-slate-500'
}

function startPolling() {
  pollingTimer = setInterval(async () => {
    if (!authStore.user) return
    try {
      const res = await api.get(`/profile/check-update`)
      if (res.data.changed && res.data.updated_at) {
        // So sánh theo giây (loại bỏ sai lệch miligiây giữa cache và DB)
        const cachedTime = Math.floor(new Date(res.data.updated_at).getTime() / 1000)
        const localTime = Math.floor(new Date(authStore.user.updated_at).getTime() / 1000)
        
        if (cachedTime > localTime) {
          realtimeNotice.value = res.data.message
        } else {
          realtimeNotice.value = null
        }
      } else {
        realtimeNotice.value = null
      }
    } catch {}
  }, 5000)
}

function stopPolling() {
  if (pollingTimer) clearInterval(pollingTimer)
}

async function refreshProfile() {
  realtimeNotice.value = null
  await authStore.fetchMe()
  form.value = {
    name: authStore.user.name,
    email: authStore.user.email,
    address: authStore.user.address,
    phone: authStore.user.phone || ''
  }
}

async function handleUpdateProfile() {
  errors.value = {}
  
  // Client-side validation: Chặn ngay nếu các trường bắt buộc bị trống
  let hasError = false
  if (!form.value.name?.trim()) {
    errors.value.name = [i18n.t('auth.name_error') || 'Vui lòng nhập họ tên']
    hasError = true
  }
  if (!form.value.email?.trim()) {
    errors.value.email = [i18n.t('auth.email_error') || 'Vui lòng nhập email']
    hasError = true
  }

  if (hasError) return

  saving.value = true
  try {
    const res = await api.put('/profile', {
      ...form.value,
      updated_at: authStore.user.updated_at // Optimistic locking
    })
    authStore.user = res.data.user
    toast.success(i18n.t('profile.update_success'))
    realtimeNotice.value = null // Xóa cảnh báo xung đột nếu thành công
  } catch (e) {
    console.error('Update Profile Error:', e.response?.data)
    
    if (e.response?.status === 409) {
      realtimeNotice.value = e.response.data.message || 'Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại dữ liệu.'
      return
    }
    
    const serverMessage = e.response?.data?.message || ''
    
    if (e.response?.status === 422) {
      const data = e.response.data
      if (data.errors) {
        errors.value = data.errors
      } else {
        errors.value = { name: [data.message || 'Lỗi xác thực dữ liệu'] }
      }
    } else {
      // Nếu lỗi 500 hoặc lỗi khác, cũng đẩy vào label đỏ cho người dùng đọc thay vì messbox
      if (serverMessage.toLowerCase().includes('name') || serverMessage.toLowerCase().includes('tên')) {
        errors.value.name = [serverMessage]
      } else if (serverMessage.toLowerCase().includes('email')) {
        errors.value.email = [serverMessage]
      } else {
        // Fallback: Nếu không phân loại được, hiện ở trường Tên cho dễ thấy
        errors.value.name = [serverMessage || 'Không thể cập nhật hồ sơ do lỗi hệ thống!']
      }
    }
  } finally {
    saving.value = false
  }
}

async function handleUpdatePassword() {
  errors.value = {}
  
  // Client-side validation: Chặn ngay nếu trống
  let hasError = false
  if (!passwordForm.value.current_password) {
    errors.value.current_password = [i18n.t('auth.current_password_required')]
    hasError = true
  }
  if (!passwordForm.value.new_password) {
    errors.value.new_password = [i18n.t('auth.new_password_required')]
    hasError = true
  }
  if (passwordForm.value.new_password && passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    errors.value.new_password_confirmation = [i18n.locale === 'vi' ? 'Mật khẩu xác nhận không khớp' : 'Password confirmation does not match']
    hasError = true
  }

  if (hasError) return
  
  saving.value = true
  try {
    await api.put('/profile/password', passwordForm.value)
    toast.success(i18n.t('profile.password_success'))
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (e) {
    console.error('Update Password Error:', e.response?.data)
    
    const serverMessage = e.response?.data?.message || ''

    if (e.response?.status === 422) {
      const data = e.response.data
      if (data.errors) {
        errors.value = data.errors
      } else {
        // Fallback for manual validation exceptions that only return message
        errors.value = { current_password: [data.message || 'Lỗi xác thực mật khẩu'] }
      }
    } else {
      // Chuyển lỗi Server sang nhãn đỏ thay vì Swal
      if (serverMessage.toLowerCase().includes('current') || serverMessage.toLowerCase().includes('hiện tại')) {
        errors.value.current_password = [serverMessage]
      } else if (serverMessage.toLowerCase().includes('new') || serverMessage.toLowerCase().includes('mới')) {
        errors.value.new_password = [serverMessage]
      } else {
        errors.value.current_password = [serverMessage || 'Lỗi hệ thống khi đổi mật khẩu!']
      }
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(-20px); opacity: 0; }

/* Pagination */
.pagination-wrapper { display: flex; justify-content: center; }
.pagination-apple-wrapper { background: #fff; border-radius: 50px; padding: 6px 14px; display: inline-block; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.pagination-apple { display: flex; align-items: center; gap: 2px; padding: 0; list-style: none; margin: 0; }
.page-link { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #1d1d1f; font-weight: 600; font-size: 13px; background: transparent; border: none; cursor: pointer; font-family: inherit; transition: 0.2s; }
.page-link:hover { background: #f5f5f7; }
.page-item.active .page-link { background: #1d1d1f; color: #fff; }
</style>