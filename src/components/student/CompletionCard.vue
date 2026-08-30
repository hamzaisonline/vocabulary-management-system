<script setup>
import { useRouter } from 'vue-router'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { useStudentProgressStore } from '@/stores/studentProgressStore'

const router = useRouter()
const vocabularyStore = useVocabularyStore()
const progressStore = useStudentProgressStore()

function proceedToNextStep() {
  const currentLevel = vocabularyStore.currentLevel
  const completed = Boolean(progressStore.selectedLevelProgress?.summary?.completed)
  const nextLevel = progressStore.progressSummary.find((level) => !level.completed)

  vocabularyStore.setPhase('learn')

  if (currentLevel && !completed) {    
    router.push(`/student/flow/${currentLevel.id}`)
  } else if (nextLevel) {
    router.push(`/student/flow/${nextLevel.id}`)
  } else {
    router.push('/student/completed')
  }
}
</script>

<template>
  <div class="alert alert-success shadow-lg text-center p-6">
    <div class="flex flex-col items-center space-y-4">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="stroke-current h-12 w-12 text-green-600"
        fill="none"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>

      <h2 class="text-xl font-semibold">
        🎉 Congratulations! You've completed this part!
      </h2>

      <button @click="proceedToNextStep" class="btn btn-primary mt-2">
        Continue →
      </button>
    </div>
  </div>
</template>
