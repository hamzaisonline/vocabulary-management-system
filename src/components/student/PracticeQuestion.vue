<script setup>
import { ref } from 'vue';

defineProps({
  question: String,
  options: Array,
  answer: String,
  onAnswer: Function, // Ensure onAnswer is a function passed from the parent
});

// Track selected answer
const selectedAnswer = ref(null);

function checkAnswer(correctAnswer, onAnswerCallback) {
  const isCorrect = selectedAnswer.value === correctAnswer;
  onAnswerCallback(isCorrect); // Call the parent callback
}
</script>

<template>
  <div class="card bg-base-100 shadow-md p-6">
    <h2 class="text-xl font-bold mb-4">{{ question }}</h2>
    <div class="flex flex-col space-y-2">
      <label
        v-for="option in options"
        :key="option"
        class="flex items-center space-x-2 cursor-pointer"
      >
        <input type="radio" :value="option" v-model="selectedAnswer" class="radio radio-secondary" />
        <span>{{ option }}</span>
      </label>
    </div>
    <button class="btn btn-primary mt-4" @click="checkAnswer(answer, onAnswer)">
      Check Answer
    </button>
  </div>
</template>
