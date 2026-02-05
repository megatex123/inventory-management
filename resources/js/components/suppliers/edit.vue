<template>
    <div>
        <div class='row'>
            <router-link to="/suppliers" class="btn btn-primary ml-3">
                <i class="fas fa-arrow-left mr-1"></i> All Suppliers
            </router-link>
        </div>
        <div class="row justify-content-center">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="login-form">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Update Supplier</h1>
                                    </div>
                                    <form class="user" @submit.prevent='suppliersUpdate' enctype="multipart/form-data">
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="small font-weight-bold text-muted">Full Name *</label>
                                                    <input type="text" class="form-control" v-model='form.name'
                                                        placeholder="Enter Full Name" required>
                                                    <small class="text-danger" v-if='errors.name'>{{ errors.name[0] }}</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="small font-weight-bold text-muted">Email *</label>
                                                    <input type="email" class="form-control" v-model='form.email'
                                                        placeholder="Enter Supplier Email" required>
                                                    <small class="text-danger" v-if='errors.email'>{{ errors.email[0] }}</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="small font-weight-bold text-muted">Address</label>
                                                    <input type="text" class="form-control" v-model='form.address'
                                                        placeholder="Enter Supplier Address">
                                                    <small class="text-danger" v-if='errors.address'>{{ errors.address[0] }}</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="small font-weight-bold text-muted">Shop Name</label>
                                                    <input type="text" class="form-control" v-model='form.shopname'
                                                        placeholder="Enter Shop Name">
                                                    <small class="text-danger" v-if='errors.shopname'>{{ errors.shopname[0] }}</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="small font-weight-bold text-muted">Phone Number *</label>
                                                    <input type="text" class="form-control" v-model='form.phone'
                                                        placeholder="Enter Phone Number" required>
                                                    <small class="text-danger" v-if='errors.phone'>{{ errors.phone[0] }}</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="small font-weight-bold text-muted">Photo</label>
                                                    <div class="custom-file">
                                                        <input type="file" @change='onFileSelect' class="custom-file-input" id="photoInputEdit">
                                                        <label class="custom-file-label" for="photoInputEdit" id="photoLabelEdit">
                                                            {{ photoFileName || 'Choose new file (optional)' }}
                                                        </label>
                                                    </div>
                                                    <small class="text-danger" v-if='errors.photo'>{{ errors.photo[0] }}</small>
                                                    <small class="text-muted">Max size: 1MB. Supported: JPG, PNG, JPEG</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12 text-center">
                                                    <p class="small text-muted mb-2">Current Photo:</p>
                                                    <img :src="form.photo || '/img/default-avatar.png'"
                                                         class="img-thumbnail"
                                                         width="150"
                                                         height="150"
                                                         alt="Current Photo">
                                                    <div v-if="form.photo" class="mt-2">
                                                        <button type="button" @click="removePhoto" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash mr-1"></i> Remove Photo
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                                                        <i class="fas fa-save mr-1"></i>
                                                        {{ loading ? 'Updating...' : 'Update Supplier' }}
                                                    </button>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="button" @click="cancelEdit" class="btn btn-secondary btn-block">
                                                        <i class="fas fa-times mr-1"></i> Cancel
                                                    </button>
                                                </div>
                                            </div>
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
</template>

<script>
    export default {
        created() {
            if (!User.loggedIn()) {
                this.$router.push({ name: 'login' });
            }

            let id = this.$route.params.id;
            axios.get('/api/suppliers/' + id)
                .then(res => {
                    this.form = res.data;
                    // If the photo is a full URL, keep it as is
                    // If it's just a path, prepend the base URL if needed
                    if (this.form.photo && !this.form.photo.startsWith('http') && !this.form.photo.startsWith('data:')) {
                        this.form.photo = this.form.photo;
                    }
                })
                .catch(err => {
                    console.error(err);
                    notification.error('Failed to load supplier data');
                    this.$router.push({ name: 'suppliers' });
                });
        },
        data() {
            return {
                form: {
                    name: '',
                    email: '',
                    address: '',
                    shopname: '',
                    phone: '',
                    photo: null,
                },
                errors: {},
                loading: false,
                photoFileName: ''
            }
        },
        methods: {
            onFileSelect(event) {
                let file = event.target.files[0];

                if (!file) {
                    this.photoFileName = '';
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    notification.error('Only JPG, JPEG, and PNG files are allowed!');
                    event.target.value = '';
                    this.photoFileName = '';
                    return;
                }

                // Check file size (1MB = 1048576 bytes)
                if (file.size > 1048576) {
                    notification.Image_size();
                    event.target.value = '';
                    this.photoFileName = '';
                    return;
                }

                this.photoFileName = file.name;

                let reader = new FileReader();
                reader.onload = event => {
                    this.form.photo = event.target.result;
                };
                reader.readAsDataURL(file);

                // Update the label text
                document.getElementById('photoLabelEdit').textContent = file.name;
            },

            suppliersUpdate() {
                this.loading = true;
                this.errors = {};

                let id = this.$route.params.id;
                axios.patch('/api/suppliers/' + id, this.form)
                    .then(() => {
                        this.loading = false;
                        notification.success('Supplier updated successfully');
                        this.$router.push({ name: 'suppliers' });
                    })
                    .catch(err => {
                        this.loading = false;
                        if (err.response && err.response.data.errors) {
                            this.errors = err.response.data.errors;
                        } else {
                            notification.error('Failed to update supplier');
                        }
                    });
            },

            removePhoto() {
                this.form.photo = null;
                this.photoFileName = '';
                document.getElementById('photoInputEdit').value = '';
                document.getElementById('photoLabelEdit').textContent = 'Choose new file (optional)';
            },

            cancelEdit() {
                this.$router.push({ name: 'suppliers' });
            }
        }
    }
</script>

<style scoped>
.custom-file-label::after {
    content: "Browse";
}

.img-thumbnail {
    object-fit: cover;
    border: 2px solid #dee2e6;
}

.btn:disabled {
    cursor: not-allowed;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-block {
        margin-bottom: 10px;
    }
}
</style>
