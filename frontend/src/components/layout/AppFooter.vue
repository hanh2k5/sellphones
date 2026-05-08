<template>
  <footer class="site-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <!-- Brand -->
        <div class="footer-col">
          <div class="footer-brand">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-.5-.22-1.07-.46-1.92-.46-.86 0-1.4.23-1.96.46-1.04.46-2.02.58-2.97-.4C4.16 17.38 3.5 12.06 5.5 8.6c1-1.74 2.76-2.82 4.7-2.85 1.1-.02 1.95.43 2.6.43.6 0 1.63-.53 2.92-.4 1.34.13 2.37.62 2.9 1.4-2.73 1.63-2.3 5.15.44 6.27-.6 1.54-1.37 3.08-2.02 3.84zM12.03 5.75c-.2-.02-.4-.02-.6-.02.05-2.26 1.9-4.2 4.14-4.23.23 0 .46.03.7.05-2.1 2.38-4.04 4.2-4.24 4.2z"/></svg>
            <span>SELLPHONES</span>
          </div>
          <p class="footer-desc">{{ i18n.t('footer.brand_desc') }}</p>
          <div class="footer-socials">
            <button class="social-btn">f</button>
            <button class="social-btn">▶</button>
          </div>
        </div>
        <!-- Products -->
        <div class="footer-col">
          <h6 class="footer-heading">{{ i18n.t('footer.products') }}</h6>
          <ul class="footer-list">
            <li><router-link :to="{ path: '/products', query: { search: 'iPhone' } }">iPhone</router-link></li>
            <li><router-link :to="{ path: '/products', query: { search: 'Samsung' } }">Samsung</router-link></li>
          </ul>
        </div>
        <!-- Policy -->
        <div class="footer-col">
          <h6 class="footer-heading">{{ i18n.t('footer.policy') }}</h6>
          <ul class="footer-list">
            <li><router-link to="/products">{{ i18n.t('footer.warranty') }}</router-link></li>
            <li><router-link to="/products">{{ i18n.t('footer.returns') }}</router-link></li>
          </ul>
        </div>
        <!-- Contact -->
        <div class="footer-col">
          <h6 class="footer-heading">{{ i18n.t('footer.contact') }}</h6>
          <ul class="footer-list">
            <li>📞 1800 6969</li>
            <li>✉️ cskh@sellphones.vn</li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <span>{{ i18n.t('footer.copyright') }}</span>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { useI18nStore } from '../../stores/i18n'
const i18n = useI18nStore()
</script>

<style scoped>
.site-footer { background: #1c1c1e; color: #fff; padding: 48px 0 24px; margin-top: 80px; border-top: 1px solid rgba(255,255,255,0.1); }
.footer-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; padding-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.08); }
.footer-brand { display: flex; align-items: center; gap: 8px; font-size: 18px; font-weight: 700; margin-bottom: 12px; }
.footer-desc { font-size: 13px; color: rgba(255,255,255,0.45); line-height: 1.6; max-width: 240px; }
.footer-socials { display: flex; gap: 10px; margin-top: 20px; }
.social-btn { width: 38px; height: 38px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; transition: 0.2s; }
.social-btn:hover { background: rgba(255,255,255,0.1); }
.footer-heading { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #fff; margin-bottom: 16px; }
.footer-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
.footer-list li, .footer-list a { font-size: 13px; color: rgba(255,255,255,0.5); transition: 0.15s; }
.footer-list a:hover { color: #fff; }
.footer-bottom { padding-top: 24px; text-align: center; font-size: 12px; color: rgba(255,255,255,0.3); }

@media (max-width: 768px) {
  .site-footer { padding: 40px 0 20px; margin-top: 40px; }
  .footer-grid { grid-template-columns: 1fr 1fr; gap: 30px; }
  .footer-col:first-child { grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 20px; }
  .footer-desc { max-width: none; }
}
</style>

