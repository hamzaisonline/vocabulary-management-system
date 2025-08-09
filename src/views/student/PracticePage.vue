<script setup>
import { ref } from 'vue';

const questions = ref([
  { id: 1, question: 'Translate "Gato"', options: ['Cat', 'Dog', 'Fish'], answer: 'Cat' },
  { id: 2, question: 'Translate "Perro"', options: ['Dog', 'Rabbit', 'Snake'], answer: 'Dog' },
]);

const currentQuestionIndex = ref(0);
const selectedAnswer = ref(null);
const isCorrect = ref(null);

function checkAnswer() {
  isCorrect.value = selectedAnswer.value === questions.value[currentQuestionIndex.value].answer;
}

function nextQuestion() {
  currentQuestionIndex.value = (currentQuestionIndex.value + 1) % questions.value.length;
  selectedAnswer.value = null;
  isCorrect.value = null;
}
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-3xl font-bold">Practice Your Vocabulary</h1>
    <div class="card bg-base-100 shadow-md p-6">
      <h2 class="text-xl font-bold mb-4">{{ questions[currentQuestionIndex].question }}</h2>
      <div class="flex flex-col space-y-2">
        <label
          v-for="option in questions[currentQuestionIndex].options"
          :key="option"
          class="flex items-center space-x-2 cursor-pointer"
        >
          <input type="radio" :value="option" v-model="selectedAnswer" class="radio radio-secondary" />
          <span>{{ option }}</span>
        </label>
      </div>
      <button class="btn btn-primary mt-4" @click="checkAnswer">Check Answer</button>

      <div v-if="isCorrect !== null" class="mt-4">
        <p v-if="isCorrect" class="text-green-500 font-bold">Correct!</p>
        <p v-else class="text-red-500 font-bold">Incorrect. Try Again!</p>
        <button class="btn btn-secondary mt-4" @click="nextQuestion">Next Question</button>
      </div>
    </div>
  </div>
</template>
