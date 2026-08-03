<template>
  <div class="inspection-group mb-3 p-3 border rounded">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">{{ label }}</h6>
      <select class="form-control form-control-sm no-print" style="width: auto;" :value="status" @change="$emit('update:status', $event.target.value)">
        <option :value="goodValue">{{ goodLabel }}</option>
        <option :value="badValue">{{ badLabel }}</option>
      </select>
      <strong class="print-only">{{ status === goodValue ? goodLabel : badLabel }}</strong>
    </div>

    <div>
      <label class="small text-muted mb-1">
        Photo evidence <span v-if="status === goodValue">(1–2 required)</span><span v-else>(optional, up to 2)</span>
      </label>
      <div class="d-flex flex-wrap align-items-center">
        <div v-for="path in existingPhotos" :key="path" class="photo-thumb">
          <img :src="`/storage/${path}`" alt="photo">
          <button type="button" class="remove-btn" @click="$emit('remove-existing', path)">&times;</button>
          <span class="photo-filename" :title="fileName(path)">{{ fileName(path) }}</span>
        </div>
        <div v-for="(file, idx) in newPhotos" :key="'new-' + idx" class="photo-thumb">
          <img :src="fileUrl(file)" alt="new photo">
          <button type="button" class="remove-btn" @click="$emit('remove-new', idx)">&times;</button>
          <span class="photo-filename" :title="file.name">{{ file.name }}</span>
        </div>
        <div v-if="(existingPhotos.length + newPhotos.length) < 2" class="photo-upload-btn">
          <input type="file" accept="image/*" multiple @change="onFileChange" ref="fileInput">
        </div>
      </div>
    </div>
    <div class="mt-2">
      <label class="small text-muted mb-1">
        Note <span v-if="status !== goodValue">(required)</span><span v-else>(optional)</span>
      </label>
      <textarea class="form-control" rows="2" :value="note" @input="$emit('update:note', $event.target.value)" placeholder="Describe the issue..."></textarea>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    label: String,
    goodValue: String,
    goodLabel: String,
    badValue: String,
    badLabel: String,
    status: String,
    note: String,
    existingPhotos: { type: Array, default: () => [] },
    newPhotos: { type: Array, default: () => [] }
  },
  methods: {
    fileUrl(file) {
      return URL.createObjectURL(file);
    },
    fileName(path) {
      return path.split('/').pop();
    },
    onFileChange(event) {
      if (event.target.files && event.target.files.length) {
        this.$emit('add-photos', event.target.files);
      }
      event.target.value = '';
    }
  }
};
</script>

<style scoped>
.print-only {
  display: none;
}
.photo-thumb {
  position: relative;
  width: 70px;
  margin: 0 0.5rem 0.5rem 0;
}
.photo-thumb img {
  display: block;
  width: 70px;
  height: 70px;
  object-fit: cover;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #dee2e6;
}
.photo-filename {
  display: block;
  width: 70px;
  margin-top: 2px;
  font-size: 10px;
  color: #6c757d;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: center;
}
.remove-btn {
  position: absolute;
  top: 0;
  right: 0;
  background: rgba(220, 53, 69, 0.85);
  color: #fff;
  border: none;
  width: 20px;
  height: 20px;
  line-height: 18px;
  font-size: 14px;
  cursor: pointer;
}
.photo-upload-btn {
  position: relative;
  width: 70px;
  height: 70px;
  border: 1px dashed #adb5bd;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.5rem;
}
.photo-upload-btn input[type="file"] {
  font-size: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  position: absolute;
}
.photo-upload-btn::before {
  content: '+';
  font-size: 1.5rem;
  color: #adb5bd;
  pointer-events: none;
}
</style>
