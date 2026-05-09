import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Public
    { path: '/', name: 'home', component: () => import('../views/ProductListView.vue') },
    { path: '/products', name: 'products', component: () => import('../views/ProductListView.vue') },
    { path: '/login', name: 'login', component: () => import('../views/LoginView.vue'), meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: () => import('../views/RegisterView.vue'), meta: { guestOnly: true } },
    { path: '/products/:id', name: 'product-detail', component: () => import('../views/ProductDetailView.vue') },
    { path: '/cart', name: 'cart', component: () => import('../views/CartView.vue'), meta: { requiresAuth: true } },
    { path: '/checkout', name: 'checkout', component: () => import('../views/CheckoutView.vue'), meta: { requiresAuth: true } },
    { path: '/orders', name: 'orders', component: () => import('../views/OrderListView.vue'), meta: { requiresAuth: true } },
    { path: '/orders/:id', name: 'order-detail', component: () => import('../views/OrderDetailView.vue'), meta: { requiresAuth: true } },

    // Auth required
    { path: '/profile', name: 'profile', component: () => import('../views/HomeView.vue'), meta: { requiresAuth: true } },

    // Admin
    { 
      path: '/admin', 
      name: 'admin-dashboard',
      component: () => import('../views/HomeView.vue'),
      meta: { requiresAdmin: true }
    },

    // 404
    { path: '/:pathMatch(.*)*', name: 'not-found', redirect: '/' },
  ],
  scrollBehavior() { return { top: 0 } }
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const token = authStore.token
  const user = authStore.user

  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }
  
  if (to.meta.requiresAdmin && (!token || user?.role !== 'admin')) {
    return next({ name: 'home' })
  }
  
  if (to.meta.guestOnly && token) {
    return next({ name: 'home' })
  }

  next()
})

export default router
