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
                            <th>Meeting ID</th>
                            <th>Customer</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Notes</th>
                            <th>Document</th>
                            <th>Action</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="meeting in filteredMeetings" :key="meeting.id">
                            <td>{{ meeting.meeting_id }}</td>
                            <td>{{ meeting.customer.full_name }}</td>
                            <td>{{ meeting.title }}</td>
                            <td>{{ formatDate(meeting.meeting_date) }}</td>
                            <td>
                                {{
                                meeting.meeting_notes
                                    ? (meeting.meeting_notes.length > 50
                                        ? meeting.meeting_notes.substring(0, 50) + '...'
                                        : meeting.meeting_notes)
                                    : '-'
                                }}
                            </td>
                            <td>
                                <a v-if="meeting.document" :href="`/storage/${meeting.document}`" target="_blank">View</a>
                            </td>
                            <td>
                                <router-link :to="`/meeting/edit/${meeting.id}`" class="btn btn-sm btn-primary">Edit</router-link>
                                <button class="btn btn-sm btn-danger" @click="deleteMeeting(meeting.id)">Delete</button>
                            </td>
                            </tr>

                            <tr v-if="filteredMeetings.length === 0">
                            <td colspan="5" class="text-center text-muted">
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
        (m.meeting_date && m.meeting_date.toLowerCase().includes(keyword)) ||
        (m.title && m.title.toLowerCase().includes(keyword))
      );
    }
  },

  methods: {
    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    },
    fetchMeetings() {
      axios.get('/api/meetings').then(res => {
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
        axios.delete(`/api/meetings/${id}`)
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

