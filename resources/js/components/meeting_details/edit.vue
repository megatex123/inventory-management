<template>
  <div class="container my-5">
        <div class="card shadow-sm form-card">
          <div class="card-body">
            <div class="text-center mb-4">
                <h2>Edit Meeting</h2>
            </div>
            <form @submit.prevent="submitMeeting" enctype="multipart/form-data">
            <label>Customer</label>
            <select v-model="form.customer_id" class="form-control" required>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.full_name }}
                </option>
            </select>

            <label class="mt-2">Meeting Date</label>
            <input type="date" v-model="form.meeting_date" class="form-control" required>

            <label class="mt-2">Meeting Notes</label>
            <textarea v-model="form.meeting_notes" class="form-control"></textarea>

            <label class="mt-2">Current Document</label>
            <div v-if="existingDocument" class="mb-2">
                <a :href="`/storage/${existingDocument}`" target="_blank" class="text-primary">
                    View uploaded document
                </a>
            </div>

            <label class="mt-2">Upload New Document</label>
            <input type="file" @change="handleFileUpload" class="form-control">

            <button class="btn btn-success mt-3" :disabled="loading">
                {{ loading ? 'Updating...' : 'Update' }}
            </button>
            <router-link to="/meeting" class="btn btn-secondary mt-3 ml-2">Back</router-link>
            </form>
          </div>
        </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      meetings: [],
      form: {},
      loading: false,
      id: this.$route.params.id,
    };
  },
  mounted() {
    axios.get('/api/meetings').then(res => {
      this.meetings = res.data;
    });

    axios.get(`/api/meeting-details/${this.id}`).then(res => {
      this.form = res.data;
    });
  },
  methods: {
    submitMeeting() {
      this.loading = true;

      axios.put(`/api/meeting-details/${this.id}`, this.form)
        .then(() => this.$router.push('/meeting'))
        .finally(() => this.loading = false);
    },
  },
};
</script>
