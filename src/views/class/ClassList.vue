<script setup>
import ClassCard from '../../components/ClassCard.vue';


const classes = [
  { id: 1, name: 'Spanish 101', language: 'Spanish', students: 25 },
  { id: 2, name: 'French Basics', language: 'French', students: 18 },
];
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">My Classes</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <ClassCard v-for="cls in classes" :key="cls.id" :classData="cls" />
    </div>
    <button class="btn btn-primary mt-6">Create New Class</button>
  </div>
</template>
