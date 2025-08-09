<script setup>
defineProps(["word"]);
</script>

<template>
  <div class="card bg-base-100 shadow-md hover:shadow-lg">
    <div class="card-body text-center">
      <h2 class="card-title">{{ word.word }}</h2>
      <progress
        class="progress progress-secondary"
        :value="word.mastery"
        max="100"
      ></progress>
      <p>{{ word.mastery }}% Mastery</p>
    </div>
  </div>
</template>
