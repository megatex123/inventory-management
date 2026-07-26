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
<router-link to="/expens/create" class="btn btn-primary ml-3">Add Expens</router-link>
                  <h5 class="m-0 font-weight-bold text-primary">Expens List</h5>
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
                        <th>ID</th>
                        <th>Amount</th>
                        <th>Expens Date</th>
                        <th>Details</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for='(data,index) in filterSearch' :key="index" >

                        <td>{{index+1}}</td>
                        <td>{{data.amount}}</td>
                        <td>{{data.expenses_date}}</td>
                        <td>{{data.details}}</td>

                        <td>
                            <router-link :to="{name:'expensedit', params:{id:data.id}}" class="btn btn-sm btn-primary">Edit </router-link>
                            <a href='javascript:void(0)' @click='deleteExpens(data.id)' class="btn btn-sm btn-danger">Delete </a>
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
categories: [],
showFilters: false,
filterColumns: [
    { key: 'details', label: 'Details', type: 'text' },
],
filters: {
    details: '',
},
            }
        },
        methods: {
getEmp(){
    axios.get('/api/expens')
.then(res => {
    this.categories=res.data;
    // console.log(res.data)
})
.catch(err => {
    // console.error(err);
       notification.error();

})
},
deleteExpens(id){

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


axios.delete("/api/expens/"+id)
.then(() => {
     this.categories=this.categories.filter(data=>{
         return data.id != id
     })
})
.catch(() => {
   this.$router.push({ name:'expens'})
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
        computed: {
filterSearch(){
    return this.categories.filter(data=>{
        return data.details.match(this.filters.details)
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
