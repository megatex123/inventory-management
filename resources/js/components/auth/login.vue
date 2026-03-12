<template>
  <div>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-primary">
      <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
          <img
            src="/backend/img/logo/logo.png"
            alt="QuiviTech Logo"
            style="height: 32px; margin-right: 8px;"
          />
          <span class="text-white font-weight-bold">QuiviTech</span>
        </a>
      </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container-fluid">
      <div class="row min-vh-100">
        <!-- FORM SIDE -->
        <div class="col-md-8 d-flex align-items-center justify-content-center bg-light">
          <div class="card shadow-sm p-4" style="width: 100%; max-width: 420px;">
            <h4 class="text-center mb-4">Login</h4>

            <form @submit.prevent="login">
              <!-- EMAIL -->
              <div class="form-group">
                <input
                  type="email"
                  class="form-control"
                  v-model="form.email"
                  placeholder="Email address"
                  required
                  autofocus
                />
                <small class="text-danger" v-if="errors.email">
                  {{ errors.email[0] }}
                </small>
              </div>

              <!-- PASSWORD -->
              <div class="form-group">
                <input
                  type="password"
                  class="form-control"
                  v-model="form.password"
                  placeholder="Password"
                  required
                />
                <small class="text-danger" v-if="errors.password">
                  {{ errors.password[0] }}
                </small>
              </div>

              <!-- SUBMIT -->
              <button
                type="submit"
                class="btn btn-primary btn-block"
                :disabled="loading"
              >
                {{ loading ? 'Logging in...' : 'Login' }}
              </button>

              <hr />

              <div class="text-center">
                <router-link to="/register" class="small font-weight-bold">
                  Create an Account
                </router-link>
              </div>
            </form>
          </div>
        </div>

        <!-- RIGHT TEXT SIDE -->
        <div class="col-md-4 d-flex align-items-center justify-content-center">
          <div class="text-center px-4">
            <h2 class="text-primary">Welcome Back 👋</h2>
            <p class="text-muted mt-2">
              Build something amazing with QuiviTech
              <span class="version" style="padding:10px; font-size: 10px;">
                <br>Version 0.0.2 By Enigma Code Solution
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  created() {
    if (User.loggedIn()) {
      this.$router.push({ name: 'dashboard' });
    }
  },
  data() {
    return {
      form: {
        email: '',
        password: '',
      },
      errors: {},
      loading: false,
    };
  },
  methods: {
    login() {
      this.loading = true;
      this.errors = {};

      axios
        .post('/api/auth/login', this.form)
        .then(res => {
          User.responseAfterLogin(res);

          Toast.fire({
            icon: 'success',
            title: 'Login successful',
          });

          this.$router.push({ name: 'dashboard' });
        })
        .catch(err => {
          if (err.response?.status === 422) {
            this.errors = err.response.data.errors;
          } else {
            Toast.fire({
              icon: 'error',
              title: 'Invalid email or password',
            });
          }
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
};
</script>

<style scoped>
.navbar-brand {
  font-size: 1.2rem;
}

.navbar {
  width: 100vw;
  margin-left: calc(-50vw + 50%);
}

.card {
  border-radius: 10px;
}
</style>
