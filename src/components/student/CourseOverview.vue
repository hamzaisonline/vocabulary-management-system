<script setup>
import ProgressBar from "@/components/student/ProgressBar.vue";

defineProps(["course"]);
</script>

<template>
  <div class="card bg-base-100 shadow-md hover:shadow-lg">
    <div class="card-body">
      <h2 class="card-title">{{ course.name }}</h2>
      <p>{{ course.description }}</p>
      <progress-bar :value="course.progress" />
      <router-link
        :to="'/student/vocabulary-flow'"
        class="btn btn-primary btn-sm mt-4"
      >
        Continue Learning
      </router-link>
    </div>
  </div>
</template>
