<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { useToast } from 'vue-toastification'
import VocabularyActivities from '@/components/student/VocabularyActivities.vue'
import { ArrowLeftIcon, ArrowRightIcon, TrophyIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const vocabularyStore = useVocabularyStore()
const toast = useToast()

// Activity types and current state
const activityTypes = [
  { id: 'multiple-choice', name: 'Multiple Choice', icon: '✓', description: 'Select the correct translation' },
  { id: 'audio-recognition', name: 'Audio Recognition', icon: '🔊', description: 'Listen and identify the word' },
  { id: 'sentence-reconstruction', name: 'Sentence Builder', icon: '🔧', description: 'Reconstruct the sentence' },
  { id: 'word-match', name: 'Word Matching', icon: '🎯', description: 'Match words with translations' }
]

const currentActivityIndex = ref(0)
const completedActivities = ref(new Set())
const sessionScore = ref(0)
const totalActivities = ref(0)
const showResults = ref(false)

const currentActivityType = computed(() => activityTypes[currentActivityIndex.value])
const currentLevel = computed(() => vocabularyStore.currentLevel)
const currentWord = computed(() => vocabularyStore.currentWord)

const progressPercentage = computed(() => {
  return totalActivities.value > 0 ? Math.round((completedActivities.value.size / totalActivities.value) * 100) : 0
})

const canProceed = computed(() => {
  return completedActivities.value.has(currentActivityType.value.id)
})

function startPracticeSession() {
  // Reset session state
  completedActivities.value.clear()
  sessionScore.value = 0
  totalActivities.value = activityTypes.length
  currentActivityIndex.value = 0
  showResults.value = false

  // Ensure we have a level and word selected
  if (!vocabularyStore.currentLevelId) {
    const nextLevel = vocabularyStore.nextPendingLevel
    if (nextLevel) {
      vocabularyStore.setLevel(nextLevel.id)
    } else {
      toast.error("No levels available for practice")
      router.push('/student')
      return
    }
  }

  toast.success(`Starting practice session: ${currentLevel.value?.title}`)
}

function onActivityComplete(success) {
  const activityId = currentActivityType.value.id

  if (success) {
    completedActivities.value.add(activityId)
    sessionScore.value++
    toast.success(`✅ Activity completed: ${currentActivityType.value.name}`)
  } else {
    toast.info(`⏭️ Moving to next activity`)
  }

  // Auto-advance after a delay
  setTimeout(() => {
    nextActivity()
  }, 2000)
}

function nextActivity() {
  if (currentActivityIndex.value < activityTypes.length - 1) {
    currentActivityIndex.value++
  } else {
    finishSession()
  }
}

function prevActivity() {
  if (currentActivityIndex.value > 0) {
    currentActivityIndex.value--
  }
}

function selectActivity(index) {
  currentActivityIndex.value = index
}

function finishSession() {
  showResults.value = true

  const finalScore = Math.round((sessionScore.value / totalActivities.value) * 100)

  if (finalScore >= 80) {
    toast.success(`🏆 Excellent session! Score: ${finalScore}%`)
  } else if (finalScore >= 60) {
    toast.success(`👍 Good job! Score: ${finalScore}%`)
  } else {
    toast.info(`📚 Keep practicing! Score: ${finalScore}%`)
  }
}

function restartSession() {
  startPracticeSession()
}

function goToDashboard() {
  router.push('/student')
}

function goToVocabularyFlow() {
  if (vocabularyStore.currentLevelId) {
    router.push(`/student/flow/${vocabularyStore.currentLevelId}`)
  } else {
    router.push('/student/vocabulary-flow')
  }
}

onMounted(() => {
  startPracticeSession()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Practice Activities</h1>
        <p class="text-base-content/70">{{ currentLevel?.title || 'Vocabulary Practice' }}</p>
      </div>
      <button @click="goToDashboard" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Dashboard
      </button>
    </div>

    <!-- Progress Overview -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="flex items-center justify-between mb-4">
          <h2 class="card-title">Session Progress</h2>
          <span class="text-lg font-bold">{{ completedActivities.size }} / {{ totalActivities }}</span>
        </div>

        <progress
          class="progress progress-primary w-full mb-4"
          :value="progressPercentage"
          max="100"
        ></progress>

        <!-- Activity Type Selector -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <button
            v-for="(activity, index) in activityTypes"
            :key="activity.id"
            @click="selectActivity(index)"
            class="btn btn-sm"
            :class="{
              'btn-primary': currentActivityIndex === index,
              'btn-success': completedActivities.has(activity.id),
              'btn-outline': currentActivityIndex !== index && !completedActivities.has(activity.id)
            }"
          >
            <span class="text-lg">{{ activity.icon }}</span>
            <span class="hidden md:inline ml-1">{{ activity.name }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Current Activity -->
    <div v-if="!showResults && currentWord.word" class="space-y-4">
      <div class="card bg-primary text-primary-content">
        <div class="card-body">
          <h3 class="card-title">{{ currentActivityType.name }}</h3>
          <p>{{ currentActivityType.description }}</p>
          <div class="badge badge-secondary">
            Current word: {{ currentWord.word }}
          </div>
        </div>
      </div>

      <VocabularyActivities
        :activity-type="currentActivityType.id"
        :on-complete="onActivityComplete"
        :key="`${currentActivityType.id}-${currentWord.id}`"
      />

      <!-- Navigation -->
      <div class="flex justify-between">
        <button
          @click="prevActivity"
          :disabled="currentActivityIndex === 0"
          class="btn btn-outline gap-2"
        >
          <ArrowLeftIcon class="w-4 h-4" />
          Previous
        </button>

        <button
          @click="nextActivity"
          :disabled="currentActivityIndex === activityTypes.length - 1"
          class="btn btn-primary gap-2"
        >
          Next
          <ArrowRightIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Session Results -->
    <div v-if="showResults" class="space-y-6">
      <div class="card bg-base-100 shadow-lg">
        <div class="card-body text-center">
          <TrophyIcon class="w-16 h-16 mx-auto text-warning mb-4" />
          <h2 class="card-title justify-center text-2xl">Session Complete!</h2>

          <div class="stats shadow mt-6">
            <div class="stat">
              <div class="stat-title">Activities Completed</div>
              <div class="stat-value text-primary">{{ completedActivities.size }}</div>
              <div class="stat-desc">out of {{ totalActivities }}</div>
            </div>

            <div class="stat">
              <div class="stat-title">Success Rate</div>
              <div class="stat-value text-secondary">{{ Math.round((sessionScore / totalActivities) * 100) }}%</div>
              <div class="stat-desc">{{ sessionScore }} successful</div>
            </div>

            <div class="stat">
              <div class="stat-title">XP Earned</div>
              <div class="stat-value text-accent">{{ sessionScore * 10 }}</div>
              <div class="stat-desc">Experience points</div>
            </div>
          </div>

          <div class="card-actions justify-center mt-6 gap-3">
            <button @click="restartSession" class="btn btn-primary gap-2">
              <ArrowRightIcon class="w-4 h-4" />
              Practice Again
            </button>
            <button @click="goToVocabularyFlow" class="btn btn-outline">
              Continue Learning
            </button>
            <button @click="goToDashboard" class="btn btn-ghost">
              Back to Dashboard
            </button>
          </div>
        </div>
      </div>

      <!-- Activity Breakdown -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h3 class="card-title">Activity Breakdown</h3>
          <div class="space-y-3">
            <div
              v-for="activity in activityTypes"
              :key="activity.id"
              class="flex items-center justify-between p-3 bg-base-200 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <span class="text-2xl">{{ activity.icon }}</span>
                <div>
                  <p class="font-semibold">{{ activity.name }}</p>
                  <p class="text-sm text-base-content/70">{{ activity.description }}</p>
                </div>
              </div>
              <div class="badge" :class="completedActivities.has(activity.id) ? 'badge-success' : 'badge-warning'">
                {{ completedActivities.has(activity.id) ? 'Completed' : 'Attempted' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!currentWord.word && !showResults" class="card bg-base-100 shadow-md">
      <div class="card-body text-center">
        <h3 class="text-xl font-semibold mb-4">No vocabulary available</h3>
        <p class="text-base-content/70 mb-6">Start learning vocabulary to unlock practice activities.</p>
        <button @click="goToDashboard" class="btn btn-primary">
          Go to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>
