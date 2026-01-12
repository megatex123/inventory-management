<template>
  <div>
    <div class="row justify-content-center">
      <div class="col-xl-12 col-lg-12 col-md-12">
        <div class="card shadow-sm my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <!-- Card Header -->
                <div class="card">
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <router-link to="/meeting/create" class="btn btn-primary ml-3">
                      Create Meeting
                    </router-link>

                    <h5 class="m-0 font-weight-bold text-primary">
                      Meetings
                    </h5>

                    <input
                      type="text"
                      class="form-control"
                      v-model="searchItem"
                      id="searchItems"
                      placeholder="Search Meetings By Customer"
                    />
                  </div>

                  <!-- Table -->
                  <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                      <thead class="thead-light">
                        <tr>
                            <th class="text-center align-top">Meeting ID </th>
                            <th class="text-center align-top">Budget (RM)</th>
                            <th class="text-center align-top">Reason</th>
                            <th class="text-center align-top">Play Mode</th>
                            <th class="text-center align-top">Include Monitor</th>
                            <th class="text-center align-top">Notes</th>
                            <th class="text-center align-top">Theme Style</th>
                            <th class="text-center align-top">Preference</th>
                            <th class="text-center align-top">Exemption</th>
                            <th class="text-center align-top">Future Proof / Case Size / AIO / GPU Sag</th>
                            <th class="text-center align-top">QVCRF Tag / QVSE / QVCA / QVTD</th>
                            <th class="text-center align-top">Target Date</th>
                            <th class="text-center align-top">Target Location</th>
                            <th class="text-center align-top">Action</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="meeting in filteredMeetings" :key="meeting.id">
                            <td>{{ meeting.meeting.meeting_id }}</td>
                            <td>RM {{ Number(meeting.initial_budget).toFixed(2) }}</td>
                            <td>{{ meeting.reason }}</td>
                            <td>{{ meeting.play_mode }}</td>
                            <td>{{ meeting.include_monitor }}</td>
                            <td>{{ meeting.notes }}</td>
                            <td>{{ meeting.theme_style }}</td>
                            <td>{{ meeting.preference }}</td>
                            <td>{{ meeting.exemption }}</td>
                            <td class="text-center">
                                <span v-if="meeting.future_proof == 1">Future Proof : Yes</span>
                                <span v-else>Future Proof : No</span>
                                <br>
                                <span v-if="meeting.case_size == 1">Case Size : Yes</span>
                                <span v-else>Case Size : No</span>
                                <br>
                                <span v-if="meeting.okay_with_aio == 1">Okey with AIO: Yes</span>
                                <span v-else>Okey with AIO : No</span>
                                <br>
                                <span v-if="meeting.gpu_sag == 1">GPU SAG: Yes</span>
                                <span v-else>GPU SAG : No</span>
                            </td>
                            <td class="text-center">
                                <span v-if="meeting.qvcrf_tag == 1">QVCRF TAG : Yes</span>
                                <span v-else>QVCRF TAG : No</span>
                                <br>
                                <span v-if="meeting.qvse == 1">QVSE : Yes</span>
                                <span v-else>QVSE : No</span>
                                <br>
                                <span v-if="meeting.qvca == 1">QVCA : Yes</span>
                                <span v-else>QVCA : No</span>
                                <br>
                                <span v-if="meeting.qvtd == 1">QVTD: Yes</span>
                                <span v-else>QVTD : No</span>
                            </td>
                            <td>{{ meeting.target_build_date }}</td>
                            <td>{{ meeting.target_location }}</td>
                            <td>
                                <router-link :to="`/meeting-details/edit/${meeting.id}`" class="btn btn-sm btn-primary">Edit</router-link>
                                <button class="btn btn-sm btn-danger" @click="deleteMeeting(meeting.id)">Delete</button>
                            </td>
                            </tr>

                            <tr v-if="filteredMeetings.length === 0">
                            <td colspan="14" class="text-center text-muted">
                                No meetings found.
                            </td>
                        </tr>
                      </tbody>
                    </table>
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
  data() {
    return {
      meetings: [],
      searchItem: '',
    };
  },
  mounted() {
    this.fetchMeetings();
  },
  computed: {
    filteredMeetings() {
      if (!this.searchItem) return this.meetings;

      const keyword = this.searchItem.toLowerCase();

      return this.meetings.filter(m =>
        (m.customer?.full_name && m.customer.full_name.toLowerCase().includes(keyword)) ||
        (m.customer?.phone && m.customer.phone.toLowerCase().includes(keyword)) ||
        (m.meeting_date && m.meeting_date.toLowerCase().includes(keyword))
      );
    }
  },

  methods: {
    formatPrice(value) {
      return value ? Number(value).toLocaleString() : '0.00';
    },
    calculateCountdown(date) {
      if (!date) return '-';
      const target = new Date(date);
      const now = new Date();
      const diff = target - now;
      
      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      
      if (days < 0) return 'Overdue';
      if (days === 0) return 'Due Today';
      return `${days} Days Left`;
    },
    getCountdownClass(date) {
      if (!date) return '';
      const days = Math.floor((new Date(date) - new Date()) / (1000 * 60 * 60 * 24));
      if (days < 3) return 'text-danger'; // Urgent
      if (days < 7) return 'text-warning'; // Upcoming
      return 'text-success';
    },
    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    },
    fetchMeetings() {
      axios.get('/api/meeting-details').then(res => {
        this.meetings = res.data;
      });
    },
    deleteMeeting(id) {
      Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
      }).then(result => {
        if (result.isConfirmed) {
        axios.delete(`/api/meeting-details/${id}`)
        .then(() => {
            this.meetings = this.meetings.filter(c => c.id !== id);
            Swal.fire('Deleted!', 'Meeting has been deleted.', 'success');
        })
        .catch(() => {
            Swal.fire('Error!', 'Failed to delete meeting.', 'error');
        })
        .then(() => {
            this.fetchMeetings();
        });
        }
      });
    },
  },
};
</script>

<style scoped>
    #searchItems {
        width: 270px !important;
    }
    img {
        object-fit: cover;
    }
    .bg-highlight-purple {
        background: rgba(111, 66, 193, 0.15);
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-block;
    }
</style>

