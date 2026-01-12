<template lang="">
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <router-link to="/care/create" class="btn btn-primary ml-3">Add QuiviCare</router-link>
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviCare List</h5>
                                    <input type="text" class="form-control" v-model='searchItem' id="searchItems" placeholder="Search QuiviCare By Name">
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Fee (RM)</th>
                                                <th>Period</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='(data,index) in filterSearch' :key="index" >
                                                <td>{{index+1}}</td>
                                                <td>{{data.name}}</td>
                                                <td>{{data.code}}</td>
                                                <td>{{data.fee}}</td>
                                                <td>{{data.period}}</td>
                                                <td>
                                                    <router-link :to="{name:'Careedit', params:{id:data.id}}" class="btn btn-sm btn-primary">Edit </router-link>
                                                    <a href='javascript:void(0)' @click='deleteCat(data.id)' class="btn btn-sm btn-danger">Delete </a>
                                                </td>
                                            </tr>
                                            <tr v-if="filterSearch.length === 0">
                                                <td colspan="3" class="text-center text-muted">
                                                    No Category found.
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
</template>
<script>
    export default {
        data() {
            return {
                care: [],
                searchItem:'',
            }
        },
        methods: {
            getEmp(){
                axios.get('/api/care')
                .then(res => {
                    this.care=res.data;
                })
                .catch(err => {
                    notification.error();
                })
            },
            deleteCat(id){
                Swal.fire({
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                if (result.value) {
                    axios.delete("/api/care/"+id)
                    .then(() => {
                        this.care=this.care.filter(data=>{
                            return data.id != id
                        })
                    })
                    .catch(() => {
                    this.$router.push({ name:'care'})
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
                return this.care.filter(data=>{
                    return data.name.match(this.searchItem)
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
