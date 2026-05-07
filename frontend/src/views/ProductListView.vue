<template>
  <main class="container-main mt-4 mb-5">
    <!-- Ultra-Minimalist Hero Section -->
    <header class="minimal-hero mb-8 reveal-item">
      <h1 class="minimal-title">{{ i18n.t('home.hero_title') }}</h1>
      <p class="minimal-subtitle">{{ i18n.t('home.hero_subtitle') }}</p>
    </header>

    <!-- Compact Integrated Filter Bar -->
    <div class="compact-filter-bar mb-8 reveal-item">
      <div class="filter-main-row">
        <div class="category-pills">
          <button @click="resetCategory" class="pill-modern" :class="{ active: !currentCatId }">
            {{ i18n.locale === 'vi' ? 'Tất cả' : 'All' }}
          </button>
          <button v-for="cat in parentCategories" :key="cat.id" @click="setCategory(cat.id)" class="pill-modern"
            :class="{ active: isParentActive(cat) }">
            {{ i18n.transName(cat.name) }}
          </button>
        </div>

        <div class="filter-divider-v"></div>

        <div class="utility-row">
          <select v-model="sortBy" @change="doFetch" class="select-minimal">
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

      <div class="price-filter-row mt-4 pt-3 border-t border-slate-100">
        <div class="price-pills">
          <button v-for="range in priceRanges" :key="range.val" @click="setPriceRange(range)" class="pill-tag-minimal"
            :class="{ active: currentRangeVal === range.val }">
            {{ range.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="productStore.loading" class="home-grid">
      <div v-for="i in 8" :key="i" class="skeleton-card"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="productStore.list.length === 0" class="empty-state reveal-item is-visible">
      <div class="empty-icon">🔍</div>
      <h4>{{ i18n.locale === 'vi' ? 'Không tìm thấy sản phẩm nào' : 'No products found' }}</h4>
      <button @click="resetFilter" class="btn-reset-filter">
        {{ i18n.locale === 'vi' ? 'Đặt lại bộ lọc' : 'Reset Filters' }}
      </button>
    </div>

    <div v-if="productStore.loading" class="home-grid">
      <div v-for="i in 8" :key="i" class="skeleton-card"></div>
    </div>

    <div v-else-if="productStore.list.length === 0" class="empty-state reveal-item is-visible">
      <div class="empty-icon">🔍</div>
      <h4>{{ i18n.locale === 'vi' ? 'Không tìm thấy sản phẩm nào' : 'No products found' }}</h4>
      <button @click="resetFilter" class="btn-reset-filter">
        {{ i18n.locale === 'vi' ? 'Đặt lại bộ lọc' : 'Reset Filters' }}
      </button>
    </div>

    <template v-else>
      <section v-if="featuredProducts.length && !currentCatId && !route.query.search"
        class="featured-section mb-10 reveal-item">
        <div class="section-header mb-6">
          <h2 class="section-title">✨ {{ i18n.t('home.featured_products') || 'Sản phẩm nổi bật' }}</h2>
          <div class="section-line"></div>
        </div>
        <div class="featured-grid">
          <ProductCard v-for="product in featuredProducts" :key="'feat-' + product.id" :product="product"
            class="featured-card" />
        </div>
      </section>

      <div class="home-grid" ref="gridRef">
        <ProductCard v-for="product in productStore.list" :key="product.id" :product="product" />
      </div>
    </template>



    <!-- Pagination -->
    <div v-if="productStore.pagination && productStore.pagination.last_page > 1" class="pagination-wrapper reveal-item">
      <div class="pagination-apple-wrapper">
        <ul class="pagination-apple">
          <li v-if="productStore.pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(1)">«</button>
          </li>
          <li v-if="productStore.pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(productStore.pagination.current_page - 1)">‹</button>
          </li>
          <li v-for="page in productStore.pagination.last_page" :key="page" class="page-item"
            :class="{ active: page === productStore.pagination.current_page }">
            <button class="page-link" @click="goPage(page)">{{ page }}</button>
          </li>
          <li v-if="productStore.pagination.current_page < productStore.pagination.last_page" class="page-item">
            <button class="page-link" @click="goPage(productStore.pagination.current_page + 1)">›</button>
          </li>
          <li v-if="productStore.pagination.current_page < productStore.pagination.last_page" class="page-item">
            <button class="page-link" @click="goPage(productStore.pagination.last_page)">»</button>
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
//import { useI18nStore } from '../stores/i18n'
//import { useUtils } from '../composables/useUtils'
import ProductCard from '../components/ProductCard.vue'
import api from '../services/api'

const productStore = useProductStore()
//const i18n = useI18nStore()
const route = useRoute()
const router = useRouter()
const { fmtPrice } = useUtils()
const currentRangeVal = ref('')
const sortBy = ref('')
const giaTu = ref('')
const giaDen = ref('')
const gridRef = ref(null)
const categories = ref([])
const featuredProducts = ref([])

async function fetchFeatured() {
  try {
    const res = await api.get('/products', { params: { is_featured: 1, per_page: 4 } })
    featuredProducts.value = res.data.data
  } catch { }
}

async function fetchCategories() {
  try {
    const res = await api.get('/categories')
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
  return parent?.children || []
})

function findCategoryRecursive(list, id) {
  for (const c of list) {
    if (c.id == id) return c
    if (c.children?.length) {
      const found = findCategoryRecursive(c.children, id)
      if (found) return found
    }
  }
  return null
}

function isParentActive(parent) {
  if (currentCatId.value == parent.id) return true
  return parent.children?.some(c => c.id == currentCatId.value)
}

function setCategory(id) {
  router.push({ path: '/products', query: { ...route.query, category: id } })
}

function resetCategory() {
  router.push({ path: '/products', query: { ...route.query, category: undefined } })
}

const priceRanges = computed(() => [
  { val: '', label: i18n.t('product.price_all'), from: '', to: '' },
  { val: 'u5', label: i18n.t('product.price_under_5'), from: '', to: '5000000' },
  { val: '510', label: i18n.t('product.price_5_10'), from: '5000000', to: '10000000' },
  { val: '1020', label: i18n.t('product.price_10_20'), from: '10000000', to: '20000000' },
  { val: 'o20', label: i18n.t('product.price_over_20'), from: '20000000', to: '' },
])

function initReveal() {
  try {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => entry.target.classList.add('is-visible'), index * 80)
          observer.unobserve(entry.target)
        }
      })
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })
    document.querySelectorAll('.reveal-item').forEach(el => observer.observe(el))
  } catch {
    document.querySelectorAll('.reveal-item').forEach(el => el.classList.add('is-visible'))
  }
}

onMounted(() => {
  doFetch()
  fetchCategories()
  fetchFeatured()
  nextTick(initReveal)
})

watch(() => [route.query.category, route.query.search], () => { doFetch() })

function setPriceRange(range) {
  currentRangeVal.value = range.val;
  giaTu.value = range.from;
  giaDen.value = range.to;
  doFetch()
}

async function doFetch(page = 1) {
  const category_id = route.query.category || undefined
  const search = route.query.search || undefined

  await productStore.fetchProducts({
    page,
    category_id,
    search,
    gia_tu: giaTu.value || undefined,
    gia_den: giaDen.value || undefined,
    sort_by: sortBy.value ? 'price' : 'created_at',
    sort_dir: (sortBy.value === 'price_asc') ? 'asc' : 'desc',
    per_page: 10
  })
  nextTick(() => {
    document.querySelectorAll('.reveal-item').forEach(el => el.classList.remove('is-visible'))
    setTimeout(initReveal, 100)
  })
}

function resetFilter() {
  currentRangeVal.value = '';
  giaTu.value = '';
  giaDen.value = '';
  sortBy.value = '';
  router.push({ path: '/products', query: {} })
  doFetch()
}

function goPage(page) {
  doFetch(page);
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<style scoped>
/* ===== ULTRA-MINIMALIST HERO ===== */
.minimal-hero {
  text-align: center;
  padding: 15px 0 10px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 15px;
}

.minimal-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: -0.04em;
  margin-bottom: 4px;
}

.minimal-subtitle {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 400;
  max-width: 550px;
  margin: 0 auto;
  line-height: 1.4;
}

/* ===== COMPACT INTEGRATED FILTER BAR ===== */
.compact-filter-bar {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.01);
  max-width: 1100px;
  margin: 0 auto 20px;
}

.filter-main-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.category-pills {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
}

.category-pills::-webkit-scrollbar {
  display: none;
}

.pill-modern {
  padding: 9px 18px;
  border-radius: 12px;
  font-size: 13.5px;
  font-weight: 600;
  color: #475569;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  cursor: pointer;
  transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
}

.pill-modern:hover {
  background: #f1f5f9;
  border-color: #e2e8f0;
  color: #1e293b;
}

.pill-modern.active {
  background: #1e293b;
  color: #fff;
  border-color: #1e293b;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.filter-divider-v {
  width: 1px;
  height: 24px;
  background: #e2e8f0;
  margin: 0 10px;
}

.utility-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.select-minimal {
  appearance: none;
  background: #fff;
  border: 1px solid #e2e8f0;
  padding: 8px 32px 8px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  outline: none;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 14px;
}

.select-minimal:hover {
  border-color: #cbd5e1;
}

.btn-reset-minimal {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  color: #94a3b8;
  cursor: pointer;
  transition: 0.2s;
}

.btn-reset-minimal:hover {
  color: #ef4444;
  border-color: #fecaca;
  background: #fff1f2;
}

/* Sub & Price Rows */
.sub-category-row,
.price-filter-row {
  display: flex;
  overflow-x: auto;
  scrollbar-width: none;
}

.sub-category-row::-webkit-scrollbar,
.price-filter-row::-webkit-scrollbar {
  display: none;
}

.category-pills-sm,
.price-pills {
  display: flex;
  gap: 8px;
}

.pill-modern-sm {
  padding: 7px 15px;
  border-radius: 10px;
  font-size: 12.5px;
  font-weight: 600;
  color: #64748b;
  background: #fff;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: 0.2s;
  white-space: nowrap;
}

.pill-modern-sm.active {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #2563eb;
}

.pill-tag-minimal {
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  cursor: pointer;
  transition: 0.2s;
  white-space: nowrap;
}

.pill-tag-minimal.active {
  background: #334155;
  color: #fff;
}

/* Grid & Items - 4 COLUMN STANDARD */
.container-main {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 30px;
}

.home-grid {
  display: grid;
  gap: 30px;
  grid-template-columns: repeat(4, 1fr);
  padding: 10px 0 60px;
}

.reveal-item {
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.6s cubic-bezier(0.2, 1, 0.2, 1);
}

.reveal-item.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 50px;
  margin-top: 20px;
}

.pagination-apple-wrapper {
  background: #fff;
  border-radius: 18px;
  padding: 6px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

.pagination-apple {
  display: flex;
  gap: 5px;
  list-style: none;
  padding: 0;
  margin: 0;
}

.page-link {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  color: #64748b;
  cursor: pointer;
  transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: transparent;
  border: none;
}

.page-link:hover {
  background: #f1f5f9;
  color: #1e293b;
  transform: translateY(-1px);
}

.page-item.active .page-link {
  background: #1e293b;
  color: #fff;
  box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
}

/* Skeleton */
.skeleton-card {
  height: 400px;
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

/* Empty State */
.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 100px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 20px;
  opacity: 0.5;
}

.empty-state h4 {
  font-size: 1.4rem;
  color: #1e293b;
  margin-bottom: 12px;
  font-weight: 700;
}

.btn-reset-filter {
  margin-top: 20px;
  padding: 12px 30px;
  border-radius: 12px;
  background: #1e293b;
  color: #fff;
  border: none;
  font-weight: 700;
  cursor: pointer;
  transition: 0.3s;
}

.btn-reset-filter:hover {
  background: #334155;
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

/* ===== FEATURED SECTION ===== */
.featured-section {
  padding: 0 0 40px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 20px;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 800;
  color: #1e293b;
  white-space: nowrap;
  margin: 0;
}

.section-line {
  height: 1px;
  background: #e2e8f0;
  flex: 1;
}

.featured-grid {
  display: grid;
  gap: 30px;
  grid-template-columns: repeat(4, 1fr);
}

.featured-card {
  transform: scale(1.02);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06) !important;
}

/* Responsiveness */
@media (max-width: 1024px) {
  .home-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
  }
}

@media (max-width: 768px) {
  .container-main {
    padding: 0 20px;
  }

  .minimal-hero {
    padding: 40px 0 20px;
  }

  .minimal-title {
    font-size: 1.6rem;
  }

  .filter-main-row {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .filter-divider-v {
    display: none;
  }

  .utility-row {
    justify-content: space-between;
  }

  .home-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .compact-filter-bar {
    padding: 15px;
  }
}
</style>