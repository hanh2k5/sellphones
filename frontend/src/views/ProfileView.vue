<template>
  <div class="min-h-screen bg-[#f9f9f9] py-6 md:py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Mobile Header (chỉ hiện khi đang xem content trên mobile) -->
      <header v-if="isMobileContentVisible" class="md:hidden sticky top-0 z-40 mb-4 flex items-center pt-2">
        <button @click="backToMobileMenu" class="relative z-10 inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-[13px] font-bold text-slate-600 shadow-sm border border-slate-200 transition-all active:scale-95 hover:bg-slate-50">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
          Quay lại menu
        </button>
      </header>

      <div v-if="authStore.user" class="grid grid-cols-1 gap-6 md:grid-cols-[280px_minmax(0,1fr)] lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-8">
        <!-- Sidebar Menu (Ẩn trên mobile nếu đang xem content) -->
        <aside class="space-y-5" :class="{ 'hidden md:block': isMobileContentVisible }">
          <div class="flex flex-col rounded-3xl md:rounded-[2.5rem] bg-white px-5 py-8 md:px-6 md:py-10 shadow-sm border border-slate-100/50">
            
            <!-- User Info (Centered) -->
            <div class="flex flex-col items-center">
              <!-- Avatar -->
              <div class="mb-4 md:mb-5 flex h-20 w-20 md:h-[110px] md:w-[110px] items-center justify-center rounded-full bg-[#1c1f26] text-3xl md:text-[42px] font-bold text-white shadow-lg md:shadow-xl shadow-slate-900/10">
                {{ authStore.user.name?.charAt(0)?.toUpperCase() }}
              </div>
              
              <!-- Name -->
              <h2 class="text-xl md:text-[24px] font-bold text-[#1a1a1a] text-center">{{ authStore.user.name }}</h2>
              
              <!-- Handle -->
              <p class="mt-1 text-sm md:text-[15px] font-medium text-slate-500 text-center">@{{ authStore.user.email?.split('@')[0] }}</p>
            </div>
            
            <hr class="my-6 md:my-8 w-full border-slate-100" />
            
            <!-- Navigation -->
            <nav class="w-full space-y-2 md:space-y-3">
              <button
                v-for="m in menu"
                :key="m.id"
                @click="setActiveTab(m.id)"
                class="flex w-full items-center gap-3 md:gap-4 rounded-2xl px-5 py-3 md:px-6 md:py-4 text-left transition-all font-bold text-sm md:text-[15px]"
                :class="activeTab === m.id ? 'bg-[#1c1f26] text-white shadow-lg shadow-slate-900/20' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
              >
                <span class="flex h-5 w-5 items-center justify-center shrink-0" v-html="m.icon"></span>
                <span class="truncate">{{ i18n.t(m.labelKey) }}</span>
              </button>
            </nav>
          </div>
        </aside>

        <!-- Content Main (Ẩn trên mobile nếu đang ở menu) -->
        <main class="space-y-6" :class="{ 'hidden md:block': !isMobileContentVisible }">
          <Transition name="slide-up">
            <section v-if="realtimeNotice" class="flex flex-col gap-4 rounded-[2rem] border border-rose-200 bg-rose-50 p-5 shadow-sm md:flex-row md:items-center md:justify-between">
              <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.25em] text-rose-500">{{ i18n.t('profile.data_conflict') }}</p>
                  <p class="text-sm font-semibold text-rose-900">{{ realtimeNotice }}</p>
                </div>
              </div>
              <button @click="refreshProfile" class="rounded-2xl bg-rose-500 px-6 py-3 text-[11px] font-bold uppercase tracking-widest text-white transition-colors hover:bg-rose-600">
                {{ i18n.t('profile.reload_data') }}
              </button>
            </section>
          </Transition>

          <section v-if="activeTab === 'profile'" class="rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-sm md:p-8">
            <div class="mb-8 flex items-center gap-4 border-b border-slate-100 pb-5">
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-slate-400">{{ i18n.t('nav.profile') }}</p>
                <h3 class="text-xl font-bold text-slate-900">{{ i18n.t('profile.update_info') }}</h3>
              </div>
            </div>

            <form @submit.prevent="handleUpdateProfile" novalidate class="space-y-6">
              <div class="grid gap-5 md:grid-cols-2" :class="{ 'opacity-70': realtimeNotice }">
                <div>
                  <label class="mb-2 ml-1 block text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ i18n.t('auth.name') }}</label>
                  <input v-model="form.name" @input="clearFieldError('name')" :disabled="!!realtimeNotice" type="text" class="field-input" :class="{ 'field-error': errors?.name }" />
                  <p v-if="errors?.name" class="field-error-label">{{ errors.name[0] }}</p>
                </div>
                <div>
                  <label class="mb-2 ml-1 block text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ i18n.t('auth.email') }}</label>
                  <input v-model="form.email" @input="clearFieldError('email')" :disabled="!!realtimeNotice" type="email" class="field-input" :class="{ 'field-error': errors?.email }" />
                  <p v-if="errors?.email" class="field-error-label">{{ errors.email[0] }}</p>
                </div>
                <div>
                  <label class="mb-2 ml-1 block text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ i18n.t('auth.phone') }}</label>
                  <input
                    v-model="form.phone"
                    @input="form.phone = form.phone.replace(/\D/g, ''); clearFieldError('phone')"
                    :disabled="!!realtimeNotice"
                    maxlength="10"
                    type="text"
                    class="field-input"
                    :class="{ 'field-error': errors?.phone }"
                  />
                  <p v-if="errors?.phone" class="field-error-label">{{ errors.phone[0] }}</p>
                </div>
                <div class="md:col-span-2">
                  <label class="mb-2 ml-1 block text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ i18n.t('auth.address') }}</label>
                  <textarea
                    v-model="form.address"
                    @input="clearFieldError('address')"
                    :disabled="!!realtimeNotice"
                    rows="4"
                    class="field-input min-h-[132px] resize-none"
                    :class="{ 'field-error': errors?.address }"
                  ></textarea>
                  <p v-if="errors?.address" class="field-error-label">{{ errors.address[0] }}</p>
                </div>
              </div>

              <div class="flex justify-end pt-2">
                <button v-if="realtimeNotice" @click.prevent="refreshProfile" type="button" class="action-btn bg-rose-500 hover:bg-rose-600">
                  {{ i18n.t('profile.reload_data') }}
                </button>
                <button v-else type="submit" :disabled="saving" class="action-btn bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 text-white shadow-lg shadow-blue-500/30">
                  <span v-if="saving" class="h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></span>
                  {{ saving ? i18n.t('common.processing') : i18n.t('common.save') }}
                </button>
              </div>
            </form>
          </section>

          <section v-if="activeTab === 'password'" class="rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-sm md:p-8">
            <div class="mb-8 flex items-center gap-4 border-b border-slate-100 pb-5">
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </div>
              <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-slate-400">{{ i18n.t('profile.password_tab') }}</p>
                <h3 class="text-xl font-bold text-slate-900">{{ i18n.t('profile.update_password') }}</h3>
              </div>
            </div>

            <form @submit.prevent="handleUpdatePassword" novalidate class="space-y-5">
              <div>
                <label class="mb-2 ml-1 block text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ i18n.t('profile.current_password') }}</label>
                <input v-model="passwordForm.current_password" @input="clearFieldError('current_password')" type="password" class="field-input" :class="{ 'field-error': errors?.current_password }" />
                <p v-if="errors?.current_password" class="field-error-label">{{ errors.current_password[0] }}</p>
              </div>
              <div>
                <label class="mb-2 ml-1 block text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ i18n.t('profile.new_password') }}</label>
                <input v-model="passwordForm.new_password" @input="clearFieldError('new_password')" type="password" class="field-input" :class="{ 'field-error': errors?.new_password }" />
                <p v-if="errors?.new_password" class="field-error-label">{{ errors.new_password[0] }}</p>
              </div>
              <div>
                <label class="mb-2 ml-1 block text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ i18n.t('profile.confirm_new_password') }}</label>
                <input v-model="passwordForm.new_password_confirmation" @input="clearFieldError('new_password_confirmation')" type="password" class="field-input" :class="{ 'field-error': errors?.new_password_confirmation }" />
                <p v-if="errors?.new_password_confirmation" class="field-error-label">{{ errors.new_password_confirmation[0] }}</p>
              </div>

              <div class="flex justify-end pt-2">
                <button type="submit" :disabled="saving" class="action-btn bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 text-white shadow-lg shadow-blue-500/30">
                  <span v-if="saving" class="h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></span>
                  {{ saving ? i18n.t('common.processing') : i18n.t('profile.update_password') }}
                </button>
              </div>
            </form>
          </section>

          <section v-if="activeTab === 'orders'" class="rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-sm md:p-8">
            <div class="mb-8 flex items-center gap-4 border-b border-slate-100 pb-5">
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
              </div>
              <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-slate-400">{{ i18n.t('nav.my_orders') }}</p>
                <h3 class="text-xl font-bold text-slate-900">{{ i18n.t('order.history') }}</h3>
              </div>
            </div>

            <div v-if="loadingOrders" class="space-y-4">
              <div v-for="n in 3" :key="n" class="animate-pulse rounded-[1.75rem] border border-slate-100 bg-slate-50/70 p-5">
                <div class="mb-4 flex items-center justify-between gap-4">
                  <div class="h-5 w-28 rounded-full bg-slate-200"></div>
                  <div class="h-5 w-20 rounded-full bg-slate-200"></div>
                </div>
                <div class="h-4 w-2/3 rounded-full bg-slate-200"></div>
              </div>
            </div>

            <div v-else-if="!orders.length" class="rounded-[1.75rem] bg-slate-50 px-6 py-16 text-center">
              <p class="text-sm font-semibold text-slate-500">{{ i18n.t('order.empty') }}</p>
            </div>

            <div v-else class="space-y-4">
              <article v-for="order in orders" :key="order.id" class="rounded-[1.75rem] border border-slate-100 bg-slate-50/80 p-5 transition-colors hover:border-slate-200">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                  <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="relative flex h-16 w-16 md:h-20 md:w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-100 bg-white p-2">
                      <img v-if="order.items?.length > 0 && order.items[0].product?.hinh_anh_url" :src="order.items[0].product.hinh_anh_url" class="h-full w-full object-contain" :alt="order.items[0].product_name" @error="onImgError" />
                      <svg v-else class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                      <div v-if="order.items?.length > 1" class="absolute bottom-0 right-0 rounded-tl-xl bg-blue-600 px-1.5 py-0.5 text-[9px] font-bold text-white">
                        +{{ order.items.length - 1 }}
                      </div>
                    </div>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-bold text-slate-900 md:text-base">#{{ order.order_code }}</p>
                      <p class="mt-1 truncate text-[11px] font-bold uppercase tracking-widest text-slate-400">
                        <span v-if="order.items?.length > 0">{{ order.items[0].product?.name || order.items[0].product_name }} • </span>{{ fmtDate(order.created_at) }}
                      </p>
                    </div>
                  </div>

                  <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4 lg:gap-6 mt-3 lg:mt-0 shrink-0">
                    <div class="w-full md:w-32 lg:w-36 flex justify-start md:justify-center shrink-0">
                      <span :class="statusClass(order.status)" class="inline-flex justify-center rounded-full px-3 py-2 text-[10px] font-bold uppercase tracking-widest truncate max-w-full">
                        {{ statusLabel(order.status) }}
                      </span>
                    </div>
                    <div class="w-full md:w-32 lg:w-32 text-left md:text-right shrink-0">
                      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ i18n.t('order.total') }}</p>
                      <p class="text-base font-bold text-slate-900 truncate">{{ fmtPrice(order.total_amount) }}</p>
                    </div>
                    <div class="w-full md:w-32 lg:w-36 text-right shrink-0">
                      <router-link :to="`/orders/${order.id}`" class="inline-block w-full text-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-[11px] font-bold uppercase tracking-widest text-slate-900 transition-colors hover:border-blue-600 hover:bg-blue-600 hover:text-white shadow-sm whitespace-nowrap truncate">
                        {{ i18n.t('product.view_detail') }}
                      </router-link>
                    </div>
                  </div>
                </div>
              </article>

              <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white p-2 shadow-sm">
                  <button v-if="pagination.current_page > 1" class="page-link" @click="goPage(1)">«</button>
                  <button v-if="pagination.current_page > 1" class="page-link" @click="goPage(pagination.current_page - 1)">‹</button>
                  <template v-for="(p, index) in visiblePages" :key="index">
                    <button
                      v-if="p !== '...'"
                      class="page-link"
                      :class="{ 'page-link-active': p === pagination.current_page }"
                      @click="goPage(p)"
                    >
                      {{ p }}
                    </button>
                    <span v-else class="px-2 text-slate-400">...</span>
                  </template>
                  <button v-if="pagination.current_page < pagination.last_page" class="page-link" @click="goPage(pagination.current_page + 1)">›</button>
                  <button v-if="pagination.current_page < pagination.last_page" class="page-link" @click="goPage(pagination.last_page)">»</button>
                </div>
              </div>
            </div>
          </section>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
// =====================================================================
// [Nguyễn Duy Khang - 4.2.9] ProfileView — Trang hồ sơ cá nhân
// LUỒNG 3 TAB:
//   Tab "Hồ sơ"   → handleUpdateProfile() → PUT /profile → ProfileController@update
//              → Optimistic Locking (so sánh updated_at) → 409 nếu xung đột 2 tab
//   Tab "Mật khẩu" → handleUpdatePassword() → PUT /profile/password → ProfileController@updatePassword
//   Tab "Đơn hàng" → fetchOrders() → GET /orders → OrderController@index (lọc user_id)
// POLLING: setInterval gọi GET /profile/check-update mỗi 30s
//   → phát hiện Admin sửa thông tin user từ tab khác → hiện cảnh báo xung đột
// =====================================================================
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'
import api from '../services/api'
import { useToast } from '../composables/useToast'
import { useUtils } from '../composables/useUtils'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore() // Lấy thông tin user hiện tại + cập nhật sau khi sửa
const i18n  = useI18nStore()
const toast = useToast()
const { fmtPrice, fmtDate } = useUtils()

const tabIds    = ['profile', 'password', 'orders']
const activeTab = ref(normalizeTab(route.query.tab)) // Tab hiện tại (đồng bộ với URL ?tab=...)
const saving       = ref(false) // true khi đang gọi API lưu → disable nút submit
const loadingOrders = ref(false) // true khi đang tải danh sách đơn hàng
const errors    = ref({}) // Lưu lỗi validate từng field
const orders    = ref([]) // Danh sách đơn hàng của user (hiển ở tab "Đơn hàng")
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const isMobileContentVisible = ref(false) // Mobile: ẩn/hiện menu hoặc content

function backToMobileMenu() {
  isMobileContentVisible.value = false
  // Xoá tab khỏi URL để khi refresh trên mobile nó hiện menu
  router.replace({ query: { ...route.query, tab: undefined } })
}

const visiblePages = computed(() => {
  if (!pagination.value) return []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
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

const realtimeNotice = ref(null) // Thông báo xung đột Optimistic Locking (null = không có)
let pollingTimer = null           // Lưu ID timer polling để clearInterval khi rời trang

// Form dữ liệu hồ sơ — tự điền từ thông tin user hiện tại
const form = ref({
  name:    authStore.user?.name    || '',
  email:   authStore.user?.email   || '',
  address: authStore.user?.address || '',
  phone:   authStore.user?.phone   || ''
})

// Form đổi mật khẩu (3 field: mật khẩu cũ, mới, xác nhận)
const passwordForm = ref({
  current_password:         '',
  new_password:             '',
  new_password_confirmation: ''
})

const menu = [
  {
    id: 'profile',
    labelKey: 'nav.profile',
    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`
  },
  {
    id: 'password',
    labelKey: 'profile.password_tab',
    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>`
  },
  {
    id: 'orders',
    labelKey: 'nav.my_orders',
    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>`
  }
]

watch(
  () => route.query.tab,
  (newTab) => {
    activeTab.value = normalizeTab(newTab)
  }
)

watch(activeTab, async (tab, previousTab) => {
  if (tab === previousTab) return
  errors.value = {}

  const nextQuery = { ...route.query }
  if (tab === 'profile') {
    delete nextQuery.tab
  } else {
    nextQuery.tab = tab
  }

  if (route.query.tab !== nextQuery.tab) {
    router.replace({ query: nextQuery })
  }

  if (tab === 'orders' && !orders.value.length) {
    await fetchOrders()
  }
})

watch(
  () => authStore.user,
  (user) => {
    if (!user) return
    form.value = {
      name: user.name || '',
      email: user.email || '',
      address: user.address || '',
      phone: user.phone || ''
    }
  },
  { immediate: true, deep: true }
)

onMounted(() => {
  startPolling()
  // Trên mobile, nếu URL không có tab cụ thể, thì mặc định hiện menu
  if (window.innerWidth < 768 && !route.query.tab) {
    isMobileContentVisible.value = false
  } else {
    isMobileContentVisible.value = true
  }

  if (activeTab.value === 'orders') {
    fetchOrders()
  }
})

onUnmounted(() => stopPolling())

function clearFieldError(field) {
  if (errors.value?.[field]) {
    delete errors.value[field]
  }
}

// GET /orders?page=N → OrderController@index → lọc theo user_id hiện tại
async function fetchOrders(page = 1) {
  loadingOrders.value = true
  try {
    const res = await api.get('/orders', { params: { page } })
    orders.value     = res.data.data || res.data // Danh sách đơn hàng của user
    pagination.value = res.data.meta || {
      current_page: res.data.current_page || 1,
      last_page:    res.data.last_page    || 1,
      total:        res.data.total        || orders.value.length
    }
  } catch {
    orders.value = []
  } finally {
    loadingOrders.value = false
  }
}

function goPage(page) {
  fetchOrders(page)
}

function normalizeTab(tab) {
  return tabIds.includes(tab) ? tab : 'profile'
}

function setActiveTab(id) {
  const normalizedId = normalizeTab(id)
  activeTab.value = normalizedId
  isMobileContentVisible.value = true
  router.replace({ query: { ...route.query, tab: normalizedId } })
  
  // Trên mobile không cần auto scroll nữa vì đã ẩn sidebar
  if (window.innerWidth >= 768 && window.innerWidth < 1024) {
    setTimeout(() => {
      const mainEl = document.querySelector('main')
      if (mainEl) {
        const offset = mainEl.getBoundingClientRect().top + window.scrollY - 100
        window.scrollTo({ top: offset, behavior: 'smooth' })
      }
    }, 50)
  }
}

function statusLabel(status) {
  const map = {
    pending: i18n.locale === 'vi' ? 'Chờ xác nhận' : 'Pending',
    confirmed: i18n.locale === 'vi' ? 'Đã xác nhận' : 'Confirmed',
    shipping: i18n.locale === 'vi' ? 'Đang giao hàng' : 'Shipping',
    shipped: i18n.locale === 'vi' ? 'Đã giao đơn vị vận chuyển' : 'Shipped',
    completed: i18n.locale === 'vi' ? 'Hoàn thành' : 'Completed',
    cancelled: i18n.locale === 'vi' ? 'Đã hủy' : 'Cancelled'
  }
  return map[status] || status
}

function statusClass(status) {
  const map = {
    pending: 'bg-amber-100 text-amber-700',
    confirmed: 'bg-indigo-100 text-indigo-700',
    shipping: 'bg-sky-100 text-sky-700',
    shipped: 'bg-cyan-100 text-cyan-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-rose-100 text-rose-700'
  }
  return map[status] || 'bg-slate-100 text-slate-600'
}

function startPolling() {
  // Polling disabled to prevent false 'Data Conflict' when Orders update User's updated_at.
  // Optimistic locking is still enforced on form submission.
}

function stopPolling() {
  if (pollingTimer) clearInterval(pollingTimer)
}

async function refreshProfile() {
  realtimeNotice.value = null
  await authStore.fetchMe()
  if (activeTab.value === 'orders' && !orders.value.length) {
    await fetchOrders()
  }
}

// Sửa thông tin hồ sơ cá nhân (có Optimistic Locking)
// PUT /profile → gửi kèm updated_at (timestamp) → ProfileController@update
//   → ProfileService so sánh updated_at client vs DB
//   → 409 Conflict nếu bị cập nhật từ tab khác → hiện realtimeNotice yêu cầu tải lại
async function handleUpdateProfile() {
  errors.value = {}

  let hasError = false
  if (!form.value.name?.trim()) {
    errors.value.name = [i18n.t('auth.name_error') || 'Vui lòng nhập họ tên']
    hasError = true
  }
  if (!form.value.email?.trim()) {
    errors.value.email = [i18n.t('auth.email_error') || 'Vui lòng nhập email']
    hasError = true
  }
  if (hasError) return // Lỗi FE → dừng, hiển thị lỗi dưới field

  saving.value = true
  try {
    const res = await api.put('/profile', {
      ...form.value,
      updated_at: authStore.user.updated_at // Gửi timestamp Optimistic Locking
    })

    authStore.user = res.data.user                                // Cập nhật state Pinia
    localStorage.setItem('auth_user', JSON.stringify(res.data.user)) // Đồng bộ localStorage
    form.value = {
      name:    res.data.user.name    || '',
      email:   res.data.user.email   || '',
      address: res.data.user.address || '',
      phone:   res.data.user.phone   || ''
    }
    toast.success(i18n.t('profile.update_success'))
    realtimeNotice.value = null // Xóa thông báo xung đột nếu có
  } catch (e) {
    if (e.response?.status === 409) {
      // 409 = Optimistic Locking Conflict: ai đó sửa user này trong khi bạn đang sửa
      realtimeNotice.value = e.response.data.message || 'Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại dữ liệu.'
      return
    }

    const serverMessage = e.response?.data?.message || ''
    if (e.response?.status === 422) {
      const data = e.response.data
      errors.value = data.errors || { name: [data.message || 'Lỗi xác thực dữ liệu'] }
    } else if (serverMessage.toLowerCase().includes('name') || serverMessage.toLowerCase().includes('tên')) {
      errors.value.name = [serverMessage]
    } else if (serverMessage.toLowerCase().includes('email')) {
      errors.value.email = [serverMessage]
    } else {
      errors.value.name = [serverMessage || 'Không thể cập nhật hồ sơ do lỗi hệ thống']
    }
  } finally {
    saving.value = false
  }
}

// Đổi mật khẩu: validate FE trước → PUT /profile/password → ProfileController@updatePassword
// ProfileService: Hash::check(current) → hash::make(new) → UPDATE users.password
async function handleUpdatePassword() {
  errors.value = {}

  let hasError = false
  if (!passwordForm.value.current_password) {
    errors.value.current_password = [i18n.t('auth.current_password_required')]; hasError = true
  }
  if (!passwordForm.value.new_password) {
    errors.value.new_password = [i18n.t('auth.new_password_required')]; hasError = true
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
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' } // Reset form
  } catch (e) {
    const serverMessage = e.response?.data?.message || ''
    if (e.response?.status === 422) {
      const data = e.response.data
      errors.value = data.errors || { current_password: [data.message || 'Lỗi xác thực mật khẩu'] }
    } else if (serverMessage.toLowerCase().includes('current') || serverMessage.toLowerCase().includes('hiện tại')) {
      errors.value.current_password = [serverMessage] // Mật khẩu cũ sai
    } else if (serverMessage.toLowerCase().includes('new') || serverMessage.toLowerCase().includes('mới')) {
      errors.value.new_password = [serverMessage]
    } else {
      errors.value.current_password = [serverMessage || 'Lỗi hệ thống khi đổi mật khẩu']
    }
  } finally {
    saving.value = false
  }
}

function onImgError(e) {
  e.target.src = 'https://via.placeholder.com/400'
}
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.35s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.field-input {
  width: 100%;
  border-radius: 1.25rem;
  border: 1px solid transparent;
  background: #f8fafc;
  padding: 1rem 1.25rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: #0f172a;
  outline: none;
  transition: all 0.2s ease;
}

.field-input:focus {
  border-color: #a7f3d0;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
}

.field-input:disabled {
  cursor: not-allowed;
  opacity: 0.8;
}

.field-error {
  border-color: #fb7185 !important;
  box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.08);
}

.field-error-label {
  margin-left: 0.25rem;
  margin-top: 0.35rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #f43f5e;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border-radius: 9999px;
  padding: 0.9rem 1.5rem;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: white;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.action-btn:active {
  transform: scale(0.98);
}

.page-link {
  height: 2.25rem;
  min-width: 2.25rem;
  border-radius: 9999px;
  border: none;
  background: transparent;
  padding: 0 0.8rem;
  font-size: 0.85rem;
  font-weight: 700;
  color: #0f172a;
  transition: all 0.2s ease;
}

.page-link:hover {
  background: #f1f5f9;
}

.page-link-active {
  background: #2563eb; /* blue-600 */
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}
</style>
