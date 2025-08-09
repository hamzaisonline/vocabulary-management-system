<script setup lang="ts">
import { PresentationChartBarIcon, Square3Stack3DIcon, Squares2X2Icon, UserGroupIcon, UsersIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline';
</script>

<template>
  <nav class="flex-1 p-4 space-y-4">
    <router-link
      to="/admin"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <Squares2X2Icon class="w-5" />
      <span>Dashboard</span>
    </router-link>
    <router-link
      to="/admin/classes"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <PresentationChartBarIcon class="w-5" />
      <span>Classes</span>
    </router-link>
    <router-link
      to="/admin/words"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <Square3Stack3DIcon class="w-5" />
      <span>Words</span>
    </router-link>
    <router-link
      to="/admin/students"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <UserGroupIcon class="w-5" />
      <span>Students</span>
    </router-link>
    <router-link
      to="/admin/teachers"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <UsersIcon class="w-5" />
      <span>Teachers</span>
    </router-link>
    <router-link
      to="/admin/settings"
      class="flex items-center px-4 py-2 space-x-2 rounded-lg hover:bg-base-200"
    >
      <WrenchScrewdriverIcon class="w-5" />
      <span>Settings</span>
    </router-link>
  </nav>
</template>
