<template>
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Header Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="m-0 font-weight-bold">
                                            <i class="fas fa-calendar-day mr-2"></i>Today's Orders
                                        </h5>
                                        <div class="d-flex align-items-center">
                                            <div class="input-group" style="width: 300px;">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-white">
                                                        <i class="fas fa-search text-primary"></i>
                                                    </span>
                                                </div>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    v-model='searchItem'
                                                    placeholder="Search by Customer Name or Order ID"
                                                >
                                            </div>
                                            <button class="btn btn-light ml-2" @click="refreshData" title="Refresh">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-light">Showing orders for: {{ todayDate }}</small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="stat-card bg-light p-3 rounded">
                                                <h6 class="text-muted mb-1">Total Orders</h6>
                                                <h3 class="text-primary mb-0">{{ totalOrders }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stat-card bg-light p-3 rounded">
                                                <h6 class="text-muted mb-1">Approved</h6>
                                                <h3 class="text-success mb-0">{{ approvedOrders }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stat-card bg-light p-3 rounded">
                                                <h6 class="text-muted mb-1">Draft</h6>
                                                <h3 class="text-warning mb-0">{{ draftOrders }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stat-card bg-light p-3 rounded">
                                                <h6 class="text-muted mb-1">Rejected</h6>
                                                <h3 class="text-danger mb-0">{{ rejectedOrders }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistics Summary -->
                            <div class="card mt-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-circle bg-primary text-white mr-3">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Total Today</h6>
                                                    <h4 class="mb-0">RM{{ formatNumber(totalRevenue) }}</h4>
                                                    <small class="text-muted">Revenue for today</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-circle bg-success text-white mr-3">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Approval Rate</h6>
                                                    <h4 class="mb-0">{{ approvalRate }}%</h4>
                                                    <small class="text-muted">Approved orders percentage</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-circle bg-info text-white mr-3">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Pending Approval</h6>
                                                    <h4 class="mb-0">{{ draftOrders }}</h4>
                                                    <small class="text-muted">Orders waiting for approval</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Orders Table -->
                            <div class="card">
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Payment</th>
                                                <th>Date</th>
                                                <th>QuiviCraft</th>
                                                <th>QuiviServe</th>
                                                <th>QuiviCare</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='data in filteredOrders' :key="data.id" >
                                                <td>
                                                    <span class="badge badge-light font-weight-bold">{{ data.order_id }}</span>
                                                </td>
                                                <td>
                                                    <strong>{{ data.customer && data.customer.full_name ? data.customer.full_name : 'N/A' }}</strong><br>
                                                    <small class="text-muted">{{ data.customer && data.customer.email ? data.customer.email : '' }}</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block">Total Amount:</small>
                                                    <strong>RM{{ formatNumber(
                                                        Number(data.craft && data.craft.fee ? data.craft.fee : 0) +
                                                        Number(data.total || 0) +
                                                        Number(data.serve && data.serve.fee ? data.serve.fee : 0) +
                                                        careFeeContribution(data)
                                                    ) }}</strong><br>
                                                    <small class="text-muted d-block">Total Pay:</small>
                                                    <strong class="text-success">RM{{ formatNumber(data.total) }}</strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block">Order Date:</small>
                                                    {{ formatDate(data.order_date) }} <br>
                                                    <small class="text-muted d-block">Created:</small>
                                                    {{ formatDateTime(data.created_at) }}
                                                </td>
                                                <td>{{ data.craft && data.craft.name ? data.craft.name : 'N/A' }}</td>
                                                <td>
                                                    <span
                                                        v-if="data.serve"
                                                        class="serve-badge"
                                                        :style="{
                                                            backgroundColor: data.serve.colour,
                                                            color: isLightColor(data.serve.colour) ? '#000' : '#fff'
                                                        }"
                                                    >
                                                        {{ data.serve.name }}
                                                    </span>
                                                    <span v-else class="badge badge-secondary">N/A</span>
                                                </td>
                                                <td>{{ data.care && data.care.name ? data.care.name : 'N/A' }}</td>
                                                <td>
                                                    <span :class="getStatusBadgeClass(data)" class="badge">
                                                        {{ getStatusText(data) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <router-link
                                                            :to="{name:'vieworder', params:{id:data.id}}"
                                                            class="btn btn-sm btn-primary"
                                                            title="View Details"
                                                        >
                                                            <i class="fas fa-eye"></i>
                                                        </router-link>
                                                        <!-- Approve Button -->
                                                        <button
                                                            v-if="data.approve === null || data.approve === '' || data.approve === undefined"
                                                            class="btn btn-sm btn-success ml-1"
                                                            @click="approveOrder(data, 1)"
                                                            title="Approve Order"
                                                        >
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <!-- Reject Button -->
                                                        <button
                                                            v-if="data.approve === null || data.approve === '' || data.approve === undefined"
                                                            class="btn btn-sm btn-danger ml-1"
                                                            @click="approveOrder(data, 0)"
                                                            title="Reject Order"
                                                        >
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <!-- Reset to Draft Button -->
                                                        <button
                                                            v-if="data.approve == 1 || data.approve == 0"
                                                            class="btn btn-sm btn-warning ml-1"
                                                            @click="approveOrder(data, null)"
                                                            title="Reset to Draft"
                                                        >
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="filteredOrders.length === 0">
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                                        <h5 class="text-muted">No orders for today</h5>
                                                        <p class="text-muted">There are no orders placed today</p>
                                                    </div>
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
</template>

<script>
import Swal from 'sweetalert2';

export default {
    data() {
        return {
            orders: [],
            searchItem: '',
            loading: false
        }
    },
    computed: {
        todayDate() {
            const today = new Date();
            return today.toLocaleDateString('en-MY', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        // Backend already scopes /api/orders/today to order_date = Carbon::today()
        // (same definition getStatistics() uses for the All Orders page's "Today's
        // Summary" card) — only the free-text search is applied client-side here.
        filteredOrders() {
            let filtered = this.orders;

            if (this.searchItem) {
                const search = this.searchItem.toLowerCase();
                filtered = filtered.filter(order => {
                    const customerName = order.customer && order.customer.full_name ? order.customer.full_name.toLowerCase() : '';
                    const orderId = order.order_id ? order.order_id.toString().toLowerCase() : '';
                    const customerEmail = order.customer && order.customer.email ? order.customer.email.toLowerCase() : '';

                    return (
                        customerName.includes(search) ||
                        orderId.includes(search) ||
                        customerEmail.includes(search)
                    );
                });
            }

            return filtered;
        },

        totalOrders() {
            return this.filteredOrders.length;
        },
        approvedOrders() {
            return this.filteredOrders.filter(order => order.approve == 1).length;
        },
        draftOrders() {
            return this.filteredOrders.filter(order =>
                order.approve === null ||
                order.approve === '' ||
                order.approve === undefined
            ).length;
        },
        rejectedOrders() {
            return this.filteredOrders.filter(order => order.approve == 0).length;
        },
        totalRevenue() {
            return this.filteredOrders.reduce((total, order) => {
                return total + Number(order.total || 0);
            }, 0);
        },
        approvalRate() {
            if (this.totalOrders === 0) return 0;
            return Math.round((this.approvedOrders / this.totalOrders) * 100);
        }
    },
    methods: {
        getOrders() {
            this.loading = true;
            axios.get('/api/orders/today')
            .then(res => {
                this.orders = res.data;
                this.loading = false;
            })
            .catch(err => {
                console.error(err);
                this.loading = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to load orders',
                    confirmButtonText: 'OK'
                });
            });
        },
        refreshData() {
            this.getOrders();
            Swal.fire({
                icon: 'success',
                title: 'Refreshed!',
                text: 'Data has been refreshed',
                timer: 1000,
                showConfirmButton: false
            });
        },
        formatNumber(value) {
            return Number(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        // Customer opted out of QuiviCare at checkout (skip_quivicare) -- no
        // CareData was/will be created for this order, so its charge must
        // not appear in the Total Amount at all.
        careFeeContribution(data) {
            if (data && data.skip_quivicare) return 0;
            return (data && data.care_price) ? Number(data.care_price) : 0;
        },
        formatDate(date) {
            // UTC-based, matching allorder.vue's formatDate — avoids re-interpreting
            // the server's naive datetime string in the browser's local timezone.
            if (!date) return '';
            const d = new Date(date);
            const day = String(d.getUTCDate()).padStart(2, '0');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = monthNames[d.getUTCMonth()];
            const year = d.getUTCFullYear();
            return `${parseInt(day)} ${month} ${year}`;
        },
        formatDateTime(date) {
            if (!date) return '';
            const d = new Date(date);
            const day = String(d.getUTCDate()).padStart(2, '0');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = monthNames[d.getUTCMonth()];
            const year = d.getUTCFullYear();
            const hours = String(d.getUTCHours()).padStart(2, '0');
            const minutes = String(d.getUTCMinutes()).padStart(2, '0');
            return `${parseInt(day)} ${month} ${year}, ${hours}:${minutes}`;
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
        getStatusBadgeClass(order) {
            if (order.approve === null || order.approve === '' || order.approve === undefined) {
                return 'badge-secondary';
            }
            if (order.approve == 1) return 'badge-success';
            if (order.approve == 0) return 'badge-danger';
            return 'badge-primary';
        },
        getStatusText(order) {
            if (order.approve === null || order.approve === '' || order.approve === undefined) {
                return 'Draft';
            }
            if (order.approve == 1) return 'Approved';
            if (order.approve == 0) return 'Rejected';
            return 'Unknown';
        },
        approveOrder(order, status) {
            const statusText = status === 1 ? 'approve' :
                             status === 0 ? 'reject' :
                             'reset to draft';

            Swal.fire({
                title: `Are you sure?`,
                text: `Do you want to ${statusText} Order #${order.order_id}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: `Yes, ${statusText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    this.updateApprove(order, status);
                }
            });
        },
        updateApprove(order, status) {
            const loading = Swal.fire({
                title: 'Updating...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            axios.put(`/api/order/${order.id}/approve`, {
                approve: status
            })
            .then((response) => {
                loading.close();

                // Update local order data
                order.approve = status;
                if (status === 1) {
                    order.approved_at = new Date().toISOString();
                } else {
                    order.approved_at = null;
                }

                const statusMessage = status === 1 ? 'approved' :
                                    status === 0 ? 'rejected' :
                                    'reset to draft';

                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: `Order has been ${statusMessage}`,
                    timer: 1500,
                    showConfirmButton: false
                });

                // Refresh data
                this.getOrders();
            })
            .catch((error) => {
                loading.close();
                console.error('Error updating approval:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update order status',
                    confirmButtonText: 'OK'
                });
            });
        }
    },
    created() {
        if (!User.loggedIn()) {
            this.$router.push({ name: 'login' });
        }
        this.getOrders();
    }
}
</script>

<style scoped>
.stat-card {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.serve-badge {
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 500;
    white-space: nowrap;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.15s ease;
    display: inline-block;
    text-align: center;
    min-width: 100px;
}

.serve-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
}

.badge {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}

.btn-group .btn {
    margin-right: 5px;
    border-radius: 6px !important;
}

.table th {
    border-top: none;
    border-bottom: 2px solid #e3e6f0;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    color: #4e73df;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
    transition: background-color 0.2s ease;
}

.empty-state {
    padding: 40px 0;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

.input-group-text {
    background-color: #fff;
    border-right: none;
}

.form-control {
    border-left: none;
}

.form-control:focus {
    box-shadow: none;
    border-color: #ced4da;
}
</style>
