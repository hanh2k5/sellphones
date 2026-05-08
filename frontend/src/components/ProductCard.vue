<template>
  <div class="product-card-wrap reveal-item">
    <article
      class="premium-card static-card"
      tabindex="0"
      :aria-label="product.name"
    >
      <!-- Image Box: Exactly 180px, 1:1 ratio -->
      <div class="premium-card-img-box">
        <img
          :src="getImageUrl(product.hinh_anh)"
          :alt="product.name"
          @error="onImgError"
        />
        <!-- Out of stock badge -->
        <div v-if="product.stock <= 0" class="out-of-stock-badge">
          {{ i18n.locale === 'vi' ? 'HẾT HÀNG' : 'OUT OF STOCK' }}
        </div>
      </div>

      <div class="card-body-inner">
        <h2 class="premium-card-title">{{ product.name }}</h2>
        <p class="premium-card-price">{{ i18n.t('product.from_price') }} {{ fmtPrice(product.price) }}</p>

        <!-- Action Row: Add to Cart -->
        <div class="premium-action-row">
          <button 
            @click.stop="handleAddToCart" 
            class="btn-add-cart-minimal"
            :class="{ 'is-loading': isAdding, 'is-out-of-stock': product.stock <= 0 }"
            :disabled="isAdding || product.stock <= 0"
          >
            <template v-if="product.stock <= 0">
              <span>{{ i18n.locale === 'vi' ? 'Hết hàng' : 'Out of stock' }}</span>
            </template>
            <template v-else-if="isAdding">
              <span class="spinner-tiny"></span>
            </template>
            <template v-else>
              <svg class="cart-plus-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.56-7.43H5.12"/>
                <line x1="12" y1="9" x2="16" y2="9"/><line x1="14" y1="7" x2="14" y2="11"/>
              </svg>
              <span>{{ i18n.t('product.add_to_cart') || 'Thêm vào giỏ' }}</span>
            </template>
          </button>
        </div>
      </div>
    </article>
  </div>
</template>

<script setup>
import { useI18nStore } from '../stores/i18n'
import { useUtils } from '../composables/useUtils'
import { useCartStore } from '../stores/cart'
import { useToast } from '../composables/useToast'
import { ref } from 'vue'

const { fmtPrice, getImageUrl } = useUtils()
const props = defineProps({ product: { type: Object, required: true } })
const i18n = useI18nStore()
const cartStore = useCartStore()
const toast = useToast()

const isAdding = ref(false)

async function handleAddToCart() {
  isAdding.value = true
  try {
    await cartStore.addToCart(props.product.id)
    toast.success('Đã thêm vào giỏ hàng!')
  } catch (err) {
    toast.error(err.message || 'Lỗi khi thêm vào giỏ')
  } finally {
    isAdding.value = false
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
  display: flex; flex-direction: column;
  height: 100%; position: relative; overflow: hidden;
}

/* Image Presentation */
.premium-card-img-box {
  width: 100%; height: 180px; margin-bottom: 12px;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border-radius: 14px;
  padding: 8px; transition: 0.5s;
}
.premium-card-img-box img {
  max-width: 100%; max-height: 100%;
  object-fit: contain; transition: 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  filter: drop-shadow(0 10px 20px rgba(0,0,0,0.05));
}

.out-of-stock-badge {
  position: absolute; top: 12px; right: 12px;
  background: rgba(30, 41, 59, 0.9); color: #fff;
  padding: 4px 10px; border-radius: 8px; font-size: 10px; font-weight: 800;
  backdrop-filter: blur(4px);
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
  margin-bottom: 12px; text-align: left;
}

.premium-action-row { margin-top: auto; opacity: 0; transform: translateY(10px); transition: 0.3s; }
.premium-card:hover .premium-action-row { opacity: 1; transform: translateY(0); }

.btn-add-cart-minimal {
  width: 100%; padding: 12px; border-radius: 14px;
  background: #1e293b; color: #fff; border: none;
  font-weight: 700; font-size: 13px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: all 0.2s;
}
.btn-add-cart-minimal:hover { background: #0f172a; transform: scale(1.02); }
.btn-add-cart-minimal:active { transform: scale(0.98); }
.btn-add-cart-minimal:disabled { background: #94a3b8; cursor: not-allowed; }
.btn-add-cart-minimal.is-out-of-stock { background: #e2e8f0; color: #94a3b8; }

.spinner-tiny {
  width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .premium-card { border-radius: 20px; padding: 12px; }
  .premium-card-img-box { height: 160px; }
  .premium-card-title { font-size: 14px; height: 36px; }
  .premium-card-price { font-size: 16px; margin-bottom: 8px; }
  .premium-action-row { opacity: 1; transform: none; }
  .btn-add-cart-minimal { padding: 8px; font-size: 11px; border-radius: 10px; }
}
</style>
