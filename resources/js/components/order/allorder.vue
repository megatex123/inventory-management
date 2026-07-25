<template>
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="m-0 font-weight-bold">
                                        <i class="fas fa-chart-bar mr-2"></i>QuiviCraft Statistics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 col-sm-6 mb-4" v-for="stat in statistics.overview" :key="stat.label">
                                            <div class="stat-card shadow-sm p-3 border rounded">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="text-muted mb-1">{{ stat.label }}</h6>
                                                        <h4 class="mb-0" :class="stat.class">{{ stat.value }}</h4>
                                                    </div>
                                                    <div class="icon-circle" :class="stat.iconClass">
                                                        <i class="fas" :class="stat.icon"></i>
                                                    </div>
                                                </div>
                                                <small class="text-muted">{{ stat.description }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quick Stats -->
                                    <div class="row mt-12">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Today's Summary</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row text-center">
                                                        <div class="col-6">
                                                            <h3 class="text-primary">{{ statistics.today_orders || 0 }}</h3>
                                                            <small class="text-muted">Today's Orders</small>
                                                        </div>
                                                        <div class="col-6">
                                                            <h3 class="text-success">RM{{ formatNumber(statistics.today_revenue || 0) }}</h3>
                                                            <small class="text-muted">Today's Revenue</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-filter mr-2"></i>Filter QuiviCraft
                                    </h5>
                                    <button class="btn btn-outline-secondary btn-sm" @click="showFilters = !showFilters">
                                        <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                        {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                    </button>
                                </div>
                                <transition name="filter-panel">
                                <div class="card-body" v-if="showFilters">
                                    <column-search-panel
                                        :columns="filterColumns"
                                        v-model="filters"
                                        :visible="true"
                                    />

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" v-model="filters.approve" @change="applyFilters">
                                                <option value="">All Status</option>
                                                <option value="1">Approved</option>
                                                <option value="0">Rejected</option>
                                                <option value="null">Draft</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Date From</label>
                                            <input
                                                type="date"
                                                class="form-control"
                                                v-model="filters.date_from"
                                                @change="applyFilters"
                                            >
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Date To</label>
                                            <input
                                                type="date"
                                                class="form-control"
                                                v-model="filters.date_to"
                                                @change="applyFilters"
                                            >
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <button class="btn btn-secondary mr-2" @click="resetFilters">
                                                <i class="fas fa-redo mr-1"></i> Reset Filters
                                            </button>
                                            <button class="btn btn-primary" @click="applyFilters">
                                                <i class="fas fa-filter mr-1"></i> Apply Filters
                                            </button>
                                            <span class="ml-3 text-muted">
                                                Showing {{ filteredOrders.length }} of {{ orders.length }} orders
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                </transition>
                            </div>

                            <!-- Orders Table -->
                            <div class="card">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviCraft List</h5>
                                    <div>
                                        <button class="btn btn-sm btn-success mr-2" @click="exportToExcel">
                                            <i class="fas fa-file-excel mr-1"></i> Export
                                        </button>
                                        <button class="btn btn-sm btn-info" @click="refreshData">
                                            <i class="fas fa-sync-alt mr-1"></i> Refresh
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Order</th>
                                                <th>Payment</th>
                                                <th>Date</th>
                                                <th>QuiviServe</th>
                                                <th>QuiviCare</th>
                                                <th>Status</th>
                                                <th>Remaining</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='order in paginatedOrders' :key="order.id">
                                                <td>
                                                    <span class="badge badge-light">{{ order.order_id }}</span><br><br>
                                                    <!-- Reason -->
                                                    <div class="mb-1">
                                                        <span v-if="order.is_reason == 1" class="badge badge-primary">
                                                        <i class="fas fa-briefcase mr-1"></i> Workstation
                                                        </span>
                                                        <span v-else-if="order.is_reason == 2" class="badge badge-success">
                                                        <i class="fas fa-gamepad mr-1"></i> Gaming
                                                        </span>
                                                    </div><br>
                                                    <strong>{{ order.customer && order.customer.full_name ? order.customer.full_name : 'N/A' }}</strong><br>
                                                    <small class="text-muted">{{ order.customer && order.customer.email ? order.customer.email : '' }}</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">Total Part:</small><br>
                                                    <strong>RM{{ formatNumber(order.total || 0) }}</strong><br>
                                                    <small class="text-muted">Fees: RM{{
                                                        formatNumber(
                                                            (order.craft && order.craft.fee ? Number(order.craft.fee) : 0) +
                                                            (order.serve && order.serve.fee ? Number(order.serve.fee) : 0) +
                                                            careFeeContribution(order)
                                                        )
                                                    }}</small><br>
                                                    <strong>
                                                        Grand Total <br> RM {{ formatNumber(
                                                                (order && order.total ? Number(order.total) : 0) +
                                                                (order.craft && order.craft.fee ? Number(order.craft.fee) : 0) +
                                                                (order.serve && order.serve.fee ? Number(order.serve.fee) : 0) +
                                                                careFeeContribution(order)
                                                            ) }}
                                                    </strong>
                                                </td>
                                                <td>
                                                    {{ formatDate(order.order_date) }}
                                                </td>
                                                <td>
                                                    <span
                                                        v-if="order.serve"
                                                        class="badge serve-badge"
                                                        :class="{ 'serve-badge-clickable': canOpenServeRecord(order) }"
                                                        :style="{
                                                            backgroundColor: order.serve.colour,
                                                            color: isLightColor(order.serve.colour) ? '#000' : '#fff'
                                                        }"
                                                        :title="canOpenServeRecord(order) ? `Open ${order.serve.name} record` : null"
                                                        @click="canOpenServeRecord(order) && goToServeRecord(order, serveTierFor(order))"
                                                    >
                                                        {{ order.serve.name }}
                                                        <i v-if="canOpenServeRecord(order)" class="fas fa-arrow-right ml-1"></i>
                                                    </span>
                                                    <span v-else class="badge badge-secondary">N/A</span>
                                                </td>
                                                <td>
                                                     <span
                                                        v-if="order.serve"
                                                        class="badge serve-badge"
                                                        :class="{ 'serve-badge-clickable': canOpenCareRecord(order) }"
                                                        :style="{
                                                            backgroundColor: order.serve.colour,
                                                            color: isLightColor(order.serve.colour) ? '#000' : '#fff'
                                                        }"
                                                        :title="canOpenCareRecord(order) ? 'Open QuiviCare record' : null"
                                                        @click="canOpenCareRecord(order) && goToCareRecord(order)"
                                                    >
                                                        {{ order.care && order.care.name ? order.care.name : 'N/A' }}
                                                        <i v-if="canOpenCareRecord(order)" class="fas fa-arrow-right ml-1"></i>
                                                    </span>
                                                    <span v-else class="badge badge-secondary">N/A</span>
                                                </td>
                                                <td>
                                                    <span :class="getStatusBadgeClass(order)" class="badge">
                                                        {{ getStatusText(order) }}
                                                    </span>
                                                    <span :class="getStatusBadgeClass(order)" class="badge" v-if="order.invoice_id ">
                                                        {{ order.invoice_id }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div v-if="careMembershipUpdateOn(order)">
                                                        <span class="badge badge-info">
                                                            <i class="fa fa-shield-alt mr-1"></i>
                                                            {{ careMembershipRemaining(order) }}
                                                        </span>
                                                    </div>
                                                    <div v-else-if="order.approve == 1 && order.approved_at">
                                                        <span :class="getRemainingClass(order)" class="badge">
                                                            <i class="fa fa-clock mr-1"></i>
                                                            {{ order.time_remaining }}
                                                        </span>
                                                    </div>
                                                    <div v-else class="text-muted">
                                                        -
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <router-link
                                                            :to="{name:'vieworder', params:{id:order.id}}"
                                                            class="btn btn-sm btn-primary"
                                                            title="View Details"
                                                        >
                                                            <i class="fas fa-eye"></i>
                                                        </router-link>
                                                        <button
                                                            class="btn btn-sm btn-info"
                                                            @click="showQuickInfo(order)"
                                                            title="Quick Info"
                                                        >
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                        <router-link
                                                            :to="{name:'craftinspection', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Studio Inspection"
                                                        >
                                                            <i class="fas fa-clipboard-check"></i>
                                                        </router-link>
                                                        <router-link
                                                            :to="{name:'performancetest', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Performance Testing"
                                                        >
                                                            <i class="fas fa-tachometer-alt"></i>
                                                        </router-link>
                                                        <!-- Approve Button -->
                                                        <button
                                                            v-if="order.approve === null || order.approve === '' || order.approve === undefined"
                                                            class="btn btn-sm btn-success ml-1"
                                                            @click="approveOrder(order, 1)"
                                                            title="Approve Order"
                                                        >
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <!-- Reject Button -->
                                                        <button
                                                            v-if="order.approve === null || order.approve === '' || order.approve === undefined"
                                                            class="btn btn-sm btn-danger ml-1"
                                                            @click="approveOrder(order, 0)"
                                                            title="Reject Order"
                                                        >
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <!-- Reset to Draft Button -->
                                                        <button
                                                            v-if="order.approve == 1 || order.approve == 0"
                                                            class="btn btn-sm btn-warning ml-1"
                                                            @click="approveOrder(order, null)"
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
                                                        <h5>No orders found</h5>
                                                        <p class="text-muted">Try adjusting your filters or create a new order</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="card-footer" v-if="filteredOrders.length > itemsPerPage">
                                    <nav aria-label="Order navigation">
                                        <ul class="pagination justify-content-center mb-0">
                                            <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                                <button class="page-link" @click="prevPage">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>
                                            </li>
                                            <li
                                                class="page-item"
                                                v-for="page in totalPages"
                                                :key="page"
                                                :class="{ active: page === currentPage }"
                                            >
                                                <button class="page-link" @click="goToPage(page)">
                                                    {{ page }}
                                                </button>
                                            </li>
                                            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                                <button class="page-link" @click="nextPage">
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </nav>
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
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
    components: { ColumnSearchPanel },
    data() {
        return {
            orders: [],
            statistics: {
                overview: [],
                today_orders: 0,
                today_revenue: 0
            },
            showFilters: false,
            filterColumns: [
                { key: 'order_id', label: 'Order ID', type: 'text' },
                { key: 'customer_name', label: 'Customer Name', type: 'text' },
                { key: 'customer_email', label: 'Customer Email', type: 'text' },
                { key: 'total', label: 'Total (RM)', type: 'text' },
            ],
            filters: {
                order_id: '',
                customer_name: '',
                customer_email: '',
                total: '',
                approve: '', // Changed from status to approve
                date_from: '',
                date_to: '',
                serve_id: '',
                care_id: ''
            },
            currentPage: 1,
            itemsPerPage: 10,
            loading: false
        }
    },
    computed: {
        filteredOrders() {
            let filtered = this.orders;

            // Order ID filter
            if (this.filters.order_id) {
                const kw = this.filters.order_id.toLowerCase();
                filtered = filtered.filter(order => order.order_id && order.order_id.toString().toLowerCase().includes(kw));
            }

            // Customer Name filter
            if (this.filters.customer_name) {
                const kw = this.filters.customer_name.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(kw));
            }

            // Customer Email filter
            if (this.filters.customer_email) {
                const kw = this.filters.customer_email.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.email && order.customer.email.toLowerCase().includes(kw));
            }

            // Total filter
            if (this.filters.total) {
                filtered = filtered.filter(order => order.total !== undefined && order.total !== null && order.total.toString().includes(this.filters.total));
            }

            // Approve status filter - fixed to use actual approve values
            if (this.filters.approve !== '') {
                if (this.filters.approve === 'null') {
                    // Filter for draft orders (null or undefined)
                    filtered = filtered.filter(order =>
                        order.approve === null ||
                        order.approve === '' ||
                        order.approve === undefined
                    );
                } else {
                    // Filter for numeric values (1 = approved, 0 = rejected)
                    const approveValue = parseInt(this.filters.approve);
                    filtered = filtered.filter(order => order.approve == approveValue);
                }
            }

            // Date range filter
            if (this.filters.date_from) {
                filtered = filtered.filter(order =>
                    order.order_date && new Date(order.order_date) >= new Date(this.filters.date_from)
                );
            }
            if (this.filters.date_to) {
                filtered = filtered.filter(order =>
                    order.order_date && new Date(order.order_date) <= new Date(this.filters.date_to + 'T23:59:59')
                );
            }

            return filtered;
        },
        paginatedOrders() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredOrders.slice(start, end);
        },
        totalPages() {
            return Math.ceil(this.filteredOrders.length / this.itemsPerPage);
        }
    },
    methods: {
        getOrders() {
            this.loading = true;
            const params = {};

            // Add filters to params if they exist
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key] !== '' && this.filters[key] !== undefined) {
                    // Handle null value for draft
                    if (key === 'approve' && this.filters[key] === 'null') {
                        params[key] = null;
                    } else {
                        params[key] = this.filters[key];
                    }
                }
            });

            axios.get('/api/orders', { params })
                .then(res => {
                    this.orders = res.data;
                    this.loading = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loading = false;
                });
        },
        getStatistics() {
            axios.get('/api/orders/statistics')
                .then(res => {
                    this.statistics = res.data;
                    // Format overview statistics
                    this.statistics.overview = [
                        {
                            label: 'Total Orders',
                            value: res.data.overview ? res.data.overview.total_orders : 0,
                            icon: 'fa-shopping-cart',
                            iconClass: 'bg-primary',
                            class: 'text-primary',
                            description: 'All time orders'
                        },
                        {
                            label: 'Total Revenue',
                            value: 'RM' + this.formatNumber(res.data.overview ? res.data.overview.total_revenue : 0),
                            icon: 'fa-dollar-sign',
                            iconClass: 'bg-success',
                            class: 'text-success',
                            description: 'All time revenue'
                        },
                        {
                            label: 'Approved Orders',
                            value: res.data.overview ? res.data.overview.total_approved : 0,
                            icon: 'fa-check-circle',
                            iconClass: 'bg-info',
                            class: 'text-info',
                            description: 'Approved orders'
                        },
                        {
                            label: 'Draft Orders',
                            value: res.data.overview ? res.data.overview.total_draft : 0,
                            icon: 'fa-clock',
                            iconClass: 'bg-warning',
                            class: 'text-warning',
                            description: 'Draft orders'
                        },
                        {
                            key: 'active_orders',
                            label: 'Active Orders',
                            value: res.data.overview ? res.data.overview.active_orders : 0,
                            icon: 'fa-check-circle',
                            iconClass: 'bg-success',
                            class: 'text-success',
                            description: 'Active Orders (within 6 months)'
                        },
                        {
                            key: 'expired_orders',
                            label: 'Expired Orders',
                            value: res.data.overview ? res.data.overview.expired_orders : 0,
                            icon: 'fa-clock',
                            iconClass: 'bg-danger',
                            class: 'text-danger',
                            description: 'Expired Orders (older than 6 months)'
                        }
                    ];
                })
                .catch(err => {
                    console.error(err);
                });
        },
        getStatValue(key) {
            // Safely get statistic value
            if (this.statistics.overview) {
                const stat = this.statistics.overview.find(s => s.key === key);
                return stat ? stat.value : 0;
            }
            return 0;
        },
        formatNumber(value) {
            return Number(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        formatDate(date) {
            // if (!date) return '';
            // const d = new Date(date);
            // return d.toLocaleDateString('en-MY', {
            //     day: '2-digit',
            //     month: 'short',
            //     year: 'numeric'
            // });

            if (!date) return '';

            // Convert to UTC date string
            const d = new Date(date);

            // Get UTC components
            const day = String(d.getUTCDate()).padStart(2, '0');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = monthNames[d.getUTCMonth()];
            const year = d.getUTCFullYear();

            return `${parseInt(day)} ${month} ${year}`;
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
        applyFilters() {
            this.currentPage = 1;
            this.getOrders();
        },
        resetFilters() {
            this.filters = {
                order_id: '',
                customer_name: '',
                customer_email: '',
                total: '',
                approve: '',
                date_from: '',
                date_to: '',
                serve_id: '',
                care_id: ''
            };
            this.currentPage = 1;
            this.getOrders();
        },
        canOpenServeRecord(order) {
            return order.approve == 1 && [1, 2, 3].includes(Number(order.serve_id));
        },

        serveTierFor(order) {
            return { 1: 'bek', 2: 'mps', 3: 'pce' }[Number(order.serve_id)];
        },

        goToServeRecord(order, tier) {
            const endpoints = {
                bek: `/api/serve-beks/order/${order.id}`,
                mps: `/api/serve-mps/order/${order.id}`,
                pce: `/api/serve-pce/order/${order.id}`
            };
            const routeNames = {
                bek: 'servebekedit',
                mps: 'servempsedit',
                pce: 'servepceedit'
            };
            const endpoint = endpoints[tier];
            const routeName = routeNames[tier];

            axios.get(endpoint)
                .then((response) => {
                    const record = response.data.data;
                    this.$router.push({ name: routeName, params: { id: record.id } });
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to open the Serve record', 'error');
                });
        },

        canOpenCareRecord(order) {
            return order.approve == 1 && !!order.care_id;
        },

        goToCareRecord(order) {
            axios.get(`/api/care-data/order/${order.id}`)
                .then((response) => {
                    const record = (response.data.data || [])[0];
                    if (!record) {
                        Swal.fire('Not Found', 'No QuiviCare record exists for this order yet.', 'warning');
                        return;
                    }
                    this.$router.push({ name: 'caredataedit', params: { id: record.id } });
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to open the Care record', 'error');
                });
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

            // Make sure we're sending the correct status value
            console.log('Updating order:', order.id, 'to status:', status);

            axios.put(`/api/order/${order.id}/approve`, {
                approve: status
            })
            .then((response) => {
                loading.close();

                // Update local order data - convert to correct type
                order.approve = status;

                // Set approved_at only for approved orders
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

                // Refresh the data to get updated orders
                this.getOrders();
                this.getStatistics();
            })
            .catch((error) => {
                loading.close();
                console.error('Error updating approval:', error);
                console.error('Error response:', error.response);

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update order status. Please check the console for details.',
                    confirmButtonText: 'OK'
                });
            });
        },
        getStatusBadgeClass(order) {
            // Handle all possible null/undefined/empty cases
            if (order.approve === null || order.approve === '' || order.approve === undefined) {
                return 'badge-secondary';
            }
            if (order.approve == 1) return 'badge-success';
            if (order.approve == 0) return 'badge-danger';
            return 'badge-primary';
        },
        getStatusText(order) {
            // Handle all possible null/undefined/empty cases
            if (order.approve === null || order.approve === '' || order.approve === undefined) {
                return 'Draft';
            }
            if (order.approve == 1) return 'Approved';
            if (order.approve == 0) return 'Rejected';
            return 'Unknown';
        },
        getRemainingClass(order) {
            if (order.time_remaining === 'Expired') return 'badge-danger';
            if (order.months_remaining < 1) return 'badge-warning';
            return 'badge-success';
        },
        careMembershipUpdateOn(order) {
            const careData = order.care_data && order.care_data[0];
            return !!(careData && careData.update_membership);
        },
        // Customer opted out of QuiviCare at checkout (skip_quivicare) -- no
        // CareData was/will be created for this order, so its charge must
        // not appear in the Fees/Grand Total breakdown at all.
        careFeeContribution(order) {
            if (order && order.skip_quivicare) return 0;
            return (order && order.care_price) ? Number(order.care_price) : 0;
        },
        careMembershipRemaining(order) {
            const careData = order.care_data && order.care_data[0];
            return careData ? careData.membership_remaining : 'N/A';
        },
        showQuickInfo(order) {
            Swal.fire({
                title: `Order #${order.order_id}`,
                html: `
                    <div class="text-left">
                        <p><strong>Customer:</strong> ${order.customer ? order.customer.full_name : 'N/A'}</p>
                        <p><strong>Email:</strong> ${order.customer && order.customer.email ? order.customer.email : 'N/A'}</p>
                        <p><strong>Order Date:</strong> ${this.formatDate(order.order_date)}</p>
                        <p><strong>Total Amount:</strong> RM${this.formatNumber(order.total || 0)}</p>
                        <p><strong>Status:</strong> <span class="badge ${this.getStatusBadgeClass(order)}">${this.getStatusText(order)}</span></p>
                        <p><strong>Approve Value:</strong> ${order.approve !== null && order.approve !== undefined ? order.approve : 'null'}</p>
                        ${order.approved_at ? `<p><strong>Approved At:</strong> ${this.formatDate(order.approved_at)}</p>` : ''}
                        ${order.time_remaining ? `<p><strong>Remaining:</strong> ${order.time_remaining}</p>` : ''}
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false
            });
        },
        exportToExcel() {
            Swal.fire({
                title: 'Export Options',
                html: `
                    <div class="text-left">
                        <p>Choose export format:</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatExcel" value="excel" checked>
                            <label class="form-check-label" for="formatExcel">
                                Excel/HTML Format (Styled Report)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatCSV" value="csv">
                            <label class="form-check-label" for="formatCSV">
                                Simple CSV Format
                            </label>
                        </div>
                        <br>
                        <p>Choose what to export:</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportScope" id="exportFiltered" value="filtered" checked>
                            <label class="form-check-label" for="exportFiltered">
                                Export filtered data (${this.filteredOrders.length} records)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportScope" id="exportAll" value="all">
                            <label class="form-check-label" for="exportAll">
                                Export all data (${this.orders.length} records)
                            </label>
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Export',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const format = document.querySelector('input[name="exportFormat"]:checked').value;
                    const scope = document.querySelector('input[name="exportScope"]:checked').value;
                    return { format, scope };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { format, scope } = result.value;

                    if (format === 'excel') {
                        this.generateStyledExcelReport(scope);
                    } else {
                        this.generateSimpleCSV(scope);
                    }
                }
            });
        },
        async generateStyledExcelReport(scope) {
            Swal.fire({
                title: 'Generating Report...',
                text: 'Please wait while we prepare your export',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                let dataToExport;
                if (scope === 'filtered') {
                    // Get ALL filtered data
                    dataToExport = await this.getAllFilteredOrders();
                } else {
                    // Fetch all data without any filters
                    const params = { per_page: 10000 };
                    const res = await axios.get('/api/orders', { params });
                    dataToExport = res.data;
                }

                if (!dataToExport || dataToExport.length === 0) {
                    Swal.close();
                    Swal.fire('No Data', 'There is no data to export', 'warning');
                    return;
                }

                // Calculate summary statistics
                const totalOrders = dataToExport.length;
                const totalRevenue = dataToExport.reduce((sum, order) => sum + (parseFloat(order.total) || 0), 0);
                const totalFees = dataToExport.reduce((sum, order) => {
                    return sum + (
                        (order.craft && order.craft.fee ? Number(order.craft.fee) : 0) +
                        (order.serve && order.serve.fee ? Number(order.serve.fee) : 0) +
                        this.careFeeContribution(order)
                    );
                }, 0);
                const approvedOrders = dataToExport.filter(order => order.approve == 1).length;
                const rejectedOrders = dataToExport.filter(order => order.approve == 0).length;
                const draftOrders = dataToExport.filter(order => order.approve === null || order.approve === '' || order.approve === undefined).length;

                const exportDate = new Date().toLocaleString('en-MY', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Generate filter summary
                const filterSummary = [];
                if (this.filters.order_id) filterSummary.push(`Order ID: "${this.filters.order_id}"`);
                if (this.filters.customer_name) filterSummary.push(`Customer Name: "${this.filters.customer_name}"`);
                if (this.filters.customer_email) filterSummary.push(`Customer Email: "${this.filters.customer_email}"`);
                if (this.filters.total) filterSummary.push(`Total: "${this.filters.total}"`);
                if (this.filters.approve) {
                    const statusMap = {
                        '1': 'Approved',
                        '0': 'Rejected',
                        'null': 'Draft'
                    };
                    filterSummary.push(`Status: ${statusMap[this.filters.approve] || this.filters.approve}`);
                }
                if (this.filters.date_from) filterSummary.push(`From: ${this.filters.date_from}`);
                if (this.filters.date_to) filterSummary.push(`To: ${this.filters.date_to}`);

                const htmlContent = `
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <title>QuiviCraft Report</title>
                    <style>
                        body {
                            font-family: Arial, Helvetica, sans-serif;
                            margin: 20px;
                            background-color: #ffffff;
                        }
                        h1 {
                            color: #4e73df;
                            text-align: center;
                            font-size: 24px;
                            margin-bottom: 5px;
                        }
                        h3 {
                            text-align: center;
                            color: #858796;
                            font-size: 14px;
                            margin-top: 0;
                            margin-bottom: 20px;
                            font-weight: normal;
                        }
                        .stats-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: black;
                        }
                        .stats-table td {
                            padding: 15px;
                            text-align: center;
                            border: none;
                        }
                        .stats-label {
                            font-size: 12px;
                            text-transform: uppercase;
                        }
                        .stats-value {
                            font-size: 20px;
                            font-weight: bold;
                            margin-top: 5px;
                        }
                        .filter-section {
                            background-color: #f8f9fc;
                            padding: 15px;
                            border-radius: 8px;
                            margin-bottom: 20px;
                            border: 1px solid #e3e6f0;
                        }
                        .filter-title {
                            font-size: 14px;
                            font-weight: bold;
                            color: #4e73df;
                            margin-bottom: 10px;
                        }
                        .filter-badge {
                            background-color: #4e73df;
                            color: white;
                            padding: 5px 10px;
                            border-radius: 20px;
                            font-size: 12px;
                            display: inline-block;
                            margin-right: 5px;
                            margin-bottom: 5px;
                        }
                        .generated-info {
                            font-size: 11px;
                            color: #858796;
                            text-align: right;
                            margin-bottom: 10px;
                        }
                        table.data-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                            font-size: 12px;
                        }
                        table.data-table th {
                            background-color: #4e73df;
                            color: white;
                            font-weight: bold;
                            padding: 12px;
                            text-align: center;
                            border: 1px solid #ddd;
                        }
                        table.data-table td {
                            padding: 8px;
                            border: 1px solid #ddd;
                            text-align: center;
                            color: #000000; /* Black text for all cells */
                        }
                        table.data-table tr:nth-child(even) {
                            background-color: #f2f2f2;
                        }
                        .total-row {
                            font-weight: bold;
                            background-color: #4e73df !important;
                            color: white;
                        }
                        .total-row td {
                            color: white !important; /* Keep total row white text */
                        }
                        .footer {
                            text-align: center;
                            font-size: 10px;
                            color: #95a5a6;
                            margin-top: 30px;
                            padding-top: 10px;
                            border-top: 1px solid #ecf0f1;
                        }
                        .text-right { text-align: right; }
                        .text-left { text-align: left; }
                        .text-center { text-align: center; }
                        .text-black { color: #000000; } /* Utility class for black text */
                    </style>
                </head>
                <body>
                    <h1>ORDERS REPORT</h1>
                    <h3>Comprehensive Order Data Analysis</h3>

                    <!-- Statistics Table -->
                    <table class="stats-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td><div class="stats-label">Total Orders</div><div class="stats-value">${totalOrders}</div></td>
                            <td><div class="stats-label">Total Revenue</div><div class="stats-value">RM ${this.formatNumber(totalRevenue)}</div></td>
                            <td><div class="stats-label">Total Fees</div><div class="stats-value">RM ${this.formatNumber(totalFees)}</div></td>
                            <td><div class="stats-label">Approved</div><div class="stats-value">${approvedOrders}</div></td>
                            <td><div class="stats-label">Draft</div><div class="stats-value">${draftOrders}</div></td>
                            <td><div class="stats-label">Rejected</div><div class="stats-value">${rejectedOrders}</div></td>
                        </tr>
                    </table>

                    <div class="generated-info">
                        Generated on: ${exportDate}
                    </div>

                    <!-- Main Data Table -->
                    <table class="data-table" cellspacing="0" cellpadding="0" border="1">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Customer Email</th>
                                <th>Order Date</th>
                                <th>Total (RM)</th>
                                <th>QuiviServe</th>
                                <th>QuiviCare</th>
                                <th>Status</th>
                                <th>Time Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${dataToExport.map((order, index) => {
                                const statusText = order.approve === null || order.approve === '' || order.approve === undefined ? 'Draft' :
                                                order.approve == 1 ? 'Approved' : 'Rejected';

                                const serveStyle = order.serve && order.serve.colour ?
                                    `background-color: ${order.serve.colour}; color: #000000;` :
                                    'background-color: #f2f2f2; color: #000000;';

                                return `
                                <tr>
                                    <td class="text-center">${index + 1}</td>
                                    <td class="text-center"><strong>${this.escapeHtml(order.order_id || 'N/A')}</strong></td>
                                    <td class="text-left">${this.escapeHtml(order.customer?.full_name || 'N/A')}</td>
                                    <td class="text-left">${this.escapeHtml(order.customer?.email || 'N/A')}</td>
                                    <td class="text-center">${this.formatDate(order.order_date)}</td>
                                    <td class="text-right"><strong>RM ${this.formatNumber(order.total || 0)}</strong></td>
                                    <td class="text-center">
                                        <span style="${serveStyle} padding: 3px 8px; border-radius: 20px; color: #000000;">
                                            ${this.escapeHtml(order.serve?.name || 'N/A')}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span style="background-color: #f2f2f2; padding: 3px 8px; border-radius: 20px; color: #000000;">
                                            ${this.escapeHtml(order.care?.name || 'N/A')}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span style="color: #000000;">
                                            ${statusText}
                                        </span>
                                        ${order.invoice_id ? `<br><small>${this.escapeHtml(order.invoice_id)}</small>` : ''}
                                    </td>
                                    <td class="text-center">
                                        ${order.approve == 1 && order.approved_at ?
                                            `<span style="color: #000000;">
                                                ${this.escapeHtml(order.time_remaining || 'N/A')}
                                            </span>` :
                                            '-'
                                        }
                                    </td>
                                </tr>
                            `}).join('')}

                            <tr class="total-row">
                                <td colspan="5" class="text-center"><strong>GRAND TOTAL</strong></td>
                                <td class="text-right"><strong>RM ${this.formatNumber(totalRevenue)}</strong></td>
                                <td colspan="4"></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="footer">
                        <p>Generated by Order Management System | ${exportDate}</p>
                        <p>This is a computer-generated report. No signature is required.</p>
                        <p>Total Pages: 1 | Confidential</p>
                    </div>
                </body>
                </html>`;

                const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');

                const date = new Date().toISOString().split('T')[0];
                const filterType = scope === 'filtered' ? 'Filtered' : 'All';
                const filename = `Orders_Report_${date}_${filterType}.xls`;

                link.href = url;
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                window.URL.revokeObjectURL(url);

                Swal.close();
                Swal.fire({
                    title: 'Export Complete!',
                    text: `Report "${filename}" has been downloaded`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });

            } catch (error) {
                console.error('Export error:', error);
                Swal.fire({
                    title: 'Export Failed!',
                    text: error.response?.data?.message || error.message || 'Failed to generate report',
                    icon: 'error'
                });
            }
        },
        async getAllFilteredOrders() {
            try {
                const params = {
                    ...this.filters,
                    per_page: 10000,
                    page: 1
                };

                // Handle null value for draft
                if (params.approve === 'null') {
                    params.approve = null;
                }

                // Remove empty filters
                Object.keys(params).forEach(key => {
                    if (params[key] === '' || params[key] === null || params[key] === undefined) {
                        delete params[key];
                    }
                });

                console.log('Fetching all filtered orders with params:', params);

                const res = await axios.get('/api/orders', { params });

                let filteredData = res.data || [];

                // Apply any client-side filtering if needed
                if (this.filters.order_id) {
                    const kw = this.filters.order_id.toLowerCase();
                    filteredData = filteredData.filter(order => order.order_id && order.order_id.toString().toLowerCase().includes(kw));
                }
                if (this.filters.customer_name) {
                    const kw = this.filters.customer_name.toLowerCase();
                    filteredData = filteredData.filter(order => order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(kw));
                }
                if (this.filters.customer_email) {
                    const kw = this.filters.customer_email.toLowerCase();
                    filteredData = filteredData.filter(order => order.customer && order.customer.email && order.customer.email.toLowerCase().includes(kw));
                }
                if (this.filters.total) {
                    filteredData = filteredData.filter(order => order.total !== undefined && order.total !== null && order.total.toString().includes(this.filters.total));
                }

                // Apply date filters again to ensure consistency
                if (this.filters.date_from) {
                    filteredData = filteredData.filter(order =>
                        order.order_date && new Date(order.order_date) >= new Date(this.filters.date_from)
                    );
                }
                if (this.filters.date_to) {
                    filteredData = filteredData.filter(order =>
                        order.order_date && new Date(order.order_date) <= new Date(this.filters.date_to + 'T23:59:59')
                    );
                }

                console.log(`Fetched ${filteredData.length} records for export`);
                return filteredData;

            } catch (error) {
                console.error('Error fetching all filtered orders:', error);
                // Fallback to client-side filtering from current data
                return this.getFilteredOrdersForExport();
            }
        },

        generateSimpleCSV(scope) {
            Swal.fire({
                title: 'Generating CSV...',
                text: 'Please wait while we prepare your export',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                let dataToExport;
                if (scope === 'filtered') {
                    dataToExport = this.filteredOrders;
                } else {
                    dataToExport = this.orders;
                }

                if (!dataToExport || dataToExport.length === 0) {
                    Swal.close();
                    Swal.fire('No Data', 'There is no data to export', 'warning');
                    return;
                }

                const headers = [
                    'No.',
                    'Order ID',
                    'Customer Name',
                    'Customer Email',
                    'Order Date',
                    'Total Amount (RM)',
                    'Fees (RM)',
                    'QuiviServe',
                    'QuiviCare',
                    'Status',
                    'Invoice ID',
                    'Time Remaining',
                    'Approved At',
                    'Created At'
                ];

                const rows = dataToExport.map((order, index) => {
                    const fees = (
                        (order.craft && order.craft.fee ? Number(order.craft.fee) : 0) +
                        (order.serve && order.serve.fee ? Number(order.serve.fee) : 0) +
                        this.careFeeContribution(order)
                    );

                    const status = order.approve === null || order.approve === '' || order.approve === undefined ? 'Draft' :
                                order.approve == 1 ? 'Approved' : 'Rejected';

                    return [
                        index + 1,
                        order.order_id || '',
                        order.customer?.full_name || '',
                        order.customer?.email || '',
                        this.formatDate(order.order_date),
                        order.total || '0',
                        fees.toFixed(2),
                        order.serve?.name || 'N/A',
                        order.care?.name || 'N/A',
                        status,
                        order.invoice_id || '',
                        order.time_remaining || 'N/A',
                        order.approved_at ? this.formatDate(order.approved_at) : '',
                        order.created_at ? this.formatDate(order.created_at) : ''
                    ].map(cell => `"${cell}"`);
                });

                const csvContent = [
                    headers.join(','),
                    ...rows.map(row => row.join(','))
                ].join('\n');

                const BOM = '\uFEFF';
                const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');

                const date = new Date().toISOString().split('T')[0];
                const filterType = scope === 'filtered' ? 'Filtered' : 'All';
                const filename = `QuiviCraft_Data_${date}_${filterType}.csv`;

                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                URL.revokeObjectURL(url);

                Swal.close();
                Swal.fire({
                    title: 'Export Complete!',
                    text: 'CSV file has been generated and downloaded',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

            } catch (error) {
                console.error('CSV export error:', error);
                Swal.fire({
                    title: 'Export Failed!',
                    text: error.message || 'Failed to generate CSV',
                    icon: 'error'
                });
            }
        },

        getFilteredOrdersForExport() {
            let filtered = [...this.orders];

            // Order ID filter
            if (this.filters.order_id) {
                const kw = this.filters.order_id.toLowerCase();
                filtered = filtered.filter(order => order.order_id && order.order_id.toString().toLowerCase().includes(kw));
            }

            // Customer Name filter
            if (this.filters.customer_name) {
                const kw = this.filters.customer_name.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.full_name && order.customer.full_name.toLowerCase().includes(kw));
            }

            // Customer Email filter
            if (this.filters.customer_email) {
                const kw = this.filters.customer_email.toLowerCase();
                filtered = filtered.filter(order => order.customer && order.customer.email && order.customer.email.toLowerCase().includes(kw));
            }

            // Total filter
            if (this.filters.total) {
                filtered = filtered.filter(order => order.total !== undefined && order.total !== null && order.total.toString().includes(this.filters.total));
            }

            // Approve status filter
            if (this.filters.approve !== '') {
                if (this.filters.approve === 'null') {
                    filtered = filtered.filter(order =>
                        order.approve === null ||
                        order.approve === '' ||
                        order.approve === undefined
                    );
                } else {
                    const approveValue = parseInt(this.filters.approve);
                    filtered = filtered.filter(order => order.approve == approveValue);
                }
            }

            // Date range filter
            if (this.filters.date_from) {
                filtered = filtered.filter(order =>
                    order.order_date && new Date(order.order_date) >= new Date(this.filters.date_from)
                );
            }
            if (this.filters.date_to) {
                filtered = filtered.filter(order =>
                    order.order_date && new Date(order.order_date) <= new Date(this.filters.date_to + 'T23:59:59')
                );
            }

            return filtered;
        },

        escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, m => map[m]);
        },
        refreshData() {
            this.getOrders();
            this.getStatistics();
            Swal.fire({
                icon: 'success',
                title: 'Refreshed',
                text: 'Data has been refreshed',
                timer: 1000,
                showConfirmButton: false
            });
        },
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },
        goToPage(page) {
            this.currentPage = page;
        }
    },
    created() {
        if (!User.loggedIn()) {
            this.$router.push({ name: 'login' });
        }
        this.getOrders();
        this.getStatistics();
    }
}
</script>

<style scoped>
.stat-card {
    background: white;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.serve-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: all 0.15s ease;
}

.serve-badge-clickable {
    cursor: pointer;
}

.serve-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.empty-state {
    padding: 40px 0;
}

.badge {
    font-size: 12px;
    padding: 5px 10px;
}

.page-link {
    cursor: pointer;
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.input-group-text {
    background-color: #f8f9fc;
    border: 1px solid #d1d3e2;
}

.btn-group .btn {
    margin-right: 5px;
}

.table th {
    border-top: none;
    border-bottom: 2px solid #e3e6f0;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
}

.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
