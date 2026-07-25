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
                                        <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
                                            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                            {{ showFilters ? 'Hide Search' : 'Show Search' }}
                                        </button>
                                    </div>
                                    <div class="px-3">
                                        <column-search-panel
                                            :columns="filterColumns"
                                            v-model="filters"
                                            :visible="showFilters"
                                        />
                                        <div class="text-right mb-2" v-if="showFilters">
                                            <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
                                                <i class="fas fa-redo mr-1"></i> Reset
                                            </button>
                                        </div>
                                    </div>
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
                filters: {
                    name: '',
                    code: '',
                    category: '',
                    price: '',
                    status: '',
                    product_qty: '',
                },
            }
        },
        methods: {
            getEmp(){
                axios.get('/api/product')
                .then(res => {
                    this.suppliers=res.data;
                })
                .catch(err => {
                    notification.error();
                })
            },
            resetFilters() {
                this.filters = {
                    name: '',
                    code: '',
                    category: '',
                    price: '',
                    status: '',
                    product_qty: '',
                };
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
            categoryOptions() {
                const names = [...new Set(this.suppliers.map(p => p.cat_name).filter(Boolean))];
                return names.sort().map(n => ({ value: n, label: n }));
            },
            filterColumns() {
                return [
                    { key: 'name', label: 'Name', type: 'text' },
                    { key: 'code', label: 'Code', type: 'text' },
                    { key: 'category', label: 'Category', type: 'select', options: this.categoryOptions },
                    { key: 'price', label: 'Price (RM)', type: 'text' },
                    { key: 'status', label: 'Status', type: 'select', options: [
                        { value: 'available', label: 'Stock Available' },
                        { value: 'out', label: 'Stock Out' },
                    ] },
                    { key: 'product_qty', label: 'Product Quantity', type: 'text' },
                ];
            },
            filterSearch(){
                let filtered = this.suppliers;
                if (this.filters.name) {
                    const kw = this.filters.name.toLowerCase();
                    filtered = filtered.filter(d => d.product_name && d.product_name.toLowerCase().includes(kw));
                }
                if (this.filters.code) {
                    const kw = this.filters.code.toLowerCase();
                    filtered = filtered.filter(d => d.product_code && d.product_code.toLowerCase().includes(kw));
                }
                if (this.filters.category) {
                    filtered = filtered.filter(d => d.cat_name === this.filters.category);
                }
                if (this.filters.price) {
                    filtered = filtered.filter(d => d.price !== undefined && d.price !== null && d.price.toString().includes(this.filters.price));
                }
                if (this.filters.status) {
                    const wantAvailable = this.filters.status === 'available';
                    filtered = filtered.filter(d => (d.product_qty >= 1) === wantAvailable);
                }
                if (this.filters.product_qty) {
                    filtered = filtered.filter(d => d.product_qty !== undefined && d.product_qty !== null && d.product_qty.toString().includes(this.filters.product_qty));
                }
                return filtered;
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
</style>
