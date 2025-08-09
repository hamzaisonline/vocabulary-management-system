<script setup>
defineProps(["classData"]);
</script>

<template>
  <div class="card bg-base-100 shadow-md">
    <div class="card-body">
      <h2 class="card-title">{{ classData.name }}</h2>
      <p>Language: {{ classData.language }}</p>
      <p>Students: {{ classData.students }}</p>
      <div class="card-actions justify-end">
        <button class="btn btn-secondary">Manage</button>
      </div>
    </div>
  </div>
</template>
