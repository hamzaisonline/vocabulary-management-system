<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useClassStore } from '@/stores/classStore';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from 'vue-toastification';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const classStore = useClassStore();
const authStore = useAuthStore();
const toast = useToast();

const className = ref('');
const description = ref('');
const language = ref('');
const teacherId = ref('');
const errors = ref({});

// No teacher-directory endpoint is in scope, so admins can assign by ID.
const showTeacherSelector = authStore.isAdmin;
const classesPath = authStore.isAdmin ? '/admin/classes' : '/teacher/classes';

async function handleCreateClass() {
  errors.value = {};

  if (!className.value.trim()) {
    errors.value.name = 'Class name is required';
  }

  if (!language.value.trim()) {
    errors.value.language = 'Language is required';
  }

  if (Object.keys(errors.value).length > 0) {
    return;
  }

  const payload = {
    name: className.value.trim(),
    description: description.value.trim(),
    language: language.value.trim(),
  };

  // Only include teacher_id for admin role
  if (authStore.isAdmin && teacherId.value) {
    payload.teacher_id = parseInt(teacherId.value);
  }

  try {
    classStore.loading = true;
    await classStore.createClass(payload);
    toast.success('Class created successfully!');
    router.push(classesPath);
  } catch (error) {
    if (error?.response?.status === 422) {
      const data = error?.response?.data;
      if (data?.errors) {
        errors.value = data.errors;
      } else {
        toast.error(data?.message || 'Validation error');
      }
    } else if (error?.response?.status === 403) {
      toast.error('You do not have permission to create classes');
    } else {
      toast.error(error?.response?.data?.message || 'Failed to create class');
    }
  } finally {
    classStore.loading = false;
  }
}

function goToClasses() {
  router.push(classesPath);
}
</script>

<template>
  <div class="min-w-0 space-y-4 p-0 sm:space-y-6 sm:p-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-primary sm:text-3xl">Create New Class</h1>
      <button @click="goToClasses" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Classes
      </button>
    </div>

    <!-- Create Class Form -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <form @submit.prevent="handleCreateClass" class="space-y-4">
          <!-- Class Name -->
          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Class Name *</span>
            </label>
            <input
              v-model="className"
              type="text"
              class="input input-bordered"
              :class="{ 'input-error': errors.name }"
              placeholder="e.g., Spanish Basics"
              :disabled="classStore.loading"
            />
            <label v-if="errors.name" class="label">
              <span class="label-text-alt text-error">{{ errors.name }}</span>
            </label>
          </div>

          <!-- Description -->
          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Description</span>
            </label>
            <textarea
              v-model="description"
              class="textarea textarea-bordered h-24"
              placeholder="Class description and objectives"
              :disabled="classStore.loading"
            ></textarea>
          </div>

          <!-- Language -->
          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Language *</span>
            </label>
            <input
              v-model="language"
              type="text"
              class="input input-bordered"
              :class="{ 'input-error': errors.language }"
              placeholder="e.g., Spanish, French, German"
              :disabled="classStore.loading"
            />
            <label v-if="errors.language" class="label">
              <span class="label-text-alt text-error">{{ errors.language }}</span>
            </label>
          </div>

          <!-- Teacher Selector (Admin only) -->
          <div v-if="showTeacherSelector" class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Teacher</span>
              <span class="label-text-alt text-warning">Optional for admin</span>
            </label>
            <input
              v-model="teacherId"
              type="number"
              class="input input-bordered"
              placeholder="Teacher ID (optional)"
              :disabled="classStore.loading"
            />
            <label class="label">
              <span class="label-text-alt">Leave blank to assign to yourself</span>
            </label>
          </div>

          <!-- Form Actions -->
          <div class="card-actions justify-end gap-2 pt-4">
            <button 
              type="button" 
              @click="goToClasses" 
              :disabled="classStore.loading"
              class="btn btn-ghost"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="classStore.loading"
              class="btn btn-primary"
            >
              <span v-if="classStore.loading" class="loading loading-spinner loading-sm"></span>
              {{ classStore.loading ? 'Creating...' : 'Create Class' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
