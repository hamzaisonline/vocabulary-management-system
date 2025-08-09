<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStudentStore } from '@/stores/studentStore'
import { ArrowLeftIcon, TrophyIcon, AcademicCapIcon, ClockIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const studentStore = useStudentStore()

const studentId = ref(parseInt(route.params.id))

const student = computed(() => {
  return studentStore.students.find(s => s.id === studentId.value)
})

const overallProgress = computed(() => {
  if (!student.value?.progress || Object.keys(student.value.progress).length === 0) return 0
  const levels = Object.values(student.value.progress)
  return Math.round(levels.reduce((sum, level) => sum + level.score, 0) / levels.length)
})

const getProgressColor = (score) => {
  if (score >= 90) return 'progress-success'
  if (score >= 70) return 'progress-warning'
  return 'progress-error'
}

const getActivityIcon = (activity) => {
  if (activity.includes('Completed')) return '🎉'
  if (activity.includes('Practiced')) return '📚'
  if (activity.includes('Started')) return '🚀'
  return '📝'
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  if (!student.value) {
    router.push('/teacher/classes')
  }
})
</script>

<template>
  <div class="p-6 space-y-6" v-if="student">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">{{ student.name }}</h1>
        <p class="text-base-content/70">{{ student.email }}</p>
      </div>
      <button @click="goBack" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back
      </button>
    </div>

    <!-- Student Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <TrophyIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total XP</div>
        <div class="stat-value text-primary">{{ student.totalXP }}</div>
        <div class="stat-desc">Experience points</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <AcademicCapIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Levels Completed</div>
        <div class="stat-value text-secondary">{{ student.levelsCompleted }}</div>
        <div class="stat-desc">Vocabulary levels</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-accent">
          <ClockIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Current Level</div>
        <div class="stat-value text-accent text-lg">{{ student.currentLevel }}</div>
        <div class="stat-desc">In progress</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-success">
          <span class="text-2xl">📊</span>
        </div>
        <div class="stat-title">Overall Progress</div>
        <div class="stat-value text-success">{{ overallProgress }}%</div>
        <div class="stat-desc">Average score</div>
      </div>
    </div>

    <!-- Level Progress -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Level Progress</h2>
        <div class="space-y-4">
          <div 
            v-for="(progress, levelName) in student.progress" 
            :key="levelName"
            class="flex items-center justify-between p-4 bg-base-200 rounded-lg"
          >
            <div class="flex-1">
              <h3 class="font-semibold">{{ levelName }}</h3>
              <div class="flex items-center gap-4 mt-2">
                <progress 
                  class="progress w-40" 
                  :class="getProgressColor(progress.score)"
                  :value="progress.score" 
                  max="100"
                ></progress>
                <span class="text-sm font-medium">{{ progress.score }}%</span>
              </div>
            </div>
            
            <div class="text-right">
              <div class="badge" :class="progress.completed ? 'badge-success' : 'badge-warning'">
                {{ progress.completed ? 'Completed' : 'In Progress' }}
              </div>
              <p class="text-xs text-base-content/60 mt-1">{{ progress.timeSpent }} min</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Recent Activity</h2>
        <div class="space-y-3">
          <div 
            v-for="activity in student.activityHistory" 
            :key="activity.date"
            class="flex items-center gap-3 p-3 bg-base-200 rounded-lg"
          >
            <span class="text-2xl">{{ getActivityIcon(activity.activity) }}</span>
            <div class="flex-1">
              <p class="font-medium">{{ activity.activity }}</p>
              <p class="text-sm text-base-content/60">{{ activity.date }}</p>
            </div>
            <div class="badge badge-success">+{{ activity.xpEarned }} XP</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Student Info -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Student Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm font-medium text-base-content/70">Enrolled Date</p>
            <p class="font-semibold">{{ student.enrolledDate }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-base-content/70">Last Activity</p>
            <p class="font-semibold">{{ student.lastActivity }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-base-content/70">Email</p>
            <p class="font-semibold">{{ student.email }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-base-content/70">Class ID</p>
            <p class="font-semibold">{{ student.classId }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Not Found State -->
  <div v-else class="p-6">
    <div class="card bg-base-100 shadow-md">
      <div class="card-body text-center">
        <h2 class="text-xl font-semibold mb-4">Student Not Found</h2>
        <p class="text-base-content/70 mb-6">The requested student could not be found.</p>
        <button @click="goBack" class="btn btn-primary">
          Go Back
        </button>
      </div>
    </div>
  </div>
</template>
