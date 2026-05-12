<template>
  <footer class="site-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <!-- Brand -->
        <div class="footer-col">
          <div class="footer-brand">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="url(#footerBrandGradient)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <defs>
                <linearGradient id="footerBrandGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#2563eb;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#4f46e5;stop-opacity:1" />
                </linearGradient>
              </defs>
              <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
              <path d="M10 5h4" stroke-width="2" stroke="url(#footerBrandGradient)"></path>
              <line x1="12" y1="18" x2="12.01" y2="18"></line>
            </svg>
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

