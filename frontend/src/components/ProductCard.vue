<template>
  <div class="product-card-wrap reveal-item">
    <article
      class="premium-card"
      tabindex="0"
      role="link"
      :aria-label="product.name"
      @click="goToDetail"
      @keydown.enter.prevent="goToDetail"
      @keydown.space.prevent="goToDetail"
    >
      <!-- Image Box: Exactly 180px, 1:1 ratio -->
      <div class="premium-card-img-box">
        <img
          :src="getImageUrl(product.hinh_anh)"
          :alt="product.name"
          @error="onImgError"
        />
      </div>

      <div class="card-body-inner">
        <h2 class="premium-card-title">{{ product.name }}</h2>
        <p class="premium-card-price">{{ i18n.t('product.from_price') }} {{ fmtPrice(product.price) }}</p>

        <div class="premium-action-row">
          <!-- Nút Mua ngay (Chính) -->
          <button
            class="btn-custom-buy"
            @click.stop="handleBuyNow"
            :disabled="isAdding || isBuying"
          >
            <span v-if="isBuying" class="spin-dot"></span>
            <span v-else>{{ i18n.t('home.shop_now') }}</span>
          </button>

          <!-- Nút Thêm vào giỏ (Phụ - Icon) -->
          <button 
            class="btn-custom-add" 
            @click.stop="handleAddToCart" 
            :disabled="isAdding || isBuying"
            :title="i18n.t('product.add_to_cart')"
          >
            <span v-if="isAdding" class="spin-dot-dark"></span>
            <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
          </button>
        </div>
      </div>
    </article>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useI18nStore } from '../stores/i18n'
import { useUtils } from '../composables/useUtils'
import { useToast } from '../composables/useToast'

const { fmtPrice, getImageUrl } = useUtils()
const props = defineProps({ product: { type: Object, required: true } })
const cartStore = useCartStore()
const i18n = useI18nStore()
const router = useRouter()
const toast = useToast()

const isAdding = ref(false)
const isBuying = ref(false)

function goToDetail() {
  router.push(`/products/${props.product.id}`)
}

async function handleAddToCart() {
  if (props.product.stock <= 0) {
    toast.error(i18n.t('product.out_of_stock'))
    return
  }
  isAdding.value = true
  const res = await cartStore.addToCart(props.product.id, 1)
  isAdding.value = false
  if (res.success) {
    toast.success(i18n.t('common.cart_add_success'))
  }
}

async function handleBuyNow() {
  if (props.product.stock <= 0) {
    toast.error(i18n.t('product.out_of_stock'))
    return
  }
  isBuying.value = true
  const res = await cartStore.addToCart(props.product.id, 1)
  isBuying.value = false
  if (res.success) {
    router.push('/cart')
  }
}

function onImgError(e) {
  e.target.src = 'https://via.placeholder.com/180'
}
</script>

<style scoped>
/* ===== MODERN PREMIUM CARD ===== */
.product-card-wrap {
  opacity: 0; transform: translateY(20px);
  transition: all 0.8s cubic-bezier(0.2, 1, 0.2, 1);
}
.product-card-wrap.is-visible { opacity: 1; transform: translateY(0); }

.premium-card {
  background: #ffffff; border-radius: 24px;
  padding: 16px; border: 1px solid rgba(0, 0, 0, 0.04);
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer; display: flex; flex-direction: column;
  height: 100%; position: relative; overflow: hidden;
}
.premium-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.08);
  border-color: rgba(59, 130, 246, 0.2);
}

/* Image Presentation */
.premium-card-img-box {
  width: 100%; height: 180px; margin-bottom: 12px;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border-radius: 14px;
  padding: 8px; transition: 0.5s;
}
.premium-card:hover .premium-card-img-box img {
  transform: scale(1.1);
}
.premium-card-img-box img {
  max-width: 100%; max-height: 100%;
  object-fit: contain; transition: 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  filter: drop-shadow(0 10px 20px rgba(0,0,0,0.05));
}

.card-body-inner { flex: 1; display: flex; flex-direction: column; }

.premium-card-title {
  font-size: 17px; font-weight: 700; color: #1e293b;
  margin-bottom: 8px; text-align: left;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
}

.premium-card-price {
  color: #3b82f6; font-weight: 800; font-size: 19px;
  margin-bottom: 18px; text-align: left;
}

.premium-action-row { display: flex; align-items: center; gap: 10px; }

.btn-custom-buy {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #fff; font-weight: 700; border-radius: 14px;
  height: 48px; font-size: 13px; text-transform: uppercase;
  letter-spacing: 0.05em; border: none; cursor: pointer; flex: 1;
  transition: 0.3s;
}
.btn-custom-buy:hover:not(:disabled) {
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
  transform: translateY(-2px);
}

.btn-custom-add {
  width: 48px; height: 48px; border-radius: 14px;
  background: #f1f5f9; color: #475569;
  border: none; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: 0.3s;
}
.btn-custom-add:hover { background: #e2e8f0; color: #1e293b; }

@media (max-width: 768px) {
  .premium-card { border-radius: 20px; padding: 12px; }
  .premium-card-img-box { height: 160px; }
  .premium-card-title { font-size: 14px; height: 36px; }
  .premium-card-price { font-size: 16px; }
  .btn-custom-buy, .btn-custom-add { height: 40px; }
  .btn-custom-add { width: 40px; }
}
</style>
