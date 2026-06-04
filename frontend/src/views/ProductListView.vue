<template>
  <main class="container-main mt-0 mb-5">
    <!-- Ultra-Minimalist Hero Section -->
    <header class="minimal-hero reveal-item">
      <h1 class="minimal-title">{{ i18n.t('home.hero_title') }}</h1>
      <p class="minimal-subtitle">{{ i18n.t('home.hero_subtitle') }}</p>
    </header>

    <!-- Compact Integrated Filter Bar -->
    <div class="compact-filter-bar mb-6 reveal-item">
      <!-- Main Category Row -->
      <div class="filter-main-row">
        <div class="category-pills">
          <button @click="resetCategory" class="pill-modern" :class="{ active: !currentCatId }">
            {{ i18n.t('product.price_all') }}
          </button>
          <button v-for="cat in parentCategories" :key="cat.id" @click="setCategory(cat.id)" class="pill-modern"
            :class="{ active: isParentActive(cat) }">
            {{ i18n.transName(cat.name) }}
          </button>
        </div>

        <div class="filter-divider-v d-none d-lg-block"></div>

        <div class="utility-row">
          <select v-model="sortBy" @change="handleSortChange" class="select-minimal">
            <option value="">{{ i18n.t('common.newest') }}</option>
            <option value="price_asc">↑ {{ i18n.t('common.price_asc') }}</option>
            <option value="price_desc">↓ {{ i18n.t('common.price_desc') }}</option>
          </select>
          <button @click="resetFilter" class="btn-reset-minimal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
              <path d="M3 3v5h5" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Price Range Slider (Dual-range Component) -->
      <div class="slider-wrapper mt-3">
        <div class="slider-label-row">
          <span class="slider-label-title">{{ i18n.t('product.filter_price') || 'Lọc giá' }}</span>
          <span class="slider-label-value">{{ fmtPrice(tempMinPrice) }} — {{ fmtPrice(tempMaxPrice) }}</span>
        </div>
        <div class="custom-slider-container">
          <div class="custom-slider-track"></div>
          <div class="custom-slider-range" :style="rangeStyle"></div>
          <input 
            type="range" 
            v-model.number="tempMinPrice" 
            :min="minLimit" 
            :max="maxLimit" 
            :step="step" 
            class="range-thumb range-min" 
            @input="onMinSliderInput" 
          />
          <input 
            type="range" 
            v-model.number="tempMaxPrice" 
            :min="minLimit" 
            :max="maxLimit" 
            :step="step" 
            class="range-thumb range-max" 
            @input="onMaxSliderInput" 
          />
        </div>
        <div class="slider-limits">
          <span>0đ</span>
          <span>50.000.000đ</span>
        </div>
      </div>

      <!-- Sub-categories (Conditional) -->
      <Transition name="slide-down-fade">
        <div v-if="activeSubCategories.length" class="sub-category-row pt-3 mt-3 border-t border-slate-100">
          <div class="category-pills-sm">
            <button v-for="sub in activeSubCategories" :key="sub.id" @click="setCategory(sub.id)" class="pill-modern-sm"
              :class="{ active: currentCatId == sub.id }">
              {{ i18n.transName(sub.name) }}
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="productStore.loading" class="home-grid">
      <div v-for="i in 10" :key="i" class="skeleton-card"></div>
    </div>

    <!-- Empty State - Premium -->
    <div v-else-if="productStore.list.length === 0" class="empty-state reveal-item">
      <div class="empty-illustration-wrap">
        <img src="/images/no_results.png" alt="No products found" class="empty-illustration" />
      </div>
      <h3>{{ i18n.t('product.not_found') || 'Không tìm thấy sản phẩm' }}</h3>
      <p>{{ i18n.t('product.search_placeholder') || 'Thử thay đổi bộ lọc hoặc khoảng giá của bạn.' }}</p>
      <button class="pill-modern active" @click="resetFilter">
        {{ i18n.t('product.clear_filter') || 'Xóa bộ lọc' }}
      </button>
    </div>

    <template v-else>
      <!-- Filter Result Info -->
      <div v-if="isFilterActive" class="filter-result-info reveal-item mb-4">
        <div class="filter-message-badge">
          <span>💡 {{ productStore.listMessage || `Tìm thấy ${productStore.pagination?.total || 0} sản phẩm trong tầm giá của bạn` }}</span>
        </div>
      </div>

      <div class="home-grid" ref="gridRef">
        <ProductCard v-for="product in productStore.list" :key="product.id" :product="product" />
      </div>
    </template>

    <!-- Pagination (Chuẩn Apple - 10 sản phẩm/trang) -->
    <div v-if="productStore.pagination && productStore.pagination.last_page > 1" class="pagination-wrapper reveal-item">
      <div class="pagination-apple-wrapper">
        <ul class="pagination-apple">
          <!-- Back -->
          <li v-if="productStore.pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(productStore.pagination.current_page - 1)">«</button>
          </li>

          <!-- Dynamic Numbers -->
          <li v-for="p in visiblePages" :key="p" class="page-item"
            :class="{ active: p === productStore.pagination.current_page }">
            <button v-if="p !== '...'" class="page-link" @click="goPage(p)">{{ p }}</button>
            <span v-else class="page-link-text">...</span>
          </li>

          <!-- Next -->
          <li v-if="productStore.pagination.current_page < productStore.pagination.last_page" class="page-item">
            <button class="page-link" @click="goPage(productStore.pagination.current_page + 1)">»</button>
          </li>
        </ul>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, onMounted, nextTick, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '../stores/product'
import { useI18nStore } from '../stores/i18n'
import ProductCard from '../components/ProductCard.vue'
import { categoriesApi } from '../api'
import { useUtils } from '../composables/useUtils'

const productStore = useProductStore()
const i18n = useI18nStore()
const route = useRoute()
const router = useRouter()
const sortBy = ref('')
const categories = ref([])

// Range limits
const minLimit = 0
const maxLimit = 50000000
const step = 500000

// Local ranges for dragging slider
const tempMinPrice = ref(minLimit)
const tempMaxPrice = ref(maxLimit)

// Computed active range percentage style for dual range track
const rangeStyle = computed(() => {
  const leftPercent = (tempMinPrice.value / maxLimit) * 100
  const rightPercent = 100 - (tempMaxPrice.value / maxLimit) * 100
  return {
    left: `${leftPercent}%`,
    right: `${rightPercent}%`
  }
})

// Format price helper
const { fmtPrice, fmtDate, getImageUrl } = useUtils()

// Check if any price filter is active
const isFilterActive = computed(() => {
  return productStore.listFilters.gia_tu !== '' || productStore.listFilters.gia_den !== ''
})

// Logic hiển thị trang thông minh
const visiblePages = computed(() => {
  if (!productStore.pagination) return []
  const current = productStore.pagination.current_page
  const last = productStore.pagination.last_page
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

async function fetchCategories() {
  try {
    const res = await categoriesApi.tree()
    categories.value = res.data.data || res.data
  } catch { }
}

const parentCategories = computed(() => categories.value)
const currentCatId = computed(() => route.query.category)

const activeParentId = computed(() => {
  if (!currentCatId.value) return null
  const cat = findCategoryRecursive(categories.value, currentCatId.value)
  if (!cat) return null
  return cat.parent_id || cat.id
})

const activeSubCategories = computed(() => {
  if (!activeParentId.value) return []
  const parent = categories.value.find(c => c.id == activeParentId.value)
  return parent?.children || parent?.active_children || []
})

function findCategoryRecursive(list, id) {
  for (const c of list) {
    if (c.id == id) return c
    const children = c.children || c.active_children
    if (children?.length) {
      const found = findCategoryRecursive(children, id)
      if (found) return found
    }
  }
  return null
}

function isParentActive(parent) {
  if (currentCatId.value == parent.id) return true
  const children = parent.children || parent.active_children
  return children?.some(c => c.id == currentCatId.value)
}

function setCategory(id) {
  router.push({ path: '/products', query: { ...route.query, category: id, page: 1 } })
}

function resetCategory() {
  router.push({ path: '/products', query: { ...route.query, category: undefined, page: 1 } })
}

function initReveal() {
  try {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => entry.target.classList.add('is-visible'), index * 60)
          observer.unobserve(entry.target)
        }
      })
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })
    document.querySelectorAll('.reveal-item').forEach(el => observer.observe(el))
  } catch {
    document.querySelectorAll('.reveal-item').forEach(el => el.classList.add('is-visible'))
  }
}

let isMounted = false

onMounted(() => {
  fetchCategories()
  
  // Sync URL query to Store filters on mount
  if (Object.keys(route.query).length > 0) {
    productStore.setFilters({
      category_id: route.query.category || null,
      search: route.query.search || '',
      gia_tu: route.query.gia_tu || '',
      gia_den: route.query.gia_den || '',
      page: Number(route.query.page) || 1,
      sort_by: route.query.sort_by || 'created_at',
      sort_dir: route.query.sort_dir || 'desc'
    })
    
    // Sync local sliders
    tempMinPrice.value = route.query.gia_tu ? Number(route.query.gia_tu) : minLimit
    tempMaxPrice.value = route.query.gia_den ? Number(route.query.gia_den) : maxLimit
  } else {
    // If no query parameters, but Store has filters, push them to the URL
    const sf = productStore.listFilters
    if (sf.category_id || sf.search || sf.gia_tu || sf.gia_den || sf.page > 1) {
      router.replace({
        path: '/products',
        query: {
          category: sf.category_id || undefined,
          search: sf.search || undefined,
          gia_tu: sf.gia_tu || undefined,
          gia_den: sf.gia_den || undefined,
          page: sf.page || undefined,
          sort_by: sf.sort_by || undefined,
          sort_dir: sf.sort_dir || undefined
        }
      })
      // Sync local sliders
      tempMinPrice.value = sf.gia_tu ? Number(sf.gia_tu) : minLimit
      tempMaxPrice.value = sf.gia_den ? Number(sf.gia_den) : maxLimit
    } else {
      tempMinPrice.value = minLimit
      tempMaxPrice.value = maxLimit
    }
  }

  sortBy.value = route.query.sort_by === 'price' 
    ? (route.query.sort_dir === 'asc' ? 'price_asc' : 'price_desc') 
    : ''

  isMounted = true
  doFetch(productStore.listFilters.page)
})

// Watch route query to update Store (handles browser Back button)
watch(() => route.query, (newQuery) => {
  if (!isMounted) return
  
  productStore.setFilters({
    category_id: newQuery.category || null,
    search: newQuery.search || '',
    gia_tu: newQuery.gia_tu || '',
    gia_den: newQuery.gia_den || '',
    page: Number(newQuery.page) || 1,
    sort_by: newQuery.sort_by || 'created_at',
    sort_dir: newQuery.sort_dir || 'desc'
  })
  
  // Sync local sliders
  tempMinPrice.value = newQuery.gia_tu ? Number(newQuery.gia_tu) : minLimit
  tempMaxPrice.value = newQuery.gia_den ? Number(newQuery.gia_den) : maxLimit
  
  sortBy.value = newQuery.sort_by === 'price' 
    ? (newQuery.sort_dir === 'asc' ? 'price_asc' : 'price_desc') 
    : ''
}, { deep: true })

// Watch Store filters and trigger API call
watch(() => productStore.listFilters, () => {
  if (!isMounted) return
  doFetch(productStore.listFilters.page)
}, { deep: true })

let debounceTimer = null
function handleSliderChange() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    productStore.setFilters({
      gia_tu: tempMinPrice.value === minLimit ? '' : String(tempMinPrice.value),
      gia_den: tempMaxPrice.value === maxLimit ? '' : String(tempMaxPrice.value),
      page: 1
    })
    
    router.push({
      path: '/products',
      query: {
        ...route.query,
        gia_tu: tempMinPrice.value === minLimit ? undefined : tempMinPrice.value,
        gia_den: tempMaxPrice.value === maxLimit ? undefined : tempMaxPrice.value,
        page: 1
      }
    })
  }, 300)
}

function onMinSliderInput() {
  if (tempMinPrice.value > tempMaxPrice.value - step) {
    tempMinPrice.value = tempMaxPrice.value - step
  }
  handleSliderChange()
}

function onMaxSliderInput() {
  if (tempMaxPrice.value < tempMinPrice.value + step) {
    tempMaxPrice.value = tempMinPrice.value + step
  }
  handleSliderChange()
}

async function doFetch(page = 1) {
  const category_id = route.query.category || undefined
  const search = route.query.search || undefined

  await productStore.fetchProducts({
    page,
    category_id,
    search,
    sort_by: sortBy.value ? 'price' : 'created_at',
    sort_dir: (sortBy.value === 'price_asc') ? 'asc' : 'desc',
    per_page: 15
  })
  nextTick(initReveal)
}

function resetFilter() {
  tempMinPrice.value = minLimit
  tempMaxPrice.value = maxLimit
  sortBy.value = ''
  productStore.resetFilters()
  router.push({ path: '/products', query: {} })
}

function handleSortChange() {
  const sort_by = sortBy.value ? 'price' : 'created_at'
  const sort_dir = (sortBy.value === 'price_asc') ? 'asc' : 'desc'
  productStore.setFilters({ sort_by, sort_dir, page: 1 })
  
  router.push({
    path: '/products',
    query: {
      ...route.query,
      sort_by: sortBy.value ? 'price' : undefined,
      sort_dir: sortBy.value ? ((sortBy.value === 'price_asc') ? 'asc' : 'desc') : undefined,
      page: 1
    }
  })
}

function goPage(page) {
  router.push({ path: '/products', query: { ...route.query, page } })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<style scoped>
/* Container & Layout */
.container-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
  min-height: 80vh;
}

/* Hero Section - Premium Modern */
.minimal-hero {
  padding: 10px 20px 5px;
  text-align: center;
  max-width: 1200px;
  margin: 0 auto;
}

.minimal-title {
  font-size: 24px;
  font-weight: 900;
  color: #1e293b;
  letter-spacing: -0.05em;
  line-height: 1.1;
  margin-bottom: 15px;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  display: block;
  margin-bottom: 2px;
}

.minimal-subtitle {
  font-size: 13px;
  color: #94a3b8;
  font-weight: 600;
  display: block;
}

/* Filter Bar - Premium Glass */
.compact-filter-bar {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(226, 232, 240, 0.6);
  border-radius: 20px;
  padding: 6px 16px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
  margin: 0 auto 10px;
  max-width: 1200px;
  z-index: 80;
}

/* Product Grid - Desktop Default (4 Columns) */
.home-grid {
  display: grid;
  gap: 30px;
  grid-template-columns: repeat(4, 1fr);
  padding: 10px 0 60px;
}

@media (max-width: 1400px) {
  .home-grid {
    gap: 20px;
  }
}

@media (max-width: 1200px) {
  .home-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
  }
}

/* Mobile Priority Overrides */
@media (max-width: 768px) {
  .container-main {
    padding: 0 10px;
  }

  .minimal-hero {
    padding: 20px 20px 10px;
  }

  .minimal-title {
    font-size: 28px;
    letter-spacing: -1px;
  }

  .minimal-subtitle {
    display: none;
  }

  .compact-filter-bar {
    position: relative;
    top: 0;
    margin: 5px 0 15px;
    padding: 6px 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 80;
  }

  .filter-main-row {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    scrollbar-width: none;
    align-items: center;
    white-space: nowrap;
  }

  .filter-main-row::-webkit-scrollbar {
    display: none;
  }

  .category-pills {
    flex: none;
    display: flex;
    gap: 8px;
  }

  .utility-row {
    flex: none;
    display: flex;
    gap: 8px;
    border-left: 1px solid #f1f5f9;
    padding-left: 10px;
  }

  .pill-modern {
    padding: 6px 14px;
    font-size: 12px;
    border-radius: 50px;
  }

  .select-minimal {
    padding: 5px 22px 5px 10px;
    font-size: 12px;
    border-radius: 8px;
    background-size: 12px;
  }

  .btn-reset-minimal {
    width: 32px;
    height: 32px;
    border-radius: 8px;
  }

  .price-filter-row {
    display: flex;
    overflow-x: auto;
    scrollbar-width: none;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(0, 0, 0, 0.03);
    white-space: nowrap;
  }

  .price-filter-row::-webkit-scrollbar {
    display: none;
  }

  .price-pills {
    display: flex;
    gap: 8px;
  }

  .pill-tag-minimal {
    padding: 5px 14px;
    font-size: 11px;
    border-radius: 50px;
  }

  /* FORCE 2 COLUMNS ON MOBILE */
  .home-grid {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 12px;
    padding: 10px 0 40px;
  }
}

.pill-modern-sm {
  padding: 6px 14px;
  border-radius: 50px;
  background: #f1f5f9;
  border: 1.5px solid transparent;
  color: #475569;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.pill-modern-sm:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.pill-modern-sm.active {
  background: #3b82f6;
  color: #fff;
  border-color: #3b82f6;
}

.sub-category-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  padding-top: 15px;
  margin-top: 15px;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.category-pills-sm {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

/* Common Components */
.category-pills {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  scrollbar-width: none;
}

.category-pills::-webkit-scrollbar {
  display: none;
}

.pill-modern {
  padding: 8px 20px;
  border-radius: 50px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  color: #64748b;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  white-space: nowrap;
}

.pill-modern:hover {
  color: #1e293b;
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.pill-modern.active {
  background: #1e293b;
  color: #fff;
  border-color: #1e293b;
}

.filter-divider-v {
  width: 1.5px;
  height: 24px;
  background: #e2e8f0;
  margin: 0 5px;
}

.utility-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.select-minimal {
  appearance: none;
  padding: 8px 36px 8px 16px;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 12px center;
  background-size: 15px;
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
  cursor: pointer;
  outline: none;
  transition: 0.3s;
}

.btn-reset-minimal {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.3s;
}

.btn-reset-minimal:hover {
  color: #ef4444;
  border-color: #fecaca;
  background: #fff1f2;
}

.price-pills {
  display: flex;
  gap: 10px;
}

.pill-tag-minimal {
  padding: 7px 18px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  background: #fff;
  border: 1.5px solid #e2e8f0;
  cursor: pointer;
  transition: 0.2s;
  white-space: nowrap;
}

.pill-tag-minimal.active {
  background: #3b82f6;
  color: #fff;
  border-color: #3b82f6;
}

/* Animations */
.reveal-item {
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.7s cubic-bezier(0.2, 1, 0.2, 1);
}

.reveal-item.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.slide-down-fade-enter-active,
.slide-down-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-down-fade-enter-from,
.slide-down-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.animate-fade-in {
  animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 60px;
  margin-top: 20px;
}

.pagination-apple-wrapper {
  background: #fff;
  border-radius: 50px;
  padding: 6px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.pagination-apple {
  display: flex;
  align-items: center;
  gap: 4px;
  list-style: none;
  padding: 0;
  margin: 0;
}

.page-link {
  min-width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 14px;
  color: #64748b;
  cursor: pointer;
  transition: 0.3s;
  background: transparent;
  border: none;
}

.page-link-text {
  min-width: 30px;
  text-align: center;
  color: #94a3b8;
  font-weight: bold;
}

.page-link:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.page-item.active .page-link {
  background: #1e293b;
  color: #fff;
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.skeleton-card {
  height: 420px;
  background: #f8fafc;
  border-radius: 24px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {

  0%,
  100% {
    opacity: 0.6;
  }

  50% {
    opacity: 1;
  }
}

/* Empty State - Premium Design */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: #fff;
  border-radius: 32px;
  border: 1px solid #f1f5f9;
  max-width: 600px;
  margin: 40px auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
}

.empty-icon-wrap {
  width: 100px;
  height: 100px;
  background: #f8fafc;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  font-size: 40px;
}

.empty-state h3 {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 12px;
}

.empty-state p {
  color: #64748b;
  margin-bottom: 24px;
}

/* Dual Range Slider Styles */
.slider-wrapper {
  background: #ffffff;
  padding: 20px;
  border-radius: 20px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
  margin-top: 15px;
  margin-bottom: 5px;
}

.slider-label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.slider-label-title {
  font-size: 11px;
  font-weight: 800;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.slider-label-value {
  font-size: 13.5px;
  font-weight: 800;
  color: #0071e3;
  background: #f5f5f7;
  padding: 5px 12px;
  border-radius: 8px;
  letter-spacing: -0.01em;
}

.custom-slider-container {
  position: relative;
  width: 100%;
  height: 20px;
  margin: 15px 0;
  display: flex;
  align-items: center;
}

.custom-slider-track {
  position: absolute;
  width: 100%;
  height: 4px;
  background: #e8e8ed;
  border-radius: 2px;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
}

.custom-slider-range {
  position: absolute;
  height: 4px;
  background: #0071e3;
  border-radius: 2px;
  top: 50%;
  transform: translateY(-50%);
}

.range-thumb {
  position: absolute;
  width: 100%;
  height: 20px;
  background: none;
  pointer-events: none;
  -webkit-appearance: none;
  appearance: none;
  top: 0;
  left: 0;
  margin: 0;
  z-index: 2;
}

.range-thumb::-webkit-slider-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #0071e3;
  cursor: pointer;
  pointer-events: auto;
  -webkit-appearance: none;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s;
}

.range-thumb::-webkit-slider-thumb:hover {
  transform: scale(1.15);
  background-color: #f5f5f7;
}

.range-thumb::-webkit-slider-thumb:active {
  transform: scale(0.9);
  background-color: #0071e3;
}

.range-thumb::-moz-range-thumb {
  height: 18px;
  width: 18px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #0071e3;
  cursor: pointer;
  pointer-events: auto;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.slider-limits {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #86868b;
  font-weight: 700;
  margin-top: 10px;
}

/* Empty State Illustration */
.empty-illustration-wrap {
  margin-bottom: 20px;
  display: flex;
  justify-content: center;
}

.empty-illustration {
  width: 180px;
  height: auto;
  object-fit: contain;
  opacity: 0.85;
}

/* Filter Result Message Badge */
.filter-result-info {
  display: flex;
  margin-top: 10px;
}

.filter-message-badge {
  background: #f5f5f7;
  color: #1d1d1f;
  padding: 8px 16px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 700;
  border: 1px solid rgba(0, 0, 0, 0.04);
}
</style>