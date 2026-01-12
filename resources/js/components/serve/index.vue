<template lang="">
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <router-link to="/serve/create" class="btn btn-primary ml-3">Add QuiviServe</router-link>
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviServe List</h5>
                                    <input type="text" class="form-control" v-model='searchItem' id="searchItems" placeholder="Search Serve By Name">
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Colour</th>
                                                <th>Fee (RM)<th>
                                                <th>Description</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='(data,index) in filterSearch' :key="index" >
                                                <td>{{index+1}}</td>
                                                <td>{{data.name}}</td>
                                                <td>{{data.code}}</td>
                                                <td>
                                                    <div class="serve-options">
                                                        <label class="serve-item">
                                                            <span class="serve-badge" :style="{ backgroundColor: data.colour, color: isLightColor(data.colour) ? '#000' : '#fff'}">
                                                                {{ data.colour }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>{{data.fee}}</td>
                                                <td style="text-align: left;">
                                                    <span v-html="formatDescription(data.description)"></span>
                                                </td>
                                                <td>
                                                    <router-link :to="{name:'Serveedit', params:{id:data.id}}" class="btn btn-sm btn-primary">Edit </router-link>
                                                    <a href='javascript:void(0)' @click='deleteCat(data.id)' class="btn btn-sm btn-danger">Delete </a>
                                                </td>
                                            </tr>
                                            <tr v-if="filterSearch.length === 0">
                                                <td colspan="6" class="text-center text-muted">
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
                serves: [],
                searchItem:'',
            }
        },
        methods: {
            formatDescription(text) {
                if (!text) return '';
                return text.replace(/\n/g, '<br>');
            },
            getTextColor(bgColor) {
                if (!bgColor) return '#000';
                const color = bgColor.replace('#', '');
                if (color.toUpperCase() === 'FFFFFF') return '#000';

                const r = parseInt(color.substring(0, 2), 16);
                const g = parseInt(color.substring(2, 2 + 2), 16);
                const b = parseInt(color.substring(4, 4 + 2), 16);
                const brightness = (r * 299 + g * 587 + b * 114) / 1000;

                return brightness > 160 ? '#000' : '#fff';
            },
            getEmp(){
                axios.get('/api/serves')
                .then(res => {
                    this.serves=res.data;
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
                    axios.delete("/api/serves/"+id)
                    .then(() => {
                        this.serves=this.serves.filter(data=>{
                            return data.id != id
                        })
                    })
                    .catch(() => {
                    this.$router.push({ name:'serves'})
                    })
                        Swal.fire(
                        'Deleted!',
                        'Your file has been deleted.',
                        'success'
                        )
                    }
                })
            },
            isLightColor(hex) {
                if (!hex) return false;

                hex = hex.replace('#', '');
                const r = parseInt(hex.substr(0, 2), 16);
                const g = parseInt(hex.substr(2, 2), 16);
                const b = parseInt(hex.substr(4, 2), 16);
                const brightness = (r * 299 + g * 587 + b * 114) / 1000;

                return brightness > 180;
            },
        },
        computed: {
            filterSearch(){
                return this.serves.filter(data=>{
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
    .serve-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    .serve-item {
        display: flex;
        align-items: left;
        gap: 10px;
        cursor: pointer;
    }
    .serve-badge {
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 500;
        white-space: nowrap;

        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);

        transition: all 0.15s ease;
    }
    .serve-item:hover .serve-badge {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
    }
</style>
