<template lang="">
    <div>
        <div class='row'>
            <router-link to="/sub-category" class="btn btn-primary ml-3">All Sub Category</router-link>
        </div>
        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-8 m-auto">
                                <div class="login-form">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Add Sub Category</h1>
                                    </div>
                                    <form class="user" @submit.prevent='SubCategoryInsert' enctype="multipart/form-data">
                                        <div class="form-group">
                                            <div class="form-row">
                                                <select v-model="form.cat_id" class="form-control">
                                                    <option disabled value="">Select Category</option>
                                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                        {{ cat.name }}
                                                    </option>
                                                </select>
                                                <div class="col-12">
                                                    <input type="text" class="form-control" v-model='form.name' placeholder="Enter Full Name">
                                                    <small class="text-danger" v-if='errors.name'> {{errors.name[0]}}</small> </small>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control" v-model='form.code' placeholder="Enter Code">
                                                    <small class="text-danger" v-if='errors.code'> {{errors.code[0]}}</small> </small>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control" v-model='form.fee' placeholder="Enter Fee">
                                                    <small class="text-danger" v-if='errors.fee'> {{errors.fee[0]}}</small> </small>
                                                </div>
                                            </div>
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
            axios.get('/api/categories')
            .then(res => {
                this.categories = res.data;
            })
            .catch(err => {
                console.error(err);
            })
        },
        data() {
            return {
                form: {
                    cat_id: null,
                    name: null,
                    code: null,
                    fee: null,
                },
                errors: {},
                categories: {},
            }
        },
        methods: {
            SubCategoryInsert(){
                axios.post('/api/sub-categories',this.form)
                .then( () => {
                    this.$router.push({name: 'Category'})
                    notification.success()
                })
                .catch(err => {
                    this.errors=err.response.data.errors;
                    notification.error();
                })
            }
        }
    }
</script>
<style lang="">

</style>
