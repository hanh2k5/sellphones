import axios from 'axios';
import { getApiBaseUrl } from '../utils/url';

const api = axios.create({
    baseURL: `${getApiBaseUrl()}/api`,
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
    }
});

// Interceptor: tự động gắn token Bearer vào mọi request
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    const locale = localStorage.getItem('locale') || 'vi';
    config.headers['Accept-Language'] = locale;
    return config;
});

// Interceptor: xử lý lỗi 401/403 global (đá người dùng nếu bị khóa hoặc hết hạn)
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const message = error.response?.data?.message || '';
        
        // 401 (Hết hạn token) hoặc 403 + bị khóa thì mới đá ra
        const isLocked = status === 403 && (message.includes('khóa') || message.includes('locked'));
        
        if (status === 401 || isLocked) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;