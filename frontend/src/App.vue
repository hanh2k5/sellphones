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
          :is-logged-in="authStore.isLoggedIn"
          :is-admin="authStore.isAdmin"
          :user-name="authStore.user?.name"
          @do-search="doSearch"
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
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useToast } from './composables/useToast'

import ToastManager from './components/ToastManager.vue'
import AppBackground from './components/layout/AppBackground.vue'
import AppNavbar from './components/layout/AppNavbar.vue'
import AppFooter from './components/layout/AppFooter.vue'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()

const isAdminRoute = computed(() => route.path.startsWith('/admin'))
const searchQuery = ref('')

function doSearch() {
  if (!searchQuery.value.trim()) return
  router.push({ path: '/products', query: { ...route.query, search: searchQuery.value } })
}

watch(() => route.query.search, (newVal) => {
  searchQuery.value = newVal || ''
}, { immediate: true })

async function handleLogout() {
  await authStore.logout()
  toast.success('Đã đăng xuất')
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

body {
  margin: 0;
  padding: 0;
  font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
  background-color: #f8fafc;
  color: #1e293b;
  -webkit-font-smoothing: antialiased;
}

@media (max-width: 768px) {
  footer { padding-bottom: 20px !important; }
}
</style>
