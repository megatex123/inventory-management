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
                        <th>Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th>Price (RM)</th>
                        <th>Status</th>
                        <th>Product Quantity</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>

                      <tr v-for='data in filterSearch' :key="data.id" >
                        <td><img :src="data.image" class="img-fluid" width='40px' height='40px' /></td>
                        <td>{{data.product_name}}</td>
                        <td>{{data.product_code}}</td>
                        <td>{{data.cat_name}}</td>
                        <td>{{data.price}}</td>
                        <td>
                           <span v-if='data.product_qty>=1' class="badge badge-pill badge-success">Stock Available</span>
                           <span v-else='' class="badge badge-pill badge-danger">Stock Out</span>
                        </td>
                        <td>{{data.product_qty}}</td>
                        <td>
                            <router-link :to="{name:'stockedit', params:{id:data.id}}" class="btn btn-sm   btn-primary">Edit </router-link>
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
suppliers: [],
showFilters: false,
filterColumns: [
    { key: 'product_name', label: 'Name', type: 'text' },
],
filters: {
    product_name: '',
},
            }
        },
        methods: {
getEmp(){
    axios.get('/api/product/all')
.then(res => {
    this.suppliers=res.data;
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
    return this.suppliers.filter(data=>{
        return data.product_name.match(this.filters.product_name)
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
