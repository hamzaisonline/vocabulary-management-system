<script setup>
import { ref } from 'vue';

const className = ref('');
const language = ref('');
const studentFile = ref(null);

function handleFileUpload(event) {
  studentFile.value = event.target.files[0];
}

function createClass() {
  // Handle class creation logic here
  console.log({ className: className.value, language: language.value, studentFile: studentFile.value });
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Create New Class</h1>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Class Name</span>
      </label>
      <input type="text" class="input input-bordered" v-model="className" />
    </div>

    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Language</span>
      </label>
      <input type="text" class="input input-bordered" v-model="language" />
    </div>

    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Upload Students (Spreadsheet)</span>
      </label>
      <input type="file" class="file-input file-input-bordered" @change="handleFileUpload" />
    </div>

    <button class="btn btn-primary" @click="createClass">Create Class</button>
  </div>
</template>
