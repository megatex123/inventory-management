<template lang="">
    <div>
    <div class='row'>
<router-link to="/care" class="btn btn-primary ml-3">All QuiviCare</router-link>
    </div>
        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-8 m-auto">
                                <div class="login-form">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Add QuiviCare</h1>
                                    </div>
                                    <form class="user" @submit.prevent='CategoryInsert' enctype="multipart/form-data">
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-6">
                                                    <input type="text" class="form-control" v-model='form.name' placeholder="Enter Full Name">
                                                    <small class="text-danger" v-if='errors.name'> {{errors.name[0]}}</small> </small>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control" v-model='form.code' placeholder="Enter Code">
                                                    <small class="text-danger" v-if='errors.code'> {{errors.code[0]}}</small> </small>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control" v-model='form.fee' placeholder="Enter Fee">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control" v-model='form.period' placeholder="Enter Period">
                                                    <small class="text-danger" v-if='errors.period'> {{errors.period[0]}}</small> </small>
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
            }
        },
        data() {
            return {
                form: {
                    name: null,
                    code: null,
                    fee: null,
                    period: null,
                },
                errors: {}
            }
        },
        methods: {
            CategoryInsert(){
            axios.post('/api/care',this.form)
                    .then( () => {
                        this.$router.push({name: 'Care'})
                    notification.success()
                    })

                    .catch(err => {
                        this.errors=err.response.data.errors;
                notification.error();
                })
            }
        },
    }
</script>
<style lang="">

</style>
