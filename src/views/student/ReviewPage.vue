<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const router = useRouter()

const wordsToReview = ref([
  { id: 1, word: 'Gato', translation: 'Cat', level: 80 },
  { id: 2, word: 'Perro', translation: 'Dog', level: 60 },
])

function reviewWord(word) {
  alert(`Reviewing: ${word.word}`)
}

function goToDashboard() {
  router.push('/student')
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold text-primary">Review Your Vocabulary</h1>
      <button @click="goToDashboard" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Dashboard
      </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="word in wordsToReview"
        :key="word.id"
        class="card bg-base-100 shadow-md hover:shadow-lg"
      >
        <div class="card-body text-center">
          <h2 class="card-title">{{ word.word }}</h2>
          <p>{{ word.translation }}</p>
          <progress class="progress progress-secondary" :value="word.level" max="100"></progress>
          <p>{{ word.level }}% Mastery</p>
          <button class="btn btn-primary btn-sm mt-4" @click="reviewWord(word)">
            Review
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
