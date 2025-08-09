<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
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

function goToDashboard() {
  router.push('/teacher');
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold text-primary">Create New Class</h1>
      <button @click="goToDashboard" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Dashboard
      </button>
    </div>
    <!-- Create Class Form -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <form @submit.prevent="createClass" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Class Name *</span>
            </label>
            <input
              type="text"
              class="input input-bordered"
              v-model="className"
              placeholder="Enter class name"
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Language *</span>
            </label>
            <input
              type="text"
              class="input input-bordered"
              v-model="language"
              placeholder="e.g., Spanish, French, German"
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Upload Students (Optional)</span>
            </label>
            <input
              type="file"
              class="file-input file-input-bordered"
              accept=".csv,.xlsx,.xls"
              @change="handleFileUpload"
            />
            <label class="label">
              <span class="label-text-alt">CSV or Excel file with student list</span>
            </label>
          </div>

          <div class="card-actions justify-end gap-2">
            <button type="button" @click="goToDashboard" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Create Class
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
