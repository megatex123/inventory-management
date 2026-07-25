<template>
  <transition name="filter-panel">
    <div v-if="visible" class="row bg-light rounded p-3 border mt-2">
      <div class="col-md-3 mb-3" v-for="col in columns" :key="col.key">
        <label class="small font-weight-bold text-muted text-uppercase mb-1">{{ col.label }}</label>
        <select
          v-if="col.type === 'select'"
          class="form-control form-control-sm"
          :value="value[col.key]"
          @change="onChange(col.key, $event.target.value)"
        >
          <option value="">{{ col.placeholder || ('All ' + col.label) }}</option>
          <option v-for="opt in col.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <input
          v-else
          type="text"
          class="form-control form-control-sm"
          :placeholder="col.placeholder || ('Search ' + col.label)"
          :value="value[col.key]"
          @input="onChange(col.key, $event.target.value)"
        >
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'ColumnSearchPanel',
  props: {
    columns: {
      type: Array,
      required: true,
    },
    value: {
      type: Object,
      required: true,
    },
    visible: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    onChange(key, val) {
      this.$emit('input', { ...this.value, [key]: val });
    },
  },
};
</script>

<style scoped>
.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
