<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { useStudentProgressStore } from '@/stores/studentProgressStore'
import { useToast } from 'vue-toastification'
import VocabularyActivities from '@/components/student/VocabularyActivities.vue'
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

const currentActivityIndex = ref(0)
const completedActivities = ref(new Set())
const sessionScore = ref(0)
const totalActivities = ref(0)
const showResults = ref(false)
const currentWordIndex = ref(0)

const currentActivityType = computed(() => activityTypes[currentActivityIndex.value])
const currentLevel = computed(() => vocabularyStore.currentLevel)
const currentWord = computed(() => {
  if (!currentLevel.value?.words?.length) return {}
  const word = currentLevel.value.words[currentWordIndex.value] || {}
  const progress = progressStore.wordById(word.id) || {}
  return {
    ...word,
    ...progress,
    audio_path: word.audio_path,
    audio_url: word.audio_url,
    audio: word.audio_url || ''
  }
})

const progressPercentage = computed(() => {
  return totalActivities.value > 0 ? Math.round((completedActivities.value.size / totalActivities.value) * 100) : 0
})

const levelProgressPercentage = computed(() => {
  return Number(progressStore.selectedLevelProgress?.summary?.progress_percent || 0)
})

const masteryForWord = (wordId) => progressStore.wordById(wordId)?.mastery_percent || 0

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
  sessionScore.value = 0
  totalActivities.value = activityTypes.length
  currentActivityIndex.value = 0
  currentWordIndex.value = 0
  showResults.value = false
  
  toast.success(`Starting vocabulary session: ${currentLevel.value?.title}`)
}

async function onActivityComplete(success) {
  const activityId = currentActivityType.value.id
  try {
    const update = await progressStore.submitWordProgress(currentWord.value.id, success)
    if (!update) return
  } catch (error) {
    const status = error?.response?.status
    if (status === 401) toast.error('Your session has expired. Please sign in again.')
    else if (status === 403) toast.error('You do not have access to update this word.')
    else if (status === 404) toast.error('This vocabulary word is no longer available.')
    else if (status === 422) toast.error(error?.response?.data?.message || 'The progress update was invalid.')
    else toast.error('Progress could not be saved. Check your connection and try again.')
    return
  }
  
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
    // Move to next word or finish level
    if (currentWordIndex.value < (currentLevel.value?.words?.length || 0) - 1) {
      currentWordIndex.value++
      currentActivityIndex.value = 0
      completedActivities.value.clear()
      sessionScore.value = 0
      toast.info(`Moving to next word: ${currentLevel.value.words[currentWordIndex.value]?.word}`)
    } else {
      finishSession()
    }
  }
}

function prevActivity() {
  if (currentActivityIndex.value > 0) {
    currentActivityIndex.value--
  } else if (currentWordIndex.value > 0) {
    // Go to previous word
    currentWordIndex.value--
    currentActivityIndex.value = activityTypes.length - 1
    completedActivities.value.clear()
    sessionScore.value = 0
    toast.info(`Back to previous word: ${currentLevel.value.words[currentWordIndex.value]?.word}`)
  }
}

function selectActivity(index) {
  // Activities are now automatic - students cannot manually change steps
  // This function is disabled to prevent manual step changes
  return
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
                <div class="badge" :class="{
                  'badge-success': level.difficulty === 'beginner',
                  'badge-warning': level.difficulty === 'intermediate', 
                  'badge-error': level.difficulty === 'advanced'
                }">
                  {{ level.difficulty || 'beginner' }}
                </div>
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
            <span class="text-sm font-medium">Current Word Activities</span>
            <span class="text-sm font-bold">{{ completedActivities.size }} / {{ totalActivities }}</span>
          </div>
          <progress 
            class="progress progress-primary w-full" 
            :value="progressPercentage" 
            max="100"
          ></progress>
        </div>
        
        <!-- Activity Type Progress Display -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <div
            v-for="(activity, index) in activityTypes"
            :key="activity.id"
            class="btn btn-sm cursor-default"
            :class="{
              'btn-primary': currentActivityIndex === index,
              'btn-success': completedActivities.has(activity.id),
              'btn-outline': currentActivityIndex !== index && !completedActivities.has(activity.id)
            }"
          >
            <span class="text-lg">{{ activity.icon }}</span>
            <span class="hidden md:inline ml-1">{{ activity.name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Current Activity -->
    <div v-if="currentLevel && currentWord.word && !showResults" class="space-y-4">
      <div class="card bg-primary text-primary-content">
        <div class="card-body">
          <h3 class="card-title">{{ currentActivityType.name }}</h3>
          <p>{{ currentActivityType.description }}</p>
          <div class="flex gap-2">
            <div class="badge badge-secondary">
              Current word: {{ currentWord.word }}
            </div>
            <div class="badge badge-accent">
              Mastery: {{ currentWord.mastery_percent || 0 }}%
            </div>
          </div>
          <audio v-if="currentWord.audio_url" :src="currentWord.audio_url" controls preload="none" class="mt-3 w-full max-w-md"></audio>
        </div>
      </div>

      <VocabularyActivities
        :activity-type="currentActivityType.id"
        :word="currentWord"
        :on-complete="onActivityComplete"
        :key="`${currentActivityType.id}-${currentWord.id}-${currentWordIndex}`"
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
              <div class="stat-value text-primary">{{ currentWordIndex + 1 }}</div>
              <div class="stat-desc">out of {{ currentLevel?.words?.length || 0 }}</div>
            </div>
            
            <div class="stat">
              <div class="stat-title">Level Progress</div>
              <div class="stat-value text-secondary">{{ levelProgressPercentage }}%</div>
              <div class="stat-desc">{{ Math.round((currentWordIndex + 1) / (currentLevel?.words?.length || 1) * 100) }}% of words</div>
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
              Continue Learning
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
