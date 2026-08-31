<script setup>
defineProps(["word"]);
</script>

<template>
  <div class="card bg-base-100 shadow-md hover:shadow-lg">
    <div class="card-body text-center">
      <h2 class="card-title">{{ word.word }}</h2>
      <progress
        class="progress progress-secondary"
        :value="word.effective_mastery_percent ?? word.mastery_percent ?? 0"
        max="100"
      ></progress>
      <p>{{ word.effective_mastery_percent ?? word.mastery_percent ?? 0 }}% Mastery</p>
    </div>
  </div>
</template>
