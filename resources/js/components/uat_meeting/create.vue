<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Create UAT Meeting</h4>
          <router-link to="/uat-meeting" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <h5 class="mb-3">Header</h5>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Meeting <span class="text-danger">*</span></label>
                <select v-model="form.meeting_id" class="form-control" required>
                  <option value="">Select Meeting</option>
                  <option v-for="m in meetings" :key="m.id" :value="m.id">{{ m.meeting_id }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Require Meeting ID</label>
                <select v-model="form.requirement_id" class="form-control">
                  <option value="">Select Require Meeting ID</option>
                  <option v-for="r in requirementMeetings" :key="r.id" :value="r.requirement_id">{{ r.requirement_id }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">QV-BLDP ID</label>
                <select v-model="form.order_id" class="form-control">
                  <option value="">Select Order</option>
                  <option v-for="o in orders" :key="o.id" :value="o.order_id">{{ o.order_id }}</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">UAT Meeting ID</label>
            <div class="form-control-plaintext bg-light p-2 rounded text-muted">
              Auto-generated on save (UAT-000001, ...)
            </div>
          </div>

          <hr>
          <h5 class="mb-3">Changes Requested</h5>
          <div class="form-group">
            <label class="form-label">Budget Change</label>
            <textarea v-model="form.budget_change" class="form-control" rows="2" placeholder="New budget / budget adjustment"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Parts Changes</label>
            <textarea v-model="form.parts_changes" class="form-control" rows="2" placeholder="Existing proposed parts customer wants changed"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Add-on Parts</label>
            <textarea v-model="form.add_on_parts" class="form-control" rows="2" placeholder="Additional parts requested"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Parts Notes</label>
            <textarea v-model="form.parts_notes" class="form-control" rows="2" placeholder="Reason/preferences regarding the changes"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Case Size Change</label>
            <input type="text" v-model="form.case_size_change" class="form-control" maxlength="191" placeholder="New case-size requirement">
          </div>
          <div class="form-group">
            <label class="form-label">Overall Notes</label>
            <textarea v-model="form.overall_notes" class="form-control" rows="2" placeholder="Anything else discussed"></textarea>
          </div>

          <hr>
          <h5 class="mb-3">Build Details</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Target Build Date</label>
                <input type="date" v-model="form.target_build_date" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Target Location</label>
                <input type="text" v-model="form.target_location" class="form-control" maxlength="191">
              </div>
            </div>
          </div>

          <hr>
          <h5 class="mb-3">Service Changes</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">QuiviCare</label>
                <select v-model="form.quivicare_change" class="form-control">
                  <option value="no_change">No Change</option>
                  <option value="add">Add</option>
                  <option value="remove">Remove</option>
                  <option value="change_plan">Change Plan</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">QuiviThread</label>
                <select v-model="form.quivithread_change" class="form-control">
                  <option value="no_change">No Change</option>
                  <option value="add">Add</option>
                  <option value="remove">Remove</option>
                  <option value="change_option">Change Option</option>
                </select>
              </div>
            </div>
          </div>

          <hr>
          <h5 class="mb-3">UAT Result</h5>
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Changes Required</label>
                <select v-model="form.changes_required" class="form-control">
                  <option :value="null">-</option>
                  <option :value="true">Yes</option>
                  <option :value="false">No</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">New Proposal Required</label>
                <select v-model="form.new_proposal_required" class="form-control">
                  <option :value="null">-</option>
                  <option :value="true">Yes</option>
                  <option :value="false">No</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Customer Approval</label>
                <select v-model="form.customer_approval" class="form-control">
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Follow-up Required</label>
                <select v-model="form.follow_up_required" class="form-control">
                  <option :value="null">-</option>
                  <option :value="true">Yes</option>
                  <option :value="false">No</option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Create UAT Meeting
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      meetings: [],
      requirementMeetings: [],
      orders: [],
      loading: false,
      errors: [],
      form: {
        meeting_id: '',
        requirement_id: '',
        order_id: '',
        budget_change: '',
        parts_changes: '',
        add_on_parts: '',
        parts_notes: '',
        case_size_change: '',
        overall_notes: '',
        quivicare_change: 'no_change',
        quivithread_change: 'no_change',
        changes_required: null,
        new_proposal_required: null,
        customer_approval: 'pending',
        follow_up_required: null,
        target_build_date: '',
        target_location: '',
      },
    };
  },
  mounted() {
    this.fetchMeetings();
    this.fetchRequirementMeetings();
    this.fetchOrders();
  },
  methods: {
    fetchMeetings() {
      axios.get('/api/meetings/all')
        .then(res => { this.meetings = res.data; })
        .catch(() => Swal.fire('Error!', 'Failed to load meetings', 'error'));
    },
    fetchRequirementMeetings() {
      axios.get('/api/meeting-details/all')
        .then(res => { this.requirementMeetings = res.data; })
        .catch(() => Swal.fire('Error!', 'Failed to load requirement meetings', 'error'));
    },
    fetchOrders() {
      axios.get('/api/orders')
        .then(res => { this.orders = res.data.data || res.data; })
        .catch(() => Swal.fire('Error!', 'Failed to load orders', 'error'));
    },
    submit() {
      this.loading = true;
      this.errors = [];

      const payload = { ...this.form };
      Object.keys(payload).forEach(key => {
        if (payload[key] === '') payload[key] = null;
      });

      axios.post('/api/uat-meeting', payload)
        .then(res => {
          Swal.fire({ title: 'Success!', text: `UAT Meeting ${res.data.data.uat_id} created successfully`, icon: 'success', timer: 2000, showConfirmButton: false })
            .then(() => this.$router.push('/uat-meeting'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create UAT meeting');
          }
          Swal.fire('Error!', this.errors.join('<br>'), 'error');
        })
        .finally(() => { this.loading = false; });
    },
  },
};
</script>

<style scoped>
.form-card { border-radius: 10px; border: none; }
.form-label { font-weight: 600; color: #495057; }
</style>
