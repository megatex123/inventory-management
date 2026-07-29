<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-body">
        <div class="text-center mb-4">
          <h2>Create UAT Meeting</h2>
        </div>
        <form @submit.prevent="submitUatMeetings">
          <!-- Row 1 -->
          <div class="form-group">
            <div class="form-row">
              <div class="col-4">
                <label class="mt-2">Meeting *</label>
                <select v-model="form.meeting_id" class="form-control" required>
                  <option value="">Select Meeting</option>
                  <option v-for="m in meetings" :key="m.id" :value="m.id">
                    {{ m.meeting_id }}
                  </option>
                </select>
              </div>

              <div class="col-4">
                <label class="mt-2">Initial Budget (MYR)</label>
                <input
                  type="number"
                  v-model.number="form.initial_budget"
                  class="form-control"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                >
              </div>

              <div class="col-4">
                <label class="mt-2">Reason *</label>
                <select v-model.number="form.reason" class="form-control" required>
                  <option value="">Select a reason</option>
                  <option :value="1">Work</option>
                  <option :value="2">Gaming</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Row 2 -->
          <div class="form-group">
            <div class="form-row">
              <div v-if="form.reason == 2" class="col-4">
                <label class="mt-2">Play Mode</label>
                <select v-model.number="form.play_mode" class="form-control">
                  <option value="">Select Play Mode</option>
                  <option :value="1">Multiplayer</option>
                  <option :value="2">Singleplayer</option>
                </select>
              </div>

              <div class="col-4">
                <label class="mt-2">Theme Style</label>
                <input
                  type="text"
                  v-model="form.theme_style"
                  class="form-control"
                  placeholder="e.g., Minimalist, RGB, etc."
                >
              </div>

              <div class="col-4">
                <div class="form-group">
                    <label class="mt-2 d-block">Include Peripheral</label>

                    <!-- Bootstrap-style toggle switch -->
                    <div class="custom-control custom-switch mb-2">
                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="includePeripheralToggle"
                        v-model="form.include_monitor"
                    >
                    <label
                        class="custom-control-label"
                        for="includePeripheralToggle"
                    >
                        {{ form.include_monitor ? 'Yes' : 'No' }}
                    </label>
                    </div>

                    <!-- Conditional input field -->
                    <div v-if="form.include_monitor" class="mt-2">
                    <input
                        type="text"
                        v-model="form.include_notes"
                        class="form-control"
                        placeholder="Describe peripheral (e.g., Monitor, Keyboard, Mouse)"
                    >
                    </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Row 3 -->
          <div class="form-group">
            <div class="form-row">
              <div class="col-4">
                <label class="mt-2">Preference</label>
                <input
                  type="text"
                  v-model="form.preference"
                  class="form-control"
                  placeholder="Specific preferences"
                >
              </div>

              <div class="col-4">
                <label class="mt-2">Exemption</label>
                <input
                  type="text"
                  v-model="form.exemption"
                  class="form-control"
                  placeholder="Any exemptions"
                >
              </div>

              <div class="col-4">
                <label class="mt-2">Case Size</label>
                <select v-model.number="form.case_size" class="form-control">
                  <option value="">Select Case Size</option>
                  <option :value="1">ITX</option>
                  <option :value="2">MATX</option>
                  <option :value="3">ATX</option>
                  <option :value="4">EATX</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Row 4: Notes -->
          <div class="form-group">
            <label>Notes</label>
            <textarea
              v-model="form.notes"
              class="form-control"
              rows="3"
              placeholder="Additional notes..."
            ></textarea>
          </div>

          <!-- Row 5: Boolean Fields -->
          <div class="form-group">
            <div class="form-row">
              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.future_proof" class="custom-control-input" id="future_proof">
                  <label class="custom-control-label" for="future_proof">Future Proof</label>
                </div>
              </div>

              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.okay_with_aio" class="custom-control-input" id="okay_with_aio">
                  <label class="custom-control-label" for="okay_with_aio">Okay with AIO</label>
                </div>
              </div>

              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.need_rgb" class="custom-control-input" id="need_rgb">
                  <label class="custom-control-label" for="need_rgb">Need RGB</label>
                </div>
              </div>

              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.gpu_sag" class="custom-control-input" id="gpu_sag">
                  <label class="custom-control-label" for="gpu_sag">GPU Sag Concern</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Row 6: QV Fields -->
          <div class="form-group">
            <div class="form-row">
              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.qvcrf_tag" class="custom-control-input" id="qvcrf_tag">
                  <label class="custom-control-label" for="qvcrf_tag">QVCRF Tag Along</label>
                </div>
              </div>

              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.qvse" class="custom-control-input" id="qvse">
                  <label class="custom-control-label" for="qvse">QVSE</label>
                </div>
              </div>

              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.qvca" class="custom-control-input" id="qvca">
                  <label class="custom-control-label" for="qvca">QVCA</label>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-row">
              <div class="col-3">
                <div class="custom-control custom-checkbox mt-3">
                  <input type="checkbox" v-model="form.qvtd" class="custom-control-input" id="qvtd">
                  <label class="custom-control-label" for="qvtd">QVTD</label>
                </div>
              </div>

              <div v-if="form.qvtd" class="mt-2">
                    <input
                        type="text"
                        v-model="form.qvtd_notes"
                        class="form-control"
                        placeholder="Describe qvtd"
                    >
              </div>
            </div>
          </div>

          <!-- Row 7: Date and Location -->
          <div class="form-group">
            <div class="form-row">
              <div class="col-6">
                <label class="mt-2">Target Build Date</label>
                <input
                  type="datetime-local"
                  v-model="form.target_build_date"
                  class="form-control"
                >
              </div>

              <div class="col-6">
                <label class="mt-2">Target Location</label>
                <input
                  type="text"
                  v-model="form.target_location"
                  class="form-control"
                  placeholder="Build location"
                >
              </div>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="form-group mt-4">
            <button class="btn btn-success" :disabled="loading">
              {{ loading ? 'Saving...' : 'Save UAT Meeting' }}
            </button>
            <router-link to="/uat-meeting" class="btn btn-secondary ml-2">Back</router-link>
            <button type="button" class="btn btn-outline-secondary ml-2" @click="resetForm">
              Reset Form
            </button>
          </div>
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
      loading: false,
      form: {
        // Required fields
        meeting_id: '',
        reason: '',

        // Optional fields
        initial_budget: null,
        play_mode: null,
        include_monitor: '',
        include_notes: '',
        notes: '',
        theme_style: '',
        preference: '',
        exemption: '',
        future_proof: false,
        case_size: null,
        okay_with_aio: false,
        need_rgb: false,
        gpu_sag: null,
        qvcrf_tag: false,
        qvse: false,
        qvca: false,
        qvtd: false,
        qvtd_notes: '',
        target_build_date: '',
        target_location: '',
      }
    };
  },
  mounted() {
    this.fetchMeetings();
  },
  watch: {
    'form.reason': function(newVal) {
      if (newVal != 2) {
        this.form.play_mode = null;
      }
    }
  },
  methods: {
    fetchMeetings() {
      axios.get('/api/meetings/all')
        .then(res => {
          this.meetings = res.data;
        })
        .catch(error => {
          console.error('Error fetching meetings:', error);
          alert('Failed to load meetings');
        });
    },

    submitUatMeetings() {
      this.loading = true;

      // Prepare data for submission
      const formData = { ...this.form };

      // Convert empty strings to null for optional fields
      Object.keys(formData).forEach(key => {
        if (formData[key] === '') {
          formData[key] = null;
        }
      });

      // Convert checkboxes from true/false to 1/0 for database
      const booleanFields = [
        'future_proof', 'okay_with_aio', 'need_rgb', 'gpu_sag',
        'qvcrf_tag', 'qvse', 'qvca', 'qvtd'
      ];

      booleanFields.forEach(field => {
        if (formData[field] === true) {
          formData[field] = 1;
        } else if (formData[field] === false) {
          formData[field] = 0;
        }
      });

      axios.post('/api/uat-meeting', formData)
        .then(() => {
          alert('UAT meeting created successfully!');
          this.$router.push('/uat-meeting');
        })
        .catch(error => {
          console.error('Error creating UAT meeting:', error);
          alert('Failed to create UAT meeting');
        })
        .finally(() => {
          this.loading = false;
        });
    },

    resetForm() {
      if (confirm('Are you sure you want to reset the form?')) {
        this.form = {
          meeting_id: '',
          reason: '',
          initial_budget: null,
          play_mode: null,
          include_monitor: '',
          include_notes: '',
          notes: '',
          theme_style: '',
          preference: '',
          exemption: '',
          future_proof: false,
          case_size: null,
          okay_with_aio: false,
          need_rgb: false,
          gpu_sag: null,
          qvcrf_tag: false,
          qvse: false,
          qvca: false,
          qvtd: false,
          qvtd_notes: '',
          target_build_date: '',
          target_location: '',
        };
      }
    }
  }
};
</script>

<style scoped>
.form-card {
  border-radius: 10px;
}

.custom-checkbox {
  padding-left: 1.5rem;
}

.custom-control-input:checked ~ .custom-control-label::before {
  border-color: #28a745;
  background-color: #28a745;
}

.mt-2 {
  margin-top: 0.5rem !important;
}

.mt-3 {
  margin-top: 1rem !important;
}

.ml-2 {
  margin-left: 0.5rem !important;
}
</style>
