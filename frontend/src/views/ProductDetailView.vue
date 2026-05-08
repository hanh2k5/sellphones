<template>
  <div class="max-w-5xl mx-auto px-4 py-10 relative z-10">
    <!-- Loading -->
    <div v-if="loading" class="animate-pulse space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
        <div class="backdrop-blur-xl bg-white/40 border border-white/60 rounded-[2rem] h-96"></div>
        <div class="space-y-4">
          <div class="h-8 bg-white/50 rounded-full w-3/4"></div>
          <div class="h-6 bg-white/50 rounded-full w-1/2"></div>
          <div class="h-10 bg-white/50 rounded-xl w-1/3 mt-8"></div>
        </div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else-if="!product" class="text-center py-24 backdrop-blur-xl bg-white/40 border border-white/60 rounded-[3rem] shadow-sm mt-8">
      <div class="text-7xl mb-6">😢</div>
      <p class="text-2xl font-bold text-slate-600 mb-8">{{ i18n.t('product.not_exist') || 'Sản phẩm không tồn tại' }}</p>
      <router-link to="/products" class="inline-block bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 px-8 rounded-2xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 transition-all active:scale-95 uppercase tracking-wider text-sm">{{ i18n.t('product.go_back') }}</router-link>
    </div>

    <!-- Product detail -->
    <div v-else>
      <div class="mb-10">
        <button @click="$router.back()" class="flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors w-fit">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
          {{ i18n.t('product.go_back') }}
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-14 mb-16">
        <!-- Ảnh sản phẩm -->
        <div>
          <!-- Ảnh chính -->
          <div class="backdrop-blur-xl bg-white/60 rounded-[2rem] overflow-hidden mb-4 aspect-square flex items-center justify-center border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-8">
            <img :src="getImageUrl(activeImage || product.hinh_anh)" :alt="product.name" class="w-full h-full object-contain drop-shadow-xl transition-all duration-300 hover:scale-105" />
          </div>
          <!-- Ảnh phụ -->
          <div v-if="product.images?.length" class="flex gap-3 flex-wrap justify-center">
            <!-- Nút chọn ảnh chính -->
            <button @click="activeImage = product.hinh_anh"
              class="w-16 h-16 rounded-2xl overflow-hidden border-2 transition-all bg-white/80 backdrop-blur-sm"
              :class="(!activeImage || activeImage === product.hinh_anh) ? 'border-blue-500 shadow-md ring-2 ring-blue-100 scale-110' : 'border-transparent shadow-sm hover:shadow hover:scale-105'">
              <img :src="getImageUrl(product.hinh_anh)" class="w-full h-full object-contain p-1" />
            </button>
            <!-- Các nút chọn ảnh phụ -->
            <button v-for="img in product.images" :key="img.id" @click="activeImage = img.image_path"
              class="w-16 h-16 rounded-2xl overflow-hidden border-2 transition-all bg-white/80 backdrop-blur-sm"
              :class="activeImage === img.image_path ? 'border-blue-500 shadow-md ring-2 ring-blue-100 scale-110' : 'border-transparent shadow-sm hover:shadow hover:scale-105'">
              <img :src="getImageUrl(img.image_path)" class="w-full h-full object-contain p-1" />
            </button>
          </div>
        </div>

        <!-- Thông tin -->
        <div class="flex flex-col justify-center">
          <h1 class="text-3xl lg:text-4xl font-bold text-[#1d1d1f] mb-3 leading-tight tracking-tight">{{ product.name }}</h1>

          <!-- Rating -->
          <div class="flex items-center gap-2 mb-4">
            <div class="flex">
              <span v-for="i in 5" :key="i" class="text-sm drop-shadow-sm" :class="i <= Math.round(product.avg_rating || 0) ? 'text-amber-500' : 'text-slate-200'">★</span>
            </div>
            <span class="text-xs font-bold text-slate-800">
              {{ product.avg_rating ? Number(product.avg_rating).toFixed(1) : '' }}
              <span class="font-medium text-slate-500">{{ i18n.t('product.reviews_count', { count: product.reviews?.length || 0 }) }}</span>
            </span>
          </div>

          <p class="text-3xl font-bold text-[#1d1d1f] mb-2">{{ fmtPrice(product.price) }}</p>
          <p class="text-xs font-medium text-slate-500 mb-8 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            {{ i18n.t('product.shipping_note') }}
          </p>

          <!-- Tình trạng / Variant Box -->
          <div class="mb-8">
            <p class="font-bold text-[14px] text-slate-800 mb-3">{{ i18n.t('product.variant_title') }}</p>
            <div class="border-2 rounded-xl p-4 w-full md:w-3/4 lg:w-2/3 border-blue-500 bg-blue-50/20 relative">
              <p class="text-blue-600 font-bold text-[14px]">{{ i18n.t('product.variant_default') }}</p>
              <p class="text-slate-500 text-xs mt-1">{{ product.category?.name || i18n.t('product.default_brand') }}</p>
              <span v-if="product.stock > 0" class="inline-block mt-2 text-[11px] bg-orange-100 text-orange-700 font-bold px-2 py-0.5 rounded">{{ i18n.t('product.stock_count', { count: product.stock }) }}</span>
              <span v-else class="inline-block mt-2 text-[11px] bg-rose-100 text-rose-700 font-bold px-2 py-0.5 rounded">{{ i18n.t('product.out_of_stock') }}</span>
            </div>
          </div>

          <p class="text-sm text-slate-500 font-medium mb-8 leading-relaxed line-clamp-2">{{ product.description || i18n.t('product.default_description') }}</p>

          <!-- Add to cart & Buy now -->
          <div class="flex flex-col sm:flex-row items-center gap-4 mb-6">
            <div class="flex items-center bg-white border border-slate-200 rounded-2xl overflow-hidden h-14 px-1 shadow-sm w-full sm:w-auto shrink-0 justify-between">
              <button @click="qty = Math.max(1, qty - 1)" class="w-12 h-full hover:bg-slate-50 font-bold text-xl text-slate-600 transition-colors flex items-center justify-center">−</button>
              <span class="w-10 font-bold text-[15px] text-center text-slate-800">{{ qty }}</span>
              <button @click="qty = Math.min(product.stock, qty + 1)" :disabled="qty >= product.stock" class="w-12 h-full hover:bg-slate-50 disabled:opacity-40 font-bold text-xl text-slate-600 transition-colors flex items-center justify-center">+</button>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full">
              <!-- Thêm vào giỏ -->
              <button @click="addToCart" :disabled="product.stock === 0 || adding || buying"
                class="w-full h-14 bg-white border-2 border-slate-200 hover:border-slate-900 hover:bg-slate-50 disabled:opacity-50 text-slate-900 font-bold rounded-2xl transition-all duration-300 active:scale-95 tracking-wide text-sm flex items-center justify-center gap-2">
                <span v-if="adding" class="w-5 h-5 border-2 border-slate-900/30 border-t-slate-900 rounded-full animate-spin"></span>
                <template v-else>
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                  {{ i18n.t('product.add_to_cart') }}
                </template>
              </button>
              
              <!-- Mua ngay -->
              <button @click="buyNow" :disabled="product.stock === 0 || adding || buying"
                class="w-full h-14 bg-[#1d1d1f] hover:bg-[#333336] disabled:opacity-50 text-white font-bold rounded-2xl transition-all duration-300 active:scale-95 tracking-wide text-sm flex items-center justify-center gap-2 shadow-xl shadow-black/10">
                <span v-if="buying" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                {{ buying ? i18n.t('common.processing') : i18n.t('product.buy_now') }}
              </button>
            </div>
          </div>

          <!-- Mô tả -->
          <div v-if="product.description" class="mt-8 pt-8 border-t border-white/60">
            <h3 class="font-bold text-slate-800 mb-4 text-[15px] uppercase tracking-wider flex items-center gap-2">
              <span class="w-2 h-6 bg-gradient-to-b from-blue-500 to-indigo-500 rounded-full"></span>
              {{ i18n.t('product.description') }}
            </h3>
            <p class="text-slate-600 text-[15px] leading-loose whitespace-pre-wrap font-medium">{{ product.description }}</p>
          </div>
        </div>
      </div>

      <!-- Đánh giá -->
      <div id="reviews" class="border-t border-white/60 pt-12">
        <h2 class="text-2xl font-bold text-slate-800 mb-8 flex items-center gap-3">
          <span class="text-3xl">⭐</span> {{ i18n.t('product.reviews') }}
        </h2>

        <!-- Form đánh giá: Hiện khi đang sửa HOẶC (đã mua & chưa đánh giá) -->
        <div v-if="authStore.isLoggedIn && (editingReviewId || (eligibleOrderId && !userReview))" 
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

        <div v-else-if="!authStore.isLoggedIn" class="backdrop-blur-md bg-blue-50/80 border border-blue-200 rounded-[2rem] p-8 mb-10 text-center shadow-sm">
          <p class="text-blue-800 font-medium mb-4">{{ i18n.t('product.must_login_review') || 'Vui lòng đăng nhập để đánh giá' }}</p>
          <router-link to="/login" class="inline-block bg-white text-blue-600 font-bold px-8 py-3 rounded-xl shadow hover:shadow-md transition-all active:scale-95 uppercase text-sm tracking-wider">{{ i18n.t('nav.login') }}</router-link>
        </div>

        <!-- Danh sách đánh giá -->
        <div v-if="product.reviews?.length" class="space-y-6">
          <div v-for="review in product.reviews" :key="review.id" 
               class="backdrop-blur-2xl bg-white/40 rounded-[2.5rem] p-8 border border-white/60 shadow-[0_20px_50px_rgba(0,0,0,0.05)] hover:shadow-[0_30px_60px_rgba(0,0,0,0.08)] transition-all duration-500 hover:-translate-y-1 relative overflow-hidden group">
            <!-- Trang trí nền -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex items-start sm:items-center justify-between mb-6 flex-col sm:flex-row gap-4 relative z-10">
              <div class="flex items-center gap-5">
                <div class="relative">
                  <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-lg transform group-hover:rotate-6 transition-transform duration-500">
                    {{ review.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                  </div>
                  <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full shadow-sm"></div>
                </div>
                <div>
                  <h4 class="font-bold text-slate-800 text-[17px] tracking-tight">{{ review.user?.name || (i18n.t('common.anonymous') || 'Ẩn danh') }}</h4>
                  <div class="flex mt-1 drop-shadow-sm">
                    <span v-for="i in 5" :key="i" class="text-base" :class="i <= review.rating ? 'text-amber-400' : 'text-slate-200/60'">★</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <span v-if="review.created_at" class="text-[11px] font-bold text-slate-400/80 bg-white/40 px-4 py-2 rounded-xl border border-white/60 shadow-sm backdrop-blur-md uppercase tracking-widest">
                  {{ fmtDate(review.created_at) }}
                </span>
                <span v-else class="text-[11px] font-bold text-blue-500 bg-blue-50/50 px-4 py-2 rounded-xl border border-blue-100 shadow-sm backdrop-blur-md uppercase tracking-widest animate-pulse">
                  Vừa xong
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
            <div class="relative">
              <div class="absolute left-0 top-0 w-1 h-full bg-blue-500/20 rounded-full"></div>
              <p class="text-[16px] text-slate-600 font-medium leading-relaxed pl-6 py-1 drop-shadow-sm">{{ review.comment }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-16 backdrop-blur-md bg-white/40 border border-white/60 rounded-[3rem] shadow-sm">
          <div class="text-6xl mb-4 drop-shadow-sm">💬</div>
          <p class="text-lg font-bold text-slate-600">{{ i18n.t('product.no_reviews') || 'Chưa có đánh giá' }}</p>
          <p class="text-[15px] text-slate-500 mt-1">{{ i18n.t('product.be_the_first') || 'Hãy là người đầu tiên chia sẻ!' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
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

const userReview = computed(() => {
  if (!product.value || !authStore.user) return null
  return product.value.reviews?.find(r => r.user_id === authStore.user.id)
})

onMounted(async () => {
  try {
    const res = await api.get(`/products/${route.params.id}`)
    product.value = res.data
    activeImage.value = res.data.hinh_anh
    // Auto-detect eligible order
    if (authStore.isLoggedIn) {
      await findEligibleOrder()
    }
  } catch {
    product.value = null
  } finally {
    loading.value = false
  }
})

const hasPendingOrder = ref(false)

async function findEligibleOrder() {
  try {
    // 1. Check for completed order (to show form)
    const res = await api.get('/orders', { 
      params: { status: 'completed', product_id: product.value?.id } 
    })
    const orders = res.data.data || []
    // Double check status just to be absolutely safe
    const completedOrder = orders.find(o => o.status === 'completed')
    if (completedOrder) {
      eligibleOrderId.value = completedOrder.id
      return
    }

    // 2. If not found, check for ANY order (pending/processing) to show a specific message
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
  const result = await cartStore.addToCart(product.value.id, qty.value)
  adding.value = false
  if (result.success) {
    toast.success(i18n.t('common.add_success') || 'Đã thêm vào giỏ hàng')
  } else {
    toast.error(result.message || i18n.t('common.error') || 'Lỗi!')
  }
}

async function buyNow() {
  buying.value = true
  const result = await cartStore.addToCart(product.value.id, qty.value)
  buying.value = false
  if (result.success) {
    router.push('/cart')
  } else {
    toast.error(result.message || i18n.t('common.error') || 'Lỗi!')
  }
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
</script>
