<script setup>
import { useAuthStore } from '@/stores/authStore';
import AdminNavbarLinks from './admin/AdminNavbarLinks.vue';
import StudentNavbarLinks from './student/StudentNavbarLinks.vue';
import TeacherNavbarLinks from './teacher/TeacherNavbarLinks.vue';

const authStore = useAuthStore()

</script>
<template>
  <AdminNavbarLinks v-if="authStore.role === 'admin'"/>  
  <StudentNavbarLinks v-if="authStore.role === 'student'"/>
  <TeacherNavbarLinks v-if="authStore.role === 'teacher'"/>
</template>

