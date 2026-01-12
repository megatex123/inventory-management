<template lang="">
    <div>
    <div class='row'>
<router-link to="/craft" class="btn btn-primary ml-3">All QuiviCraft</router-link>
    </div>
        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-7 m-auto">
                                <div class="login-form">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Update QuiviCraft</h1>
                                    </div>
                                    <form class="user" @submit.prevent='CraftUpdate' enctype="multipart/form-data">
                                        <div class="form-group">
                                            <div class="form-row">
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
                                            <button type="submit" class="btn btn-primary btn-block">Update</button>
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
            axios.get('/api/craft/'+id)
            .then(res => {
               this.form = res.data
            })
            .catch(err => {
                console.error(err);
            })

        },
        data() {
            return {
                form: {
                    name: null,
                    code: null,
                    fee: null,

                },
                errors: {}
            }
        },
        methods: {

CraftUpdate(){
    let id = this.$route.params.id
 axios.patch('/api/craft/'+id,this.form)
         .then( () => {

             this.$router.push({name: 'Craft'})
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
