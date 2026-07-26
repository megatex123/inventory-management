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
  width: 70px;
  height: 70px;
  margin: 0 0.5rem 0.5rem 0;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #dee2e6;
}
.photo-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
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
