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
      <!-- Image Box: Exactly 180px, 1:1 ratio with premium background -->
      <div class="premium-card-img-box">
        <img
          :src="getImageUrl(product.hinh_anh)"
          :alt="product.name"
          @error="onImgError"
        />
        <div class="img-overlay-glow"></div>
      </div>

      <div class="card-body-inner">
        <h2 class="premium-card-title">{{ product.name }}</h2>
        
        <div class="price-box">
          <span class="price-label">{{ i18n.t('product.from_price') }}</span>
          <p class="premium-card-price">{{ fmtPrice(product.price) }}</p>
        </div>

        <div class="premium-action-row">
          <!-- Buy Now Button (Main) -->
          <button
            class="btn-custom-buy"
            @click.stop="handleBuyNow"
            :disabled="isAdding || isBuying"
          >
            <span v-if="isBuying" class="spin-dot"></span>
            <span v-else>{{ i18n.t('home.shop_now') }}</span>
          </button>

          <!-- Add to Cart Button (Secondary Icon) -->
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
    toast.success(i18n.t('common.cart_add_success'), {
      label: i18n.t('product.view_cart'),
      url: '/cart'
    })
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
  opacity: 0; transform: translateY(30px);
  transition: all 0.7s cubic-bezier(0.2, 1, 0.2, 1);
}
.product-card-wrap.is-visible { opacity: 1; transform: translateY(0); }

.premium-card {
  background: #ffffff; border-radius: 24px;
  padding: 12px; border: 1px solid rgba(0, 0, 0, 0.04);
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer; display: flex; flex-direction: column;
  height: 100%; position: relative; overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
}

@media (hover: hover) and (pointer: fine) {
  .premium-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 30px 60px rgba(0,0,0,0.08);
    border-color: rgba(59,130,246,0.1);
  }
  .premium-card:hover .premium-card-img-box img {
    transform: scale(1.1) translateY(-5px) rotate(2deg);
  }
  .premium-card:hover .img-overlay-glow {
    opacity: 1;
  }
}

.premium-card-img-box {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  height: 160px; border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  padding: 10px; overflow: hidden; position: relative;
  transition: background 0.4s;
}
.img-overlay-glow {
  position: absolute; top: 0; left: 0; width: 100%; height: 100%;
  background: radial-gradient(circle at center, rgba(37,99,235,0.05) 0%, transparent 70%);
  opacity: 0; transition: opacity 0.5s; pointer-events: none;
}

.premium-card-img-box img {
  max-width: 100%; max-height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 15px 25px rgba(0,0,0,0.08));
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  mix-blend-mode: darken;
}

.card-body-inner {
  padding: 8px 4px 4px;
  display: flex; flex-direction: column; flex-grow: 1;
}

.premium-card-title {
  font-size: 15px; font-weight: 800; color: #1e293b;
  margin-bottom: 4px; line-height: 1.35;
  display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden; min-height: 2.7em;
  letter-spacing: -0.01em;
}

.price-box { margin-bottom: 8px; }
.premium-card-price {
  font-size: 18px; font-weight: 900; color: #d70018;
  margin: 0; line-height: 1;
}
.price-label {
  font-size: 11px; font-weight: 800; color: #94a3b8;
  text-transform: uppercase; letter-spacing: 0.05em;
  margin-bottom: 4px; display: block;
}

.premium-action-row {
  display: flex; gap: 10px; margin-top: auto;
}
.btn-custom-buy {
  flex: 1; background: #0071e3; color: #fff;
  border: none; height: 44px; border-radius: 12px;
  font-weight: 800; font-size: 13.5px;
  cursor: pointer; transition: all 0.3s;
}
.btn-custom-buy:hover {
  background: #0077ed;
  box-shadow: 0 8px 20px rgba(0,113,227,0.3);
  transform: scale(1.02);
}

.btn-custom-add {
  width: 44px; height: 44px; border-radius: 12px;
  border: 1.5px solid #e2e8f0; background: #fff;
  color: #64748b; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.3s;
}
.btn-custom-add:hover {
  border-color: #0071e3; color: #0071e3;
  background: #f0f7ff; transform: rotate(8deg);
}

.spin-dot, .spin-dot-dark {
  width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
.spin-dot-dark { border-color: rgba(0,0,0,0.1); border-top-color: #1e293b; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .premium-card { border-radius: 16px; padding: 10px; }
  .premium-card-img-box { height: 140px; border-radius: 14px; padding: 15px; margin-bottom: 0; }
  .card-body-inner { padding: 12px 4px 4px; }
  .premium-card-title { font-size: 13.5px; min-height: 2.7em; margin-bottom: 5px; }
  .premium-card-price { font-size: 16px; }
  .price-label { font-size: 10px; margin-bottom: 2px; }
  .price-box { margin-bottom: 10px; }
  .btn-custom-buy, .btn-custom-add { height: 36px; border-radius: 10px; font-size: 11px; }
  .btn-custom-add { width: 36px; }
}
</style>
