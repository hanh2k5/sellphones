<template>
  <div class="admin-card" style="padding: 24px; min-height: 500px; display: flex; flex-direction: column;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
      <h2 class="font-outfit" style="font-size: 18px; font-weight: 800; margin: 0;">
        {{ i18n.t('admin.category_structure') }}
      </h2>
      
      <!-- 2. Bộ lọc & Tìm kiếm: Sử dụng @submit.prevent trong Vue -->
      <form @submit.prevent="handleSearch" style="display: flex; gap: 8px; max-width: 300px; width: 100%;">
        <div style="position: relative; flex: 1;">
          <input 
            v-model="searchVal"
            type="text" 
            placeholder="Tìm kiếm danh mục..." 
            class="form-input"
            style="padding-right: 36px; height: 38px; font-size: 13px;"
          />
          <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #86868b; pointer-events: none;">🔍</span>
        </div>
        <button type="submit" class="btn-primary" style="height: 38px; padding: 0 16px; font-size: 13px;">
          Tìm
        </button>
      </form>
    </div>

    <!-- Loading State -->
    <div v-if="loading" style="display: flex; justify-content: center; align-items: center; flex: 1; padding: 40px 0;">
      <div class="spinner"></div>
    </div>

    <template v-else>
      <!-- 2. Bộ lọc & Tìm kiếm: Hiện thông báo "Không tìm thấy danh mục phù hợp." nếu danh sách trống -->
      <div v-if="categories.length === 0" class="table-empty" style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 60px 0;">
        <div class="empty-icon">🔎</div>
        <p style="font-weight: 700; color: #ef4444; margin-top: 12px;">Không tìm thấy danh mục phù hợp.</p>
      </div>

      <div v-else style="flex: 1; overflow-x: auto;">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Tên danh mục & Slug</th>
              <th>Danh mục cha</th>
              <th>Trạng thái</th>
              <th style="text-align: right;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="cat in categories" :key="cat.id">
              <!-- Parent/Main Category Row -->
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Toggle Expand Button with '+' and '−' -->
                    <button 
                      v-if="cat.children && cat.children.length > 0"
                      type="button"
                      @click="toggleExpand(cat.id)"
                      class="btn-toggle"
                      :title="isExpanded(cat.id) ? 'Thu gọn' : 'Mở rộng'"
                    >
                      {{ isExpanded(cat.id) ? '−' : '+' }}
                    </button>
                    <!-- Placeholder spacing if category has no children -->
                    <span v-else style="width: 24px; display: inline-block;"></span>

                    <div>
                      <div style="font-weight: 700; color: #1d1d1f;">{{ cat.name }}</div>
                      <!-- 4. Hiển thị URL Slug kèm theo tên để Admin kiểm tra tối ưu SEO -->
                      <div style="font-size: 11px; color: #2563eb; font-weight: 600; margin-top: 2px;">
                        /{{ cat.slug }}
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span v-if="cat.parent" class="parent-badge">
                    {{ cat.parent.name }}
                  </span>
                  <span v-else style="color: #86868b; font-style: italic; font-size: 13px;">
                    -- Gốc --
                  </span>
                </td>
                <td>
                  <!-- 5. Trạng thái hiển thị: Sử dụng Badge màu Xanh/Xám -->
                  <span :class="['status-badge', cat.is_active ? 'active' : 'inactive']">
                    {{ cat.is_active ? 'Kích hoạt' : 'Ẩn' }}
                  </span>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button @click="$emit('edit', cat)" class="btn-action info-mini" title="Sửa">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </button>
                    <button @click="$emit('delete', cat)" class="btn-action danger-mini" title="Xóa">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Subcategory Rows (Shown when parent is expanded) -->
              <tr 
                v-if="isExpanded(cat.id)"
                v-for="child in cat.children" 
                :key="child.id"
                class="child-row"
              >
                <td style="padding-left: 36px;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Indent connector icon -->
                    <span style="color: #cbd5e1; font-weight: bold; font-size: 16px; user-select: none;">└─</span>
                    <div>
                      <div style="font-weight: 600; color: #334155;">{{ child.name }}</div>
                      <div style="font-size: 11px; color: #2563eb; font-weight: 600; margin-top: 2px;">
                        /{{ child.slug }}
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="parent-badge" style="background-color: #f1f5f9; color: #475569;">
                    {{ cat.name }}
                  </span>
                </td>
                <td>
                  <span :class="['status-badge', child.is_active ? 'active' : 'inactive']">
                    {{ child.is_active ? 'Kích hoạt' : 'Ẩn' }}
                  </span>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button @click="$emit('edit', child)" class="btn-action info-mini" title="Sửa">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </button>
                    <button @click="$emit('delete', child)" class="btn-action danger-mini" title="Xóa">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- 3. Phân trang (Pagination) Component -->
      <div v-if="pagination.last_page > 1" class="pagination-container">
        <button 
          :disabled="pagination.current_page === 1" 
          @click="changePage(pagination.current_page - 1)"
          class="btn-page-arrow"
        >
          &larr;
        </button>

        <div style="display: flex; gap: 6px;">
          <button 
            v-for="page in pages" 
            :key="page" 
            @click="changePage(page)"
            :class="['btn-page-number', { active: page === pagination.current_page }]"
          >
            {{ page }}
          </button>
        </div>

        <button 
          :disabled="pagination.current_page === pagination.last_page" 
          @click="changePage(pagination.current_page + 1)"
          class="btn-page-arrow"
        >
          &rarr;
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useI18nStore } from '../../stores/i18n'

const props = defineProps({
  categories: { type: Array, default: () => [] },
  pagination: { type: Object, default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 }) },
  loading: { type: Boolean, default: false },
  searchQuery: { type: String, default: '' },
  currentPage: { type: Number, default: 1 }
})

const emit = defineEmits(['edit', 'delete', 'update:searchQuery', 'update:currentPage', 'search', 'page-change'])
const i18n = useI18nStore()

const searchVal = ref(props.searchQuery)
const expandedIds = ref([])

watch(() => props.searchQuery, (newVal) => {
  searchVal.value = newVal
})

const pages = computed(() => {
  const list = []
  for (let i = 1; i <= props.pagination.last_page; i++) {
    list.push(i)
  }
  return list
})

function toggleExpand(id) {
  const idx = expandedIds.value.indexOf(id)
  if (idx === -1) {
    expandedIds.value.push(id)
  } else {
    expandedIds.value.splice(idx, 1)
  }
}

function isExpanded(id) {
  return expandedIds.value.includes(id)
}

function handleSearch() {
  emit('update:searchQuery', searchVal.value)
  emit('update:currentPage', 1)
  emit('search')
}

function changePage(page) {
  emit('update:currentPage', page)
  emit('page-change', page)
}
</script>

<style scoped>
.premium-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
.premium-table th {
  text-align: left;
  padding: 12px 16px;
  font-weight: 800;
  font-size: 13px;
  color: #64748b;
  border-bottom: 2px solid #f1f5f9;
}
.premium-table td {
  padding: 14px 16px;
  font-size: 14px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}
.premium-table tbody tr:hover {
  background-color: #f8fafc;
}
.parent-badge {
  background-color: #eff6ff;
  color: #1e40af;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
}
.status-badge {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 800;
  display: inline-block;
}
.status-badge.active {
  background-color: #e6f4ea;
  color: #137333;
}
.status-badge.inactive {
  background-color: #f1f3f4;
  color: #5f6368;
}
.info-mini, .danger-mini {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  background-color: white;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}
.info-mini:hover {
  background-color: #eff6ff;
  color: #2563eb;
  border-color: #bfdbfe;
}
.danger-mini:hover {
  background-color: #fef2f2;
  color: #ef4444;
  border-color: #fecaca;
}
.btn-toggle {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  background-color: #f8fafc;
  color: #475569;
  font-weight: bold;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
  line-height: 1;
}
.btn-toggle:hover {
  background-color: #eff6ff;
  color: #2563eb;
  border-color: #3b82f6;
}
.child-row {
  background-color: #fafbfd;
}
.child-row:hover {
  background-color: #f1f5f9 !important;
}
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 20px;
  border-top: 1px solid #f1f5f9;
}
.btn-page-arrow {
  height: 36px;
  width: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background-color: white;
  cursor: pointer;
  font-weight: bold;
}
.btn-page-arrow:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-page-number {
  height: 36px;
  padding: 0 14px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background-color: white;
  cursor: pointer;
  font-weight: 700;
  font-size: 13px;
  color: #475569;
}
.btn-page-number.active {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: white;
  border-color: #2563eb;
}
.spinner {
  width: 32px;
  height: 32px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
