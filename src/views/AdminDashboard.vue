<script setup>
import {
  PresentationChartBarIcon,
  Square3Stack3DIcon,
  UserGroupIcon,
  UsersIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

// Dummy statistics for the admin dashboard
const stats = ref({
  classes: 12,
  students: 230,
  teachers: 15,
  words: 450,
});
</script>

<template>
  <div class="p-6 space-y-10">
    <!-- Title -->
    <div class="flex justify-between items-center">
      <h1 class="text-4xl font-bold">Admin Dashboard</h1>
      <router-link to="/admin/settings" class="btn btn-primary flex items-center">
        <WrenchScrewdriverIcon class="w-5 h-5 mr-2" />
        Admin Settings
      </router-link>
    </div>

    <!-- Statistics Section (Center-Aligned) -->
    <div class="flex flex-wrap justify-start gap-6">
      <!-- Classes Card -->
      <div class="card bg-primary text-primary-content shadow-md hover:shadow-lg transition-shadow w-52 text-center">
        <div class="card-body flex flex-col items-center">
          <Square3Stack3DIcon class="w-12 h-12" />
          <h2 class="card-title text-lg">Classes</h2>
          <p class="text-2xl font-bold">{{ stats.classes }}</p>
          <router-link to="/admin/classes" class="btn btn-secondary btn-sm mt-4">
            Manage
          </router-link>
        </div>
      </div>

      <!-- Students Card -->
      <div class="card bg-secondary text-secondary-content shadow-md hover:shadow-lg transition-shadow w-52 text-center">
        <div class="card-body flex flex-col items-center">
          <UsersIcon class="w-12 h-12" />
          <h2 class="card-title text-lg">Students</h2>
          <p class="text-2xl font-bold">{{ stats.students }}</p>
          <router-link to="/admin/students" class="btn btn-primary btn-sm mt-4">
            Manage
          </router-link>
        </div>
      </div>

      <!-- Teachers Card -->
      <div class="card bg-accent text-accent-content shadow-md hover:shadow-lg transition-shadow w-52 text-center">
        <div class="card-body flex flex-col items-center">
          <UserGroupIcon class="w-12 h-12" />
          <h2 class="card-title text-lg">Teachers</h2>
          <p class="text-2xl font-bold">{{ stats.teachers }}</p>
          <router-link to="/admin/teachers" class="btn btn-primary btn-sm mt-4">
            Manage
          </router-link>
        </div>
      </div>

      <!-- Words Card -->
      <div class="card bg-neutral text-neutral-content shadow-md hover:shadow-lg transition-shadow w-52 text-center">
        <div class="card-body flex flex-col items-center">
          <PresentationChartBarIcon class="w-12 h-12" />
          <h2 class="card-title text-lg">Words</h2>
          <p class="text-2xl font-bold">{{ stats.words }}</p>
          <router-link to="/admin/words" class="btn btn-secondary btn-sm mt-4">
            Manage
          </router-link>
        </div>
      </div>
    </div>

    <!-- Quick Links Section -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title text-2xl mb-4">Quick Links</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <router-link to="/admin/classes" class="btn btn-outline w-full">
            Manage Classes
          </router-link>
          <router-link to="/admin/students" class="btn btn-outline w-full">
            Manage Students
          </router-link>
          <router-link to="/admin/teachers" class="btn btn-outline w-full">
            Manage Teachers
          </router-link>
          <router-link to="/admin/words" class="btn btn-outline w-full">
            Manage Words
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
