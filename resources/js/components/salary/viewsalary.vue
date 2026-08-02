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
<router-link to="/salary" class="btn btn-primary ml-3">Salary</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Employee List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Employee By Name">
                </div>
     <div class="table-responsive">
                  <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                      <tr>
                        <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
                        <sortable-th label="Phone" sort-key="phone" :current-sort="sortState" @sort="onSort" />
                        <th>Month</th>
                        <sortable-th label="Sallery" sort-key="amount" :current-sort="sortState" @sort="onSort" />
                        <th>Date</th>
                        <!-- <th>Action</th> -->
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for='data in employees' :key="data.id" >

                        <td>{{data.name}}</td>
                        <td>{{data.phone}}</td>
                        <td>{{data.salary_month}}</td>
                        <td>{{data.amount}}</td>
                        <td>{{data.salary_date}}</td>
<!--
                        <td>
 <router-link :to="{name:'paysalary', params:{id:data.id}}" class="btn btn-sm   btn-primary">Pay Salary </router-link>

                        </td> -->
                      </tr>

                    </tbody>
                  </table>
                </div>
                <div class="card-footer" v-if="!loading && employees.length > 0">
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
    import PaginationControl from '../shared/PaginationControl.vue';
    import SortableTh from '../shared/SortableTh.vue';
    import sortablePaginationMixin from '../../mixins/sortablePagination';

    export default {
        components: { PaginationControl, SortableTh },
        mixins: [sortablePaginationMixin],
        data() {
            return {
employees: [],
searchItem: '',
loading: false,
meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
sortState: { key: 'created_at', dir: 'desc' },
            }
        },
        methods: {
fetchList(){
    this.loading = true;
    let id = this.$route.params.id;
    const params = {
        page: this.meta.current_page,
        per_page: this.meta.per_page,
        sort_by: this.sortState.key,
        sort_dir: this.sortState.dir,
        search: this.searchItem
    };
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === undefined) {
            delete params[key];
        }
    });
    axios.get('/api/salaryview/' + id, { params })
        .then(res => {
            this.employees = res.data.data || [];
            if (res.data.meta) {
                this.meta = res.data.meta;
            }
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        })
},
applyFilters() {
    this.meta.current_page = 1;
    this.fetchList();
},

        },
       watch: {
searchItem() {
    this.applyFilters();
},
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
#searchItems {
    width: 270px !important;
}
</style>
