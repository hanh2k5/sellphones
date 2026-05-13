<template>
  <main class="container-main mt-4 mb-5">
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
          <button v-for="cat in parentCategories" :key="cat.id"
            @click="setCategory(cat.id)"
            class="pill-modern" :class="{ active: isParentActive(cat) }">
            {{ i18n.transName(cat.name) }}
          </button>
        </div>

        <div class="filter-divider-v d-none d-lg-block"></div>

        <div class="utility-row">
          <select v-model="sortBy" @change="doFetch" class="select-minimal">
            <option value="">{{ i18n.t('common.newest') }}</option>
            <option value="price_asc">↑ {{ i18n.t('common.price_asc') }}</option>
            <option value="price_desc">↓ {{ i18n.t('common.price_desc') }}</option>
          </select>
          <button @click="resetFilter" class="btn-reset-minimal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
          </button>
        </div>
      </div>

      <!-- Price Range Row (Horizontal Scroll on Mobile) -->
      <div class="price-filter-row mt-3">
        <div class="price-pills">
          <button v-for="range in priceRanges" :key="range.val"
            @click="setPriceRange(range)"
            class="pill-tag-minimal" :class="{ active: currentRangeVal === range.val }">
            {{ range.label }}
          </button>
        </div>
      </div>

      <!-- Sub-categories (Conditional) -->
      <Transition name="slide-down-fade">
        <div v-if="activeSubCategories.length" class="sub-category-row pt-3 mt-3 border-t border-slate-100">
          <div class="category-pills-sm">
            <button v-for="sub in activeSubCategories" :key="sub.id"
              @click="setCategory(sub.id)"
              class="pill-modern-sm" :class="{ active: currentCatId == sub.id }">
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
      <div class="empty-icon-wrap">🔍</div>
      <h3>{{ i18n.t('product.not_found') }}</h3>
      <p>{{ i18n.t('product.search_placeholder') }}</p>
      <button class="pill-modern active" @click="resetFilter">
        {{ i18n.t('product.clear_filter') }}
      </button>
    </div>

    <template v-else>
      <div class="home-grid" ref="gridRef">
        <ProductCard v-for="product in productStore.list" :key="product.id" :product="product" />
      </div>
    </template>

    <!-- Pagination -->
    <div v-if="productStore.pagination && productStore.pagination.last_page > 1"
      class="pagination-wrapper reveal-item">
      <div class="pagination-apple-wrapper">
        <ul class="pagination-apple">
          <li v-if="productStore.pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(1)">«</button>
          </li>
          <li v-for="page in productStore.pagination.last_page" :key="page"
            class="page-item" :class="{ active: page === productStore.pagination.current_page }">
            <button class="page-link" @click="goPage(page)">{{ page }}</button>
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
import { useI18nStore } from '../stores/i18n'
import ProductCard from '../components/ProductCard.vue'
import { categoriesApi } from '../api'

const productStore = useProductStore()
const i18n = useI18nStore()
const route = useRoute()
const router = useRouter()
const currentRangeVal = ref('')
const sortBy = ref('')
const giaTu = ref('')
const giaDen = ref('')
const categories = ref([])

async function fetchCategories() {
  try {
    const res = await categoriesApi.tree()
    categories.value = res.data.data || res.data
  } catch {}
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
  router.push({ path: '/products', query: { ...route.query, category: id, page: 1 } })
}

function resetCategory() {
  router.push({ path: '/products', query: { ...route.query, category: undefined, page: 1 } })
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
          // Staggered delay logic
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

onMounted(() => { 
  doFetch()
  fetchCategories()
})

watch(() => route.query, () => { doFetch(route.query.page || 1) }, { deep: true })

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
    per_page: 15
  })
  nextTick(initReveal)
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
  router.push({ path: '/products', query: { ...route.query, page } })
  window.scrollTo({ top: 0, behavior: 'smooth' }) 
}
</script>

<style scoped>
/* Container & Layout */
.container-main { max-width: 1400px; margin: 0 auto; padding: 0 24px; min-height: 80vh; }

/* Hero Section - Premium Modern */
.minimal-hero {
  padding: 60px 20px 40px; text-align: center;
  background: radial-gradient(circle at top, rgba(37, 99, 235, 0.03) 0%, transparent 70%);
}
.minimal-title {
  font-size: 56px; font-weight: 900; color: #1e293b;
  letter-spacing: -0.05em; line-height: 1.1; margin-bottom: 15px;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.minimal-subtitle {
  font-size: 18px; color: #64748b; font-weight: 500;
  max-width: 600px; margin: 0 auto; line-height: 1.6;
}

/* Filter Bar - Premium Glass */
.compact-filter-bar {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(226, 232, 240, 0.6);
  border-radius: 20px; padding: 12px 20px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.02);
  margin: 0 auto 30px;
  max-width: 1200px;
  z-index: 80;
}

/* Product Grid - Desktop Default (4 Columns) */
.home-grid {
  display: grid; gap: 30px;
  grid-template-columns: repeat(4, 1fr);
  padding: 20px 0 60px;
}

@media (max-width: 1400px) {
  .home-grid { gap: 20px; }
}

@media (max-width: 1200px) { 
  .home-grid { grid-template-columns: repeat(3, 1fr); gap: 20px; } 
}

/* Mobile Priority Overrides */
@media (max-width: 768px) {
  .container-main { padding: 0 10px; }
  .minimal-hero { padding: 40px 20px 20px; }
  .minimal-title { font-size: 28px; letter-spacing: -1px; }
  .minimal-subtitle { display: none; }

  .compact-filter-bar {
    position: relative; top: 0; 
    margin: 5px 0 15px; padding: 6px 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    display: flex; flex-direction: column; gap: 4px;
    z-index: 80;
  }
  
  .filter-main-row { 
    display: flex; gap: 10px; overflow-x: auto; 
    scrollbar-width: none; align-items: center;
    white-space: nowrap;
  }
  .filter-main-row::-webkit-scrollbar { display: none; }
  
  .category-pills { flex: none; display: flex; gap: 8px; }
  .utility-row { flex: none; display: flex; gap: 8px; border-left: 1px solid #f1f5f9; padding-left: 10px; }
  
  .pill-modern { padding: 6px 14px; font-size: 12px; border-radius: 50px; }
  .select-minimal { padding: 5px 22px 5px 10px; font-size: 12px; border-radius: 8px; background-size: 12px; }
  .btn-reset-minimal { width: 32px; height: 32px; border-radius: 8px; }

  .price-filter-row { 
    display: flex; overflow-x: auto; scrollbar-width: none;
    margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.03); 
    white-space: nowrap;
  }
  .price-filter-row::-webkit-scrollbar { display: none; }
  .price-pills { display: flex; gap: 8px; }
  
  .pill-tag-minimal { padding: 5px 14px; font-size: 11px; border-radius: 50px; }
  
  /* FORCE 2 COLUMNS ON MOBILE */
  .home-grid { 
    grid-template-columns: repeat(2, 1fr) !important; 
    gap: 12px; 
    padding: 10px 0 40px; 
  }
}

.pill-modern-sm {
  padding: 6px 14px; border-radius: 50px;
  background: #f1f5f9; border: 1.5px solid transparent;
  color: #475569; font-size: 12.5px; font-weight: 700;
  cursor: pointer; transition: all 0.2s;
  white-space: nowrap;
}
.pill-modern-sm:hover { background: #e2e8f0; color: #1e293b; }
.pill-modern-sm.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }

.sub-category-row { 
  display: flex; gap: 8px; flex-wrap: wrap;
  padding-top: 15px; margin-top: 15px; border-top: 1px solid rgba(0,0,0,0.05); 
}
.category-pills-sm { display: flex; gap: 8px; flex-wrap: wrap; }

/* Common Components */
.category-pills { display: flex; gap: 10px; overflow-x: auto; scrollbar-width: none; }
.category-pills::-webkit-scrollbar { display: none; }
.pill-modern {
  padding: 8px 20px; border-radius: 50px;
  background: #f8fafc; border: 1.5px solid #e2e8f0;
  color: #64748b; font-size: 13.5px; font-weight: 700;
  cursor: pointer; transition: all 0.3s;
  white-space: nowrap;
}
.pill-modern:hover { color: #1e293b; background: #f1f5f9; border-color: #cbd5e1; }
.pill-modern.active { background: #1e293b; color: #fff; border-color: #1e293b; }

.filter-divider-v { width: 1.5px; height: 24px; background: #e2e8f0; margin: 0 5px; }

.utility-row { display: flex; align-items: center; gap: 10px; }
.select-minimal {
  appearance: none; padding: 8px 36px 8px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0;
  background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 12px center;
  background-size: 15px; font-size: 13.5px; font-weight: 700; color: #1e293b;
  cursor: pointer; outline: none; transition: 0.3s;
}

.btn-reset-minimal {
  width: 40px; height: 40px; border-radius: 12px; border: 1.5px solid #e2e8f0;
  background: #fff; color: #64748b; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: 0.3s;
}
.btn-reset-minimal:hover { color: #ef4444; border-color: #fecaca; background: #fff1f2; }

.price-pills { display: flex; gap: 10px; }
.pill-tag-minimal {
  padding: 7px 18px; border-radius: 50px; font-size: 13px; font-weight: 700;
  color: #64748b; background: #fff; border: 1.5px solid #e2e8f0;
  cursor: pointer; transition: 0.2s; white-space: nowrap;
}
.pill-tag-minimal.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }

/* Animations */
.reveal-item { opacity: 0; transform: translateY(30px); transition: all 0.7s cubic-bezier(0.2, 1, 0.2, 1); }
.reveal-item.is-visible { opacity: 1; transform: translateY(0); }

.slide-down-fade-enter-active, .slide-down-fade-leave-active { transition: all 0.3s ease; }
.slide-down-fade-enter-from, .slide-down-fade-leave-to { opacity: 0; transform: translateY(-10px); }

.animate-fade-in { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Pagination */
.pagination-wrapper { display: flex; justify-content: center; margin-bottom: 60px; margin-top: 20px; }
.pagination-apple-wrapper { 
  background: #fff; border-radius: 50px; padding: 6px; 
  border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
}
.pagination-apple { display: flex; gap: 4px; list-style: none; padding: 0; margin: 0; }
.page-link { 
  min-width: 44px; height: 44px; border-radius: 50%; 
  display: flex; align-items: center; justify-content: center; 
  font-weight: 800; font-size: 14px; color: #64748b; 
  cursor: pointer; transition: 0.3s; background: transparent; border: none; 
  padding: 0 12px;
}
.page-link:hover { background: #f1f5f9; color: #1e293b; }
.page-item.active .page-link { background: #1e293b; color: #fff; box-shadow: 0 8px 15px rgba(0,0,0,0.1); }

.skeleton-card { height: 420px; background: #f8fafc; border-radius: 24px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; } }

/* Empty State - Premium Design */
.empty-state { 
  text-align: center; padding: 80px 20px; 
  background: #fff; border-radius: 32px; border: 1px solid #f1f5f9;
  max-width: 600px; margin: 40px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}
.empty-icon-wrap { 
  width: 100px; height: 100px; background: #f8fafc; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 24px; font-size: 40px;
}
.empty-state h3 { font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 12px; }
.empty-state p { color: #64748b; margin-bottom: 24px; }
</style>