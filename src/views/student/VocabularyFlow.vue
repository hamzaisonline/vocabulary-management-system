<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { useStudentProgressStore } from '@/stores/studentProgressStore'
import { useToast } from 'vue-toastification'
import VocabularyActivities from '@/components/student/VocabularyActivities.vue'
import { buildGuidedLearningPlan, buildReinforcementPlan } from '@/utils/guidedLearningPlan'
import { buildFocusedLearningPlan, FOCUSED_MODES } from '@/utils/focusedLearningPlan'
import { ArrowLeftIcon, ArrowRightIcon, TrophyIcon, PlayIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const vocabularyStore = useVocabularyStore()
const progressStore = useStudentProgressStore()
const toast = useToast()

// Get level ID from route params (if any)
const levelId = computed(() => route.params.id || null)

// Activity types and current state
const activityTypes = [
  { id: 'multiple-choice', name: 'Choose Answer', icon: '✓', description: 'Pick the right meaning' },
  { id: 'audio-recognition', name: 'Listen & Learn', icon: '🔊', description: 'Hear and understand' },
  { id: 'speech-recognition', name: 'Say It Out Loud', icon: '🎤', description: 'Practice pronunciation' },
  { id: 'sentence-reconstruction', name: 'Build Sentence', icon: '🔧', description: 'Put words in order' },
  { id: 'word-match', name: 'Match Words', icon: '🎯', description: 'Connect words and meanings' }
]
const modeOptions = [
  { id: 'guided', name: 'Guided Learning', icon: '▶', description: 'Structured learning rounds' },
  { ...activityTypes.find((activity) => activity.id === 'audio-recognition'), id: 'listen' },
  { ...activityTypes.find((activity) => activity.id === 'multiple-choice'), id: 'choose' },
  { ...activityTypes.find((activity) => activity.id === 'word-match'), id: 'match' },
  { ...activityTypes.find((activity) => activity.id === 'speech-recognition'), id: 'speak' },
  { ...activityTypes.find((activity) => activity.id === 'sentence-reconstruction'), id: 'sentence' }
]
const requestedMode = computed(() => String(route.query.mode || 'guided'))
const activeMode = computed(() => requestedMode.value === 'guided' || FOCUSED_MODES[requestedMode.value] ? requestedMode.value : 'guided')
const isGuidedMode = computed(() => activeMode.value === 'guided')

const completedActivities = ref(new Set())
const completedStepKeys = ref(new Set())
const submittedStepKeys = ref(new Set())
const incorrectWordIds = ref(new Set())
const sessionScore = ref(0)
const showResults = ref(false)
const introducedWordIds = ref(new Set())
const submittingActivityKey = ref(null)
const learningPlan = ref([])
const currentStepIndex = ref(0)
const reinforcementAdded = ref(false)

const currentLevel = computed(() => vocabularyStore.currentLevel)
const currentStep = computed(() => learningPlan.value[currentStepIndex.value] || null)
const currentActivityIndex = computed(() => activityTypes.findIndex((activity) => activity.id === currentStep.value?.activity))
const currentActivityType = computed(() => activityTypes[currentActivityIndex.value] || activityTypes[0])
const currentWordIndex = computed(() => Math.max(0, (currentLevel.value?.words || []).findIndex((word) => String(word.id) === String(currentStep.value?.wordId))))
const totalActivities = computed(() => learningPlan.value.length)
const currentWord = computed(() => {
  if (!currentLevel.value?.words?.length) return {}
  const word = currentLevel.value.words.find((item) => String(item.id) === String(currentStep.value?.wordId)) || {}
  const progress = progressStore.wordById(word.id) || {}
  return {
    ...word,
    ...progress,
    audio_path: word.audio_path,
    audio_url: word.audio_url,
    audio: word.audio_url || ''
  }
})
const activityCompleteHandler = computed(() => {
  const stepKey = currentStep.value?.key
  return (success) => onActivityComplete(success, stepKey)
})
const activitySkipHandler = computed(() => {
  const stepKey = currentStep.value?.key
  return () => skipUnavailableActivity(stepKey)
})
const wordsPracticed = computed(() => new Set(learningPlan.value.map((step) => String(step.wordId))).size)

const progressPercentage = computed(() => {
  return totalActivities.value > 0 ? Math.round((completedStepKeys.value.size / totalActivities.value) * 100) : 0
})

const levelProgressPercentage = computed(() => {
  return Number(progressStore.selectedLevelProgress?.summary?.progress_percent || 0)
})

const masteryForWord = (wordId) => {
  const progress = progressStore.wordById(wordId)
  return progress?.effective_mastery_percent ?? progress?.mastery_percent ?? 0
}

const availableLevels = computed(() => vocabularyStore.levels || [])

function startVocabularySession(selectedLevelId = null) {
  // Set the level
  const targetLevelId = selectedLevelId || levelId.value || vocabularyStore.nextPendingLevel?.id
  
  if (!targetLevelId) {
    toast.error("No vocabulary levels available")
    router.push('/student')
    return
  }
  
  vocabularyStore.setLevel(targetLevelId)
  
  // Reset session state
  completedActivities.value.clear()
  completedStepKeys.value = new Set()
  submittedStepKeys.value = new Set()
  incorrectWordIds.value = new Set()
  sessionScore.value = 0
  showResults.value = false
  introducedWordIds.value = isGuidedMode.value
    ? new Set()
    : new Set((currentLevel.value?.words || []).map((word) => word.id))
  submittingActivityKey.value = null
  reinforcementAdded.value = false
  learningPlan.value = isGuidedMode.value
    ? buildGuidedLearningPlan(targetLevelId, currentLevel.value?.words || [])
    : buildFocusedLearningPlan(targetLevelId, currentLevel.value?.words || [], activeMode.value)
  currentStepIndex.value = 0
  
  toast.success(`Starting vocabulary session: ${currentLevel.value?.title}`)
}

async function onActivityComplete(success, expectedStepKey) {
  const step = currentStep.value
  if (!step || step.key !== expectedStepKey || completedStepKeys.value.has(step.key)) return

  if (step.exposureOnly) {
    introducedWordIds.value = new Set([...introducedWordIds.value, step.wordId])
  } else if (step.scored && !submittedStepKeys.value.has(step.key)) {
    if (submittingActivityKey.value === step.key) return
    submittingActivityKey.value = step.key
    try {
      const update = await progressStore.submitWordProgress(step.wordId, success)
      if (!update) return
      if (currentStep.value?.key !== expectedStepKey) return
      submittedStepKeys.value = new Set([...submittedStepKeys.value, step.key])
      if (!success) incorrectWordIds.value = new Set([...incorrectWordIds.value, step.wordId])
    } catch (error) {
      const status = error?.response?.status
      if (status === 401) toast.error('Your session has expired. Please sign in again.')
      else if (status === 403) toast.error('You do not have access to update this word.')
      else if (status === 404) toast.error('This vocabulary word is no longer available.')
      else if (status === 422) toast.error(error?.response?.data?.message || 'The progress update was invalid.')
      else toast.error('Progress could not be saved. Check your connection and try again.')
      return
    } finally {
      submittingActivityKey.value = null
    }
  }

  completedStepKeys.value = new Set([...completedStepKeys.value, step.key])
  completedActivities.value = new Set([...completedActivities.value, step.activity])
  if (success) {
    sessionScore.value++
    
    toast.success(`✅ Activity completed: ${currentActivityType.value.name}`)
  } else {
    toast.info(`⏭️ Moving to next activity`)
  }
  
  // Auto-advance after a delay
  setTimeout(() => {
    if (currentStep.value?.key === expectedStepKey) nextActivity()
  }, 2000)
}

function nextActivity() {
  if (currentStepIndex.value < learningPlan.value.length - 1) {
    currentStepIndex.value++
    return
  }

  if (isGuidedMode.value && !reinforcementAdded.value) {
    reinforcementAdded.value = true
    const reinforcement = buildReinforcementPlan(currentLevel.value?.id, currentLevel.value?.words || [], incorrectWordIds.value)
    if (reinforcement.length) {
      learningPlan.value = [...learningPlan.value, ...reinforcement]
      currentStepIndex.value++
      toast.info('Starting a short reinforcement round for missed words.')
      return
    }
  }

  finishSession()
}

function prevActivity() {
  if (currentStepIndex.value > 0) currentStepIndex.value--
}

function selectActivity(index) {
  selectMode(modeOptions[index]?.id || 'guided')
}

function selectMode(mode) {
  const validMode = mode === 'guided' || FOCUSED_MODES[mode] ? mode : 'guided'
  router.push({
    name: 'VocabularyFlow',
    params: { id: currentLevel.value?.id || levelId.value },
    query: validMode === 'guided' ? {} : { mode: validMode }
  })
}

function finishSession() {
  showResults.value = true
  
  const summary = progressStore.selectedLevelProgress?.summary
  const levelProgress = Number(summary?.progress_percent || 0)
  
  if (summary?.completed) {
    toast.success(`🏆 Level completed! Congratulations!`)
  } else if (levelProgress >= 80) {
    toast.success(`🎉 Excellent progress! ${levelProgress}% complete`)
  } else {
    toast.success(`👍 Good work! ${levelProgress}% complete`)
  }
}

function restartSession() {
  startVocabularySession()
}

function goToDashboard() {
  router.push('/student')
}

function goToPractice() {
  router.push('/student/practice')
}

function selectLevel(level) {
  router.push(`/student/flow/${level.id}`)
}

function skipUnavailableActivity(expectedStepKey) {
  if (!currentStep.value || currentStep.value.key !== expectedStepKey) return
  completedStepKeys.value = new Set([...completedStepKeys.value, currentStep.value.key])
  nextActivity()
}

async function loadVocabulary(id = null) {
  try {
    await Promise.all([
      vocabularyStore.levels.length ? Promise.resolve() : vocabularyStore.fetchLevels(),
      progressStore.fetchProgress()
    ])
    if (id) {
      await Promise.all([
        vocabularyStore.fetchLevel(id),
        progressStore.fetchLevelProgress(id)
      ])
      startVocabularySession(id)
    } else {
      vocabularyStore.currentLevelId = null
      vocabularyStore.currentWordIndex = 0
    }
  } catch (error) {
    if (error?.response?.status === 404) {
      toast.error('Vocabulary level not found')
      router.replace('/student/vocabulary-flow')
    } else if (error?.response?.status === 403) {
      toast.error('You do not have access to this vocabulary level')
      router.replace('/student')
    } else {
      toast.error(progressStore.error || vocabularyStore.error || 'Failed to load vocabulary')
    }
  }
}

onMounted(() => loadVocabulary(levelId.value))
watch(levelId, (id, previousId) => {
  if (id !== previousId) loadVocabulary(id)
})
watch(requestedMode, (mode, previousMode) => {
  if (mode === previousMode || !levelId.value) return
  if (mode !== 'guided' && !FOCUSED_MODES[mode]) {
    toast.info('Unknown learning mode. Starting Guided Learning instead.')
    router.replace({ name: 'VocabularyFlow', params: { id: levelId.value } })
    return
  }
  startVocabularySession(levelId.value)
})
</script>

<template>
  <div class="p-6 space-y-6">
    <div v-if="vocabularyStore.loading" class="text-center py-8">
      <span class="loading loading-spinner loading-lg"></span>
      <p class="mt-3">Loading vocabulary...</p>
    </div>
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Vocabulary Learning</h1>
        <p class="text-base-content/70">{{ currentLevel?.title || 'Select a level to start learning' }}</p>
      </div>
      <button @click="goToDashboard" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Dashboard
      </button>
    </div>

    <!-- Level Selection (if no specific level) -->
    <div v-if="!levelId && !currentLevel" class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Choose a Vocabulary Level</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="level in availableLevels" 
            :key="level.id"
            @click="selectLevel(level)"
            class="card bg-base-200 shadow-sm hover:shadow-md cursor-pointer transition-shadow"
          >
            <div class="card-body">
              <h3 class="card-title text-lg">{{ level.title }}</h3>
              <p class="text-sm text-base-content/70">{{ level.description || 'Learn vocabulary in this level' }}</p>
              
              <div class="flex items-center gap-2 mt-2">
                <div class="badge badge-outline">{{ level.words?.length || 0 }} words</div>
                <div class="badge badge-neutral">{{ level.stage || 'Not specified' }}</div>
              </div>
              
              <button class="btn btn-primary btn-sm mt-3 gap-2">
                <PlayIcon class="w-4 h-4" />
                Start Learning
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Learning Progress (when level is selected) -->
    <div v-if="currentLevel && !showResults" class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="flex items-center justify-between mb-4">
          <h2 class="card-title">Learning Progress</h2>
          <span class="text-lg font-bold">
            Word {{ currentWordIndex + 1 }} of {{ currentLevel.words?.length || 0 }}
          </span>
        </div>
        
        <!-- Level Progress -->
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium">Level Progress</span>
            <span class="text-sm font-bold">{{ levelProgressPercentage }}%</span>
          </div>
          <progress 
            class="progress progress-success w-full" 
            :value="levelProgressPercentage" 
            max="100"
          ></progress>
        </div>
        
        <!-- Current Word Progress -->
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium">Session Activities</span>
            <span class="text-sm font-bold">{{ completedStepKeys.size }} / {{ totalActivities }}</span>
          </div>
          <progress 
            class="progress progress-primary w-full" 
            :value="progressPercentage" 
            max="100"
          ></progress>
        </div>
        
        <!-- Learning Mode Selection -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
          <button
            v-for="mode in modeOptions"
            :key="mode.id"
            @click="selectMode(mode.id)"
            class="btn btn-sm"
            :class="{
              'btn-primary': activeMode === mode.id,
              'btn-outline': activeMode !== mode.id
            }"
          >
            <span class="text-lg">{{ mode.icon }}</span>
            <span class="hidden md:inline ml-1">{{ mode.name }}</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="currentLevel && !showResults && !learningPlan.length" class="card bg-base-100 shadow-md">
      <div class="card-body text-center">
        <p class="text-base-content/70">
          {{ activeMode === 'sentence'
            ? 'No words in this level have sentences available.'
            : activeMode === 'match'
              ? 'At least two words are required for Match Words.'
              : 'No words are available for this mode.' }}
        </p>
        <button class="btn btn-primary mx-auto" @click="selectMode('guided')">Back to Guided Learning</button>
      </div>
    </div>

    <!-- Current Activity -->
    <div v-if="currentLevel && currentWord.word && !showResults" class="space-y-4">
      <div class="card bg-primary text-primary-content">
        <div class="card-body">
          <h3 class="card-title">{{ currentActivityType.name }}</h3>
          <p>{{ currentActivityType.description }}</p>
          <div class="flex gap-2">
            <div v-if="currentActivityType.id !== 'word-match'" class="badge badge-secondary">
              Current word: {{ currentWord.word }}
            </div>
            <div v-else class="badge badge-secondary">
              Introduced words: {{ introducedWordIds.size }}
            </div>
            <div class="badge badge-accent">
              Mastery: {{ currentWord.effective_mastery_percent ?? currentWord.mastery_percent ?? 0 }}%
            </div>
          </div>
          <audio v-if="currentWord.audio_url" :src="currentWord.audio_url" controls preload="none" class="mt-3 w-full max-w-md"></audio>
        </div>
      </div>

      <VocabularyActivities
        :activity-type="currentActivityType.id"
        :word="currentWord"
        :level-words="currentLevel.words || []"
        :introduced-word-ids="[...introducedWordIds]"
        :exposure-only="Boolean(currentStep?.exposureOnly)"
        :on-complete="activityCompleteHandler"
        :on-skip="activitySkipHandler"
        :key="currentStep?.key"
      />

      <div v-if="progressStore.submittingWordId" class="text-center text-sm text-base-content/70">
        Saving progress...
      </div>

      <!-- Auto Progress Info -->
      <div class="text-center">
        <div class="alert alert-info">
          <span>Complete each step to automatically move to the next one!</span>
        </div>
      </div>
    </div>

    <!-- Session Results -->
    <div v-if="showResults" class="space-y-6">
      <div class="card bg-base-100 shadow-lg">
        <div class="card-body text-center">
          <TrophyIcon class="w-16 h-16 mx-auto text-warning mb-4" />
          <h2 class="card-title justify-center text-2xl">Learning Session Complete!</h2>
          
          <div class="stats shadow mt-6">
            <div class="stat">
              <div class="stat-title">Words Practiced</div>
              <div class="stat-value text-primary">{{ wordsPracticed }}</div>
              <div class="stat-desc">out of {{ currentLevel?.words?.length || 0 }}</div>
            </div>
            
            <div class="stat">
              <div class="stat-title">Level Progress</div>
              <div class="stat-value text-secondary">{{ levelProgressPercentage }}%</div>
              <div class="stat-desc">Guided rounds completed</div>
            </div>
            
            <div class="stat">
              <div class="stat-title">Total XP</div>
              <div class="stat-value text-accent">{{ progressStore.totalXp }}</div>
              <div class="stat-desc">
                Latest update:
                {{ progressStore.lastProgressUpdate
                  ? (progressStore.lastProgressUpdate.xp_awarded ? 'XP awarded' : 'no XP awarded')
                  : 'none yet' }}
              </div>
            </div>
          </div>

          <div class="card-actions justify-center mt-6 gap-3">
            <button @click="restartSession" class="btn btn-primary gap-2">
              <ArrowRightIcon class="w-4 h-4" />
              {{ isGuidedMode ? 'Continue Learning' : 'Practice This Mode Again' }}
            </button>
            <button v-if="!isGuidedMode" @click="selectMode('guided')" class="btn btn-outline">
              Guided Learning
            </button>
            <button @click="goToPractice" class="btn btn-outline">
              Practice Mode
            </button>
            <button @click="goToDashboard" class="btn btn-ghost">
              Back to Dashboard
            </button>
          </div>
        </div>
      </div>

      <!-- Level Summary -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h3 class="card-title">Level Summary: {{ currentLevel?.title }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="word in currentLevel?.words || []" 
              :key="word.id"
              class="flex items-center justify-between p-3 bg-base-200 rounded-lg"
            >
              <div>
                <p class="font-semibold">{{ word.word }}</p>
                <p class="text-sm text-base-content/70">{{ word.translation }}</p>
              </div>
              <div class="flex items-center gap-2">
                <progress 
                  class="progress progress-primary w-16" 
                  :value="masteryForWord(word.id)"
                  max="100"
                ></progress>
                <span class="text-sm font-medium">{{ masteryForWord(word.id) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!currentLevel && !availableLevels.length" class="card bg-base-100 shadow-md">
      <div class="card-body text-center">
        <h3 class="text-xl font-semibold mb-4">No vocabulary levels available</h3>
        <p class="text-base-content/70 mb-6">Contact your teacher to get access to vocabulary levels.</p>
        <button @click="goToDashboard" class="btn btn-primary">
          Go to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>
