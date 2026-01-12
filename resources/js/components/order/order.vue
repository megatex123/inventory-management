<template lang="">
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
                                    <h5 class="m-0 font-weight-bold text-primary">Today Order</h5>
                                    <input type="text" class="form-control" v-model='searchItem' id="searchItems" placeholder="Search Orders By Name">
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                      <thead class="thead-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Payment</th>
                                            <th>Date</th>
                                            <th>QuiviCraft</th>
                                            <th>QuiviServe</th>
                                            <th>QuiviCare</th>
                                            <th>Action</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <tr v-for='(data,index) in filterSearch' :key="index" >
                                            <td>{{data.customer.full_name}}</td>
                                            <td>
                                                Total Amount: <br>RM{{ formatNumber(
                                                    Number(data.craft.fee || 0) +
                                                    Number(data.total || 0) +
                                                    Number(data.serve.fee || 0) +
                                                    Number(data.care.fee || 0)
                                                ) }}<br>
                                                Total Pay: <br>RM{{ formatNumber(data.total) }}
                                            </td>
                                            <td>
                                                Order At: {{ formatDate(data.order_date) }} <br>
                                                Create At: {{ formatDate(data.created_at) }}
                                            </td>
                                            <td>{{ data.craft.name }}</td>
                                            <td>
                                                <span
                                                    class="serve-badge"
                                                    :style="{
                                                        backgroundColor: data.serve.colour,
                                                        color: isLightColor(data.serve.colour) ? '#000' : '#fff'
                                                    }"
                                                >
                                                    {{ data.serve.name }}
                                                </span>
                                            </td>
                                            <td>{{ data.care.name }}</td>
                                            <td>
                                                <router-link :to="{name:'vieworder', params:{id:data.id}}" class="btn btn-sm btn-primary rounded-circle"> <i class="fas fa-eye"></i>  </router-link>
                                            </td>
                                        </tr>
                                        <tr v-if="filterSearch.length === 0">
                                            <td colspan="7" class="text-center text-muted">
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
                orders: [],
                searchItem:'',
            }
        },
        methods: {
            fetchOrders() {
                axios.get('/api/orders')
                    .then(res => {
                        this.orders = res.data
                    })
            },
            getOrders(){
                axios.get('/api/orders')
                .then(res => {
                    this.orders=res.data;
                })
                .catch(err => {
                    notification.error();
                })
            },
            formatNumber(value) {
                return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            formatDate(date) {
                if (!date) return '';
                const d = new Date(date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}/${month}/${year}`;
            },
            isLightColor(hex) {
                if (!hex) return false;
                hex = hex.replace('#', '').toLowerCase();

                if (hex === 'ffffff') return true;

                const r = parseInt(hex.substr(0, 2), 16);
                const g = parseInt(hex.substr(2, 2), 16);
                const b = parseInt(hex.substr(4, 2), 16);

                const brightness = (r * 299 + g * 587 + b * 114) / 1000;

                return brightness > 180;
            }
        },
        computed: {
            filterSearch() {
                const todayLocal = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD

                return this.orders.filter(data => {
                    if (!data.created_at) return false;

                    const createdDate = new Date(data.created_at)
                        .toLocaleDateString('en-CA');

                    // today only
                    if (createdDate !== todayLocal) return false;

                    // no search → show today orders
                    if (!this.searchItem) return true;

                    // search by name
                    return data.customer?.full_name
                        ?.toLowerCase()
                        .includes(this.searchItem.toLowerCase());
                });
            }
        },
        created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
             this.getOrders();
        },
    }
</script>

<style scoped>
    #searchItems {
        width: 270px !important;
    }

    .serve-badge {
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 500;
        white-space: nowrap;

        /* Soft shadow */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);

        transition: all 0.15s ease;
    }
</style>
