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
                                                    <label class="small font-weight-bold text-muted">Country</label>
                                                    <input type="text" class="form-control" v-model='form.address'
                                                        placeholder="Enter Supplier Country">
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
                                                    <input
                                                        type="tel"
                                                        class="form-control"
                                                        v-model="formattedPhone"
                                                        placeholder="012-3456789"
                                                        required
                                                        maxlength="12"
                                                        pattern="[0-9]{3}-[0-9]{7,8}"
                                                        title="Please enter a valid phone number in format: 012-3456789"
                                                    >
                                                    <small class="text-muted d-block mt-1">Format: 012-3456789 (3 digits + hyphen + 7-8 digits)</small>
                                                    <small class="text-danger" v-if='errors.phone'>{{ errors.phone[0] }}</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="small font-weight-bold text-muted">Photo</label>
                                                    <div class="photo-upload-btn" :class="{ 'has-photo': previewPhoto || form.photo }" title="Click to change photo">
                                                        <img v-if="previewPhoto || form.photo" :src="previewPhoto || form.photo" alt="photo">
                                                        <input type="file" @change='onFileSelect' accept="image/jpeg,image/jpg,image/png">
                                                    </div>
                                                    <small class="text-success d-block" v-if='displayPhotoName'>Uploaded: {{ displayPhotoName }}</small>
                                                    <small class="text-muted d-block" v-if='previewPhoto || form.photo'>Click the photo above to replace it</small>
                                                    <small class="text-danger d-block" v-if='errors.photo'>{{ errors.photo[0] }}</small>
                                                    <small class="text-muted d-block">Max size: 1MB. Supported: JPG, PNG, JPEG</small>
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
                    this.originalPhoto = res.data.photo;
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
                photoFileName: '',
                previewPhoto: null,
                originalPhoto: null
            }
        },
        computed: {
            // Shows the freshly-picked file's real name if one was just
            // selected; otherwise derives a display name from the existing
            // stored photo's path (not the original upload filename --
            // that isn't stored/returned separately by this API).
            displayPhotoName() {
                if (this.photoFileName) return this.photoFileName;
                if (this.originalPhoto && !this.originalPhoto.startsWith('data:')) {
                    return this.originalPhoto.split('/').pop();
                }
                return null;
            },
            formattedPhone: {
                get() {
                    return this.form.phone;
                },
                set(value) {
                    // Remove all non-digit characters
                    let digits = value.replace(/\D/g, '');

                    // Limit to 10 digits (3 for prefix + 7 for number)
                    if (digits.length > 10) {
                        digits = digits.slice(0, 10);
                    }

                    // Format with hyphen after first 3 digits
                    if (digits.length <= 3) {
                        this.form.phone = digits;
                    } else {
                        this.form.phone = digits.slice(0, 3) + '-' + digits.slice(3);
                    }
                }
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
                    this.previewPhoto = event.target.result;
                    this.form.photo = event.target.result;
                };
                reader.readAsDataURL(file);
            },

            suppliersUpdate() {
                this.loading = true;
                this.errors = {};

                let id = this.$route.params.id;

                // If no new photo selected, keep the original
                if (!this.previewPhoto && this.originalPhoto) {
                    this.form.photo = this.originalPhoto;
                }

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

            cancelEdit() {
                this.$router.push({ name: 'suppliers' });
            }
        }
    }
</script>

<style scoped>
.photo-upload-btn {
    position: relative;
    width: 70px;
    height: 70px;
    border: 1px dashed #adb5bd;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.photo-upload-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-upload-btn input[type="file"] {
    font-size: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    position: absolute;
    top: 0;
    left: 0;
}
.photo-upload-btn:not(.has-photo)::before {
    content: '+';
    font-size: 1.5rem;
    color: #adb5bd;
    pointer-events: none;
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-block {
        margin-bottom: 10px;
    }
}

/* Style for the phone input hint */
.form-control[pattern] + .text-muted {
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Improve focus states */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
</style>
