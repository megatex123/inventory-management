// resources/js/mixins/sortablePagination.js
//
// Shared onSort/onPageChange/onPerPageChange handlers for any list page
// using the SortableTh + PaginationControl pattern. A consuming component
// must define, in its own data(): `sortState: { key, dir }`, `meta: {
// current_page, per_page, ... }`, and a `fetchList()` method that reads
// both of those to issue the actual API call.
export default {
  methods: {
    onSort(key) {
      if (this.sortState.key === key) {
        this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortState = { key, dir: 'asc' };
      }
      this.fetchList();
    },
    onPageChange(page) {
      this.meta.current_page = page;
      this.fetchList();
    },
    onPerPageChange(perPage) {
      this.meta.per_page = perPage;
      this.meta.current_page = 1;
      this.fetchList();
    },
  },
};
