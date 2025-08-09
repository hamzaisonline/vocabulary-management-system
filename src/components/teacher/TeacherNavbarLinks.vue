<script setup>
import { PresentationChartBarIcon, Square3Stack3DIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
</script>

<template>
  <nav class="flex-1 p-4 space-y-4">
    <router-link
      to="/teacher"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <Squares2X2Icon class="w-5" />
      <span>Dashboard</span>
    </router-link>
    <router-link
      to="/teacher/classes"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <PresentationChartBarIcon class="w-5" />
      <span>Classes</span>
    </router-link>
    <router-link
      to="/teacher/vocabulary"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <Square3Stack3DIcon class="w-5" />
      <span>Vocabulary</span>
    </router-link>
  </nav>
</template>
