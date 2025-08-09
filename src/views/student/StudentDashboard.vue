<script setup>
import { computed, ref } from "vue"
import { useVocabularyStore } from "@/stores/vocabularyStore"
import { useAuthStore } from "@/stores/authStore"
import { useRouter } from "vue-router"
import { 
  TrophyIcon, 
  FireIcon, 
  BookOpenIcon, 
  ClockIcon,
  ChartBarIcon,
  PlayIcon,
  CalendarIcon,
  StarIcon,
  LightBulbIcon,
  AcademicCapIcon,
  ArrowRightIcon
} from '@heroicons/vue/24/outline'

const store = useVocabularyStore()
const authStore = useAuthStore()
const router = useRouter()

const assignedLevels = computed(() => store.levels)

// Student stats and achievements
const studentStats = ref({
  currentStreak: 7,
  totalStudyTime: 145,
  wordsLearned: 89,
  perfectSessions: 12,
  rank: 15,
  totalStudents: 230
})

// Recent achievements
const achievements = ref([
  { id: 1, title: '7-Day Streak!', description: 'Studied for 7 consecutive days', icon: '🔥', earned: true, date: '2024-08-09' },
  { id: 2, title: 'Fast Learner', description: 'Completed 10 levels in record time', icon: '⚡', earned: true, date: '2024-08-08' },
  { id: 3, title: 'Vocabulary Master', description: 'Reached 100% mastery in 3 levels', icon: '👑', earned: false, progress: 2 },
  { id: 4, title: 'Perfect Practice', description: 'Score 100% in 5 practice sessions', icon: '🎯', earned: false, progress: 3 }
])

// Daily goals and progress
const dailyGoals = ref([
  { id: 1, title: 'Study for 20 minutes', target: 20, current: 14, unit: 'minutes', completed: false },
  { id: 2, title: 'Learn 5 new words', target: 5, current: 3, unit: 'words', completed: false },
  { id: 3, title: 'Complete 1 level', target: 1, current: 0, unit: 'levels', completed: false },
  { id: 4, title: 'Practice pronunciation', target: 1, current: 1, unit: 'sessions', completed: true }
])

// Weekly learning schedule
const weeklySchedule = ref([
  { day: 'Mon', completed: true, scheduled: true, xp: 120 },
  { day: 'Tue', completed: true, scheduled: true, xp: 95 },
  { day: 'Wed', completed: false, scheduled: true, xp: 0 },
  { day: 'Thu', completed: false, scheduled: true, xp: 0 },
  { day: 'Fri', completed: false, scheduled: false, xp: 0 },
  { day: 'Sat', completed: false, scheduled: true, xp: 0 },
  { day: 'Sun', completed: false, scheduled: false, xp: 0 }
])

// Recommended activities
const recommendations = ref([
  { 
    id: 1, 
    title: 'Complete "Family" Level', 
    description: 'You\'re 65% through this level', 
    action: 'Continue', 
    level: 'Family',
    progress: 65,
    estimatedTime: '15 mins'
  },
  { 
    id: 2, 
    title: 'Practice Pronunciation', 
    description: 'Improve your speaking skills', 
    action: 'Practice', 
    level: 'Speech',
    progress: 0,
    estimatedTime: '10 mins'
  },
  { 
    id: 3, 
    title: 'Review Completed Levels', 
    description: 'Reinforce your learning', 
    action: 'Review', 
    level: 'Pets',
    progress: 100,
    estimatedTime: '8 mins'
  }
])

// Calculate progress per level
const getProgress = (level) => {
  if (!level.words.length) return 0
  const total = level.words.length * 100
  const earned = level.words.reduce((acc, w) => acc + w.mastery, 0)
  return Math.round((earned / total) * 100)
}

// Calculate overall progress
const overallProgress = computed(() => {
  if (!assignedLevels.value.length) return 0
  const totalProgress = assignedLevels.value.reduce((sum, level) => sum + getProgress(level), 0)
  return Math.round(totalProgress / assignedLevels.value.length)
})

// Get next recommended level
const nextLevel = computed(() => {
  return assignedLevels.value.find(level => getProgress(level) < 100) || assignedLevels.value[0]
})

// Calculate learning streak
const learningStreak = computed(() => studentStats.value.currentStreak)

// Go to level flow
const goToLevelFlow = (levelId) => {
  router.push(`/student/flow/${levelId}`)
}

// Go to practice
const goToPractice = () => {
  router.push('/student/practice')
}

// Go to vocabulary overview
const goToVocabulary = () => {
  router.push('/student/vocabulary-flow')
}

// Quick actions
const startQuickPractice = () => {
  router.push('/student/practice')
}

const continueLevel = (levelId) => {
  router.push(`/student/flow/${levelId}`)
}

// Get achievement progress
const getAchievementProgress = (achievement) => {
  if (achievement.earned) return 100
  return Math.round((achievement.progress / (achievement.title.includes('3') ? 3 : 5)) * 100)
}

// Get day status
const getDayStatus = (day) => {
  if (day.completed) return 'bg-success text-success-content'
  if (day.scheduled) return 'bg-primary text-primary-content'
  return 'bg-base-300 text-base-content'
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Welcome Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-primary">Welcome back, {{ authStore.user?.name || 'Student' }}! 👋</h1>
        <p class="text-base-content/70">Ready to continue your learning journey?</p>
      </div>
      <div class="flex gap-2">
        <button @click="startQuickPractice" class="btn btn-primary gap-2">
          <PlayIcon class="w-4 h-4" />
          Quick Practice
        </button>
        <button @click="goToVocabulary" class="btn btn-outline gap-2">
          <BookOpenIcon class="w-4 h-4" />
          All Levels
        </button>
      </div>
    </div>

    <!-- Key Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="stat bg-primary text-primary-content shadow-md rounded-lg">
        <div class="stat-figure">
          <TrophyIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-primary-content/80">Total XP</div>
        <div class="stat-value">{{ store.xp }}</div>
        <div class="stat-desc text-primary-content/60">Experience points</div>
      </div>

      <div class="stat bg-warning text-warning-content shadow-md rounded-lg">
        <div class="stat-figure">
          <FireIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-warning-content/80">Study Streak</div>
        <div class="stat-value">{{ learningStreak }}</div>
        <div class="stat-desc text-warning-content/60">days in a row</div>
      </div>

      <div class="stat bg-success text-success-content shadow-md rounded-lg">
        <div class="stat-figure">
          <BookOpenIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-success-content/80">Words Learned</div>
        <div class="stat-value">{{ studentStats.wordsLearned }}</div>
        <div class="stat-desc text-success-content/60">vocabulary mastered</div>
      </div>

      <div class="stat bg-info text-info-content shadow-md rounded-lg">
        <div class="stat-figure">
          <ChartBarIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-info-content/80">Overall Progress</div>
        <div class="stat-value">{{ overallProgress }}%</div>
        <div class="stat-desc text-info-content/60">across all levels</div>
      </div>
    </div>

    <!-- Main Learning Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Continue Learning -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Current Level Progress -->
        <div class="card bg-gradient-to-r from-primary to-secondary text-primary-content shadow-lg">
          <div class="card-body">
            <h2 class="card-title text-xl mb-4">Continue Your Journey</h2>
            <div v-if="nextLevel" class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-bold">{{ nextLevel.title }}</h3>
                  <p class="text-primary-content/80">{{ getProgress(nextLevel) }}% Complete</p>
                </div>
                <button @click="goToLevelFlow(nextLevel.id)" class="btn btn-accent gap-2">
                  <PlayIcon class="w-4 h-4" />
                  Continue
                </button>
              </div>
              <progress class="progress progress-accent" :value="getProgress(nextLevel)" max="100"></progress>
            </div>
          </div>
        </div>

        <!-- Recommended Activities -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title">Recommended For You</h2>
            <div class="space-y-3">
              <div v-for="rec in recommendations" :key="rec.id" 
                   class="flex items-center justify-between p-4 bg-base-200 rounded-lg hover:bg-base-300 transition-colors">
                <div class="flex-1">
                  <h3 class="font-semibold">{{ rec.title }}</h3>
                  <p class="text-sm text-base-content/70">{{ rec.description }}</p>
                  <div class="flex items-center gap-4 mt-2">
                    <span class="text-xs badge badge-outline">{{ rec.estimatedTime }}</span>
                    <div class="flex items-center gap-1">
                      <progress class="progress progress-primary w-16" :value="rec.progress" max="100"></progress>
                      <span class="text-xs">{{ rec.progress }}%</span>
                    </div>
                  </div>
                </div>
                <button class="btn btn-sm btn-primary gap-1">
                  {{ rec.action }}
                  <ArrowRightIcon class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Learning Levels Grid -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <div class="flex items-center justify-between mb-4">
              <h2 class="card-title">Your Levels</h2>
              <button @click="goToVocabulary" class="btn btn-sm btn-outline">View All</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="level in assignedLevels.slice(0, 4)" :key="level.id"
                   class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                   @click="goToLevelFlow(level.id)">
                <div class="card-body p-4">
                  <h3 class="card-title text-base">{{ level.title }}</h3>
                  <div class="space-y-2">
                    <progress class="progress progress-success" :value="getProgress(level)" max="100"></progress>
                    <div class="flex justify-between text-sm">
                      <span>{{ getProgress(level) }}% Complete</span>
                      <span>{{ level.words?.length || 0 }} words</span>
                    </div>
                  </div>
                  <button class="btn btn-xs btn-primary mt-2">
                    {{ getProgress(level) === 100 ? "Review" : "Continue" }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Daily Goals -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title text-lg">Daily Goals</h2>
            <div class="space-y-3">
              <div v-for="goal in dailyGoals" :key="goal.id" class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">{{ goal.title }}</span>
                  <div class="badge" :class="goal.completed ? 'badge-success' : 'badge-outline'">
                    {{ goal.current }}/{{ goal.target }}
                  </div>
                </div>
                <progress class="progress progress-primary" :value="goal.current" :max="goal.target"></progress>
              </div>
            </div>
          </div>
        </div>

        <!-- Weekly Schedule -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title text-lg">This Week</h2>
            <div class="grid grid-cols-7 gap-1">
              <div v-for="day in weeklySchedule" :key="day.day" 
                   class="text-center p-2 rounded text-xs font-medium" :class="getDayStatus(day)">
                <div>{{ day.day }}</div>
                <div v-if="day.completed" class="text-xs mt-1">{{ day.xp }} XP</div>
                <div v-else-if="day.scheduled" class="text-xs mt-1">Planned</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Achievements -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title text-lg">Achievements</h2>
            <div class="space-y-3">
              <div v-for="achievement in achievements.slice(0, 3)" :key="achievement.id" 
                   class="flex items-center gap-3 p-3 rounded-lg" 
                   :class="achievement.earned ? 'bg-success/10' : 'bg-base-200'">
                <span class="text-2xl">{{ achievement.icon }}</span>
                <div class="flex-1">
                  <h3 class="font-semibold text-sm">{{ achievement.title }}</h3>
                  <p class="text-xs text-base-content/70">{{ achievement.description }}</p>
                  <div v-if="!achievement.earned" class="mt-1">
                    <progress class="progress progress-primary w-full" 
                              :value="getAchievementProgress(achievement)" max="100"></progress>
                  </div>
                </div>
                <div v-if="achievement.earned" class="badge badge-success badge-sm">
                  ✓
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title text-lg">Your Rank</h2>
            <div class="text-center space-y-2">
              <div class="stat-value text-primary">#{{ studentStats.rank }}</div>
              <div class="text-sm text-base-content/70">out of {{ studentStats.totalStudents }} students</div>
              <div class="badge badge-primary">Top {{ Math.round((studentStats.rank / studentStats.totalStudents) * 100) }}%</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Learning Tips -->
    <div class="card bg-gradient-to-r from-info to-primary text-info-content shadow-md">
      <div class="card-body">
        <div class="flex items-center gap-3">
          <LightBulbIcon class="w-8 h-8" />
          <div>
            <h3 class="font-bold">💡 Learning Tip of the Day</h3>
            <p class="text-sm opacity-90">
              Practice speaking out loud! The speech recognition feature helps improve your pronunciation and builds confidence.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
