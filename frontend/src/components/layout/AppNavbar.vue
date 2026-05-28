<template>
  <nav class="glass-nav sticky-top">
    <div class="nav-shell">
      <div class="nav-top-row">
        <div class="nav-left">
          <router-link to="/" class="nav-brand" @click="closeAllMenus">
            <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="url(#brandGradient)" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round">
              <defs>
                <linearGradient id="brandGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#2563eb;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#4f46e5;stop-opacity:1" />
                </linearGradient>
              </defs>
              <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
              <path d="M10 5h4" stroke-width="2" stroke="url(#brandGradient)"></path>
              <line x1="12" y1="18" x2="12.01" y2="18"></line>
            </svg>
            <span class="brand-name">SELLPHONES</span>
          </router-link>

          <div class="nav-category-wrap desktop-only">
            <button type="button" class="nav-category-btn" @click.stop="$emit('toggle-categories')">
              <span>{{ i18n.t('nav.all_categories') }}</span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                :class="{ 'rotate-180': showCategories }" class="transition-transform">
                <path d="M6 9l6 6 6-6" />
              </svg>
            </button>

            <Transition name="suggest">
              <div v-if="showCategories" class="category-dropdown-custom">
                <div class="category-header">
                  <p class="dropdown-label">{{ i18n.t('nav.all_categories') }}</p>
                </div>
                <div class="category-list">
                  <button v-for="cat in topCategories" :key="cat.id" type="button" @click="goCategory(cat.id)"
                    class="category-item-custom">
                    <span class="cat-icon" v-html="getCategoryIcon(cat.name)"></span>
                    <span class="cat-name">{{ i18n.transName(cat.name) }}</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <div class="nav-search-wrap desktop-search">
          <div class="search-inner">
            <input :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)"
              @focus="$emit('show-suggest')" @blur="$emit('hide-suggest')" @keyup.enter="$emit('do-search')" type="text"
              :placeholder="i18n.t('nav.search_placeholder')" class="search-box" />
            <button type="button" class="search-btn" @click="$emit('do-search')">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
              </svg>
            </button>
          </div>

          <Transition name="suggest">
            <div v-if="showSuggest && suggestions.length" class="suggest-box">
              <div v-for="p in suggestions" :key="p.id" @mousedown="goProduct(p.id)" class="suggest-item">
                <div class="suggest-img">
                  <img :src="getImageUrl(p.hinh_anh)" :alt="p.name" @error="onImgError" />
                </div>
                <div class="suggest-info">
                  <p class="suggest-name">{{ p.name }}</p>
                  <p class="suggest-price">{{ fmtPrice(p.price) }}</p>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <div class="nav-right">
          <button type="button" class="lang-switcher" @click="i18n.toggleLocale()">
            <span class="lang-code">{{ i18n.locale.toUpperCase() }}</span>
          </button>

          <router-link to="/cart" class="nav-icon-btn" :title="i18n.t('nav.cart')" @click="closeAllMenus">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
              <line x1="3" y1="6" x2="21" y2="6" />
              <path d="M16 10a4 4 0 0 1-8 0" />
            </svg>
            <span v-if="cartCount > 0" class="badge-notify">{{ cartCount }}</span>
          </router-link>

          <div v-if="isLoggedIn" class="user-dropdown-wrap desktop-only">
            <button type="button" class="user-btn-wrap" @click.stop="showDropdown = !showDropdown">
              <span class="user-name-text">Hi, {{ userName }}</span>
              <div class="user-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </div>
            </button>
            <div v-if="showDropdown" class="account-dropdown">
              <router-link v-if="isAdmin" to="/admin" class="dropdown-item" @click="closeAllMenus">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-indigo-600">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                {{ i18n.t('nav.admin') }}
              </router-link>
              <router-link to="/profile" class="dropdown-item" @click="closeAllMenus">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                {{ i18n.t('nav.profile') }}
              </router-link>
              <div class="dropdown-divider"></div>
              <button type="button" @click="handleLogout" class="dropdown-item danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                  <polyline points="16 17 21 12 16 7" />
                  <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                {{ i18n.t('nav.logout') }}
              </button>
            </div>
          </div>

          <router-link v-else to="/login" class="login-btn desktop-only" @click="closeAllMenus">{{ i18n.t('nav.login')
            }}</router-link>

          <button type="button" class="menu-toggle mobile-only" @click.stop="showMobileMenu = !showMobileMenu">
            <svg v-if="!showMobileMenu" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2.2" stroke-linecap="round">
              <path d="M4 7h16" />
              <path d="M4 12h16" />
              <path d="M4 17h16" />
            </svg>
            <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
              stroke-linecap="round">
              <path d="M6 6l12 12" />
              <path d="M18 6 6 18" />
            </svg>
          </button>
        </div>
      </div>

      <div class="nav-search-wrap mobile-search">
        <div class="search-inner">
          <input :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)"
            @focus="$emit('show-suggest')" @blur="$emit('hide-suggest')" @keyup.enter="$emit('do-search')" type="text"
            :placeholder="i18n.t('nav.search_placeholder')" class="search-box" />
          <button type="button" class="search-btn" @click="$emit('do-search')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
          </button>
        </div>

        <Transition name="suggest">
          <div v-if="showSuggest && suggestions.length" class="suggest-box">
            <div v-for="p in suggestions" :key="p.id" @mousedown="goProduct(p.id)" class="suggest-item">
              <div class="suggest-img">
                <img :src="getImageUrl(p.hinh_anh)" :alt="p.name" @error="onImgError" />
              </div>
              <div class="suggest-info">
                <p class="suggest-name">{{ p.name }}</p>
                <p class="suggest-price">{{ fmtPrice(p.price) }}</p>
              </div>
            </div>
          </div>
        </Transition>
      </div>

    </div>
  </nav>

  <Teleport to="body">
    <Transition name="fade">
      <div v-if="showMobileMenu" class="fixed inset-0 z-[1040] bg-black/50 md:hidden" @click="showMobileMenu = false"></div>
    </Transition>

    <Transition name="slide-drawer">
      <div v-if="showMobileMenu" class="mobile-panel md:hidden">
        <div class="mobile-drawer-header md:hidden flex justify-between items-center mb-6">
          <span class="text-lg font-bold text-slate-900">Menu</span>
          <button type="button" class="menu-toggle" @click.stop="showMobileMenu = false">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <path d="M18 6 6 18" />
              <path d="M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="mobile-section" v-if="isLoggedIn">
          <router-link v-if="isAdmin" to="/admin" class="mobile-link" @click="closeAllMenus">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-indigo-600">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            {{ i18n.t('nav.admin') }}
          </router-link>
          <router-link to="/profile" class="mobile-link" @click="closeAllMenus">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
            {{ i18n.t('nav.profile') }}
          </router-link>
          <button type="button" class="mobile-link danger" @click="handleLogout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            {{ i18n.t('nav.logout') }}
          </button>
        </div>

        <div class="mobile-section" v-else>
          <router-link to="/login" class="mobile-cta" @click="closeAllMenus">{{ i18n.t('nav.login') }}</router-link>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useI18nStore } from '../../stores/i18n'
import { useUtils } from '../../composables/useUtils'

const props = defineProps({
  showCategories: Boolean,
  categories: Array,
  searchQuery: String,
  showSuggest: Boolean,
  suggestions: Array,
  cartCount: Number,
  isLoggedIn: Boolean,
  isAdmin: Boolean,
  userName: String
})

const emit = defineEmits([
  'toggle-categories',
  'go-category',
  'update:searchQuery',
  'do-search',
  'show-suggest',
  'hide-suggest',
  'go-product',
  'logout'
])

const i18n = useI18nStore()
const { fmtPrice, getImageUrl } = useUtils()
const showDropdown = ref(false)
const showMobileMenu = ref(false)

const topCategories = computed(() => (props.categories || []).filter((c) => !c.parent_id))

function closeAllMenus() {
  showDropdown.value = false
  showMobileMenu.value = false
}

function handleLogout() {
  closeAllMenus()
  emit('logout')
}

function goCategory(id) {
  closeAllMenus()
  emit('go-category', id)
}

function goProduct(id) {
  closeAllMenus()
  emit('go-product', id)
}

function closeOnOutsideClick(e) {
  if (!e.target.closest('.user-dropdown-wrap')) {
    showDropdown.value = false
  }
  if (!e.target.closest('.nav-shell')) {
    showMobileMenu.value = false
  }
}

function onImgError(e) {
  e.target.src = 'https://via.placeholder.com/40'
}

function getCategoryIcon(name) {
  const n = (name || '').toLowerCase()
  if (n.includes('iphone') || n.includes('apple')) return `<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="opacity:0.8"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-.5-.22-1.07-.46-1.92-.46-.86 0-1.4.23-1.96.46-1.04.46-2.02.58-2.97-.4C4.16 17.38 3.5 12.06 5.5 8.6c1-1.74 2.76-2.82 4.7-2.85 1.1-.02 1.95.43 2.6.43.6 0 1.63-.53 2.92-.4 1.34.13 2.37.62 2.9 1.4-2.73 1.63-2.3 5.15.44 6.27-.6 1.54-1.37 3.08-2.02 3.84zM12.03 5.75c-.2-.02-.4-.02-.6-.02.05-2.26 1.9-4.2 4.14-4.23.23 0 .46.03.7.05-2.1 2.38-4.04 4.2-4.24 4.2z"/></svg>`
  if (n.includes('phu kien') || n.includes('phụ kiện') || n.includes('accessory')) return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>`
}

onMounted(() => window.addEventListener('click', closeOnOutsideClick))
onUnmounted(() => window.removeEventListener('click', closeOnOutsideClick))
</script>

<style scoped>
.glass-nav {
  position: sticky;
  top: 0;
  z-index: 1030;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: saturate(180%) blur(18px);
}

.nav-shell {
  max-width: 1400px;
  margin: 0 auto;
  padding: 14px 24px;
}

.nav-top-row {
  display: grid;
  grid-template-columns: 1fr minmax(320px, 560px) 1fr;
  align-items: center;
  gap: 20px;
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.nav-right {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 14px;
}

.nav-brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #0f172a;
  font-size: 20px;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.brand-icon {
  width: 30px;
  height: 30px;
}

.nav-category-wrap,
.user-dropdown-wrap {
  position: relative;
}

.nav-category-btn,
.menu-toggle,
.nav-icon-btn,
.lang-switcher,
.search-btn,
.login-btn,
.mobile-link,
.mobile-cta {
  transition: all 0.2s ease;
}

.nav-category-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: none;
  border-radius: 999px;
  background: #f1f5f9;
  padding: 10px 16px;
  color: #0f172a;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
}

.nav-category-btn:hover,
.menu-toggle:hover,
.lang-switcher:hover,
.nav-icon-btn:hover {
  background: #e2e8f0;
}

.nav-search-wrap {
  position: relative;
}

.mobile-search,
.mobile-only {
  display: none !important;
}

.search-inner {
  display: flex;
  align-items: center;
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 20px;
  background: rgba(248, 250, 252, 0.92);
  padding: 4px 6px 4px 16px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}

.search-inner:focus-within {
  border-color: rgba(13, 148, 136, 0.32);
  background: #fff;
  box-shadow: 0 14px 34px rgba(13, 148, 136, 0.12);
}

.search-box {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  padding: 10px 0;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
  text-align: center;
  outline: none;
}

.search-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 14px;
  background: transparent;
  color: #475569;
  cursor: pointer;
  outline: none;
}

.menu-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 50%;
  background: #f1f5f9;
  color: #0f172a;
  cursor: pointer;
  outline: none;
  -webkit-tap-highlight-color: transparent;
}

.suggest-box,
.category-dropdown-custom,
.account-dropdown {
  border: 1px solid rgba(255, 255, 255, 0.5);
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
}

.suggest-box {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  right: 0;
  z-index: 20;
  overflow: hidden;
  border-radius: 20px;
}

.suggest-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
}

.suggest-item:hover {
  background: #f8fafc;
}

.suggest-img {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  overflow: hidden;
  border-radius: 12px;
  background: #f8fafc;
}

.suggest-img img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.suggest-info {
  min-width: 0;
}

.suggest-name {
  margin: 0;
  color: #0f172a;
  font-size: 13px;
  font-weight: 800;
}

.suggest-price {
  margin: 2px 0 0;
  color: #0f766e;
  font-size: 12px;
  font-weight: 700;
}

.nav-right {
  justify-content: flex-end;
}

.lang-switcher {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 38px;
  border: none;
  border-radius: 12px;
  background: #f8fafc;
  padding: 0 12px;
  color: #334155;
  font-size: 11px;
  font-weight: 900;
  cursor: pointer;
}

.nav-icon-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 14px;
  color: #334155;
}

.badge-notify {
  position: absolute;
  top: -2px;
  right: -2px;
  min-width: 18px;
  height: 18px;
  border: 2px solid #fff;
  border-radius: 999px;
  background: #ef4444;
  color: #fff;
  font-size: 9px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}

.user-btn-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  border-radius: 999px;
  background: transparent;
  padding: 4px;
  cursor: pointer;
  outline: none;
  -webkit-tap-highlight-color: transparent;
}

.user-btn-wrap:hover {
  background: #f8fafc;
}

.user-name-text {
  max-width: 120px;
  overflow: hidden;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #334155;
}

.account-dropdown,
.category-dropdown-custom {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  z-index: 24;
  width: 180px;
  border-radius: 16px;
  padding: 8px;
}

.category-dropdown-custom {
  left: 0;
  right: auto;
  width: 220px;
}

.dropdown-label {
  margin-bottom: 12px;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  padding-left: 8px;
}

.category-list,
.mobile-category-grid {
  display: grid;
  gap: 2px;
}

.category-item-custom,
.mobile-category-card {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  border: none;
  border-radius: 10px;
  background: transparent;
  padding: 10px 12px;
  color: #334155;
  font-size: 14px;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
}

.category-item-custom:hover,
.mobile-category-card:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.cat-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: color 0.2s;
}

.category-item-custom:hover .cat-icon,
.mobile-category-card:hover .cat-icon {
  color: #2563eb;
}

.dropdown-item {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
  width: 100%;
  border: none;
  border-radius: 12px;
  background: transparent;
  padding: 10px 14px;
  color: #334155;
  font-size: 14px;
  font-weight: 600;
  text-align: left;
}

.mobile-link,
.mobile-cta {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 16px;
  width: 100%;
  border: none;
  border-radius: 16px;
  background: transparent;
  padding: 14px 16px;
  color: #334155;
  font-size: 15px;
  font-weight: 600;
  text-align: left;
}

.dropdown-item:hover,
.mobile-link:hover,
.mobile-cta:hover {
  background: #f8fafc;
}

.dropdown-divider {
  height: 1px;
  margin: 8px 0;
  background: #e2e8f0;
}

.danger {
  color: #dc2626;
}

.login-btn,
.mobile-cta {
  justify-content: center;
  border-radius: 14px;
  background: #0f172a;
  padding: 10px 16px;
  color: #fff;
  font-size: 13px;
  font-weight: 800;
}

.login-btn:hover,
.mobile-cta:hover {
  background: #1e293b;
}

.mobile-panel {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 250px;
  z-index: 1050;
  background: #ffffff;
  box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
  padding: 24px;
  display: flex;
  flex-direction: column;
}

.slide-drawer-enter-active,
.slide-drawer-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-drawer-enter-from,
.slide-drawer-leave-to {
  transform: translateX(100%);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.suggest-enter-active,
.suggest-leave-active {
  transition: all 0.22s ease;
}

.suggest-enter-from,
.suggest-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@media (max-width: 1024px) {
  .nav-top-row {
    grid-template-columns: minmax(0, 1fr) auto;
  }

  .desktop-search {
    display: none;
  }

  .mobile-search {
    display: block;
    margin-top: 12px;
  }
}

@media (max-width: 768px) {
  .nav-shell {
    padding: 12px;
  }

  .nav-top-row {
    gap: 10px;
  }

  .desktop-only {
    display: none;
  }

  .mobile-only {
    display: inline-flex !important;
  }

  .brand-name {
    font-size: 16px;
  }

  .brand-icon {
    width: 24px;
    height: 24px;
  }

  .nav-right {
    gap: 8px;
  }

  .lang-switcher,
  .nav-icon-btn,
  .menu-toggle {
    width: 36px;
    height: 36px;
    padding: 0;
  }

  .lang-switcher {
    border-radius: 12px;
  }

  .mobile-panel {
    display: flex;
  }

  .mobile-section+.mobile-section {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
  }

  .mobile-category-grid {
    grid-template-columns: 1fr 1fr;
  }

  .mobile-category-card,
  .mobile-link {
    min-height: 48px;
  }
}
</style>
