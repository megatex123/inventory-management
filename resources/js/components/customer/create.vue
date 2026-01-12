<template>
  <div class="container my-5">
        <div class="card shadow-sm form-card">
          <div class="card-body">

            <div class="text-center mb-4">
              <h4 class="text-primary font-weight-bold">Pre Register Customer</h4>
              <p class="text-muted">Create a new customer record</p>
            </div>

            <form @submit.prevent="CustormerInsert">

              <div class="form-group">
                <label class="form-label">
                    Preferred Name<span class="text-danger">*</span><br>
                </label>
                <input class="form-control form-input mb-3" v-model="form.preferred_name" placeholder="Preferred Name" />
                 <small class="text-danger" v-if="errors.preferred_name">
                  {{ errors.preferred_name[0] }}
                </small>
              </div>

              <div class="form-group">
                <label class="form-label">Full Name (as per IC)</label>
                <input class="form-control form-input mb-3" v-model="form.full_name" placeholder="Full Name" style="text-transform: uppercase"/>
                <small class="text-danger" v-if="errors.full_name">
                  {{ errors.full_name[0] }}
                </small>
              </div>

              <div class="form-group">
                <label>Email Address</label>
                <input type="email" class="form-control" v-model="form.email" placeholder="Enter email"/>
                <small class="text-danger" v-if="errors.email">
                  {{ errors.email[0] }}
                </small>
              </div>

              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" class="form-control form-input mb-3" v-model="phoneWithPrefix" placeholder="Ex: +60197017420" @input="sanitizePhone" autocomplete="tel" />
                <small class="text-danger" v-if="errors.phone">
                  {{ errors.phone[0] }}
                </small>
              </div>

              <div class="form-group">
                <label>Address</label>
                <textarea class="form-control" rows="2" v-model="form.address" placeholder="Enter address"></textarea>
                <small class="text-danger" v-if="errors.address">
                  {{ errors.address[0] }}
                </small>
              </div>
              <div class="mt-4">
                <button type="submit" class="btn btn-primary mt-4" >
                    Save Customer
                </button>
              </div>
              <router-link to="/customer" class="btn btn-outline-secondary mt-3">
                ← Back to List
              </router-link>
            </form>
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
  },
  data() {
    return {
      form: {
        preferred_name: "",
        full_name: "",
        email: "",
        address: "",
        phone: "",
      },
      errors: {},
    };
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
    CustormerInsert() {
        if (!this.form.preferred_name || this.form.preferred_name.trim() === "") {
            this.errors = {
            preferred_name: ["Preferred Name is required."]
            };
            return;
        }
        this.form.full_name = this.form.full_name.toUpperCase();
        axios.post("/api/customer", this.form)
        .then(() => {
          notification.success();
          this.$router.push("/customer");
        })
        .catch(err => {
          this.errors = err.response?.data?.errors || {};
          notification.error();
        });
    },
  },
};
</script>
