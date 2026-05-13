<template>
  <div id="app-root">
    <!-- Admin: Isolated layout -->
    <template v-if="isAdminRoute">
      <ToastManager />
      <router-view />
    </template>

    <!-- Shop: Full layout -->
    <template v-else>
      <AppBackground />
      <div class="app-layout">
        <ToastManager />

        <AppNavbar 
          v-model:searchQuery="searchQuery"
          :show-categories="showCategories"
          :categories="categories"
          :show-suggest="showSuggest"
          :suggestions="suggestions"
          :cart-count="cartStore.soLuong"
          :is-logged-in="authStore.isLoggedIn"
          :is-admin="authStore.isAdmin"
          :user-name="authStore.user?.name"
          @toggle-categories="showCategories = !showCategories"
          @go-category="goCategory"
          @do-search="doSearch"
          @show-suggest="showSuggest = true"
          @hide-suggest="hideSuggest"
          @go-product="goProduct"
          @logout="handleLogout"
        />

        <main class="app-main">
          <router-view v-slot="{ Component }">
            <transition name="page" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </main>

        <AppFooter />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'
import { useI18nStore } from './stores/i18n'
import { useToast } from './composables/useToast'
import { useCategories } from './composables/useCategories'
import { productsApi } from './api'

import ToastManager from './components/ToastManager.vue'
import AppBackground from './components/layout/AppBackground.vue'
import AppNavbar from './components/layout/AppNavbar.vue'
import AppFooter from './components/layout/AppFooter.vue'

const authStore = useAuthStore()
const cartStore = useCartStore()
const i18n = useI18nStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()
const { categories, fetchCategories } = useCategories()

// Cho phép dùng F12 nhưng hiển thị cảnh báo bảo mật chuyên nghiệp
console.log(
  `%c${i18n.t('common.console_stop')}`,
  "color: #e11d48; font-size: 50px; font-weight: bold; -webkit-text-stroke: 1px black;"
);
console.log(
  `%c${i18n.t('common.console_warning')}`,
  "font-size: 18px;"
);
console.log(
  "%cXem https://sellphones.vn/self-xss để biết thêm thông tin.",
  "font-size: 18px;"
);

const isAdminRoute = computed(() => route.path.startsWith('/admin'))
const searchQuery = ref('')
const suggestions = ref([])
const showSuggest = ref(false)
let searchTimer = null
const showCategories = ref(false)
const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') syncTabs()
}
const handleStorageChange = (e) => {
  if (e.key === 'cart_voucher') cartStore.fetchCart()
}

function goCategory(id) {
  showCategories.value = false
  router.push({ path: '/products', query: { category: id } })
}

function handleSearchInput(val) {
  clearTimeout(searchTimer)
  if (!val.trim()) { suggestions.value = []; return }
  searchTimer = setTimeout(async () => {
    try {
      const res = await productsApi.list({ search: val, limit: 5 })
      suggestions.value = res.data.data
    } catch {}
  }, 300)
}

watch(searchQuery, handleSearchInput)

function hideSuggest() {
  setTimeout(() => { showSuggest.value = false }, 200)
}

function goProduct(id) {
  showSuggest.value = false
  searchQuery.value = ''
  router.push(`/products/${id}`)
}

function doSearch() {
  if (!searchQuery.value.trim()) return
  showSuggest.value = false
  router.push({ path: '/products', query: { ...route.query, search: searchQuery.value } })
}

watch(() => route.query.search, (newVal) => {
  searchQuery.value = newVal || ''
}, { immediate: true })

function closeMenus(e) {
  if (!e.target.closest('.nav-category-wrap')) showCategories.value = false
}

// 2-tab sync: Fetch cart when tab becomes visible or storage changes
function syncTabs() {
  if (authStore.isLoggedIn) {
    cartStore.fetchCart()
  }
}

onMounted(() => { 
  fetchCategories()
  if (authStore.isLoggedIn) cartStore.fetchCart() 
  window.addEventListener('click', closeMenus)
  window.addEventListener('visibilitychange', handleVisibilityChange)
  window.addEventListener('storage', handleStorageChange)
})

onUnmounted(() => {
  window.removeEventListener('click', closeMenus)
  window.removeEventListener('visibilitychange', handleVisibilityChange)
  window.removeEventListener('storage', handleStorageChange)
})

async function handleLogout() {
  await authStore.logout()
  cartStore.items = []
  cartStore.soLuong = 0
  toast.success(i18n.t('common.logout_success'))
  router.push('/login')
}
</script>

<style>
/* Global Layout Styles */
#app-root { position: relative; min-height: 100vh; }
.app-layout { position: relative; z-index: 1; display: flex; flex-direction: column; min-height: 100vh; }
.app-main { flex: 1; }

/* Page Transitions */
.page-enter-active, .page-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.page-enter-from { opacity: 0; transform: translateY(10px); }
.page-leave-to { opacity: 0; transform: translateY(-10px); }

/* Utility */
.sticky-top { position: sticky; top: 0; z-index: 1000; }
.transition-transform { transition: transform 0.2s; }
.rotate-180 { transform: rotate(180deg); }

@media (max-width: 768px) {
  .nav-category-btn { display: none; }
  .user-name { display: none; }
  .footer-grid { grid-template-columns: 1fr 1fr; gap: 28px; }
  .apple-bg-sphere { filter: blur(80px); opacity: 0.3; }
  .apple-bg-sphere-1 { width: 200px; height: 200px; }
  .apple-bg-sphere-2 { width: 150px; height: 150px; }
  footer { padding-bottom: 80px !important; }
}
</style>
