<template>
  <div class="d-flex justify-content-between align-items-center flex-wrap" v-if="meta.total > 0">
    <small class="text-muted mb-2 mb-md-0">Showing {{ rangeStart }}&ndash;{{ rangeEnd }} of {{ meta.total }}</small>
    <div class="d-flex align-items-center flex-wrap">
      <nav>
        <ul class="pagination pagination-sm mb-0 mr-3">
          <li class="page-item" :class="{ disabled: isFirstPage }">
            <button type="button" class="page-link" @click="goTo(1)" :disabled="isFirstPage" aria-label="First page">&laquo;</button>
          </li>
          <li class="page-item" :class="{ disabled: isFirstPage }">
            <button type="button" class="page-link" @click="goTo(meta.current_page - 1)" :disabled="isFirstPage" aria-label="Previous page">&lsaquo;</button>
          </li>

          <li class="page-item" v-if="showFirstPageButton">
            <button type="button" class="page-link" @click="goTo(1)">1</button>
          </li>
          <li class="page-item disabled" v-if="showStartEllipsis"><span class="page-link">&hellip;</span></li>

          <li class="page-item" v-for="page in pageNumbers" :key="page" :class="{ active: page === meta.current_page }">
            <button type="button" class="page-link" @click="goTo(page)">{{ page }}</button>
          </li>

          <li class="page-item disabled" v-if="showEndEllipsis"><span class="page-link">&hellip;</span></li>
          <li class="page-item" v-if="showLastPageButton">
            <button type="button" class="page-link" @click="goTo(meta.last_page)">{{ meta.last_page }}</button>
          </li>

          <li class="page-item" :class="{ disabled: isLastPage }">
            <button type="button" class="page-link" @click="goTo(meta.current_page + 1)" :disabled="isLastPage" aria-label="Next page">&rsaquo;</button>
          </li>
          <li class="page-item" :class="{ disabled: isLastPage }">
            <button type="button" class="page-link" @click="goTo(meta.last_page)" :disabled="isLastPage" aria-label="Last page">&raquo;</button>
          </li>
        </ul>
      </nav>
      <div class="dropdown">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary dropdown-toggle"
          @click.stop="perPageMenuOpen = !perPageMenuOpen"
        >
          Show {{ meta.per_page }} items
        </button>
        <div class="dropdown-menu dropdown-menu-right" :class="{ show: perPageMenuOpen }">
          <a
            v-for="size in perPageOptions"
            :key="size"
            href="#"
            class="dropdown-item"
            :class="{ active: size === meta.per_page }"
            @click.prevent="selectPerPage(size)"
          >
            Show {{ size }} items
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PaginationControl',
  props: {
    meta: {
      type: Object,
      required: true,
      // { total, per_page, current_page, last_page }
    },
  },
  data() {
    return {
      perPageMenuOpen: false,
    };
  },
  computed: {
    isFirstPage() {
      return this.meta.current_page <= 1;
    },
    isLastPage() {
      return this.meta.current_page >= this.meta.last_page;
    },
    rangeStart() {
      if (this.meta.total === 0) return 0;
      return (this.meta.current_page - 1) * this.meta.per_page + 1;
    },
    rangeEnd() {
      return Math.min(this.meta.current_page * this.meta.per_page, this.meta.total);
    },
    startPage() {
      return Math.max(1, this.meta.current_page - 2);
    },
    endPage() {
      return Math.min(this.meta.last_page, this.meta.current_page + 2);
    },
    pageNumbers() {
      const pages = [];
      for (let i = this.startPage; i <= this.endPage; i++) pages.push(i);
      return pages;
    },
    showFirstPageButton() {
      return this.startPage > 1;
    },
    showStartEllipsis() {
      return this.startPage > 2;
    },
    showLastPageButton() {
      return this.endPage < this.meta.last_page;
    },
    showEndEllipsis() {
      return this.endPage < this.meta.last_page - 1;
    },
    perPageOptions() {
      const standard = [10, 20, 50, 100];
      if (standard.includes(this.meta.per_page)) return standard;
      // Current value isn't one of the standard sizes (e.g. a page still on
      // the old default of 15) -- include it so the select always reflects
      // reality instead of silently showing the wrong size.
      return [...standard, this.meta.per_page].sort((a, b) => a - b);
    },
  },
  methods: {
    goTo(page) {
      const clamped = Math.max(1, Math.min(page, this.meta.last_page));
      if (clamped === this.meta.current_page) return;
      this.$emit('page-change', clamped);
    },
    selectPerPage(size) {
      this.perPageMenuOpen = false;
      if (size === this.meta.per_page) return;
      this.$emit('per-page-change', size);
    },
    handleOutsideClick(event) {
      if (this.perPageMenuOpen && !this.$el.contains(event.target)) {
        this.perPageMenuOpen = false;
      }
    },
  },
  mounted() {
    document.addEventListener('click', this.handleOutsideClick);
  },
  beforeDestroy() {
    document.removeEventListener('click', this.handleOutsideClick);
  },
};
</script>
