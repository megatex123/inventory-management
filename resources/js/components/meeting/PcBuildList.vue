<template>
  <div class="container">
    <h4 class="mb-3">PC Build Requests</h4>

    <table class="table table-bordered table-sm align-middle">
      <thead class="table-dark">
        <tr>
          <th>LGMT ID</th>
          <th>Budget (RM)</th>
          <th>Reason</th>
          <th>Play Mode</th>
          <th>Case</th>
          <th>ITA</th>
          <th>RGB</th>
          <th>GPU Sag</th>
          <th>Target Date</th>
          <th>Location</th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="rows.length === 0">
          <td colspan="10" class="text-center text-muted">
            No records found
          </td>
        </tr>

        <tr v-for="row in rows" :key="row.id">
          <td>{{ row.lgmt_id }}</td>
          <td>RM {{ Number(row.initial_budget).toFixed(2) }}</td>
          <td>{{ row.reason }}</td>
          <td>{{ row.play_mode }}</td>
          <td>{{ row.case_size }}</td>

          <td class="text-center">
            <input type="checkbox" disabled :checked="row.okay_with_ita">
          </td>
          <td class="text-center">
            <input type="checkbox" disabled :checked="row.need_rgb">
          </td>
          <td class="text-center">
            <input type="checkbox" disabled :checked="row.gpu_sag">
          </td>

          <td>{{ formatDate(row.target_build_date) }}</td>
          <td>{{ row.target_location }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "PcBuildList",

  data() {
    return {
      rows: []
    };
  },

  mounted() {
    this.loadData();
  },

  methods: {
    async loadData() {
      const res = await axios.get("/api/pc-build");
      this.rows = res.data;
    },

    formatDate(date) {
      if (!date) return "-";
      return new Date(date).toLocaleDateString();
    }
  }
};
</script>
