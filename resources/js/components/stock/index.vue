<template lang="">
    <div>

        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">

<div class="card">
                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
<router-link to="/product/create" class="btn btn-primary ml-3">Add Product</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Stock List</h5>
                  <button
                      @click="showFilters = !showFilters"
                      class="btn btn-sm btn-outline-secondary"
                  >
                      <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                      {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
                <transition name="filter-panel">
                <div class="card-body py-2" v-if="showFilters">
                    <column-search-panel
                        :columns="filterColumns"
                        v-model="filters"
                        :visible="true"
                    />
                </div>
                </transition>
     <div class="table-responsive">
                  <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                      <tr>
                        <th>Photo</th>
                        <sortable-th label="Name" sort-key="product_name" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Code" sort-key="product_code" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Category" sort-key="category" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Price (RM)" sort-key="price" :current-sort="sortState" @sort="onSort" />
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="loading">
                        <td colspan="7" class="text-center py-4">Loading...</td>
                      </tr>
                      <tr v-else-if="products.length === 0">
                        <td colspan="7" class="text-center py-4">No products found.</td>
                      </tr>
                      <tr v-for='data in products' :key="data.id" v-else>
                        <td><img :src="data.image" class="img-fluid" width='40px' height='40px' /></td>
                        <td>{{data.product_name}}</td>
                        <td>{{data.product_code}}</td>
                        <td>{{data.cat_name}}</td>
                        <td>{{data.price}}</td>
                        <td>
                           <span v-if='data.product_qty>=1' class="badge badge-pill badge-success">Stock Available</span>
                           <span v-else='' class="badge badge-pill badge-danger">Stock Out</span>
                        </td>
                        <td>
                            <router-link :to="{name:'stockedit', params:{id:data.id}}" class="btn btn-sm   btn-primary">Edit </router-link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="card-footer">
                    <pagination-control
                        :meta="meta"
                        @page-change="onPageChange"
                        @per-page-change="onPerPageChange"
                    />
                </div>
                </div>
                                    <div class="text-center">
                                    </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
    import PaginationControl from '../shared/PaginationControl.vue';
    import SortableTh from '../shared/SortableTh.vue';
    import sortablePaginationMixin from '../../mixins/sortablePagination';

    const EMPTY_FILTERS = {
        product_name: '',
        category_id: '',
        status: '',
    };

    export default {
        mixins: [sortablePaginationMixin],
        components: { ColumnSearchPanel, PaginationControl, SortableTh },
        data() {
            return {
                products: [],
                categories: [],
                loading: true,
                showFilters: false,
                filterColumns: [
                    { key: 'product_name', label: 'Name', type: 'text' },
                    {
                        key: 'category_id',
                        label: 'Category',
                        type: 'select',
                        options: [],
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        type: 'select',
                        options: [
                            { value: 'available', label: 'Stock Available' },
                            { value: 'out', label: 'Stock Out' },
                        ],
                    },
                ],
                filters: { ...EMPTY_FILTERS },
                sortState: { key: 'product_name', dir: 'asc' },
                meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
            }
        },
        methods: {
            fetchList() {
                this.loading = true;
                axios.get('/api/product', {
                    params: {
                        page: this.meta.current_page,
                        per_page: this.meta.per_page,
                        sort_by: this.sortState.key,
                        sort_dir: this.sortState.dir,
                        name: this.filters.product_name,
                        category_id: this.filters.category_id,
                        status: this.filters.status,
                    },
                })
                .then(res => {
                    this.products = res.data.data;
                    this.meta = res.data.meta;
                    this.loading = false;
                })
                .catch(err => {
                    this.loading = false;
                    notification.error();
                });
            },
            fetchCategories() {
                axios.get('/api/categories/all')
                .then(res => {
                    this.categories = res.data;
                    this.filterColumns.find(c => c.key === 'category_id').options =
                        this.categories.map(c => ({ value: c.id, label: c.name }));
                })
                .catch(err => {
                    notification.error();
                });
            },
        },
        watch: {
            filters: {
                handler() {
                    this.meta.current_page = 1;
                    this.fetchList();
                },
                deep: true,
            },
        },
        created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
            this.fetchCategories();
            this.fetchList();
        },
    }
</script>

<style scoped>
.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
