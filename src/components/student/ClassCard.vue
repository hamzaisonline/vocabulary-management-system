<script setup>
import { useClassStore } from '@/stores/classStore';

const classStore = useClassStore();

defineProps(['classData']);

function selectClass(classId) {
  classStore.selectClass(classId);
}
</script>

<template>
  <div class="card bg-base-100 shadow-md hover:shadow-lg">
    <div class="card-body">
      <h2 class="card-title">{{ classData.name }}</h2>
      <p>Course: {{ classData.course }}</p>
      <progress class="progress progress-secondary" :value="classData.progress" max="100"></progress>
      <p>{{ classData.progress }}% Completed</p>
      <button
        class="btn btn-primary btn-sm mt-4"
        @click="selectClass(classData.id)"
      >
        Start Learning
      </button>
    </div>
  </div>
</template>