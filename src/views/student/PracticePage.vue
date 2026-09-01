<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeftIcon, ArrowRightIcon, PlayIcon, TrophyIcon } from '@heroicons/vue/24/outline'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { usePracticeStore } from '@/stores/practiceStore'
import { useStudentProgressStore } from '@/stores/studentProgressStore'

const router = useRouter()
const toast = useToast()
const vocabularyStore = useVocabularyStore()
const practiceStore = usePracticeStore()
const progressStore = useStudentProgressStore()
const currentQuestionIndex = ref(0)
const selectedAnswer = ref('')
const attemptResult = ref(null)

const currentQuestion = computed(() => practiceStore.questions[currentQuestionIndex.value] || null)
const currentWord = computed(() => {
  const id = currentQuestion.value?.vocabulary_word_id
  return vocabularyStore.selectedLevel?.words?.find((word) => String(word.id) === String(id)) || null
})
const progressPercent = computed(() => {
  const total = practiceStore.questions.length
  return total ? Math.round((currentQuestionIndex.value / total) * 100) : 0
})
const isLastQuestion = computed(() => currentQuestionIndex.value >= practiceStore.questions.length - 1)

const errorMessage = (error, fallback) => {
  const status = error?.response?.status
  if (status === 401) return 'Your session has expired. Please sign in again.'
  if (status === 403) return 'You do not have access to this practice session.'
  if (status === 404) return 'The practice session or vocabulary word was not found.'
  if (status === 409) return error?.response?.data?.message || 'This word was already attempted.'
  if (status === 422) return error?.response?.data?.message || 'This practice answer cannot be submitted.'
  return fallback
}

async function beginPractice() {
  try {
    if (!vocabularyStore.levels.length) await vocabularyStore.fetchLevels()
    const levelId = vocabularyStore.currentLevelId || vocabularyStore.nextPendingLevel?.id
    if (!levelId) return toast.error('No vocabulary levels are available for practice.')

    vocabularyStore.setLevel(levelId)
    await Promise.all([
      vocabularyStore.fetchLevel(levelId),
      practiceStore.startSession(levelId)
    ])
    currentQuestionIndex.value = 0
    selectedAnswer.value = ''
    attemptResult.value = null
    toast.success(`Starting practice session: ${practiceStore.level?.title || 'Vocabulary'}`)
  } catch (error) {
    toast.error(errorMessage(error, practiceStore.error || 'Unable to start practice.'))
  }
}

async function submitAnswer() {
  if (!currentQuestion.value || !selectedAnswer.value || practiceStore.submitting || attemptResult.value) return
  try {
    const result = await practiceStore.submitAttempt({
      vocabulary_word_id: currentQuestion.value.vocabulary_word_id,
      submitted_answer: selectedAnswer.value,
    })
    if (!result) return
    attemptResult.value = result
    result.is_correct ? toast.success('Correct answer!') : toast.error('Incorrect answer.')
    await progressStore.fetchProgress().catch(() => {})
  } catch (error) {
    toast.error(errorMessage(error, practiceStore.error || 'Unable to submit this answer.'))
  }
}

async function nextQuestion() {
  if (!attemptResult.value) return
  if (isLastQuestion.value) return finishSession()
  currentQuestionIndex.value++
  selectedAnswer.value = ''
  attemptResult.value = null
}

async function finishSession() {
  try {
    await practiceStore.completeSession()
    await Promise.allSettled([practiceStore.fetchHistory(), progressStore.fetchProgress()])
    toast.success('Practice session completed.')
  } catch (error) {
    toast.error(errorMessage(error, practiceStore.error || 'Unable to complete practice.'))
  }
}

function playAudio() {
  const audioUrl = currentWord.value?.audio_url
  if (!audioUrl) return toast.info('Audio not available')
  new Audio(audioUrl).play().catch(() => toast.error('Unable to play audio'))
}

async function restartSession() {
  practiceStore.resetCurrentSession()
  await beginPractice()
}

onMounted(async () => {
  practiceStore.resetCurrentSession()
  await Promise.allSettled([practiceStore.fetchHistory(), beginPractice()])
})
</script>

<template>
  <div class="min-w-0 space-y-4 p-0 sm:space-y-6 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-primary sm:text-3xl">Practice Activities</h1>
        <p class="text-base-content/70">{{ practiceStore.level?.title || 'Vocabulary Practice' }}</p>
      </div>
      <button @click="router.push('/student')" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" /> Back to Dashboard
      </button>
    </div>

    <div v-if="practiceStore.loading" class="text-center py-12">
      <span class="loading loading-spinner loading-lg"></span>
      <p class="mt-3">Starting practice...</p>
    </div>

    <template v-else-if="practiceStore.currentSession && !practiceStore.currentSession.is_completed">
      <div class="card bg-base-100 shadow-md"><div class="card-body">
        <div class="flex justify-between">
          <h2 class="card-title">Session Progress</h2>
          <span>Question {{ currentQuestionIndex + 1 }} of {{ practiceStore.questions.length }}</span>
        </div>
        <progress class="progress progress-primary w-full" :value="progressPercent" max="100"></progress>
      </div></div>

      <div v-if="currentQuestion" class="card bg-base-100 shadow-lg"><div class="card-body space-y-5">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="card-title text-primary">{{ currentQuestion.question }}</h2>
            <p v-if="currentQuestion.example" class="text-sm italic text-base-content/70 mt-2">{{ currentQuestion.example }}</p>
          </div>
          <button class="btn btn-outline btn-sm gap-2" :disabled="!currentWord?.audio_url" @click="playAudio">
            <PlayIcon class="w-4 h-4" /> Listen
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label v-for="option in currentQuestion.options" :key="option" class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer"
            :class="{
              'border-primary': selectedAnswer === option && !attemptResult,
              'border-success bg-success/10': attemptResult?.is_correct && selectedAnswer === option,
              'border-error bg-error/10': attemptResult && !attemptResult.is_correct && selectedAnswer === option
            }">
            <input v-model="selectedAnswer" type="radio" :value="option" class="radio radio-primary" :disabled="Boolean(attemptResult)" />
            <span>{{ option }}</span>
          </label>
        </div>

        <div v-if="attemptResult" class="alert" :class="attemptResult.is_correct ? 'alert-success' : 'alert-error'">
          <span>{{ attemptResult.is_correct ? 'Correct!' : 'Incorrect.' }}</span>
        </div>

        <div class="card-actions justify-end">
          <button v-if="!attemptResult" class="btn btn-primary" :disabled="!selectedAnswer || practiceStore.submitting" @click="submitAnswer">
            <span v-if="practiceStore.submitting" class="loading loading-spinner loading-sm"></span> Submit Answer
          </button>
          <button v-else class="btn btn-primary gap-2" :disabled="practiceStore.completing" @click="nextQuestion">
            {{ isLastQuestion ? 'Finish Session' : 'Next Question' }} <ArrowRightIcon class="w-4 h-4" />
          </button>
        </div>
      </div></div>

      <div v-else class="card bg-base-100 shadow-md"><div class="card-body text-center">
        <p>This level has no practice questions.</p>
        <button class="btn btn-primary" :disabled="practiceStore.completing" @click="finishSession">Complete Empty Session</button>
      </div></div>
    </template>

    <div v-else-if="practiceStore.currentSession?.is_completed" class="card bg-base-100 shadow-lg"><div class="card-body text-center">
      <TrophyIcon class="w-16 h-16 mx-auto text-warning" />
      <h2 class="card-title justify-center text-2xl">Session Complete!</h2>
      <div class="stats shadow mt-5">
        <div class="stat"><div class="stat-title">Questions</div><div class="stat-value">{{ practiceStore.currentSession.total_questions }}</div></div>
        <div class="stat"><div class="stat-title">Correct</div><div class="stat-value text-success">{{ practiceStore.currentSession.correct_answers }}</div></div>
        <div class="stat"><div class="stat-title">Score</div><div class="stat-value text-primary">{{ practiceStore.currentSession.score_percent }}%</div></div>
      </div>
      <div class="card-actions justify-center mt-5">
        <button class="btn btn-primary" @click="restartSession">Practice Again</button>
        <button class="btn btn-outline" @click="router.push('/student/vocabulary-flow')">Continue Learning</button>
      </div>
    </div></div>

    <div v-if="practiceStore.history.length" class="card bg-base-100 shadow-md"><div class="card-body">
      <h2 class="card-title">Recent Practice</h2>
      <div class="space-y-2">
        <div v-for="session in practiceStore.history.slice(0, 5)" :key="session.id" class="flex justify-between p-3 bg-base-200 rounded-lg">
          <span>{{ session.level?.title || 'Vocabulary level' }}</span>
          <span>{{ session.completed_at ? `${session.score_percent}%` : 'In progress' }}</span>
        </div>
      </div>
    </div></div>
  </div>
</template>
