<template>
  <div class="admin-card" style="padding: 24px;">
    <h2 class="font-outfit" style="font-size: 18px; font-weight: 800; margin-bottom: 20px;">
      {{ i18n.t('admin.category_structure') }}
    </h2>
    <div v-if="categories.length > 0" style="display: flex; flex-direction: column; gap: 12px;">
      <div v-for="cat in categories" :key="cat.id" class="category-tree-node">
        <!-- Parent Category -->
        <div class="category-node-header">
          <div class="node-info">
            <div class="node-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-500"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/></svg>
            </div>
            <div class="node-text">
              <p class="node-name">{{ cat.name }}</p>
              <p class="node-slug">/{{ cat.slug }}</p>
            </div>
            <span v-if="!cat.is_active" class="status-badge badge-danger">HIDDEN</span>
          </div>
          <div class="action-row">
            <button @click="$emit('edit', cat)" class="btn-action info">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </button>
            <button @click="$emit('delete', cat)" class="btn-action danger">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </button>
          </div>
        </div>

        <!-- Child Categories -->
        <div v-if="cat.children?.length" class="node-children-container">
          <div class="node-children-list">
            <div v-for="child in cat.children" :key="child.id" class="child-node">
              <div class="node-info">
                <div class="node-text">
                  <p class="child-name">{{ child.name }}</p>
                  <p class="child-slug">/{{ child.slug }}</p>
                </div>
                <span v-if="!child.is_active" class="status-badge badge-danger-mini">HIDDEN</span>
              </div>
              <div class="action-row">
                <button @click="$emit('edit', child)" class="btn-action info-mini">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </button>
                <button @click="$emit('delete', child)" class="btn-action danger-mini">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="table-empty">
      <div class="empty-icon">📭</div>
      <p>{{ i18n.t('admin.no_categories') }}</p>
    </div>
  </div>
</template>

<script setup>
import { useI18nStore } from '../../stores/i18n'

defineProps({
  categories: { type: Array, default: () => [] }
})

defineEmits(['edit', 'delete'])
const i18n = useI18nStore()
</script>

<style scoped>
.category-tree-node { border: 1px solid #f0f0f0; border-radius: 16px; overflow: hidden; }
.category-node-header { display: flex; align-items: center; justify-content: space-between; padding: 16px; background: #fafafa; border-bottom: 1px solid #f0f0f0; }
.node-info { display: flex; align-items: center; gap: 12px; }
.node-icon { width: 32px; height: 32px; background: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid #e5e5ea; }
.node-name { font-size: 14px; font-weight: 700; margin: 0; color: #1d1d1f; }
.node-slug { font-size: 12px; margin: 0; color: #86868b; }
.node-children-container { padding: 12px; background: #fff; }
.node-children-list { display: flex; flex-direction: column; gap: 8px; padding-left: 20px; border-left: 2px solid #f0f0f0; margin-left: 16px; }
.child-node { display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #fafafa; border-radius: 12px; border: 1px solid #f0f0f0; }
.child-name { font-size: 13px; font-weight: 700; margin: 0; color: #1d1d1f; }
.child-slug { font-size: 10px; margin: 0; color: #86868b; }
.badge-danger { margin-left: 8px; font-size: 9px; padding: 2px 6px; }
.badge-danger-mini { font-size: 8px; padding: 1px 4px; margin-left: 4px; }
.info-mini, .danger-mini { width: 28px; height: 28px; }
</style>
