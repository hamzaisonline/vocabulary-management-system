<script setup>
const settings = {
  siteName: 'Language Learning Platform',
  adminEmail: 'admin@example.com',
};

function updateSettings() {
  console.log('Settings updated:', settings);
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Admin Settings</h1>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Site Name</span>
      </label>
      <input type="text" class="input input-bordered" v-model="settings.siteName" />
    </div>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Admin Email</span>
      </label>
      <input type="email" class="input input-bordered" v-model="settings.adminEmail" />
    </div>
    <button class="btn btn-primary" @click="updateSettings">Update Settings</button>
  </div>
</template>
