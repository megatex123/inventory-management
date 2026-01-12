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
                  <h5 class="m-0 font-weight-bold text-primary">Product List</h5>
              <input type="text" class="form-control" v-model='searchItem' id="searchItems"
                                                    placeholder="Search Product By Name">
                </div>
     <div class="table-responsive">
                  <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                      <tr>
                        <th>Photo</th>
                        <th>Code</th>
                        <th>Product Type</th>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Form</th>
                        <th>Interface</th>
                        <th>Read</th>
                        <th>Write</th>
                        <th>Tier</th>
                        <th>Minimum Price (RM)</th>
                        <th>Maximum Price (RM)</th>
                        <th>Available</th>
                        <th>Available Local</th>
                        <th>Buying Date</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for='data in filterSearch' :key="data.id" >

                        <td><img :src="data.image" class="img-fluid" width='40px' height='40px' /></td>
                        <td>{{data.product_code}}</td>
                        <td>{{data.cat_name}}</td>
                        <td>{{data.product_name}}</td>
                        <td>{{data.capacity}}</td>
                        <td>{{data.form}}</td>
                        <td>{{data.interface}}</td>
                        <td>{{data.read_speed}}</td>
                        <td>{{data.write_speed}}</td>
                        <td>{{data.price_tier}}</td>
                        <td>{{data.min_price}}</td>
                        <td>{{data.max_price}}</td>
                        <td>{{data.available}}</td>
                        <td>{{data.available_local}}</td>
                        <td>{{data.buying_date}}</td>

                        <td>
                            <router-link :to="{name:'Productedit', params:{id:data.id}}" class="btn btn-sm   btn-primary">Edit </router-link>
                            <a href='javascript:void(0)' @click='deletePro(data.id)' class="btn btn-sm   btn-danger">Delete </a>
                        </td>
                      </tr>
                        <tr v-if="filterSearch.length === 0">
                            <td colspan="8" class="text-center text-muted">
                                No product found.
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
    export default {

        data() {
            return {
suppliers: [],
searchItem:'',
            }
        },
        methods: {
getEmp(){
    axios.get('/api/product')
.then(res => {
    this.suppliers=res.data;
    // console.log(res.data)
})
.catch(err => {
    // console.error(err);
       notification.error();

})
},
deletePro(id){

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


axios.delete("/api/product/"+id)
.then(() => {
     this.suppliers=this.suppliers.filter(data=>{
         return data.id != id
     })
})
.catch(() => {
   this.$router.push({ name:'Product'})
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
    return this.suppliers.filter(data=>{
        return data.product_name.match(this.searchItem)
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
#searchItems {
    width: 270px !important;
}
</style>
