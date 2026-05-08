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
        </div>

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

      <div class="price-filter-row mt-4 pt-3 border-t border-slate-100">
        <div class="price-pills">
          <button v-for="range in priceRanges" :key="range.val"
            @click="setPriceRange(range)"
            class="pill-tag-minimal" :class="{ active: currentRangeVal === range.val }">
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

    <template v-else>
      <section v-if="featuredProducts.length && !route.query.search" class="featured-section mb-10 reveal-item">
        <div class="section-header mb-6">
          <h2 class="section-title">✨ {{ i18n.t('home.featured_products') || 'Sản phẩm nổi bật' }}</h2>
          <div class="section-line"></div>
        </div>
        <div class="featured-grid">
          <ProductCard v-for="product in featuredProducts" :key="'feat-'+product.id" :product="product" class="featured-card" />
        </div>
      </section>

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
          <li v-if="productStore.pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(productStore.pagination.current_page - 1)">‹</button>
          </li>
          <li v-for="page in productStore.pagination.last_page" :key="page"
            class="page-item" :class="{ active: page === productStore.pagination.current_page }">
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
import { useI18nStore } from '../stores/i18n'
import ProductCard from '../components/ProductCard.vue'
import api from '../services/api'

const productStore = useProductStore()
const i18n = useI18nStore()
const route = useRoute()
const router = useRouter()

const currentRangeVal = ref('')
const sortBy = ref('')
const giaTu = ref('')
const giaDen = ref('')
const featuredProducts = ref([])

async function fetchFeatured() {
  try {
    const res = await api.get('/products', { params: { is_featured: 1, per_page: 4 } })
    featuredProducts.value = res.data.data
  } catch {}
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
  fetchFeatured()
  nextTick(initReveal) 
})

watch(() => [route.query.search], () => { doFetch() })

function setPriceRange(range) { 
  currentRangeVal.value = range.val; 
  giaTu.value = range.from; 
  giaDen.value = range.to; 
  doFetch() 
}

async function doFetch(page = 1) {
  const search = route.query.search || undefined

  await productStore.fetchProducts({ 
    page, 
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
.minimal-hero { text-align: center; padding: 15px 0 10px; border-bottom: 1px solid #f1f5f9; margin-bottom: 15px; }
.minimal-title { font-size: 1.6rem; font-weight: 800; color: #1e293b; letter-spacing: -0.04em; margin-bottom: 4px; }
.minimal-subtitle { font-size: 0.9rem; color: #64748b; font-weight: 400; max-width: 550px; margin: 0 auto; line-height: 1.4; }

.compact-filter-bar { 
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; 
  padding: 12px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.01); 
  max-width: 1100px; margin: 0 auto 20px; 
}
.filter-main-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.utility-row { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.select-minimal {
  appearance: none; background: #fff; border: 1px solid #e2e8f0;
  padding: 8px 32px 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 600;
  color: #475569; outline: none; cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 10px center; background-size: 14px;
}
.btn-reset-minimal {
  width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
  color: #94a3b8; cursor: pointer; transition: 0.2s;
}

.price-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.pill-tag-minimal {
  padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
  color: #94a3b8; background: #f8fafc; border: 1px solid #f1f5f9;
  cursor: pointer; transition: 0.2s; white-space: nowrap;
}
.pill-tag-minimal.active { background: #334155; color: #fff; }

.container-main { max-width: 1280px; margin: 0 auto; padding: 0 30px; }
.home-grid { 
  display: grid; gap: 30px; 
  grid-template-columns: repeat(4, 1fr); 
  padding: 10px 0 60px; 
}

.reveal-item { opacity: 0; transform: translateY(20px); transition: all 0.6s cubic-bezier(0.2, 1, 0.2, 1); }
.reveal-item.is-visible { opacity: 1; transform: translateY(0); }

.pagination-wrapper { display: flex; justify-content: center; margin-bottom: 50px; margin-top: 20px; }
.pagination-apple-wrapper { 
  background: #fff; border-radius: 18px; padding: 6px; 
  border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
}
.pagination-apple { display: flex; gap: 5px; list-style: none; padding: 0; margin: 0; }
.page-link { 
  width: 42px; height: 42px; border-radius: 12px; 
  display: flex; align-items: center; justify-content: center; 
  font-weight: 700; font-size: 14px; color: #64748b; 
  cursor: pointer; transition: 0.3s; background: transparent; border: none; 
}
.page-item.active .page-link { background: #1e293b; color: #fff; }

.skeleton-card { height: 400px; background: #f8fafc; border-radius: 24px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; } }

.empty-state { grid-column: 1 / -1; text-align: center; padding: 100px 20px; }

.featured-section { padding: 0 0 40px; }
.section-title { font-size: 1.2rem; font-weight: 800; color: #1e293b; }
.featured-grid { display: grid; gap: 30px; grid-template-columns: repeat(4, 1fr); }

@media (max-width: 1024px) {
  .home-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .home-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>