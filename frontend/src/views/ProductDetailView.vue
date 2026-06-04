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
        <button @click="$router.push('/')" class="btn-back-modern">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
          <span>{{ i18n.t('product.go_back') }}</span>
        </button>
      </div>

      <div class="main-content">
        <!-- LEFT: Images -->
        <div class="image-gallery">
          <div class="main-image-wrap">
            <img :src="getImageUrl(activeImage || product.hinh_anh)" :alt="product.name" class="main-img" @error="onImgError" />
          </div>
          <div v-if="product.images?.length" class="thumbnail-list">
            <button @click="activeImage = product.hinh_anh"
              class="thumb-btn" :class="{ active: !activeImage || activeImage === product.hinh_anh }">
              <img :src="getImageUrl(product.hinh_anh)" @error="onImgError" />
            </button>
            <button v-for="img in product.images" :key="img.id" @click="activeImage = img.image_path"
              class="thumb-btn" :class="{ active: activeImage === img.image_path }">
              <img :src="getImageUrl(img.image_path)" @error="onImgError" />
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
             class="bg-white rounded-3xl p-6 md:p-8 mb-10 shadow-sm animate-fade-in border border-slate-100">
          <h3 class="font-bold text-slate-900 mb-4 text-sm md:text-base">{{ editingReviewId ? i18n.t('product.edit_review') : i18n.t('product.write_review') }}</h3>
          
          <!-- Tự động liên kết Order ID (chỉ hiện khi thêm mới để đỡ rối khi sửa) -->
          <div v-if="!editingReviewId" class="text-sm font-bold text-emerald-700 bg-emerald-50/80 backdrop-blur-sm border border-emerald-200 rounded-xl px-4 py-3 mb-5 shadow-sm flex items-center gap-2">
            <span>✅</span> {{ i18n.t('product.linked_order') }} #{{ eligibleOrderId || reviewForm.order_id_input }}
          </div>

          <!-- Chọn sao -->
          <div class="flex items-center gap-2 mb-4">
            <div class="flex gap-1.5">
              <button v-for="i in 5" :key="i" @click="reviewForm.rating = i" class="text-3xl md:text-4xl transition-transform hover:scale-110 active:scale-125 focus:outline-none">
                <span :class="i <= reviewForm.rating ? 'text-amber-500' : 'text-slate-200'">★</span>
              </button>
            </div>
          </div>
          
          <textarea v-model="reviewForm.comment" rows="3" :placeholder="i18n.t('product.your_comment')" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-[14px] focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 resize-none mb-4 transition-all text-slate-800 placeholder-slate-400"></textarea>

          <div v-if="reviewError" class="text-sm font-bold text-rose-600 mb-4 bg-rose-50/80 p-3 rounded-xl border border-rose-200 flex items-center gap-2">⚠️ {{ reviewError }}</div>
          
          <div class="flex items-center gap-3">
            <button @click="submitReview" :disabled="!reviewForm.rating || submittingReview" class="bg-[#0b65ff] hover:bg-blue-700 text-white px-6 py-2.5 rounded-full font-bold text-sm disabled:opacity-50 transition-colors shadow-sm flex items-center gap-2">
              <span v-if="submittingReview" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submittingReview ? i18n.t('common.saving') : (editingReviewId ? i18n.t('common.update') : i18n.t('common.submit_review')) }}
            </button>
            
            <button v-if="editingReviewId" @click="deleteReview({ id: editingReviewId })" class="bg-white border border-rose-400 text-rose-500 hover:bg-rose-50 px-6 py-2.5 rounded-full font-bold text-sm transition-colors shadow-sm">
              {{ i18n.t('common.delete') }}
            </button>
            <button v-if="editingReviewId" @click="cancelEdit" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-6 py-2.5 rounded-full font-bold text-sm transition-colors shadow-sm">
              {{ i18n.t('common.cancel') }}
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
          <div v-for="review in product.reviews" :key="review.id" class="break-words">
            
            <div class="flex justify-between items-start mb-2 gap-4">
              <div class="flex gap-3">
                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-600 font-bold text-sm shrink-0">
                  {{ review.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    {{ review.user?.name || 'Anonymous' }}
                    <button v-if="authStore.isLoggedIn && authStore.user?.id === review.user_id && !editingReviewId" 
                            @click="editReview(review)" 
                            class="text-blue-500 hover:text-blue-700" title="Sửa đánh giá">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                  </h4>
                  <div class="flex mt-0.5 text-xs">
                    <span v-for="i in 5" :key="i" :class="i <= review.rating ? 'text-amber-500' : 'text-slate-200'">★</span>
                  </div>
                </div>
              </div>
              <span class="text-[12px] text-slate-500 font-medium">
                {{ fmtDate(review.created_at) }}
              </span>
            </div>
            
            <div class="pl-13 mt-1">
              <p class="text-sm text-slate-700 font-medium whitespace-pre-line">{{ review.comment }}</p>
              
              <!-- Admin Reply Box (If exists or mock) -->
              <div v-if="review.admin_reply" class="mt-4 border-l-[3px] border-slate-700 pl-4 py-1.5 ml-1">
                <p class="font-bold text-[11px] text-slate-900 mb-1 flex items-center gap-1.5 uppercase tracking-wide">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.19-2.05.8-3.06.8-1.25 0-2.35-.49-3.26-.8-1.63-.56-2.58-.8-3.92.51-1.39 1.35-2.22 3.19-2.22 4.96 0 2.27 1.48 4.3 3.32 5.56 1.44.97 3.01 1.48 4.54 1.48 1.45 0 3.03-.49 4.38-1.4 1.63-1.12 3.02-3.07 3.56-5.22-.05-.03-3.18-1.2-3.18-4.78 0-3 2.45-4.42 2.53-4.46-1.42-2.08-3.55-2.33-4.23-2.33-1.57-.04-3.15.82-3.92.82-.76 0-2.04-.77-3.32-.77-1.8 0-3.48.87-4.43 2.37-1.92 3.04-.49 7.55 1.38 10.26.92 1.34 2 2.85 3.44 2.8 1.38-.05 1.9-.89 3.57-.89 1.65 0 2.12.89 3.57.87 1.49-.03 2.43-1.4 3.34-2.72 1.05-1.53 1.48-3.03 1.5-3.11-.03-.01-2.91-1.12-2.91-4.46zM15.11 3.25c.78-.95 1.3-2.27 1.15-3.58-1.13.05-2.52.76-3.32 1.73-.72.87-1.32 2.22-1.15 3.5 1.25.1 2.55-.7 3.32-1.65z" transform="scale(0.85) translate(2, -2)"/></svg>
                  {{ i18n.t('product.admin_reply') }}
                </p>
                <p class="text-[13px] text-slate-600">{{ review.admin_reply }}</p>
              </div>
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
// =====================================================================
// [Đặng Văn Hà - 4.3.9] ProductDetailView — Trang chi tiết sản phẩm
// LUỒNG TỔNG QUÁT:
//   1. Trang load → gọi GET /products/{id} → ProductController@show → trả SP + reviews
//   2. User bấm "Thêm vào giỏ" → addToCart() → cartStore → POST /cart → CartController
//   3. User bấm "Mua ngay" → buyNow() → addToCart() → chuyển sang /cart
//   4. User gửi đánh giá → submitReview() → POST /products/{id}/reviews → ReviewController
// =====================================================================
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
const route = useRoute()   // Lấy id sản phẩm từ URL (vd: /products/42 → route.params.id = 42)
const router = useRouter() // Dùng để điều hướng trang (vd: push sang /cart)
const cartStore = useCartStore()   // Pinia store giỏ hàng — quản lý state items, tổng tiền
const authStore = useAuthStore()   // Pinia store xác thực — kiểm tra isLoggedIn, user.id
const i18n = useI18nStore()
const toast = useToast()

const product    = ref(null)   // Lưu toàn bộ dữ liệu SP trả về từ API (name, price, stock, reviews,...)
const loading    = ref(true)   // true khi đang gọi API → hiện skeleton loading
const activeImage = ref(null)  // Đường dẫn ảnh đang hiển thị to trong gallery
const qty    = ref(1)          // Số lượng user chọn mua (1 → product.stock)
const adding = ref(false)      // true khi đang gọi API thêm giỏ → disable nút để chống double-click
const buying = ref(false)      // true khi đang gọi API mua ngay → disable nút

const reviewForm = ref({ rating: 0, comment: '', order_id_input: null }) // Dữ liệu form đánh giá
const submittingReview = ref(false) // true khi đang gửi đánh giá → disable nút submit
const reviewError = ref('')         // Thông báo lỗi validation khi gửi đánh giá
const eligibleOrderId = ref(null)   // ID đơn hàng "completed" của user → đủ điều kiện đánh giá
const editingReviewId = ref(null)   // ID review đang được sửa (null = đang tạo mới)
const hasPendingOrder = ref(false)  // true nếu user có đơn đang xử lý nhưng chưa hoàn tất

// Tìm review của user hiện tại trong danh sách reviews của SP (để ẩn/hiện form)
const userReview = computed(() => {
  if (!product.value || !authStore.user) return null
  return product.value.reviews?.find(r => r.user_id === authStore.user.id)
})

// BƯỚC 1: Load trang → gọi API lấy chi tiết SP
// GET /products/{id} → ProductController@show → ProductService (eager load reviews đã duyệt)
onMounted(async () => {
  try {
    const res = await productsApi.show(route.params.id) // Gọi GET /products/{id}
    product.value = res.data       // Lưu SP (bao gồm category, images, reviews)
    activeImage.value = res.data.hinh_anh // Mặc định hiện ảnh đại diện
    if (authStore.isLoggedIn) {
      await findEligibleOrder() // Nếu đã đăng nhập → kiểm tra xem có đơn đủ điều kiện đánh giá không
    }
  } catch {
    product.value = null // Lỗi 404 / 500 → hiện màn hình "không tìm thấy SP"
  } finally {
    loading.value = false // Tắt skeleton loading dù thành công hay thất bại
  }
})

// Kiểm tra xem user có đơn hàng đã "completed" chứa SP này không → mới được đánh giá
// GET /orders?status=completed&product_id=X → OrderController@index → lọc theo user_id
async function findEligibleOrder() {
  try {
    const res = await api.get('/orders', { 
      params: { status: 'completed', product_id: product.value?.id } 
    })
    const orders = res.data.data || []
    const completedOrder = orders.find(o => o.status === 'completed')
    if (completedOrder) {
      eligibleOrderId.value = completedOrder.id // Lưu ID đơn đủ điều kiện → hiện form đánh giá
      return
    }
    // Không có đơn hoàn tất → kiểm tra xem có đơn đang xử lý không (để hiện thông báo chờ)
    const resAll = await api.get('/orders', { params: { product_id: product.value?.id } })
    hasPendingOrder.value = (resAll.data.data || []).length > 0 // true = có đơn nhưng chưa xong
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

// BƯỚC 2A: User bấm "Thêm vào giỏ hàng"
// → addToCart() → cartStore.addToCart() → POST /cart → CartController@store → ghi vào bảng cart_items
async function addToCart() {
  adding.value = true
  const res = await cartStore.addToCart(product.value.id, qty.value) // Gọi qua Pinia store
  adding.value = false
  if (res.success) {
    toast.success(i18n.t('common.add_success'), { label: i18n.t('product.view_cart'), url: '/cart' })
    product.value.stock = Math.max(0, product.value.stock - qty.value) // Cập nhật tồn kho UI ngay (optimistic)
    qty.value = 1 // Reset số lượng về 1 sau khi thêm
  } else {
    toast.error(res.message || i18n.t('common.error'))
  }
}

// BƯỚC 2B: User bấm "Mua ngay"
// → buyNow() → addToCart() trước → rồi push sang /cart để checkout
async function buyNow() {
  buying.value = true
  const res = await cartStore.addToCart(product.value.id, qty.value)
  buying.value = false
  if (res.success) router.push('/cart') // Sau khi thêm giỏ thành công → điều hướng sang giỏ hàng
}

// BƯỚC 4: User gửi đánh giá (rating + comment)
// Điều kiện: phải có eligibleOrderId (đơn hàng đã "completed" chứa SP này)
async function submitReview() {
  reviewError.value = ''
  if (!reviewForm.value.rating) { reviewError.value = i18n.t('product.select_rating_error') || 'Vui lòng chọn số sao!'; return }
  const orderId = eligibleOrderId.value || reviewForm.value.order_id_input // ID đơn để backend xác minh
  if (!orderId) { reviewError.value = i18n.t('product.enter_order_id_error') || 'Vui lòng nhập Order ID!'; return }

  submittingReview.value = true
  try {
    if (editingReviewId.value) {
      // SỬA đánh giá: PUT /reviews/{id} → ReviewController@update
      const res = await api.put(`/reviews/${editingReviewId.value}`, {
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment,
      })
      const idx = product.value.reviews.findIndex(r => r.id === editingReviewId.value)
      if (idx !== -1) product.value.reviews[idx] = res.data.review // Cập nhật review trong UI ngay
      product.value.avg_rating = res.data.avg_rating // Cập nhật điểm trung bình mới
      toast.success(i18n.t('common.review_update_success'))
      cancelEdit()
    } else {
      // TẠO MỚI đánh giá: POST /products/{id}/reviews → ReviewController@store
      // Backend kiểm tra: đơn hàng order_id có thuộc user này không, có status=completed không
      const res = await api.post(`/products/${product.value.id}/reviews`, {
        order_id: orderId,
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment,
      })
      product.value.reviews.unshift(res.data.review) // Thêm review mới lên đầu danh sách
      product.value.avg_rating = res.data.avg_rating  // Cập nhật điểm trung bình
      reviewForm.value = { rating: 0, comment: '', order_id_input: null } // Reset form
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
    // Increase offset to account for taller mobile header
    const offset = window.innerWidth <= 768 ? 160 : 80;
    window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' })
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
    reverseButtons: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: i18n.t('common.delete_now') || 'Xóa ngay',
    cancelButtonText: 'Hủy'
  })

  if (!result.isConfirmed) return

  try {
    const res = await api.delete(`/reviews/${review.id}`)
    product.value.reviews = product.value.reviews.filter(r => r.id !== review.id)
    
    // Update avg_rating if the backend returns it
    if (res.data && res.data.avg_rating !== undefined) {
      product.value.avg_rating = res.data.avg_rating
    }
    
    // Reset edit form if the deleted review was currently being edited
    if (editingReviewId.value === review.id) {
      cancelEdit()
    }
    
    // Re-evaluate if the user is eligible to write a new review
    findEligibleOrder()
    
    toast.success(i18n.t('common.review_delete_success') || 'Xóa đánh giá thành công')
  } catch (e) {
    console.error('Delete review error:', e)
    const msg = e.response?.data?.message || e.message || 'Lỗi không xác định!'
    toast.error(typeof msg === 'string' ? msg : 'Lỗi không xác định!')
  }
}

// [Đặng Văn Hà - 4.3.11] Xử lý hiển thị ảnh mặc định khi ảnh sản phẩm bị lỗi tải (Broken Image)
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
  background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; border: none; font-weight: 800; font-size: 16px; padding: 0 40px; border-radius: 20px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3); flex: 1.5; display: flex; align-items: center; justify-content: center; height: 52px;
}
.btn-secondary { 
  background: #fff; border: 2px solid #e2e8f0; color: #1e293b; flex: 1; height: 52px;
  white-space: nowrap; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 20px;
}
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(37, 99, 235, 0.4); filter: brightness(1.1); }
.btn-secondary:hover { border-color: #2563eb; color: #2563eb; transform: translateY(-2px); }

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
  /* Increase padding to ensure reviews aren't hidden under the 85px sticky footer */
  .product-detail-container { padding: 0 0 140px; overflow-x: hidden; }
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
    backdrop-filter: blur(20px); padding: 16px 20px; gap: 15px; align-items: center; z-index: 999;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.08); border-top: 1px solid rgba(255,255,255,0.5);
  }
  .qty-selector-sm {
    display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 4px; border-radius: 14px;
  }
  .qty-selector-sm button { width: 36px; height: 36px; border: none; background: none; font-size: 20px; font-weight: 700; }
  .qty-selector-sm span { font-weight: 800; width: 25px; text-align: center; font-size: 15px; }
  .mobile-btns { display: flex; gap: 10px; flex: 1; }
  .btn-cart-sm { width: 52px; height: 52px; border-radius: 16px; border: 2px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; color: #2563eb; transition: all 0.3s; }
  .btn-cart-sm:active { background: #eff6ff; border-color: #2563eb; }
  .btn-buy-sm { flex: 1; height: 52px; border-radius: 16px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; font-weight: 800; font-size: 15px; border: none; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
  
  /* Give reviews more margin from edges for premium feel */
  .reviews-section { padding: 25px 15px; }
  /* Make sure the review blocks don't overflow on small screens */
  .review-card { padding: 20px; border-radius: 24px; overflow-wrap: break-word; word-break: break-word; }
  .thumb-btn { width: 55px; height: 55px; border-radius: 14px; }
}

/* Animations */
.spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
