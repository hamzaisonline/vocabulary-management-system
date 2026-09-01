<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { useStudentProgressStore } from '@/stores/studentProgressStore'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const vocabularyStore = useVocabularyStore()
const progressStore = useStudentProgressStore()
const currentIndex = ref(0)

const level = computed(() => vocabularyStore.selectedLevel)
const words = computed(() => level.value?.words || [])
const currentWord = computed(() => {
  const word = words.value[currentIndex.value]
  if (!word) return null
  return {
    ...word,
    ...(progressStore.wordById(word.id) || {}),
    audio_path: word.audio_path,
    audio_url: word.audio_url,
    audio: word.audio_url || ''
  }
})

const loadLevel = async () => {
  const levelId = Number(route.params.id)
  if (!Number.isInteger(levelId) || levelId <= 0) {
    toast.error('Invalid vocabulary level')
    return router.replace('/student/vocabulary-flow')
  }
  try {
    await Promise.all([
      vocabularyStore.fetchLevel(levelId),
      progressStore.fetchLevelProgress(levelId)
    ])
  } catch (error) {
    if (error?.response?.status === 404) toast.error('Vocabulary level not found')
    else if (error?.response?.status === 403) toast.error('You do not have access to this vocabulary level')
    else toast.error(vocabularyStore.error || 'Failed to load vocabulary level')
    router.replace('/student/vocabulary-flow')
  }
}

const playAudio = () => {
  if (!currentWord.value?.audio_url) return toast.info('No audio is available for this word.')
  new Audio(currentWord.value.audio_url).play().catch(() => toast.error('Audio could not be played.'))
}

const nextWord = () => {
  if (words.value.length) currentIndex.value = (currentIndex.value + 1) % words.value.length
}

onMounted(loadLevel)
</script>

<template>
  <div class="min-w-0 space-y-4 p-0 sm:space-y-6 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0"><h1 class="break-words text-2xl font-bold sm:text-3xl">{{ level?.title || 'Vocabulary Level' }}</h1>
        <p class="text-base-content/70">{{ level?.description }}</p></div>
      <button @click="router.push('/student/vocabulary-flow')" class="btn btn-ghost">Back to Levels</button>
    </div>

    <div v-if="vocabularyStore.loading" class="text-center py-12"><span class="loading loading-spinner loading-lg"></span></div>
    <div v-else-if="!words.length" class="card bg-base-100 shadow-md"><div class="card-body text-center">No words are available in this level.</div></div>
    <div v-else-if="currentWord" class="card bg-base-100 shadow-md p-6 text-center">
      <p class="text-sm text-base-content/60">Word {{ currentIndex + 1 }} of {{ words.length }}</p>
      <h2 class="text-2xl font-bold mt-2">{{ currentWord.word }}</h2>
      <p class="text-lg text-gray-500">{{ currentWord.translation }}</p>
      <p v-if="currentWord.example" class="mt-4 italic">{{ currentWord.example }}</p>
      <p v-if="currentWord.notes" class="mt-2 text-sm text-base-content/70">{{ currentWord.notes }}</p>
      <div class="flex flex-wrap justify-center gap-2 mt-4">
        <span class="badge badge-primary">Mastery: {{ currentWord.effective_mastery_percent ?? currentWord.mastery_percent ?? 0 }}%</span>
        <span class="badge badge-outline">Attempts: {{ currentWord.attempts || 0 }}</span>
        <span class="badge badge-outline">Correct: {{ currentWord.correct_attempts || 0 }}</span>
      </div>
      <div class="flex justify-center gap-2 mt-5">
        <button class="btn btn-secondary" :disabled="!currentWord.audio_url" @click="playAudio">Play Audio</button>
        <button class="btn btn-primary" @click="nextWord">Next Word</button>
      </div>
    </div>
  </div>
</template>
