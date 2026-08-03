<template lang="">
    <div>
        <div class='row'>
            <router-link to="/product" class="btn btn-primary ml-3">All Product</router-link>
        </div>
        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="login-form">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Add Product</h1>
                                    </div>
                                    <form class="user" @submit.prevent='ProductInsert' enctype="multipart/form-data">
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <label>Product Name</label>
                                                    <input type="text" class="form-control" v-model='form.product_name'>
                                                    <small class="text-danger" v-if='errors.product_name'>
                                                        {{errors.product_name[0]}}</small> </small>
                                                </div>
                                                <div class="col-6">
                                                    <label>Product Code</label>
                                                    <input type="text" class="form-control" value="(auto-generated on save)" disabled readonly>
                                                    <small class="text-danger" v-if='errors.product_code'>
                                                        {{errors.product_code[0]}}
                                                    </small>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <label>Product Category</label>
                                                    <select v-model='form.cat_id' class="form-control">
                                                        <option :value="cat.id" v-for='cat in categories'>{{cat.name}}</option>
                                                    </select>
                                                    <small class="text-danger" v-if='errors.cat_id'>
                                                        {{errors.cat_id[0]}}
                                                    </small>
                                                </div>
                                                <div class="col-6">
                                                    <label>Product Supplier</label>
                                                    <select v-model='form.supplier_id' class="form-control">
                                                        <option :value="supplier.id" v-for='supplier in suppliers'>
                                                            {{supplier.name}}
                                                        </option>
                                                    </select>
                                                    <small class="text-danger" v-if='errors.supplier_id'>
                                                        {{errors.supplier_id[0]}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <label>Brand</label>
                                                    <select v-model='form.brand_id' class="form-control">
                                                        <option :value="null">-- Select Brand --</option>
                                                        <option :value="brand.id" v-for='brand in brands'>{{brand.name}}</option>
                                                    </select>
                                                    <small class="text-danger" v-if='errors.brand_id'>
                                                        {{errors.brand_id[0]}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-4">
                                                    <label>Buying Price (RM)</label>

                                                    <input type="text" class="form-control" v-model='form.buying_price'>
                                                    <small class="text-danger" v-if='errors.buying_price'>
                                                        {{errors.buying_price[0]}}
                                                    </small>
                                                </div>
                                                <div class="col-4">
                                                    <label>Selling Price (RM)</label>

                                                    <input type="text" class="form-control"
                                                        v-model='form.selling_price'>
                                                    <small class="text-danger" v-if='errors.selling_price'>
                                                        {{errors.selling_price[0]}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div> -->

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <label>Price (RM)</label>
                                                    <input type="number" step="0.01" class="form-control" v-model='form.price'>
                                                    <small class="text-danger" v-if='errors.price'>
                                                        {{errors.price[0]}}
                                                    </small>
                                                </div>
                                                <div class="col-6">
                                                    <label>Product Qty</label>
                                                    <input type="number" class="form-control" v-model='form.product_qty'>
                                                    <small class="text-danger" v-if='errors.product_qty'>
                                                        {{errors.product_qty[0]}}
                                                    </small>
                                                </div>
                                                <!-- <div class="col-6">
                                                    <label>Available</label>
                                                    <input type="text" class="form-control" v-model='form.available'>
                                                    <small class="text-danger" v-if='errors.available'>
                                                        {{errors.available[0]}}
                                                    </small>
                                                </div> -->
                                            </div>
                                        </div>

                                        <!-- <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-4">
                                                    <label>Available Local</label>
                                                    <input type="text" class="form-control" v-model='form.available_local'>
                                                    <small class="text-danger" v-if='errors.available_local'>
                                                        {{errors.available_local[0]}}
                                                    </small>
                                                </div>
                                                <div class="col-4">
                                                    <label>Product Part/Root</label>
                                                    <input type="text" class="form-control" v-model='form.root'>
                                                    <small class="text-danger" v-if='errors.root'>
                                                        {{errors.root[0]}}
                                                    </small>
                                                </div>
                                                <div class="col-4">
                                                    <label>Product Qty</label>
                                                    <input type="number" class="form-control" v-model='form.product_qty'>
                                                    <small class="text-danger" v-if='errors.product_qty'>
                                                        {{errors.product_qty[0]}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div> -->

                                        <!-- <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <label>Buying Date</label>
                                                    <input type="date" class="form-control" v-model='form.buying_date'>
                                                    <small class="text-danger" v-if='errors.buying_date'>
                                                        {{errors.buying_date[0]}}</small> </small>
                                                </div>
                                                <div class="col-6">
                                                    <label>Product Qty</label>
                                                    <input type="text" class="form-control" v-model='form.product_qty'>
                                                    <small class="text-danger" v-if='errors.product_qty'>
                                                        {{errors.product_qty[0]}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div> -->

                                        <div class="form-group">
                                            <label class="small text-muted mb-1">Photo</label>
                                            <div class="photo-upload-btn" :class="{ 'has-photo': form.photo }">
                                                <img v-if="form.photo" :src="form.photo" alt="photo">
                                                <input type="file" accept="image/*" @change='onFileSelect'>
                                            </div>
                                            <small class="text-success d-block" v-if='photoFileName'>Uploaded: {{ photoFileName }}</small>
                                            <small class="text-danger d-block" v-if='errors.photo'>{{errors.photo[0]}}</small>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary btn-block">Submit</button>
                                        </div>

                                    </form>
                                    <hr>

                                    <div class="text-center">
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
    export default {
        created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
            let id = this.$route.params.id

            axios.get('/api/categories/all')
                .then(res => {
                    this.categories = res.data;
                })

            axios.get('/api/suppliers/all')
                .then(res => {
                    this.suppliers = res.data;
                })

            axios.get('/api/brand', { params: { per_page: 100 } })
                .then(res => {
                    this.brands = res.data.data || [];
                })

        },
        data() {
            return {
                form: {
                    product_name: null,
                    cat_id: null,
                    product_code: null,
                    root: null,
                    buying_price: null,
                    selling_price: null,
                    supplier_id: null,
                    buying_date: null,
                    photo: null,
                    product_qty: null,
                    brand_id: null,
                    price: null,
                    price_updated_at: null,
                    available: null,
                    available_local: null,
                },
                errors: {},
                categories: {},
                suppliers: {},
                brands: {},
                photoFileName: null,
            }
        },
        methods: {
            onFileSelect(event) {
                let file = event.target.files[0];
                if (!file) return;

                if (file.size > 1048770) {
                    notification.Image_size()
                } else {
                    let reader = new FileReader();
                    reader.onload = event => {
                        this.$set(this.form, 'photo', event.target.result);
                        this.photoFileName = file.name;
                    };
                    reader.readAsDataURL(file);
                }
            },
            ProductInsert() {
                axios.post('/api/product', this.form)
                    .then(() => {
                        this.$router.push({
                            name: 'Product'
                        })
                        notification.success()
                    })

                    .catch(err => {
                        this.errors = (err.response && err.response.data && err.response.data.errors) || {};
                        notification.error();
                    })
            }
        },
    }
</script>
<style scoped>
.photo-upload-btn {
    position: relative;
    width: 70px;
    height: 70px;
    border: 1px dashed #adb5bd;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.photo-upload-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-upload-btn input[type="file"] {
    font-size: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    position: absolute;
    top: 0;
    left: 0;
}
.photo-upload-btn:not(.has-photo)::before {
    content: '+';
    font-size: 1.5rem;
    color: #adb5bd;
    pointer-events: none;
}
</style>
