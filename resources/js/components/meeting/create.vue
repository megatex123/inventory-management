<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
          <div class="card-body">
            <div class="text-center mb-4">
                <h2>Create Meeting</h2>
            </div>
            <form @submit.prevent="submitMeeting" enctype="multipart/form-data">
            <label>Customer</label>
            <select v-model="form.customer_id" class="form-control" required>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.full_name }}
                </option>
            </select>

            <div class="form-group">
                <label class="mt-2">Meeting Title</label>
                <input type="text" v-model="form.title" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="mt-2">Meeting Date</label>
                <input type="date" v-model="form.meeting_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="mt-2">Meeting Notes</label>
                <textarea v-model="form.meeting_notes" class="form-control"></textarea>
            </div>

            <div class="form-group">
                <label class="mt-2">Upload Document</label>
                <input type="file" @change="handleFileUpload" class="form-control">
            </div>

            <button class="btn btn-success mt-3" :disabled="loading">
                {{ loading ? 'Saving...' : 'Save' }}
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
      customers: [],
      form: { customer_id: '', title: '', meeting_date: '', meeting_notes: '', document: null },
      loading: false,
    };
  },
  mounted() {
    axios.get('/api/customer/all').then(res => {
        this.customers = res.data;
        console.log(this.customers);
    });
  },
  methods: {
    handleFileUpload(event) {
      this.form.document = event.target.files[0];
    },
    submitMeeting() {
      this.loading = true;
      let formData = new FormData();
      formData.append('customer_id', this.form.customer_id);
      formData.append('title', this.form.title);
      formData.append('meeting_date', this.form.meeting_date);
      formData.append('meeting_notes', this.form.meeting_notes);
      if (this.form.document) formData.append('document', this.form.document);

      axios.post('/api/meetings', formData)
        .then(() => this.$router.push('/meeting'))
        .finally(() => this.loading = false);
    },
  },
};
</script>
