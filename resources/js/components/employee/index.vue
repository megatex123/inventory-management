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
<router-link to="/employee/create" class="btn btn-primary ml-3">Add Employee</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
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
                        <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Phone" sort-key="phone" :current-sort="sortState" @sort="onSort" />
                        <th>Sallery</th>
                        <sortable-th label="Joining Date" sort-key="join_date" :current-sort="sortState" @sort="onSort" />
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for='data in employees' :key="data.id" >

                        <td><img :src="data.photo" class="img-fluid" width='40px' height='40px' /></td>
                        <td>{{data.name}}</td>
                        <td>{{data.phone}}</td>
                        <td>{{data.sallery}}</td>
                        <td>{{data.join_date}}</td>

                        <td>
                            <router-link :to="{name:'editemployee', params:{id:data.id}}" class="btn btn-sm   btn-primary">Edit </router-link>
                            <a href='javascript:void(0)' @click='deleteEmp(data.id)' class="btn btn-sm   btn-danger">Delete </a>
                        </td>
                      </tr>

                    </tbody>
                  </table>
                </div>
                <div class="card-footer" v-if="employees.length > 0">
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

    export default {
        components: { ColumnSearchPanel, PaginationControl, SortableTh },
        mixins: [sortablePaginationMixin],
        data() {
            return {
employees: [],
loading: false,
showFilters: false,
filterColumns: [
    { key: 'search', label: 'Name / Phone / Email', type: 'text' },
],
filters: {
    search: '',
},
meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
            }
        },
        methods: {
fetchList(){
    this.loading = true;

    const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.filters.search
    };

    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === undefined) {
            delete params[key];
        }
    });

    axios.get('/api/employee', { params })
.then(res => {
    this.employees = res.data.data || [];
    if (res.data.meta) {
        this.meta = res.data.meta;
    }
    this.loading = false;
})
.catch(err => {
       notification.error();
    this.loading = false;
})
},
applyFilters(){
    this.meta.current_page = 1;
    this.fetchList();
},
deleteEmp(id){

Swal.fire({
  title: 'Are you sure?',
//   text: "You won't be able to revert this!",
  icon: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Yes, delete it!'
}).then((result) => {
  if (result.value) {


axios.delete("/api/employee/"+id)
.then(() => {
     this.employees=this.employees.filter(data=>{
         return data.id != id
     })
})
.catch(() => {
   this.$router.push({ name:'employees'})
})
     Swal.fire(
      'Deleted!',
      'Your file has been deleted.',
      'success'
    )
  }
})
}
        },
        watch: {
            filters: {
                handler() {
                    this.applyFilters();
                },
                deep: true
            }
        },
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
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
