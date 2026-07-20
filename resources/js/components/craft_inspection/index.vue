<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-clipboard-check text-primary mr-2"></i>QuiviCraft Build Report</h2>
        <p class="text-muted mb-0">{{ phaseLabel }} — Round {{ round }}</p>
      </div>
      <div>
        <router-link to="/orders/all" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Back to Orders</router-link>
        <button class="btn btn-success mr-2" @click="printPdf">
          <i class="fas fa-file-pdf mr-1"></i> Print / PDF
        </button>
        <button
          class="btn btn-success"
          :disabled="!inspection || inspection.status === 'completed'"
          @click="markComplete"
        >
          <i class="fas fa-check-circle mr-1"></i>
          {{ inspection && inspection.status === 'completed' ? 'Completed' : 'Mark Inspection Complete' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <template v-else>
      <!-- Build info -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-3"><small class="text-muted d-block">Order ID</small><strong>{{ order.order_id }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Customer</small><strong>{{ order.customer ? order.customer.full_name : 'N/A' }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Build Tier</small><strong>{{ order.craft ? order.craft.name : 'N/A' }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">Inspection Status</small><span class="badge" :class="inspection && inspection.status === 'completed' ? 'badge-success' : 'badge-secondary'">{{ inspection ? inspection.status : 'N/A' }}</span></div>
          </div>
        </div>
      </div>

      <!-- Page 3 style component checklist summary -->
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-list-check mr-2"></i>Component Verification Summary</h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Component</th>
                  <th class="text-center">Model Verified</th>
                  <th class="text-center">Serial Recorded</th>
                  <th class="text-center">Factory Seal</th>
                  <th class="text-center">Visual Inspection</th>
                  <th class="text-center">QC Pass</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="items.length === 0"><td colspan="6" class="text-center text-muted py-3">No components added yet</td></tr>
                <tr v-for="item in items" :key="'summary-' + item._key">
                  <td>{{ componentLabel(item.component_type) }}</td>
                  <td class="text-center"><i class="fas" :class="item.model_verified ? 'fa-check text-success' : 'fa-times text-danger'"></i></td>
                  <td class="text-center"><i class="fas" :class="item.serial_recorded ? 'fa-check text-success' : 'fa-times text-danger'"></i></td>
                  <td class="text-center"><i class="fas" :class="item.factory_seal ? 'fa-check text-success' : 'fa-times text-danger'"></i></td>
                  <td class="text-center"><i class="fas" :class="item.inspection_status === 'sound' ? 'fa-check text-success' : 'fa-times text-danger'"></i></td>
                  <td class="text-center"><i class="fas" :class="item.qc_pass ? 'fa-check text-success' : 'fa-times text-danger'"></i></td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="text-muted small p-2 mb-0">Items marked with * (Case Fan, Accessories) represent grouped components — add one entry per unit.</p>
        </div>
      </div>

      <!-- Add component -->
      <div class="card mb-4 no-print">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-boxes mr-2"></i>Add Component</h5></div>
        <div class="card-body">
          <div class="d-flex align-items-center flex-wrap">
            <select v-model="newItemSelection" class="form-control mr-2 mb-2" style="max-width: 420px;">
              <option value="">Select component to add...</option>
              <optgroup label="Parts in this Order" v-if="availableOrderParts.length">
                <option v-for="part in availableOrderParts" :key="'part-' + part.id" :value="'part:' + part.id">
                  {{ componentLabel(part._componentType) }} — {{ part.product_name }}
                </option>
              </optgroup>
              <optgroup label="Other / Manual">
                <option v-for="type in componentTypes" :key="'type-' + type" :value="'type:' + type">{{ componentLabel(type) }}</option>
              </optgroup>
            </select>
            <button class="btn btn-primary mb-2" :disabled="!newItemSelection" @click="addItem">
              <i class="fas fa-plus-circle mr-1"></i> Add Component
            </button>
          </div>
          <p class="text-muted small mb-0" v-if="orderParts.length">
            Components from this order are added automatically.
            <span v-if="availableOrderParts.length">{{ availableOrderParts.length }} order part(s) not yet added below — use the dropdown to bring one back if removed.</span>
            <span v-if="unmappedOrderParts.length" class="text-warning">
              {{ unmappedOrderParts.length }} part(s) have a category with no matching inspection type — add manually.
            </span>
          </p>
        </div>
      </div>

      <!-- Per-component detail cards -->
      <div class="card mb-3" v-for="item in items" :key="item._key">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ componentLabel(item.component_type) }} Details Verification</h5>
            <small v-if="item.order_detail_id" class="text-success"><i class="fas fa-link mr-1"></i>Linked to order part</small>
            <small v-else class="text-muted"><i class="fas fa-pen mr-1"></i>Manually added</small>
          </div>
          <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="fas fa-trash"></i></button>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6" v-for="field in fieldSchema(item.component_type)" :key="field.key">
              <div class="form-group">
                <label class="form-label">{{ field.label }}</label>
                <input type="text" v-model="item.fields[field.key]" class="form-control">
              </div>
            </div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-3">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" :id="'mv-' + item._key" v-model="item.model_verified">
                <label class="custom-control-label" :for="'mv-' + item._key">Model Verified</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" :id="'sr-' + item._key" v-model="item.serial_recorded">
                <label class="custom-control-label" :for="'sr-' + item._key">Serial Recorded</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" :id="'fs-' + item._key" v-model="item.factory_seal">
                <label class="custom-control-label" :for="'fs-' + item._key">Factory Seal</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" :id="'qc-' + item._key" v-model="item.qc_pass">
                <label class="custom-control-label" :for="'qc-' + item._key">QC Pass</label>
              </div>
            </div>
          </div>

          <hr>

          <!-- Inspection group -->
          <inspection-group
            label="Inspection"
            good-value="sound"
            good-label="Sound"
            bad-value="not_sound"
            bad-label="Not Sound"
            :status.sync="item.inspection_status"
            :note.sync="item.inspection_note"
            :existing-photos="item.inspection_photos"
            :new-photos="item._newInspectionPhotos"
            @add-photos="files => addPhotos(item, '_newInspectionPhotos', files)"
            @remove-existing="path => removeExistingPhoto(item, 'inspection_photos', path)"
            @remove-new="idx => item._newInspectionPhotos.splice(idx, 1)"
          />

          <!-- Packaging group -->
          <inspection-group
            label="Packaging"
            good-value="intact"
            good-label="Intact"
            bad-value="damaged"
            bad-label="Damaged"
            :status.sync="item.packaging_status"
            :note.sync="item.packaging_note"
            :existing-photos="item.packaging_photos"
            :new-photos="item._newPackagingPhotos"
            @add-photos="files => addPhotos(item, '_newPackagingPhotos', files)"
            @remove-existing="path => removeExistingPhoto(item, 'packaging_photos', path)"
            @remove-new="idx => item._newPackagingPhotos.splice(idx, 1)"
          />

          <!-- Condition / Notes group -->
          <inspection-group
            label="Notes"
            good-value="sound_pristine"
            good-label="Sound & Pristine Condition"
            bad-value="issue"
            bad-label="Issue Found"
            :status.sync="item.condition_status"
            :note.sync="item.condition_note"
            :existing-photos="item.condition_photos"
            :new-photos="item._newConditionPhotos"
            @add-photos="files => addPhotos(item, '_newConditionPhotos', files)"
            @remove-existing="path => removeExistingPhoto(item, 'condition_photos', path)"
            @remove-new="idx => item._newConditionPhotos.splice(idx, 1)"
          />

          <div v-if="item._errors && item._errors.length" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="e in item._errors" :key="e">{{ e }}</li></ul>
          </div>

          <div class="text-right mt-3">
            <button class="btn btn-primary" :disabled="item._saving" @click="saveItem(item)">
              <span v-if="item._saving" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i>
              {{ item.id ? 'Update' : 'Save' }} {{ componentLabel(item.component_type) }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import InspectionGroup from './InspectionGroup.vue';

const FIELD_SCHEMAS = {
  cpu: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'batch', label: 'Batch' },
    { key: 'visual', label: 'Visual' }, { key: 'pins', label: 'Pins' }
  ],
  mbd: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'cpu_socket', label: 'CPU Socket' },
    { key: 'dimm_slot', label: 'DIMM Slot' }, { key: 'pcie_slots', label: 'PCIe Slots' }, { key: 'm2_slots', label: 'M.2 Slots' },
    { key: 'vrm_heatsinks', label: 'VRM Heatsinks' }, { key: 'rear_io', label: 'Rear I/O' },
    { key: 'cmos_batt', label: 'CMOS Batt' }, { key: 'accessories', label: 'Accessories' }
  ],
  gpu: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'connector_pin', label: 'Connector Pin' },
    { key: 'pcie_connector', label: 'PCIe Connector' }, { key: 'power_connector', label: 'Power Connector' },
    { key: 'fan_rotation', label: 'Fan Rotation' }, { key: 'vrm_heatsinks', label: 'VRM Heatsinks' },
    { key: 'backplate', label: 'Backplate' }, { key: 'rgb', label: 'RGB' }
  ],
  ram: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'capacity', label: 'Capacity' },
    { key: 'speed', label: 'Speed' }, { key: 'timing', label: 'Timing' }, { key: 'voltage', label: 'Voltage' },
    { key: 'quantity', label: 'Quantity' }, { key: 'heatspreader', label: 'Heatspreader' }, { key: 'gold_contacts', label: 'Gold Contacts' }
  ],
  ssd: [
    { key: 'type', label: 'Type' }, { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' },
    { key: 'capacity', label: 'Capacity' }, { key: 'connector', label: 'Connector' },
    { key: 'contact_pins', label: 'Contact Pins' }, { key: 'label_condition', label: 'Label Condition' }
  ],
  hdd: [
    { key: 'type', label: 'Type' }, { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' },
    { key: 'capacity', label: 'Capacity' }, { key: 'connector', label: 'Connector' },
    { key: 'contact_pins', label: 'Contact Pins' }, { key: 'label_condition', label: 'Label Condition' }
  ],
  aio: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'radiator', label: 'Radiator' },
    { key: 'pump_housing', label: 'Pump Housing' }, { key: 'cold_plate', label: 'Cold Plate' },
    { key: 'tubes', label: 'Tubes' }, { key: 'fans', label: 'Fans' }, { key: 'accessories', label: 'Accessories' }
  ],
  hsf: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'fan', label: 'Fan' },
    { key: 'mounting_kit', label: 'Mounting Kit' }, { key: 'cold_plate', label: 'Cold Plate' },
    { key: 'fins', label: 'Fins' }, { key: 'accessories', label: 'Accessories' }
  ],
  psu: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'wattage', label: 'Wattage' },
    { key: 'efficiency_rating', label: 'Efficiency Rating' }, { key: 'modularity', label: 'Modularity' },
    { key: 'cables_inclusion', label: 'Cables Inclusion' }, { key: 'housing', label: 'Housing' }, { key: 'fan', label: 'Fan' }
  ],
  cse: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'case_size', label: 'Case Size' },
    { key: 'front_panel', label: 'Front Panel' }, { key: 'glass_panel', label: 'Glass Panel' },
    { key: 'dust_filters', label: 'Dust Filters' }, { key: 'included_fans', label: 'Included Fans' },
    { key: 'front_io', label: 'Front I/O' }, { key: 'accessories', label: 'Accessories' }
  ],
  fan: [
    { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' }, { key: 'size', label: 'Size' },
    { key: 'airflow_direction', label: 'Airflow Direction' }, { key: 'position', label: 'Position' },
    { key: 'cable', label: 'Cable' }, { key: 'quantity', label: 'Quantity' }
  ],
  acc: [
    { key: 'type', label: 'Type' }, { key: 'model', label: 'Product Model' }, { key: 'serial', label: 'Serial' },
    { key: 'description', label: 'Description' }, { key: 'quantity', label: 'Quantity' }
  ]
};

const COMPONENT_LABELS = {
  cpu: 'CPU', mbd: 'Motherboard', gpu: 'GPU', ram: 'RAM', ssd: 'SSD / Storage', hdd: 'HDD / Storage',
  aio: 'Water Cooler (AIO)', hsf: 'Air Cooler (HSF)', psu: 'PSU', cse: 'Case', fan: 'Case Fan*', acc: 'Accessories*'
};

// Maps the order's product category (from categories.name) to an inspection component_type
const CATEGORY_TO_COMPONENT_TYPE = {
  CPU: 'cpu', MBD: 'mbd', GPU: 'gpu', RAM: 'ram', SSD: 'ssd', HDD: 'hdd',
  AIO: 'aio', HSF: 'hsf', PSU: 'psu', CSE: 'cse', FAN: 'fan'
};

function mapCategoryToComponentType(categoryName) {
  if (!categoryName) return null;
  const key = categoryName.trim().toUpperCase();
  if (CATEGORY_TO_COMPONENT_TYPE[key]) return CATEGORY_TO_COMPONENT_TYPE[key];
  if (key.startsWith('ACC') || key.startsWith('PER')) return 'acc';
  return null;
}

let keySeq = 0;

export default {
  components: { InspectionGroup },
  data() {
    return {
      order: {},
      inspection: null,
      items: [],
      componentTypes: [],
      orderParts: [],
      newItemSelection: '',
      loading: true
    };
  },
  computed: {
    round() {
      return this.$route.params.round || 1;
    },
    phase() {
      return this.$route.params.phase || 2;
    },
    phaseLabel() {
      const labels = { 2: 'Pre Build Inspection', 3: 'Build Inspection', 4: 'Post Build Inspection' };
      return labels[this.phase] || labels[Number(this.phase)] || 'Pre Build Inspection';
    },
    apiBase() {
      return `/api/order/${this.$route.params.id}/inspection/${this.phase}/${this.round}`;
    },
    // Order parts whose category maps to a known inspection type
    mappedOrderParts() {
      return this.orderParts
        .map(part => ({ ...part, _componentType: mapCategoryToComponentType(part.category_name) }))
        .filter(part => part._componentType);
    },
    unmappedOrderParts() {
      return this.orderParts.filter(part => !mapCategoryToComponentType(part.category_name));
    },
    // Order parts not yet linked to an existing inspection item
    availableOrderParts() {
      const usedOrderDetailIds = new Set(this.items.map(i => i.order_detail_id).filter(id => id != null));
      return this.mappedOrderParts.filter(part => !usedOrderDetailIds.has(part.id));
    }
  },
  mounted() {
    Promise.all([this.fetchData(), this.fetchOrderParts()]).then(() => {
      this.autoPopulateOrderParts();
    });
  },
  methods: {
    componentLabel(type) {
      return COMPONENT_LABELS[type] || type;
    },
    fieldSchema(type) {
      return FIELD_SCHEMAS[type] || [];
    },
    blankFields(type) {
      const fields = {};
      this.fieldSchema(type).forEach(f => { fields[f.key] = ''; });
      return fields;
    },
    async fetchData() {
      this.loading = true;
      try {
        const res = await axios.get(this.apiBase);
        const data = res.data.data;
        this.order = data.order;
        this.inspection = data.inspection;
        this.componentTypes = data.component_types;
        this.items = (data.inspection.items || []).map(this.hydrateItem);
      } catch (error) {
        console.error('Error fetching inspection:', error);
        Swal.fire('Error!', 'Failed to load inspection data', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchOrderParts() {
      try {
        const res = await axios.get(`/api/orders/orderdetails/${this.$route.params.id}`);
        this.orderParts = res.data || [];
      } catch (error) {
        console.error('Error fetching order parts:', error);
      }
    },
    hydrateItem(record) {
      return {
        ...record,
        fields: { ...this.blankFields(record.component_type), ...(record.fields || {}) },
        inspection_photos: record.inspection_photos || [],
        packaging_photos: record.packaging_photos || [],
        condition_photos: record.condition_photos || [],
        _newInspectionPhotos: [],
        _newPackagingPhotos: [],
        _newConditionPhotos: [],
        _removeInspectionPhotos: [],
        _removePackagingPhotos: [],
        _removeConditionPhotos: [],
        _key: 'item-' + (keySeq++),
        _saving: false,
        _errors: []
      };
    },
    buildItem(componentType, orderDetailId, prefilledModel) {
      const fields = this.blankFields(componentType);
      if (prefilledModel && Object.prototype.hasOwnProperty.call(fields, 'model')) {
        fields.model = prefilledModel;
      }

      return {
        id: null,
        component_type: componentType,
        fields,
        order_detail_id: orderDetailId,
        model_verified: false,
        serial_recorded: false,
        factory_seal: false,
        qc_pass: false,
        inspection_status: 'sound',
        inspection_note: '',
        inspection_photos: [],
        packaging_status: 'intact',
        packaging_note: '',
        packaging_photos: [],
        condition_status: 'sound_pristine',
        condition_note: '',
        condition_photos: [],
        _newInspectionPhotos: [],
        _newPackagingPhotos: [],
        _newConditionPhotos: [],
        _removeInspectionPhotos: [],
        _removePackagingPhotos: [],
        _removeConditionPhotos: [],
        _key: 'item-' + (keySeq++),
        _saving: false,
        _errors: []
      };
    },
    // Auto-add an inspection item for every purchased order part whose category
    // maps to a known component type, so the inspector doesn't have to pick each one manually.
    autoPopulateOrderParts() {
      this.availableOrderParts.forEach(part => {
        this.items.push(this.buildItem(part._componentType, part.id, part.product_name));
      });
    },
    addItem() {
      if (!this.newItemSelection) return;

      const [kind, value] = this.newItemSelection.split(':');
      let componentType;
      let orderDetailId = null;
      let prefilledModel = '';

      if (kind === 'part') {
        const part = this.availableOrderParts.find(p => String(p.id) === value);
        if (!part) return;
        componentType = part._componentType;
        orderDetailId = part.id;
        prefilledModel = part.product_name || '';
      } else {
        componentType = value;
      }

      this.items.push(this.buildItem(componentType, orderDetailId, prefilledModel));
      this.newItemSelection = '';
    },
    addPhotos(item, field, fileList) {
      const existingKey = field === '_newInspectionPhotos' ? 'inspection_photos'
        : field === '_newPackagingPhotos' ? 'packaging_photos' : 'condition_photos';
      const currentTotal = item[existingKey].length + item[field].length;
      const room = Math.max(0, 2 - currentTotal);
      Array.from(fileList).slice(0, room).forEach(f => item[field].push(f));
    },
    removeExistingPhoto(item, field, path) {
      const map = { inspection_photos: '_removeInspectionPhotos', packaging_photos: '_removePackagingPhotos', condition_photos: '_removeConditionPhotos' };
      item[map[field]].push(path);
      item[field] = item[field].filter(p => p !== path);
    },
    async saveItem(item) {
      item._saving = true;
      item._errors = [];

      const formData = new FormData();
      formData.append('component_type', item.component_type);
      if (item.order_detail_id != null) {
        formData.append('order_detail_id', item.order_detail_id);
      }
      Object.keys(item.fields).forEach(key => formData.append(`fields[${key}]`, item.fields[key] || ''));
      formData.append('model_verified', item.model_verified ? '1' : '0');
      formData.append('serial_recorded', item.serial_recorded ? '1' : '0');
      formData.append('factory_seal', item.factory_seal ? '1' : '0');
      formData.append('qc_pass', item.qc_pass ? '1' : '0');

      formData.append('inspection_status', item.inspection_status);
      formData.append('inspection_note', item.inspection_note || '');
      item._newInspectionPhotos.forEach(f => formData.append('inspection_photos[]', f));
      item._removeInspectionPhotos.forEach(p => formData.append('remove_inspection_photos[]', p));

      formData.append('packaging_status', item.packaging_status);
      formData.append('packaging_note', item.packaging_note || '');
      item._newPackagingPhotos.forEach(f => formData.append('packaging_photos[]', f));
      item._removePackagingPhotos.forEach(p => formData.append('remove_packaging_photos[]', p));

      formData.append('condition_status', item.condition_status);
      formData.append('condition_note', item.condition_note || '');
      item._newConditionPhotos.forEach(f => formData.append('condition_photos[]', f));
      item._removeConditionPhotos.forEach(p => formData.append('remove_condition_photos[]', p));

      try {
        const url = item.id
          ? `${this.apiBase}/items/${item.id}`
          : `${this.apiBase}/items`;

        const res = await axios.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
        const saved = res.data.data;

        Object.assign(item, this.hydrateItem(saved), { _key: item._key });

        Swal.fire({ title: 'Saved!', text: `${this.componentLabel(item.component_type)} inspection saved`, icon: 'success', timer: 1500, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors;
          item._errors = Object.keys(errors).map(field => `${field}: ${errors[field].join(', ')}`);
        } else {
          item._errors = [error.response?.data?.message || 'Failed to save inspection item'];
        }
        Swal.fire('Error!', item._errors.join('<br>'), 'error');
      } finally {
        item._saving = false;
      }
    },
    deleteItem(item) {
      if (!item.id) {
        this.items = this.items.filter(i => i._key !== item._key);
        return;
      }

      Swal.fire({
        title: 'Remove this component?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it!'
      }).then(result => {
        if (result.isConfirmed) {
          axios.delete(`${this.apiBase}/items/${item.id}`)
            .then(() => {
              this.items = this.items.filter(i => i._key !== item._key);
              Swal.fire('Removed!', '', 'success');
            })
            .catch(() => Swal.fire('Error!', 'Failed to remove component', 'error'));
        }
      });
    },
    markComplete() {
      axios.post(`${this.apiBase}/complete`)
        .then(res => {
          this.inspection = res.data.data;
          Swal.fire('Marked Complete!', 'This inspection is now marked as completed.', 'success');
        })
        .catch(() => Swal.fire('Error!', 'Failed to mark inspection complete', 'error'));
    },
    printPdf() {
      window.print();
    }
  }
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>

<style>
@media print {
    #accordionSidebar,
    #sidebarToggleTop,
    .topbar,
    .scroll-to-top {
        display: none !important;
    }

    .btn,
    .no-print,
    .photo-upload-btn,
    .remove-btn {
        display: none !important;
    }

    html, body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #wrapper,
    #content-wrapper,
    #content,
    #container-wrapper {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
        padding-left: 0.5in !important;
        padding-right: 0.5in !important;
        box-sizing: border-box !important;
    }

    .my-4 {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }

    .table-bordered,
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000 !important;
    }

    .text-primary {
        color: #000 !important;
    }

    .badge {
        border: 1px solid #000 !important;
        background-color: #fff !important;
        color: #000 !important;
    }

    .form-control {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        -webkit-appearance: none;
        appearance: none;
    }

    .print-only {
        display: inline !important;
        font-weight: 700;
    }

    .inspection-group {
        border: 1px solid #ccc !important;
        page-break-inside: avoid;
    }
}
</style>
