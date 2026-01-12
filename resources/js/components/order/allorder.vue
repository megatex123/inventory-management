<template lang="">
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header py-3 d-flex   flex-row align-items-center justify-content-between">
                                    <h5 class="m-0 font-weight-bold text-primary">All Order</h5>
                                    <input type="text" class="form-control" v-model="searchItem" id="searchItems" placeholder="Search Orders By Name">
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
                                            <th>Approve</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='order in filterSearch' :key="order.id" >
                                                <td>{{order.customer.full_name}}</td>
                                                <td>
                                                    Total Amount: <br>RM{{ formatNumber(
                                                        Number(order.craft.fee || 0) +
                                                        Number(order.total || 0) +
                                                        Number(order.serve.fee || 0) +
                                                        Number(order.care.fee || 0)
                                                    ) }}<br>
                                                    Amount Part: <br>RM{{ formatNumber(order.total) }}
                                                </td>
                                                <td>
                                                    Order At: {{ formatDate(order.order_date) }} <br>
                                                    Create At: {{ formatDate(order.created_at) }}
                                                </td>

                                                <td>{{ order.craft.name }}</td>
                                                <td>
                                                    <span
                                                        class="serve-badge"
                                                        :style="{
                                                            backgroundColor: order.serve.colour,
                                                            color: isLightColor(order.serve.colour) ? '#000' : '#fff'
                                                        }"
                                                    >
                                                        {{ order.serve.name }}
                                                    </span>
                                                </td>
                                                <td>{{ order.care.name }}</td>
                                                <td>
                                                    <div v-for="opt in approveOptions" :key="opt" class="form-check float-left mr-2">
                                                        <input
                                                            class="form-check-input"
                                                            type="radio"
                                                            :name="'approve_' + order.id"
                                                            :value="opt"
                                                            v-model="order.approve"
                                                            @change="updateApprove(order)"
                                                        >
                                                        <label class="form-check-label">{{ opt }}</label>
                                                    </div>
                                                    <br><br>
                                                    <div class="float-left mr-2" v-if="order.approved_at != null">
                                                        Approved At: <br>{{ formatDate(order.approved_at) }}
                                                        <div v-if="order.approved_at" class="float-left mr-2">
                                                            <span :class="getStatusClass(order)" class="badge">
                                                                <i class="fa fa-clock mr-1"></i>
                                                                {{ order.time_remaining }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <router-link :to="{name:'vieworder', params:{id:order.id}}" class="btn btn-sm btn-primary rounded-circle"> <i class="fas fa-eye"></i>  </router-link>
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
                approveOptions: [
                    "Approved",
                    "Rejected",
                ],
            }
        },
        methods: {
            fetchOrders() {
                axios.get('/api/orders')
                    .then(res => {
                        this.orders = res.order
                    });
            },
            getOrders() {
                axios.get('/api/orders')
                .then(res => {
                    this.orders = res.data.orders ?? res.data.map(c => ({
                    ...c,
                    approve: c.approve == 1 ? 'Approved' : 'Rejected'
                }));
                })
                .catch(err => {
                    console.error(err);
                });
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
            },
            updateApprove(order) {
                axios.put(`/api/order/${order.id}/approve`, {
                    approve: order.approve
                })
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Approval successfully',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    this.getOrders();
                })
                .catch(() => {
                    Swal.fire('Error', 'Failed to update Approval', 'error');
                });
            },
            getStatusClass(order) {
                if (order.time_remaining === 'Expired') {
                    return 'badge-danger';
                }
                if (order.months_remaining < 1) {
                    return 'badge-warning';
                }
                return 'badge-success';
            },
        },
        computed: {
            filterSearch() {
                if (!this.searchItem) {
                    return this.orders;
                }
                return this.orders.filter(order =>
                    order.customer?.full_name
                        ?.toLowerCase()
                        .includes(this.searchItem.toLowerCase())
                );
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
