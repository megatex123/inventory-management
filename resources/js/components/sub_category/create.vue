<template>
    <div>
        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card">
                                    <!-- Card Header with centered title and back button -->
                                    <div class="card-header py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <router-link to="/sub-category" class="btn btn-outline-primary">
                                                <i class="fas fa-arrow-left mr-1"></i> Back to Sub Categories
                                            </router-link>
                                            <div>
                                                <h5 class="m-0 font-weight-bold text-primary text-center mb-2">Create New Sub Category</h5>
                                                <p class="text-muted text-center small mb-0">Add a new sub category to your system</p>
                                            </div>
                                            <!-- Empty div for balance -->
                                            <div style="width: 120px;"></div>
                                        </div>
                                    </div>

                                    <!-- Add Sub Category Form -->
                                    <div class="row px-3 mt-4">
                                        <div class="col-lg-8 m-auto">
                                            <div class="card shadow-sm">
                                                <div class="card-body">
                                                    <form class="user" @submit.prevent='SubCategoryInsert' enctype="multipart/form-data">
                                                        <div class="form-group">
                                                            <label class="small font-weight-bold text-muted">Select Category *</label>
                                                            <select
                                                                v-model="form.cat_id"
                                                                class="form-control form-control-lg"
                                                                :class="{'is-invalid': errors.cat_id}"
                                                            >
                                                                <option disabled value="">Select a category</option>
                                                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                                    {{ cat.name }} ({{ cat.code }})
                                                                </option>
                                                            </select>
                                                            <div class="invalid-feedback" v-if='errors.cat_id'>
                                                                {{ errors.cat_id[0] }}
                                                            </div>
                                                            <small class="form-text text-muted">
                                                                Choose the parent category for this sub category
                                                            </small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="small font-weight-bold text-muted">Sub Category Name *</label>
                                                            <input
                                                                type="text"
                                                                class="form-control form-control-lg"
                                                                v-model='form.name'
                                                                placeholder="Enter sub category name"
                                                                :class="{'is-invalid': errors.name}"
                                                            >
                                                            <div class="invalid-feedback" v-if='errors.name'>
                                                                {{ errors.name[0] }}
                                                            </div>
                                                            <small class="form-text text-muted">
                                                                Example: Smartphones, T-Shirts, Office Chairs, etc.
                                                            </small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="small font-weight-bold text-muted">Sub Category Code *</label>
                                                            <input
                                                                type="text"
                                                                class="form-control form-control-lg"
                                                                v-model='form.code'
                                                                placeholder="Enter unique code"
                                                                :class="{'is-invalid': errors.code}"
                                                            >
                                                            <div class="invalid-feedback" v-if='errors.code'>
                                                                {{ errors.code[0] }}
                                                            </div>
                                                            <small class="form-text text-muted">
                                                                Use short, unique code (e.g., SPHONE for Smartphones, TSHIRT for T-Shirts)
                                                            </small>
                                                        </div>

                                                        <!-- Form Validation Summary -->
                                                        <div class="alert alert-danger" v-if="Object.keys(errors).length > 0 && !errors.cat_id && !errors.name && !errors.code">
                                                            <small>
                                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                Please fix the errors above before submitting.
                                                            </small>
                                                        </div>

                                                        <div class="form-group mt-4">
                                                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                                                <i class="fas fa-save mr-1"></i>Create Sub Category
                                                            </button>
                                                        </div>

                                                        <div class="form-group mt-2">
                                                            <button type="button" @click="goBack" class="btn btn-outline-secondary btn-block">
                                                                <i class="fas fa-times mr-1"></i>Cancel
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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
        this.fetchCategories();
    },
    data() {
        return {
            form: {
                cat_id: null,
                name: null,
                code: null,
            },
            errors: {},
            categories: [],
        }
    },
    methods: {
        fetchCategories() {
            axios.get('/api/categories')
            .then(res => {
                this.categories = res.data;
            })
            .catch(err => {
                console.error('Error fetching categories:', err);
                notification.error('Failed to load categories');
            })
        },

        SubCategoryInsert(){
            axios.post('/api/sub-categories', this.form)
            .then(() => {
                this.$router.push({name: 'SubCategory'})
                notification.success('Sub Category created successfully!')
            })
            .catch(err => {
                this.errors = err.response.data.errors || {};
                notification.error('Please fix the errors and try again.');
            })
        },

        goBack() {
            this.$router.push({ name: 'SubCategory' });
        }
    }
}
</script>

<style scoped>
.card {
    border: none;
    border-radius: 10px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e3e6f0;
}

.form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1rem;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }

    .card-header .btn-outline-primary {
        margin-bottom: 10px;
        margin-left: 0 !important;
        order: 2;
    }

    .card-header h5 {
        order: 1;
        margin-bottom: 10px;
        width: 100%;
    }

    .card-header .empty-div {
        display: none;
    }
}

/* Form focus states */
.form-control:focus, select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Button hover effects */
.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-outline-primary:hover, .btn-outline-secondary:hover {
    transform: translateY(-1px);
}

/* Animation for form */
.login-form {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Select dropdown styling */
select.form-control-lg {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234a5568' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1rem;
    padding-right: 2.5rem;
}
</style>
