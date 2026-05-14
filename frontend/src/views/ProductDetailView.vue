<template>
  <div class="product-detail-container">
    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="skeleton-img"></div>
      <div class="skeleton-text"></div>
      <div class="skeleton-text short"></div>
    </div>

    <!-- Not found -->
    <div v-else-if="!product" class="not-found-state">
      <div class="empty-icon">😢</div>
      <p>{{ i18n.t('product.not_exist') }}</p>
      <router-link to="/products" class="btn-back-home">{{ i18n.t('product.go_back') }}</router-link>
    </div>

    <!-- Product detail -->
    <div v-else class="detail-layout">
      <div class="top-actions">
        <button @click="$router.back()" class="btn-back-modern">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
          <span>{{ i18n.t('product.go_back') }}</span>
        </button>
      </div>

      <div class="main-content">
        <!-- LEFT: Images -->
        <div class="image-gallery">
          <div class="main-image-wrap">
            <img :src="getImageUrl(activeImage || product.hinh_anh)" :alt="product.name" class="main-img" />
          </div>
          <div v-if="product.images?.length" class="thumbnail-list">
            <button @click="activeImage = product.hinh_anh"
              class="thumb-btn" :class="{ active: !activeImage || activeImage === product.hinh_anh }">
              <img :src="getImageUrl(product.hinh_anh)" />
            </button>
            <button v-for="img in product.images" :key="img.id" @click="activeImage = img.image_path"
              class="thumb-btn" :class="{ active: activeImage === img.image_path }">
              <img :src="getImageUrl(img.image_path)" />
            </button>
          </div>
        </div>

        <!-- RIGHT: Info -->
        <div class="product-info">
          <h1 class="product-title">{{ product.name }}</h1>
          
          <div class="rating-row">
            <div class="stars">
              <span v-for="i in 5" :key="i" :class="{ active: i <= Math.round(product.avg_rating || 0) }">★</span>
            </div>
            <span class="review-count">{{ product.avg_rating ? Number(product.avg_rating).toFixed(1) : '' }} ({{ product.reviews?.length || 0 }} {{ i18n.t('product.reviews') }})</span>
          </div>

          <div class="price-box">
            <span class="current-price">{{ fmtPrice(product.price) }}</span>
            <span class="vat-note">{{ i18n.t('product.shipping_note') }}</span>
          </div>

          <!-- Variants -->
          <div class="variant-section">
            <p class="section-label">{{ i18n.t('product.variant_title') }}</p>
            <div class="variant-card active">
              <div class="variant-info">
                <span class="v-name">{{ i18n.t('product.variant_default') }}</span>
                <span class="v-stock" :class="{ 'out-of-stock': product.stock <= 0 }">
                  {{ product.stock > 0 ? i18n.t('product.stock_count', { count: product.stock }) : i18n.t('product.out_of_stock') }}
                </span>
              </div>
            </div>
          </div>

          <!-- Desktop Actions -->
          <div class="action-row desktop-only">
            <div class="qty-selector">
              <button @click="qty = Math.max(1, qty - 1)">−</button>
              <span>{{ qty }}</span>
              <button @click="qty = Math.min(product.stock, qty + 1)" :disabled="qty >= product.stock">+</button>
            </div>
            <button @click="addToCart" :disabled="product.stock === 0 || adding || buying" class="btn-secondary">
              <span v-if="adding" class="spinner"></span>
              <template v-else>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                {{ i18n.t('product.add_to_cart') }}
              </template>
            </button>
            <button @click="buyNow" :disabled="product.stock === 0 || adding || buying" class="btn-primary">
              <span v-if="buying" class="spinner"></span>
              {{ buying ? i18n.t('common.processing') : i18n.t('product.buy_now') }}
            </button>
          </div>

          <!-- Description Section (Premium Style) -->
          <div v-if="product.description" class="description-section">
            <div class="desc-title-row">
              <span class="title-marker"></span>
              <h3 class="section-label">{{ i18n.t('product.description') }}</h3>
            </div>
            <p class="desc-text">{{ product.description }}</p>
          </div>
        </div>
      </div>

      <!-- Sticky Mobile Bar -->
      <div class="sticky-mobile-bar">
        <div class="qty-selector-sm">
          <button @click="qty = Math.max(1, qty - 1)">−</button>
          <span>{{ qty }}</span>
          <button @click="qty = Math.min(product.stock, qty + 1)">+</button>
        </div>
        <div class="mobile-btns">
          <button @click="addToCart" :disabled="product.stock === 0 || adding || buying" class="btn-cart-sm">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
          </button>
          <button @click="buyNow" :disabled="product.stock === 0 || adding || buying" class="btn-buy-sm">
            {{ i18n.t('product.buy_now') }}
          </button>
        </div>
      </div>

      <!-- Reviews Section (Premium Style) -->
      <section id="reviews" class="reviews-section">
        <div class="desc-title-row mb-8">
          <span class="title-marker"></span>
          <h3 class="section-label">{{ i18n.t('product.reviews') }}</h3>
        </div>
        
        <div v-if="!authStore.isLoggedIn" class="backdrop-blur-md bg-white/40 border border-white/60 rounded-[2rem] p-8 mb-10 text-center shadow-sm">
          <div class="text-3xl mb-3">🔑</div>
          <p class="text-slate-600 font-bold mb-4">{{ i18n.t('product.must_login_review') || 'Vui lòng đăng nhập để đánh giá' }}</p>
          <router-link to="/login" class="inline-block bg-blue-600 text-white font-bold px-8 py-3 rounded-xl shadow-lg hover:bg-blue-700 transition-all uppercase text-xs tracking-widest">{{ i18n.t('nav.login') }}</router-link>
        </div>

        <!-- Form đánh giá: Hiện khi đang sửa HOẶC (đã mua & chưa đánh giá) -->
        <div v-else-if="authStore.isLoggedIn && (editingReviewId || (eligibleOrderId && !userReview))" 
             class="backdrop-blur-xl bg-white/60 rounded-[2rem] p-8 mb-10 border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)] animate-fade-in">
          <h3 class="font-bold text-slate-800 mb-5 text-lg">{{ editingReviewId ? i18n.t('common.update_review') : i18n.t('product.write_review') }}</h3>
          
          <!-- Tự động liên kết Order ID -->
          <div class="text-sm font-bold text-emerald-700 bg-emerald-50/80 backdrop-blur-sm border border-emerald-200 rounded-xl px-4 py-3 mb-5 shadow-sm flex items-center gap-2">
            <span>✅</span> {{ i18n.t('product.linked_order') }} #{{ eligibleOrderId || reviewForm.order_id_input }}
          </div>

          <!-- Chọn sao -->
          <div class="flex items-center gap-2 mb-5">
            <div class="flex gap-1">
              <button v-for="i in 5" :key="i" @click="reviewForm.rating = i" class="text-3xl transition-transform hover:scale-110 active:scale-125 drop-shadow-sm">
                <span :class="i <= reviewForm.rating ? 'text-yellow-400' : 'text-slate-300'">★</span>
              </button>
            </div>
            <span class="text-[15px] font-bold ml-3 px-3 py-1 bg-white/80 rounded-lg shadow-sm text-slate-600 border border-white">{{ reviewForm.rating > 0 ? ratingLabel(reviewForm.rating) : i18n.t('product.select_rating') }}</span>
          </div>
          <textarea v-model="reviewForm.comment" rows="3" :placeholder="i18n.t('product.your_comment')" class="w-full bg-white/80 border border-white shadow-inner rounded-2xl px-5 py-4 text-[15px] focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 resize-none mb-5 transition-all text-slate-800 placeholder-slate-400"></textarea>

          <div v-if="reviewError" class="text-sm font-bold text-rose-600 mb-4 bg-rose-50/80 p-3 rounded-xl border border-rose-200 flex items-center gap-2">⚠️ {{ reviewError }}</div>
          
          <div class="flex justify-end gap-3">
            <button v-if="editingReviewId" @click="cancelEdit" class="px-6 py-3.5 rounded-2xl font-bold text-sm uppercase tracking-wider text-slate-500 hover:text-slate-700 transition-all active:scale-95">
              {{ i18n.t('common.cancel') }}
            </button>
            <button @click="submitReview" :disabled="!reviewForm.rating || submittingReview" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-8 py-3.5 rounded-2xl font-bold text-sm uppercase tracking-wider disabled:opacity-50 disabled:grayscale transition-all shadow-lg shadow-blue-500/20 active:scale-95 flex items-center gap-2">
              <span v-if="submittingReview" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submittingReview ? i18n.t('common.saving') : (editingReviewId ? i18n.t('common.update_review') : i18n.t('common.submit_review')) }}
            </button>
          </div>
        </div>
        
        <!-- Thông báo cần mua hàng: Chỉ hiện khi chưa mua hàng VÀ chưa có đánh giá -->
        <div v-else-if="authStore.isLoggedIn && !eligibleOrderId && !userReview" 
             class="backdrop-blur-md border rounded-[2rem] p-8 mb-10 text-center shadow-sm"
             :class="hasPendingOrder ? 'bg-blue-50/60 border-blue-200' : 'bg-orange-50/60 border-orange-200'">
          <div class="text-4xl mb-3">{{ hasPendingOrder ? '🚚' : '🛍️' }}</div>
          <p :class="hasPendingOrder ? 'text-blue-800' : 'text-orange-800'" class="font-bold mb-2">
            {{ hasPendingOrder ? (i18n.locale === 'vi' ? 'Đơn hàng của bạn đang được xử lý' : 'Your order is being processed') : i18n.t('product.must_purchase') }}
          </p>
          <p class="text-sm font-medium" :class="hasPendingOrder ? 'text-blue-600' : 'text-orange-600'">
            {{ hasPendingOrder 
                ? (i18n.locale === 'vi' ? 'Hãy đợi đơn hàng được chuyển sang trạng thái "Hoàn tất" để chia sẻ cảm nhận của bạn nhé!' : 'Please wait for your order to be marked as "Completed" before sharing your feedback!')
                : (i18n.locale === 'vi' ? 'Hãy mua sản phẩm và đợi đơn hàng được duyệt để chia sẻ cảm nhận của bạn nhé!' : 'Please purchase the product and wait for order approval to share your thoughts!') 
            }}
          </p>
        </div>

        <!-- Reviews List -->
        <div v-if="product.reviews?.length" class="space-y-6">
          <div v-for="review in product.reviews" :key="review.id" class="backdrop-blur-2xl bg-white/50 rounded-[2.5rem] p-6 md:p-10 border border-white shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 relative overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
              <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-lg">
                  {{ review.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                </div>
                <div>
                  <h4 class="font-bold text-slate-800 text-lg tracking-tight">{{ review.user?.name || 'Anonymous' }}</h4>
                  <div class="flex mt-1 text-sm">
                    <span v-for="i in 5" :key="i" :class="i <= review.rating ? 'text-amber-400' : 'text-slate-200'">★</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <span class="text-[11px] font-bold text-slate-400 bg-white/60 px-4 py-2 rounded-xl border border-white shadow-sm uppercase tracking-widest">
                  {{ fmtDate(review.created_at) }}
                </span>
                <div v-if="authStore.isLoggedIn && (authStore.user?.id === review.user_id || authStore.user?.role === 'admin')" class="flex gap-2">
                  <button v-if="authStore.user?.id === review.user_id"
                          @click="editReview(review)" 
                          class="w-10 h-10 flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all shadow-sm active:scale-90"
                          title="Sửa đánh giá">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </button>
                  <button @click="deleteReview(review)" 
                          class="w-10 h-10 flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl transition-all shadow-sm active:scale-90"
                          title="Xóa đánh giá">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                  </button>
                </div>
              </div>
            </div>
            <div class="relative pl-6">
              <div class="absolute left-0 top-0 w-1 h-full bg-blue-500/20 rounded-full"></div>
              <p class="text-base text-slate-600 font-medium leading-relaxed">{{ review.comment }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-20 backdrop-blur-xl bg-white/40 border border-white rounded-[3rem] shadow-sm">
          <div class="text-6xl mb-4">💬</div>
          <p class="text-xl font-bold text-slate-800">{{ i18n.t('product.no_reviews') || 'Chưa có đánh giá nào.' }}</p>
          <p class="text-slate-500 mt-1 font-medium">{{ i18n.t('product.be_the_first') || 'Hãy là người đầu tiên chia sẻ cảm nhận!' }}</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { productsApi } from '../api'
import api from '../services/api'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'
import { useUtils } from '../composables/useUtils'
import Swal from 'sweetalert2'

const { fmtPrice, fmtDate, getImageUrl } = useUtils()
const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const i18n = useI18nStore()
const toast = useToast()

const product = ref(null)
const loading = ref(true)
const activeImage = ref(null)
const qty = ref(1)
const adding = ref(false)
const buying = ref(false)

const reviewForm = ref({ rating: 0, comment: '', order_id_input: null })
const submittingReview = ref(false)
const reviewError = ref('')
const eligibleOrderId = ref(null)
const editingReviewId = ref(null)
const hasPendingOrder = ref(false)

const userReview = computed(() => {
  if (!product.value || !authStore.user) return null
  return product.value.reviews?.find(r => r.user_id === authStore.user.id)
})

onMounted(async () => {
  try {
    const res = await productsApi.show(route.params.id)
    product.value = res.data
    activeImage.value = res.data.hinh_anh
    if (authStore.isLoggedIn) {
      await findEligibleOrder()
    }
  } catch {
    product.value = null
  } finally {
    loading.value = false
  }
})

async function findEligibleOrder() {
  try {
    const res = await api.get('/orders', { 
      params: { status: 'completed', product_id: product.value?.id } 
    })
    const orders = res.data.data || []
    const completedOrder = orders.find(o => o.status === 'completed')
    if (completedOrder) {
      eligibleOrderId.value = completedOrder.id
      return
    }

    const resAll = await api.get('/orders', { 
      params: { product_id: product.value?.id } 
    })
    const allOrders = resAll.data.data || []
    hasPendingOrder.value = allOrders.length > 0
  } catch {}
}

function ratingLabel(r) {
  const labels = {
    1: i18n.t('product.rating_1'),
    2: i18n.t('product.rating_2'),
    3: i18n.t('product.rating_3'),
    4: i18n.t('product.rating_4'),
    5: i18n.t('product.rating_5')
  }
  return labels[r] || ''
}

async function addToCart() {
  adding.value = true
  const res = await cartStore.addToCart(product.value.id, qty.value)
  adding.value = false
  if (res.success) {
    toast.success(i18n.t('common.add_success'), {
      label: i18n.t('product.view_cart'),
      url: '/cart'
    })
    product.value.stock -= qty.value // Deduct stock locally for immediate feedback
    qty.value = 1 // Reset quantity after successful addition
  }
}

async function buyNow() {
  buying.value = true
  const res = await cartStore.addToCart(product.value.id, qty.value)
  buying.value = false
  if (res.success) router.push('/cart')
}

async function submitReview() {
  reviewError.value = ''
  if (!reviewForm.value.rating) { reviewError.value = i18n.t('product.select_rating_error') || 'Vui lòng chọn số sao!'; return }
  const orderId = eligibleOrderId.value || reviewForm.value.order_id_input
  if (!orderId) { reviewError.value = i18n.t('product.enter_order_id_error') || 'Vui lòng nhập Order ID!'; return }

  submittingReview.value = true
  try {
    if (editingReviewId.value) {
      const res = await api.put(`/reviews/${editingReviewId.value}`, {
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment,
      })
      const idx = product.value.reviews.findIndex(r => r.id === editingReviewId.value)
      if (idx !== -1) product.value.reviews[idx] = res.data.review
      product.value.avg_rating = res.data.avg_rating
      toast.success(i18n.t('common.review_update_success'))
      cancelEdit()
    } else {
      const res = await api.post(`/products/${product.value.id}/reviews`, {
        order_id: orderId,
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment,
      })
      product.value.reviews.unshift(res.data.review)
      product.value.avg_rating = res.data.avg_rating
      reviewForm.value = { rating: 0, comment: '', order_id_input: null }
      toast.success(i18n.t('common.review_success'))
    }
  } catch (e) {
    reviewError.value = e.response?.data?.message || i18n.t('common.error')
    toast.error(reviewError.value)
  } finally {
    submittingReview.value = false
  }
}

function editReview(review) {
  editingReviewId.value = review.id
  reviewForm.value = { rating: review.rating, comment: review.comment, order_id_input: review.order_id }
  const target = document.getElementById('reviews')
  if (target) {
    window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' })
  }
}

function cancelEdit() {
  editingReviewId.value = null
  reviewForm.value = { rating: 0, comment: '', order_id_input: null }
}

async function deleteReview(review) {
  const result = await Swal.fire({
    title: i18n.locale === 'vi' ? 'Xóa đánh giá?' : 'Delete review?',
    text: i18n.locale === 'vi' ? 'Bạn có chắc chắn muốn xóa đánh giá này?' : 'Are you sure you want to delete this review?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Xóa ngay',
    cancelButtonText: 'Hủy'
  })

  if (!result.isConfirmed) return

  try {
    await api.delete(`/reviews/${review.id}`)
    product.value.reviews = product.value.reviews.filter(r => r.id !== review.id)
    toast.success(i18n.t('common.review_delete_success'))
  } catch (e) {
    toast.error(e.response?.data?.message || 'Error!')
  }
}

function onImgError(e) { e.target.src = 'https://via.placeholder.com/400' }
</script>

<style scoped>
.product-detail-container { 
  max-width: 1100px; margin: 0 auto; padding: 20px 20px 100px; position: relative; 
}
.detail-layout { display: flex; flex-direction: column; gap: 20px; }

.top-actions { padding: 10px 0; }

/* Back Button - Premium Modern */
.btn-back-modern {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 10px 20px; border-radius: 16px;
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #475569; font-weight: 700; font-size: 14px;
  cursor: pointer; transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.btn-back-modern:hover {
  background: #f8fafc; color: #1e293b;
  transform: translateX(-4px);
  border-color: #cbd5e1;
}
.btn-back-modern svg { transition: transform 0.4s; }
.btn-back-modern:hover svg { transform: scale(1.1); color: #3b82f6; }

.main-content { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start; }

/* Images - Premium Glass */
.image-gallery { position: sticky; top: 100px; }
.main-image-wrap { 
  background: rgba(255, 255, 255, 0.6); 
  backdrop-filter: blur(20px);
  border-radius: 40px; aspect-ratio: 1; 
  display: flex; align-items: center; justify-content: center; padding: 40px;
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 20px 50px rgba(0,0,0,0.03);
}
.main-img { max-width: 100%; max-height: 100%; object-fit: contain; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.08)); transition: 0.5s; }
.main-image-wrap:hover .main-img { transform: scale(1.05); }

.thumbnail-list { display: flex; gap: 15px; margin-top: 20px; justify-content: center; }
.thumb-btn { 
  width: 70px; height: 70px; border-radius: 18px; border: 2px solid transparent; 
  background: rgba(255,255,255,0.8); padding: 6px; cursor: pointer; transition: 0.3s;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.thumb-btn.active { border-color: #3b82f6; transform: scale(1.1); box-shadow: 0 8px 20px rgba(59,130,246,0.15); }
.thumb-btn img { width: 100%; height: 100%; object-fit: contain; }

/* Info Section */
.product-info { padding-top: 10px; }
.product-title { font-size: 36px; font-weight: 900; color: #1e293b; margin-bottom: 8px; line-height: 1.1; letter-spacing: -0.02em; }
.rating-row { display: flex; align-items: center; gap: 10px; margin-bottom: 25px; }
.stars span { color: #e2e8f0; font-size: 18px; text-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.stars span.active { color: #f59e0b; }
.review-count { font-size: 14px; font-weight: 700; color: #64748b; }

.price-box { margin-bottom: 35px; }
.current-price { font-size: 42px; font-weight: 900; color: #1e293b; display: block; margin-bottom: 4px; }
.vat-note { font-size: 13px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 4px; }

.variant-section { margin-bottom: 35px; }
.section-label { font-size: 15px; font-weight: 800; color: #1e293b; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
.variant-card { 
  border: 2px solid #3b82f6; border-radius: 20px; padding: 20px; background: rgba(59, 130, 246, 0.03);
  max-width: 320px; position: relative;
}
.v-name { display: block; font-weight: 800; color: #2563eb; font-size: 16px; margin-bottom: 2px; }
.v-stock { font-size: 12px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 3px 10px; border-radius: 8px; display: inline-block; margin-top: 8px; }
.v-stock.out-of-stock { color: #dc2626; background: #fef2f2; }

/* Desktop Actions */
.action-row { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; }
.qty-selector { 
  display: flex; align-items: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; 
  height: 56px; padding: 0 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}
.qty-selector button { width: 44px; height: 44px; border: none; background: transparent; font-size: 22px; cursor: pointer; color: #475569; transition: 0.2s; border-radius: 12px; }
.qty-selector button:hover:not(:disabled) { background: #f8fafc; color: #1e293b; }
.qty-selector span { width: 35px; text-align: center; font-weight: 800; color: #1e293b; font-size: 16px; }

.btn-primary { 
  background: #1e293b; color: #fff; flex: 1.5; 
  box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}
.btn-secondary { 
  background: #fff; border: 2px solid #e2e8f0; color: #1e293b; flex: 1;
  white-space: nowrap; display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-primary:hover { background: #0f172a; transform: translateY(-3px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
.btn-secondary:hover { border-color: #1e293b; transform: translateY(-2px); }

/* Description */
.desc-title-row { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
.title-marker { width: 6px; height: 24px; background: linear-gradient(to bottom, #3b82f6, #6366f1); border-radius: 10px; }
.desc-text { color: #475569; font-size: 16px; line-height: 1.8; font-weight: 500; }

/* Sticky Mobile */
.sticky-mobile-bar { display: none; }

/* Reviews - Ultra Premium */
.reviews-section { margin-top: 60px; padding-top: 50px; border-top: 1px solid rgba(0,0,0,0.05); }
.section-header h2 { font-size: 28px; font-weight: 900; color: #1e293b; margin-bottom: 35px; display: flex; align-items: center; gap: 15px; }
.section-header h2::before { content: '⭐'; font-size: 32px; }

.review-card { 
  background: rgba(255, 255, 255, 0.4); 
  backdrop-filter: blur(20px);
  border-radius: 35px; padding: 35px; border: 1px solid rgba(255,255,255,0.6); 
  margin-bottom: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.04);
  transition: 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative; overflow: hidden;
}
.review-card:hover { transform: translateY(-8px); box-shadow: 0 30px 60px rgba(0,0,0,0.08); border-color: #fff; }

.review-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.user-info { display: flex; gap: 15px; align-items: center; }
.user-avatar { 
  width: 56px; height: 56px; border-radius: 18px; 
  background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); 
  color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px;
  box-shadow: 0 8px 16px rgba(59,130,246,0.3);
}
.user-name { font-weight: 800; color: #1e293b; margin: 0 0 2px; font-size: 16px; }
.stars-sm span { color: #e2e8f0; font-size: 15px; }
.stars-sm span.active { color: #f59e0b; }
.review-date { font-size: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.review-comment { 
  color: #475569; font-size: 16px; line-height: 1.7; margin: 0; font-weight: 500; padding-left: 10px; border-left: 3px solid rgba(59,130,246,0.1); 
}

.empty-reviews { text-align: center; padding: 60px 0; background: rgba(255,255,255,0.4); border-radius: 40px; border: 1px dashed #e2e8f0; }
.empty-reviews p { color: #64748b; font-weight: 600; font-size: 16px; }

/* Responsive */
@media (max-width: 1024px) {
  .main-content { gap: 40px; }
  .product-title { font-size: 30px; }
  .current-price { font-size: 34px; }
}

@media (max-width: 768px) {
  .product-detail-container { padding: 0 0 100px; }
  .top-actions { padding: 15px 20px 5px; }
  .btn-back-modern {
    padding: 8px 16px; border-radius: 12px;
  }
  .main-content { grid-template-columns: 1fr; gap: 0; }
  .image-gallery { position: static; }
  .main-image-wrap { border-radius: 0; border: none; border-bottom: 1px solid rgba(0,0,0,0.05); height: 260px; padding: 20px; }
  .product-info { padding: 25px 20px; }
  .product-title { font-size: 24px; }
  .current-price { font-size: 28px; }
  .desktop-only { display: none; }
  
  .sticky-mobile-bar {
    display: flex; position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.9);
    backdrop-filter: blur(20px); padding: 16px 20px; gap: 15px; align-items: center; z-index: 1000;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.08); border-top: 1px solid rgba(255,255,255,0.5);
  }
  .qty-selector-sm {
    display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 4px; border-radius: 14px;
  }
  .qty-selector-sm button { width: 36px; height: 36px; border: none; background: none; font-size: 20px; font-weight: 700; }
  .qty-selector-sm span { font-weight: 800; width: 25px; text-align: center; font-size: 15px; }
  .mobile-btns { display: flex; gap: 10px; flex: 1; }
  .btn-cart-sm { width: 52px; height: 52px; border-radius: 16px; border: 2px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; color: #1e293b; }
  .btn-buy-sm { flex: 1; height: 52px; border-radius: 16px; background: #1e293b; color: #fff; font-weight: 800; font-size: 15px; border: none; }
  
  .reviews-section { padding: 25px 20px; }
  .review-card { padding: 25px; border-radius: 28px; }
  .thumb-btn { width: 55px; height: 55px; border-radius: 14px; }
}

/* Animations */
.spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
