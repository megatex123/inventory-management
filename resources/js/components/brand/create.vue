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
                                            <router-link to="/Brand" class="btn btn-outline-primary">
                                                <i class="fas fa-arrow-left mr-1"></i> Back to Brand
                                            </router-link>
                                            <div>
                                                <h5 class="m-0 font-weight-bold text-primary text-center mb-2">Create New Brand</h5>
                                                <p class="text-muted text-center small mb-0">Add a new Brand to your system</p>
                                            </div>
                                            <!-- Empty div for balance -->
                                            <div style="width: 120px;"></div>
                                        </div>
                                    </div>

                                    <!-- Add Brand Form -->
                                    <div class="row px-3 mt-4">
                                        <div class="col-lg-8 m-auto">
                                            <div class="card shadow-sm">
                                                <div class="card-body">
                                                    <form class="user" @submit.prevent='BrandInsert' enctype="multipart/form-data">
                                                        <div class="form-group">
                                                            <label class="small font-weight-bold text-muted">Brand Name *</label>
                                                            <input
                                                                type="text"
                                                                class="form-control form-control-lg"
                                                                v-model='form.name'
                                                                placeholder="Enter Brand name"
                                                                :class="{'is-invalid': errors.name}"
                                                            >
                                                            <div class="invalid-feedback" v-if='errors.name'>
                                                                {{ errors.name[0] }}
                                                            </div>
                                                            <small class="form-text text-muted">
                                                                Example: Electronics, Clothing, Furniture, etc.
                                                            </small>
                                                        </div>

                                                        <!-- Form Validation Summary -->
                                                        <div class="alert alert-danger" v-if="Object.keys(errors).length > 0 && !errors.name && !errors.code">
                                                            <small>
                                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                Please fix the errors above before submitting.
                                                            </small>
                                                        </div>

                                                        <div class="form-group mt-4">
                                                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                                                <i class="fas fa-save mr-1"></i>Create Brand
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
        }
    },
    data() {
        return {
            form: {
                name: null,
                code: null,
            },
            errors: {}
        }
    },
    methods: {
        BrandInsert() {
            axios.post('/api/brand', this.form)
                .then(() => {
                    this.$router.push({ name: 'Brand' });
                    notification.success('Brand created successfully!');
                })
                .catch(err => {
                    this.errors = err.response.data.errors || {};
                    notification.error('Please fix the errors and try again.');
                });
        },

        goBack() {
            this.$router.push({ name: 'Brand' });
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
.form-control:focus {
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
</style>
