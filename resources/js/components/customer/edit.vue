<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-body">

        <!-- HEADER -->
        <h3 class="font-weight-bold mb-2">Update Customer Information</h3>
        <p class="text-muted">
          Edit registered customer details.
        </p>

        <hr />

        <form @submit.prevent="handleSubmit">

            <!-- STEP 1 -->
            <div v-if="step === 1">
                <h5 class="mb-3 bg-highlight-purple">Personal Information</h5><br>

                <div class="form-group">
                    <label class="form-label">Full Name (as per IC)&nbsp;<span class="text-danger">*</span></label>
                    <input class="form-control form-input mb-3" v-model="form.full_name" style="text-transform: uppercase" placeholder="Ex: AHMAD FAIZ BIN HASAN " />
                    <small class="text-danger" v-if="errors.full_name">
                        {{ errors.full_name }}<br>
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        What would you like us to call you? (Preferred Name)
                        &nbsp;<span class="text-danger">*</span><br>
                        <small class="text-muted">
                            This name will appear on your QuiviTech communications.
                        </small>
                    </label>
                    <input class="form-control form-input mb-3" v-model="form.preferred_name" placeholder="Ex: Ahmad" />
                    <small class="text-danger" v-if="errors.preferred_name">
                        {{ errors.preferred_name }}<br>
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Email Address
                        &nbsp;<span class="text-danger">*</span><br>
                        <small class="text-muted">
                            Must be a full email address.
                        </small>
                    </label>
                    <input class="form-control form-input mb-3" v-model="form.email" type="email" placeholder="Ex: support@quivitech.com" />
                    <small class="text-danger" v-if="errors.email">
                        {{ errors.email }}<br>
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Phone Number
                        &nbsp;<span class="text-danger">*</span><br>
                        <small class="text-muted">
                            Must include country code.
                        </small>
                    </label>
                    <input type="tel" class="form-control form-input mb-3" v-model="phoneWithPrefix" placeholder="Ex: +60197017420" @input="sanitizePhone" autocomplete="tel" />
                    <small class="text-danger" v-if="errors.contact_method">
                        {{ errors.contact_method }}<br>
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Home Address
                        &nbsp;<span class="text-danger">*</span><br>
                        <small class="text-muted">
                            Must include postcode, district, and state.
                        </small>
                    </label>
                    <textarea class="form-control form-input mb-3" v-model="form.address" rows="2" placeholder="Ex: Kota Damansara, PJU 5, 47810, Petaling Jaya, Selangor"></textarea>
                    <small class="text-danger" v-if="errors.address">
                        {{ errors.address }}<br>
                    </small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Preferred Contact Method</label>
                    <div v-for="opt in contactOptions" :key="opt" class="form-check">
                        <input class="form-check-input" type="radio" :value="opt" v-model="form.contact_method">
                        <label class="form-check-label">{{ opt }}</label>
                    </div>
                    <input v-if="form.contact_method === 'Other'" class="form-control mt-2" v-model="form.contact_other" placeholder="Specify other" />
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary mt-4" type="button" @click="nextStep">Next</button>
                </div>
                <router-link class="btn btn-outline-secondary mt-3" to="/customer">
                    ← Back to List
                </router-link>
            </div>

            <!-- STEP 2 -->
            <div v-if="step === 2">
                <div class="form-group">
                    <label class="form-label">
                        <h5>Feedback (Optional)</h5>
                        <small class="text-muted">
                            We'd love to hear from you!   💬  <br>
                            Your feedback helps us improve your QuiviTech experience.<br>
                            The questions below are completely optional - feel free to skip any if you prefer.
                        </small>
                    </label>
                    <br>
                    <hr>

                    <label class="font-weight-bold">How did you hear about QuiviTech?</label>
                    <div v-for="opt in hearOptions" :key="opt" class="form-check">
                        <input class="form-check-input" type="radio" :value="opt" v-model="form.hear_about">
                        <label class="form-check-label">{{ opt }}</label>
                    </div>

                    <input v-if="form.hear_about === 'Other'" class="form-control mt-2" v-model="form.hear_about_other" placeholder="Specify other" />
                </div>

                <div class="mt-4">
                    <button class="btn btn-secondary mr-2" type="button" @click="prevStep">Back</button>
                    <button class="btn btn-primary" type="button" @click="nextStep">Next</button>
                </div>
                <router-link class="btn btn-outline-secondary mt-3" to="/customer">
                    ← Back to List
                </router-link>
            </div>

            <!-- STEP 3 -->
            <div v-if="step === 3 && this.form.hear_about === 'Friend / Referral' ">
                <div class="form-group">
                    <label class="form-label">
                        <h5>Referral Details</h5>
                        <small class="text-muted">
                            If you were referred by someone, please let us know below.<br>
                            Both you and your referrer will be eligible for exclusive QuiviTech perks or discounts.
                        </small>
                    </label>
                    <br>
                    <hr>
                    <label class="form-label">
                        Who referred you? &nbsp;<span class="text-danger">*</span><br>
                        <small class="text-muted">
                            Please include the full name (as registered with QuiviTech).
                        </small>
                    </label>
                    <input class="form-control" v-model="form.referred_by" placeholder="" />
                    <small class="text-danger" v-if="errors.referred_by">
                        {{ errors.referred_by }}<br>
                    </small>
                </div>
                <div class="mt-4">
                    <button class="btn btn-secondary mr-2" type="button" @click="prevStep">Back</button>
                    <button class="btn btn-primary" type="button" @click="nextStep">Next</button>
                </div>
                <router-link class="btn btn-outline-secondary mt-3" to="/customer">
                    ← Back to List
                </router-link>
            </div>

            <!-- STEP 4 -->
            <div v-if="step === 4">
                <div class="form-group">
                    <label class="form-label">
                        <h5>Feedback (Optional)</h5>
                        <small class="text-muted">
                            We'd love to hear from you!   💬<br>
                            Your feedback helps us improve your QuiviTech experience.<br>
                            The questions below are completely optional - feel free to skip any if you prefer.
                        </small>
                    </label>
                    <br>
                    <hr>
                    <label class="form-label">
                        What caught your attention about QuiviTech?<br>
                    </label>
                    <textarea class="form-control mb-3" v-model="form.feedback" placeholder="Feedback"></textarea>
                </div>
                <div class="mt-4">
                    <button class="btn btn-secondary mr-2" type="button" @click="prevStep">Back</button>
                    <button class="btn btn-primary" type="button" @click="nextStep">Next</button>
                </div>
                <router-link class="btn btn-outline-secondary mt-3" to="/customer">
                    ← Back to List
                </router-link>
            </div>

            <!-- STEP 5 -->
            <div v-if="step === 5">
                <div class="form-group">
                    <label class="form-label">
                        <h5>Terms & Consent</h5>
                        <small class="text-muted">
                            Please review the agreement below.<br>
                            By proceeding, you acknowledge that QuiviTech may collect, store, and process your information for the <br>
                            purposes of registration, warranty, and after-sales support in accordance with Malaysian data protection laws.<br>
                            <br>
                            Your data will be used solely for QuiviTech’s internal operations, including but not limited to:<br>
                            <ul>
                                <li>Verifying customer identity for warranty and support</li>
                                <li>Managing membership and build history</li>
                                <li>Contacting you regarding your QuiviTech services or updates</li>
                            </ul>
                            QuiviTech guarantees that your information will never be shared or sold to third parties without your consent.
                        </small>
                    </label>
                    <hr>
                    <div class="form-check mb-3">
                        <label class="form-label">
                            What caught your attention about QuiviTech?
                            &nbsp;<span class="text-danger">*</span><br>
                        </label>
                        <br>
                        <input class="form-check-input" type="checkbox" v-model="form.consent">
                        <label class="form-check-label">
                            I have read and understood QuiviTech's Terms & Consent, and I authorize QuiviTech to collect and process my information as stated.<br>
                        </label>
                    </div>
                </div>
                <button class="btn btn-secondary mr-2" type="button" @click="prevStep">Back</button>
                <button class="btn btn-success" type="submit" :disabled="loading">
                    {{ loading ? 'Updating...' : 'Update Customer' }}
                 </button>
                 <br>
                <router-link class="btn btn-outline-secondary mt-3" to="/customer">
                    ← Back to List
                </router-link>
            </div>

            <!-- STEP 6 -->
            <div v-if="step === 6" class="text-center">
                <h4 class="text-success">✅ Customer Updated</h4>
                <router-link class="btn btn-outline-secondary mt-3" to="/customer">
                ← Back to List
                </router-link>
            </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {
      step: 1,
      loading: false,
      errors: {},

      contactOptions: [
        "WhatsApp","TikTok","Facebook","Instagram","Discord","Phone / Message","Other"
      ],

      hearOptions: [
        "Facebook","Instagram","TikTok","Friend / Referral","Event / Booth","Other"
      ],

      form: {
        full_name: "",
        preferred_name: "",
        email: "",
        phone: "",
        address: "",
        contact_method: "",
        contact_other: "",
        feedback: "",
        hear_about: "",
        hear_about_other: "",
        referred_by: "",
        consent: false,
       },
    };
  },

  created() {
    if (!User.loggedIn()) {
      this.$router.push({ name: "login" });
      return;
    }

    const id = this.$route.params.id;
    axios.get(`/api/customer/${id}`)
      .then(res => {
        this.form = res.data;
      });
  },

  computed: {
        phoneWithPrefix: {
            get() {
            return this.form.phone || '+60';
            },
            set(val) {
            this.form.phone = val;
            }
        }
    },

  methods: {
    sanitizePhone(e) {
        let value = e.target.value;
        let digits = value.replace(/\D/g, '');
        if (!digits.startsWith('60')) {
        digits = '60' + digits.replace(/^60+/, '');
        }
        digits = digits.substring(0, 12);
        this.form.phone = '+' + digits;
    },
    validateStep(step) {
        this.errors = {};

        if (step === 1) {
            if (!this.form.full_name) {
            this.errors.full_name = "Full name is required";
            }
            if (!this.form.preferred_name) {
            this.errors.preferred_name = "Preferred name is required";
            }
            if (!this.form.email) {
            this.errors.email = "Email is required";
            }
            if (!this.form.phone) {
            this.errors.phone = "Phone number is required";
            }
            if (!this.form.address) {
            this.errors.address = "Address is required";
            }
            if (!this.form.contact_method) {
            this.errors.contact_method = "Please select a contact method";
            }
            if (
            this.form.contact_method === "Other" &&
            !this.form.contact_other
            ) {
            this.errors.contact_other = "Please specify other contact method";
            }
            if (this.form.phone.length < 10){
            this.errors.phone = "Invalid Malaysian phone number";
            }
        }

        if (step === 3) {
            if (!this.form.referred_by) {
            this.errors.referred_by = "Referrer name is required";
            }
        }

        if (step === 5) {
            if (!this.form.consent) {
            this.errors.consent = "You must agree to the terms";
            }
        }

        return Object.keys(this.errors).length === 0;
    },
    handleSubmit() {
        if (this.step === 5) {
        this.updateCustomer();
        } else {
        this.nextStep();
        }
    },
    nextStep() {
        if (!this.validateStep(this.step)) {
            return;
        }
        if (this.step === 1) {
            this.step = 2;

        } else if (this.step === 2) {
            if (this.form.hear_about === 'Friend / Referral') {
            this.step = 3;
            } else {
            this.step = 4;
            }
        } else if (this.step === 3) {
            this.step = 4;

        } else if (this.step === 4) {
            this.step = 5;
        }
    },
    prevStep() {
        if (this.step === 4 && !this.form.hear_about === 'Friend / Referral') {
            this.step = 2;
        } else if (this.step > 1) {
            this.step--;
        }
    },
    updateCustomer() {
      this.loading = true;
      const id = this.$route.params.id;
      this.form.full_name = this.form.full_name.toUpperCase();
      axios.patch(`/api/customer/${id}`, this.form)
        .then(() => {
          this.step = 6;
        })
        .catch(() => {
          alert("Update failed");
        })
        .finally(() => {
          this.loading = false;
        });
    },
  }
};
</script>
<style scoped>
.form-card {
  max-width: 900px;
  margin: auto;
  border-radius: 12px;
}

.form-input {
  border: none;
  border-bottom: 1px solid #ccc;
  border-radius: 0;
}

.form-input:focus {
  box-shadow: none;
  border-bottom: 2px solid #007bff;
}

.bg-highlight-purple {
  background: rgba(111, 66, 193, 0.15);
  padding: 6px 12px;
  border-radius: 6px;
  display: inline-block;
}
</style>
