<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeftIcon, PlayIcon } from '@heroicons/vue/24/outline'
import vocabularyService from '@/service/vocabularyService'
import { useReviewStore } from '@/stores/reviewStore'
import { useStudentProgressStore } from '@/stores/studentProgressStore'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const reviewStore = useReviewStore()
const progressStore = useStudentProgressStore()
const selectedWordId = ref(null)
const answerRevealed = ref(false)
const audioByWordId = ref({})

const levelId = computed(() => {
  const value = Number(route.query.levelId)
  return Number.isInteger(value) && value > 0 ? value : null
})
const reviewWords = computed(() => levelId.value ? reviewStore.selectedLevelReview : reviewStore.queue)
const selectedWord = computed(() =>
  reviewWords.value.find((item) => String(item.vocabulary_word_id) === String(selectedWordId.value)) || null
)

const messageFor = (error, fallback) => {
  const status = error?.response?.status
  if (status === 401) return 'Your session has expired. Please sign in again.'
  if (status === 403) return 'You do not have access to this vocabulary review.'
  if (status === 404) return 'The vocabulary word or level was not found.'
  if (status === 422) return error?.response?.data?.message || 'This word is no longer reviewable.'
  return fallback
}

async function loadAudio(words) {
  const ids = [...new Set(words.map((item) => item.vocabulary_level?.id || levelId.value).filter(Boolean))]
  const results = await Promise.allSettled(ids.map((id) => vocabularyService.getLevel(id)))
  const audio = {}
  results.forEach((result) => {
    if (result.status !== 'fulfilled') return
    ;(result.value?.words || []).forEach((word) => {
      audio[word.id] = word.audio_url || null
    })
  })
  audioByWordId.value = audio
}

async function loadReview() {
  selectedWordId.value = null
  answerRevealed.value = false
  try {
    const words = levelId.value
      ? await reviewStore.fetchLevelReview(levelId.value)
      : await reviewStore.fetchQueue()
    await loadAudio(words)
  } catch (error) {
    toast.error(messageFor(error, reviewStore.error || 'Unable to load vocabulary review.'))
  }
}

function startReview(word) {
  selectedWordId.value = word.vocabulary_word_id
  answerRevealed.value = false
}

async function submitReview(correct) {
  if (!selectedWord.value || reviewStore.submittingWordId !== null) return
  try {
    const result = await reviewStore.submitReview(selectedWord.value.vocabulary_word_id, correct)
    if (!result) return
    toast.success(result.completed ? 'Word mastered!' : 'Review saved.')
    await Promise.allSettled([
      levelId.value ? reviewStore.fetchLevelReview(levelId.value) : reviewStore.fetchQueue(),
      progressStore.fetchProgress()
    ])
    selectedWordId.value = null
    answerRevealed.value = false
  } catch (error) {
    toast.error(messageFor(error, reviewStore.error || 'Unable to save this review.'))
  }
}

function playAudio(word) {
  const audioUrl = audioByWordId.value[word.vocabulary_word_id]
  if (!audioUrl) return toast.info('Audio not available')
  new Audio(audioUrl).play().catch(() => toast.error('Unable to play audio'))
}

onMounted(loadReview)
watch(levelId, loadReview)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Review Your Vocabulary</h1>
        <p class="text-base-content/70">{{ levelId ? 'Level review' : 'Words that currently need review' }}</p>
      </div>
      <button @click="router.push('/student')" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" /> Back to Dashboard
      </button>
    </div>

    <div v-if="reviewStore.loading" class="text-center py-12">
      <span class="loading loading-spinner loading-lg"></span>
      <p class="mt-3">Loading review queue...</p>
    </div>

    <div v-else-if="selectedWord" class="card bg-base-100 shadow-lg max-w-2xl mx-auto">
      <div class="card-body text-center space-y-4">
        <div class="badge badge-outline mx-auto">{{ selectedWord.vocabulary_level?.title || 'Vocabulary review' }}</div>
        <h2 class="text-4xl font-bold text-primary">{{ selectedWord.word }}</h2>
        <p v-if="selectedWord.example" class="italic text-base-content/70">{{ selectedWord.example }}</p>
        <button class="btn btn-outline btn-sm gap-2 mx-auto" :disabled="!audioByWordId[selectedWord.vocabulary_word_id]" @click="playAudio(selectedWord)">
          <PlayIcon class="w-4 h-4" /> Listen
        </button>

        <div class="p-4 bg-base-200 rounded-lg">
          <p v-if="!answerRevealed" class="text-base-content/60">Think of the translation, then reveal the answer.</p>
          <p v-else class="text-2xl font-semibold">{{ selectedWord.translation }}</p>
        </div>

        <button v-if="!answerRevealed" class="btn btn-primary" @click="answerRevealed = true">Reveal Answer</button>
        <div v-else class="flex justify-center gap-3">
          <button class="btn btn-error" :disabled="reviewStore.submittingWordId !== null" @click="submitReview(false)">Need More Practice</button>
          <button class="btn btn-success" :disabled="reviewStore.submittingWordId !== null" @click="submitReview(true)">I Knew It</button>
        </div>
        <span v-if="reviewStore.submittingWordId" class="loading loading-spinner mx-auto"></span>
        <button class="btn btn-ghost btn-sm" :disabled="reviewStore.submittingWordId !== null" @click="selectedWordId = null">Back to Queue</button>
      </div>
    </div>

    <div v-else-if="!reviewWords.length" class="card bg-base-100 shadow-md">
      <div class="card-body text-center py-12">
        <h2 class="text-2xl font-semibold">Nothing to review</h2>
        <p class="text-base-content/70">No eligible vocabulary words currently require review.</p>
        <button class="btn btn-primary mx-auto" @click="router.push('/student/vocabulary-flow')">Continue Learning</button>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="word in reviewWords" :key="word.id" class="card bg-base-100 shadow-md hover:shadow-lg">
        <div class="card-body">
          <div class="flex justify-between gap-2">
            <h2 class="card-title">{{ word.word }}</h2>
            <span v-if="word.vocabulary_level" class="badge badge-outline">{{ word.vocabulary_level.title }}</span>
          </div>
          <p>{{ word.translation }}</p>
          <progress class="progress progress-secondary" :value="word.mastery_percent" max="100"></progress>
          <p>{{ word.mastery_percent }}% Mastery</p>
          <div class="text-sm text-base-content/70">
            <p>Attempts: {{ word.attempts }} · Correct: {{ word.correct_attempts }}</p>
            <p v-if="word.last_practiced_at">Last practiced: {{ new Date(word.last_practiced_at).toLocaleString() }}</p>
          </div>
          <div class="card-actions justify-end">
            <button class="btn btn-primary btn-sm" @click="startReview(word)">Review</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
