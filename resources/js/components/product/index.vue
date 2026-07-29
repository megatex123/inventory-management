<template>
  <div class="row justify-content-center">
    <div class="card">
      <!-- Card Header -->
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h2 class="mb-1 font-weight-bold text-primary">Product List</h2>
        <router-link to="/product/create" class="btn btn-primary m-0">Add Product</router-link>
      </div>

      <!-- Filter Section -->
      <div class="row px-3 mt-3">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body py-2">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter mr-2"></i>Filters
                  </h6>
                </div>
                <div class="col-md-6 text-right">
                  <button
                    @click="showFilters = !showFilters"
                    class="btn btn-sm btn-outline-secondary mr-1"
                  >
                    <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                  </button>
                </div>
              </div>

              <transition name="filter-panel">
              <div v-if="showFilters">
                <div class="text-right mb-2">
                  <button
                    @click="clearFilters"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!hasActiveFilters"
                  >
                    <i class="fas fa-times mr-1"></i>Clear Filters
                  </button>
                </div>
                <column-search-panel
                  :columns="filterColumns"
                  v-model="filters"
                  :visible="true"
                />

                <div class="row">
                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Name Starts With</label>
                    <select v-model="filters.nameStartsWith" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="letter in nameStartingLetters" :key="letter" :value="letter">{{ letter }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Code Starts With</label>
                    <select v-model="filters.codeStartsWith" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="letter in codeStartingLetters" :key="letter" :value="letter">{{ letter }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Category</label>
                    <select v-model="filters.categoryId" class="form-control form-control-sm">
                      <option value="">All Categories</option>
                      <option v-for="category in allCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Status</label>
                    <select v-model="filters.status" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option value="available">Stock Available</option>
                      <option value="out">Stock Out</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Min Price (RM)</label>
                    <input v-model="filters.minPrice" type="number" step="0.01" class="form-control form-control-sm" placeholder="Min">
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Max Price (RM)</label>
                    <input v-model="filters.maxPrice" type="number" step="0.01" class="form-control form-control-sm" placeholder="Max">
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Year</label>
                    <select v-model="filters.year" class="form-control form-control-sm">
                      <option value="">All</option>
                      <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                    </select>
                  </div>

                  <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">Month</label>
                    <select v-model="filters.month" class="form-control form-control-sm" :disabled="!filters.year">
                      <option value="">All</option>
                      <option v-for="(monthName, index) in monthNames" :key="index" :value="index + 1">{{ monthName }}</option>
                    </select>
                  </div>
                </div>

                <div class="row mt-2" v-if="hasActiveFilters">
                  <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                      <span v-for="(value, key) in activeFilters" :key="key" class="badge badge-info">
                        {{ getFilterLabel(key, value) }}
                        <button @click="removeFilter(key)" class="badge badge-light ml-1 p-0 border-0" style="background: transparent;">
                          <i class="fas fa-times"></i>
                        </button>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              </transition>
            </div>
          </div>
        </div>
      </div>

      <br>

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
              <sortable-th label="Product Quantity" sort-key="product_qty" :current-sort="sortState" @sort="onSort" />
              <sortable-th label="Created At" sort-key="created_at" :current-sort="sortState" @sort="onSort" />
              <th>Action</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
          </tbody>
          <tbody v-else>
            <tr v-for='data in products' :key="data.id">
              <td><img :src="data.image" class="img-fluid" width='40px' height='40px' /></td>
              <td>{{ data.product_name }}</td>
              <td>{{ data.product_code }}</td>
              <td>{{ data.cat_name }}</td>
              <td>{{ data.price }}</td>
              <td>
                <span v-if='data.product_qty >= 1' class="badge badge-pill badge-success">Stock Available</span>
                <span v-else class="badge badge-pill badge-danger">Stock Out</span>
              </td>
              <td>{{ data.product_qty }}</td>
              <td><small class="text-muted">{{ formatDate(data.created_at) }}</small></td>
              <td>
                <router-link :to="{name:'Productedit', params:{id:data.id}}" class="btn btn-sm btn-primary">Edit</router-link>
                <a href='javascript:void(0)' @click='deletePro(data.id)' class="btn btn-sm btn-danger">Delete</a>
              </td>
            </tr>
            <tr v-if="products.length === 0">
              <td colspan="9" class="text-center text-muted py-4">No product found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        <pagination-control :meta="meta" @page-change="onPageChange" @per-page-change="onPerPageChange" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
import PaginationControl from '../shared/PaginationControl.vue';
import SortableTh from '../shared/SortableTh.vue';
import sortablePaginationMixin from '../../mixins/sortablePagination';

const EMPTY_FILTERS = {
  name: '',
  code: '',
  nameStartsWith: '',
  codeStartsWith: '',
  categoryId: '',
  status: '',
  minPrice: '',
  maxPrice: '',
  year: '',
  month: ''
};

export default {
  mixins: [sortablePaginationMixin],
  components: { ColumnSearchPanel, PaginationControl, SortableTh },
  data() {
    return {
      products: [],
      allCategories: [],
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'code', label: 'Code', type: 'text' },
      ],
      filters: { ...EMPTY_FILTERS },
      sortState: { key: 'product_name', dir: 'asc' },
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
      nameStartingLetters: [],
      codeStartingLetters: [],
      availableYears: [],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
    }
  },
  methods: {
    fetchList() {
      this.loading = true;
      const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        name: this.filters.name,
        code: this.filters.code,
        name_starts_with: this.filters.nameStartsWith,
        code_starts_with: this.filters.codeStartsWith,
        category_id: this.filters.categoryId,
        status: this.filters.status,
        min_price: this.filters.minPrice,
        max_price: this.filters.maxPrice,
        year: this.filters.year,
        month: this.filters.month,
      };
      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      axios.get('/api/product', { params })
        .then(res => {
          this.products = res.data.data;
          this.meta = res.data.meta;
        })
        .catch(err => {
          console.error('Error fetching products:', err);
          notification.error();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchFilterOptions() {
      axios.get('/api/product/filter-options')
        .then(res => {
          this.nameStartingLetters = res.data.data.name_starting_letters;
          this.codeStartingLetters = res.data.data.code_starting_letters;
          this.availableYears = res.data.data.available_years;
        })
        .catch(err => {
          console.error('Error fetching filter options:', err);
        });
    },
    fetchAllCategories() {
      axios.get('/api/categories/all')
        .then(res => {
          this.allCategories = res.data;
        })
        .catch(err => {
          console.error('Error fetching categories:', err);
        })
    },
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    },
    deletePro(id){
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      })
      .then((result) => {
        if (result.value) {
          axios.delete("/api/product/"+id)
          .then(() => {
            Swal.fire(
              'Deleted!',
              'Your file has been deleted.',
              'success'
            )
            if (this.products.length === 1 && this.meta.current_page > 1) {
              this.meta.current_page -= 1;
            }
            this.fetchList();
            this.fetchFilterOptions();
          })
          .catch(() => {
            this.$router.push({ name:'Product'})
          })
        }
      })
    },
    clearFilters() {
      this.filters = { ...EMPTY_FILTERS };
    },
    removeFilter(filterKey) {
      if (this.filters[filterKey] !== undefined) {
        this.filters[filterKey] = '';
        if (filterKey === 'year') {
          this.filters.month = '';
        }
      }
    },
    getFilterLabel(key, value) {
      if (key === 'name') return `Name: "${value}"`;
      if (key === 'code') return `Code: "${value}"`;
      if (key === 'nameStartsWith') return `Name: ${value}`;
      if (key === 'codeStartsWith') return `Code: ${value}`;
      if (key === 'categoryId') {
        const category = this.allCategories.find(c => c.id == value);
        return `Category: ${category ? category.name : value}`;
      }
      if (key === 'status') return `Status: ${value === 'available' ? 'Stock Available' : 'Stock Out'}`;
      if (key === 'minPrice') return `Min Price: RM${value}`;
      if (key === 'maxPrice') return `Max Price: RM${value}`;
      if (key === 'year') return `Year: ${value}`;
      if (key === 'month') return `Month: ${this.monthNames[value - 1] || value}`;
      return `${key}: ${value}`;
    },
  },
  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(value => value !== '');
    },
    activeFilters() {
      const active = {};
      Object.keys(this.filters).forEach(key => {
        const value = this.filters[key];
        if (value !== '') {
          if (key === 'month' && !this.filters.year) {
            return;
          }
          active[key] = value;
        }
      });
      return active;
    }
  },
  watch: {
    filters: {
      handler() {
        this.meta.current_page = 1;
        this.fetchList();
      },
      deep: true
    },
    'filters.year': function(newYear) {
      if (!newYear) {
        this.filters.month = '';
      }
    }
  },
  created() {
    if (!User.loggedIn()) {
      this.$router.push({
        name: 'login'
      })
    };
    this.fetchFilterOptions();
    this.fetchAllCategories();
    this.fetchList();
  },
}
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle !important;
}

.badge-info {
    background-color: #36b9cc !important;
    font-size: 0.75em;
    padding: 0.4em 0.8em;
}

.d-flex.flex-wrap.gap-2 > * {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.d-flex.flex-wrap.gap-2 > *:last-child {
    margin-right: 0;
}

select:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.7;
}

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
