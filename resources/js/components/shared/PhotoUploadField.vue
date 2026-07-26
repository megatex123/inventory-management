<template>
  <div class="photo-upload-field">
    <label class="small text-muted mb-1">Photos (optional)</label>
    <div class="d-flex flex-wrap align-items-center">
      <div v-for="path in existingPhotos" :key="path" class="photo-thumb">
        <img :src="'/storage/' + path" alt="photo">
        <button type="button" class="remove-btn" @click="$emit('remove-existing', path)">&times;</button>
      </div>
      <div v-for="(file, idx) in newPhotos" :key="'new-' + idx" class="photo-thumb">
        <img :src="fileUrl(file)" alt="new photo">
        <button type="button" class="remove-btn" @click="$emit('remove-new', idx)">&times;</button>
      </div>
      <div class="photo-upload-btn">
        <input type="file" accept="image/*" multiple @change="onFileChange">
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PhotoUploadField',
  props: {
    existingPhotos: { type: Array, default: () => [] },
    newPhotos: { type: Array, default: () => [] },
  },
  methods: {
    fileUrl(file) {
      return URL.createObjectURL(file);
    },
    onFileChange(event) {
      if (event.target.files && event.target.files.length) {
        this.$emit('add-photos', event.target.files);
      }
      event.target.value = '';
    },
  },
};
</script>

<style scoped>
.photo-thumb {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 8px 8px 0;
}
.photo-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 4px;
}
.photo-thumb .remove-btn {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #dc3545;
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  line-height: 18px;
  font-size: 12px;
  cursor: pointer;
  padding: 0;
}
.photo-upload-btn input[type=file] {
  width: 180px;
}
</style>
