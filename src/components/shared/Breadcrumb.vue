<script setup>
defineProps({
  classId: String,
  className: String,
  currentLevel: String,
});
</script>

<template>
  <div class="text-sm breadcrumbs">
    <ul>
      <li><router-link to="/">Home</router-link></li>
      <li>
        <router-link :to="`/student/classes/${classId}`">{{
          className
        }}</router-link>
      </li>
      <li>{{ currentLevel }}</li>
    </ul>
  </div>
</template>


