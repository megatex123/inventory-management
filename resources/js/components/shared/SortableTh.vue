<template>
  <th class="sortable-th" @click="$emit('sort', sortKey)">
    {{ label }}
    <i class="fas ml-1" :class="arrowClass"></i>
  </th>
</template>

<script>
export default {
  name: 'SortableTh',
  props: {
    label: {
      type: String,
      required: true,
    },
    sortKey: {
      type: String,
      required: true,
    },
    currentSort: {
      type: Object,
      default: () => ({ key: '', dir: 'asc' }),
      // { key: string, dir: 'asc' | 'desc' }
    },
  },
  computed: {
    isActive() {
      return this.currentSort && this.currentSort.key === this.sortKey;
    },
    arrowClass() {
      if (!this.isActive) return 'fa-sort text-muted';
      return this.currentSort.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    },
  },
};
</script>

<style scoped>
.sortable-th {
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
}
.sortable-th:hover {
  background-color: rgba(0, 0, 0, 0.03);
}
</style>
