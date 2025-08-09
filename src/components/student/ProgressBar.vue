<script setup>
defineProps({
  value: {
    type: Number,
    required: true,
  },
  max: {
    type: Number,
    default: 100,
  },
});
</script>

<template>
  <div class="w-full">
    <progress
      class="progress progress-primary"
      :value="value"
      :max="max"
    ></progress>
    <p class="text-sm mt-2">{{ value }}% Completed</p>
  </div>
</template>
