<template>
  <div class="container">
    <h4 class="mb-3">PC Build Request</h4>

    <form @submit.prevent="submit">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-dark">
          <tr>
            <th>LGMT ID</th>
            <th>Budget (RM)</th>
            <th>Reason</th>
            <th>Play Mode</th>
            <th>Include Monitor</th>
            <th>Theme</th>
            <th>Preference</th>
            <th>Case Size</th>
            <th>ITA</th>
            <th>RGB</th>
            <th>GPU Sag</th>
            <th>Target Date</th>
            <th>Location</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td><input v-model="form.lgmt_id" class="form-control" required /></td>
            <td><input v-model="form.initial_budget" type="number" class="form-control" /></td>
            <td><input v-model="form.reason" class="form-control" /></td>

            <td>
              <select v-model="form.play_mode" class="form-control">
                <option value="">-</option>
                <option>Singleplayer</option>
                <option>Multiplayer</option>
              </select>
            </td>

            <td><input v-model="form.include_monitor" class="form-control" /></td>
            <td><input v-model="form.theme_style" class="form-control" /></td>
            <td><input v-model="form.preference" class="form-control" /></td>
            <td><input v-model="form.case_size" class="form-control" /></td>

            <td class="text-center"><input type="checkbox" v-model="form.okay_with_ita" /></td>
            <td class="text-center"><input type="checkbox" v-model="form.need_rgb" /></td>
            <td class="text-center"><input type="checkbox" v-model="form.gpu_sag" /></td>

            <td><input type="date" v-model="form.target_build_date" class="form-control" /></td>
            <td><input v-model="form.target_location" class="form-control" /></td>
          </tr>
        </tbody>
      </table>

      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "PcBuildForm",

  data() {
    return {
      form: {
        lgmt_id: "",
        initial_budget: "",
        reason: "",
        play_mode: "",
        include_monitor: "",
        theme_style: "",
        preference: "",
        case_size: "",
        okay_with_ita: false,
        need_rgb: false,
        gpu_sag: false,
        target_build_date: "",
        target_location: ""
      }
    };
  },

  methods: {
    async submit() {
      try {
        await axios.post("/api/pc-build/store", this.form);
        alert("Saved successfully");
        this.reset();
      } catch (e) {
        console.error(e);
        alert("Failed to save");
      }
    },

    reset() {
      Object.keys(this.form).forEach(key => {
        this.form[key] = typeof this.form[key] === "boolean" ? false : "";
      });
    }
  }
};
</script>
