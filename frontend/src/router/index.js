import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'


const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Báo cáo 4.3.8: Hiển thị danh sách sản phẩm (Phân trang và Tìm kiếm) - Đặng Văn Hà
    { path: '/', name: 'home', component: () => import('../views/ProductListView.vue') },
    { path: '/products', name: 'products', component: () => import('../views/ProductListView.vue') },
    
    // Báo cáo 4.2.6 & 4.2.5: Đăng nhập và Đăng ký hệ thống - Nguyễn Duy Khang
    { path: '/login', name: 'login', component: () => import('../views/LoginView.vue'), meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: () => import('../views/RegisterView.vue'), meta: { guestOnly: true } },
    
    // Báo cáo 4.3.9: Hiển thị chi tiết sản phẩm (Eager Loading & Multimedia) - Đặng Văn Hà
    { path: '/products/:id', name: 'product-detail', component: () => import('../views/ProductDetailView.vue') },
    
    // Báo cáo 4.1.1 & 4.1.3: Quản lý Giỏ hàng - Phan Đình Hạnh
    { path: '/cart', name: 'cart', component: () => import('../views/CartView.vue'), meta: { requiresAuth: true } },
    
    // Báo cáo 4.1.4: Thanh toán đơn hàng (Checkout) - Phan Đình Hạnh
    { path: '/checkout', name: 'checkout', component: () => import('../views/CheckoutView.vue'), meta: { requiresAuth: true } },
    
    // Báo cáo 4.1.6 & 4.1.7: Lịch sử và Chi tiết Đơn hàng - Phan Đình Hạnh
    { path: '/orders', name: 'orders', component: () => import('../views/OrderListView.vue'), meta: { requiresAuth: true } },
    { path: '/orders/:id', name: 'order-detail', component: () => import('../views/OrderDetailView.vue'), meta: { requiresAuth: true } },
    
    // Báo cáo 4.1.14: Thanh toán qua cổng ví điện tử (Fake MoMo UI) - Phan Đình Hạnh
    { path: '/payment/momo', name: 'payment.momo', component: () => import('../views/MomoPaymentView.vue'), meta: { requiresAuth: true } },

    // Báo cáo 4.2.9: Cập nhật hồ sơ cá nhân - Nguyễn Duy Khang
    { path: '/profile', name: 'profile', component: () => import('../views/HomeView.vue'), meta: { requiresAuth: true } },

    // Khu vực Quản trị (Admin)
    {
      path: '/admin',
      component: () => import('../views/admin/AdminLayout.vue'),
      meta: { requiresAdmin: true },
      children: [
        { path: '', name: 'admin-dashboard', component: () => import('../views/admin/AdminDashboardView.vue') },
        // Báo cáo 4.1.8: Duyệt đơn hàng - Phan Đình Hạnh
        { path: 'orders', name: 'admin-orders', component: () => import('../views/admin/OrderManagementView.vue') },
        // Báo cáo 4.3.1 -> 4.3.4: Quản lý Danh mục - Đặng Văn Hà
        { path: 'categories', name: 'admin-categories', component: () => import('../views/admin/CategoryManageView.vue') },
      ]
    },

    // 404
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundView.vue') },
  ],
  scrollBehavior() { return { top: 0 } }
})

// Báo cáo 4.2.8: Phân quyền Admin và User (Bảo vệ Router)
router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  const token = authStore.token
  const user = authStore.user

  if (to.meta.requiresAuth && !token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  
  if (to.meta.requiresAdmin && (!token || user?.role !== 'admin')) {
    return { name: 'home' }
  }
  
  if (to.meta.guestOnly && token) {
    return { name: 'home' }
  }
})

export default router
