<script setup>
import { ref } from 'vue';

const words = ref([
  { id: 1, word: 'Gato', translation: 'Cat', audio: '/audio/gato.mp3', mastery: 0 },
  { id: 2, word: 'Perro', translation: 'Dog', audio: '/audio/perro.mp3', mastery: 0 },
]);

const currentIndex = ref(0);

function playAudio(audio) {
  new Audio(audio).play();
}

function nextWord() {
  currentIndex.value = (currentIndex.value + 1) % words.value.length;
}
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-3xl font-bold">Learn New Vocabulary</h1>
    <div class="card bg-base-100 shadow-md p-6 text-center">
      <h2 class="text-2xl font-bold">{{ words[currentIndex].word }}</h2>
      <p class="text-lg text-gray-500">{{ words[currentIndex].translation }}</p>
      <button class="btn btn-secondary mt-4" @click="playAudio(words[currentIndex].audio)">
        Play Audio
      </button>
      <button class="btn btn-primary mt-4" @click="nextWord">Next Word</button>
    </div>
  </div>
</template>