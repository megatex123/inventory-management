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

                        <th>Sallery Month</th>

                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for='data in filterSearch' :key="data.id" >
                        <td>{{data.salary_month}}</td>

                        <td>
<router-link :to="{name:'viewsalary', params:{id:data.salary_month}}" class="btn btn-sm   btn-primary">View Salary </router-link>

                        </td>
                      </tr>

                    </tbody>
                  </table>
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

    export default {
        components: { ColumnSearchPanel },
        data() {
            return {
employees: [],
showFilters: false,
filterColumns: [
    { key: 'salary_month', label: 'Salary Month', type: 'text' },
],
filters: {
    salary_month: '',
},
            }
        },
        methods: {
getEmp(){
    axios.get('/api/salary')
.then(res => {
    this.employees=res.data;
    // console.log(res.data)
})
.catch(err => {
    // console.error(err);
       notification.error();

})
},

        },
        computed: {
filterSearch(){
    return this.employees.filter(data=>{
        return data.salary_month.match(this.filters.salary_month)
    })
}
        },
       created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.getEmp();

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
