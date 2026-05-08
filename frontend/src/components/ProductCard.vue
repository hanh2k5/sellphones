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
      </div>

      <div class="card-body-inner">
        <h2 class="premium-card-title">{{ product.name }}</h2>
        <p class="premium-card-price">{{ i18n.t('product.from_price') }} {{ fmtPrice(product.price) }}</p>

        <!-- No Action buttons for simple display -->
        <div class="premium-action-row empty-row">
        </div>
      </div>
    </article>
  </div>
</template>

<script setup>
import { useI18nStore } from '../stores/i18n'
import { useUtils } from '../composables/useUtils'

const { fmtPrice, getImageUrl } = useUtils()
const props = defineProps({ product: { type: Object, required: true } })
const i18n = useI18nStore()

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

.premium-action-row.empty-row { height: 10px; }

@media (max-width: 768px) {
  .premium-card { border-radius: 20px; padding: 12px; }
  .premium-card-img-box { height: 160px; }
  .premium-card-title { font-size: 14px; height: 36px; }
  .premium-card-price { font-size: 16px; }
}
</style>
