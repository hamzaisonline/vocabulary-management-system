<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useClassStore } from '@/stores/classStore'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { PlusIcon, AcademicCapIcon, BookOpenIcon, UserGroupIcon, ChartBarIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const classStore = useClassStore()
const vocabularyStore = useVocabularyStore()

// Mock data for teacher dashboard
const teacherStats = ref({
  totalStudents: 42,
  totalClasses: 6,
  vocabularyLevels: vocabularyStore.levels.length,
  averageProgress: 73
})

const recentActivity = ref([
  { id: 1, action: 'Student Maria completed level "Pets"', time: '2 hours ago', type: 'completion' },
  { id: 2, action: 'New student joined "Spanish Basics"', time: '4 hours ago', type: 'enrollment' },
  { id: 3, action: 'Created new vocabulary level "Food"', time: '1 day ago', type: 'content' },
  { id: 4, action: 'Student Carlos achieved platinum badge', time: '2 days ago', type: 'achievement' }
])

const upcomingTasks = ref([
  { id: 1, task: 'Review pending student assignments', priority: 'high', dueDate: 'Today' },
  { id: 2, task: 'Create vocabulary for next week', priority: 'medium', dueDate: 'Tomorrow' },
  { id: 3, task: 'Grade practice exercises', priority: 'medium', dueDate: '2 days' },
  { id: 4, task: 'Prepare monthly progress report', priority: 'low', dueDate: '1 week' }
])

const classPerformance = ref([
  { name: 'Spanish Basics', students: 15, avgProgress: 85, trend: 'up' },
  { name: 'Intermediate Spanish', students: 12, avgProgress: 67, trend: 'up' },
  { name: 'Advanced Conversation', students: 8, avgProgress: 92, trend: 'stable' },
  { name: 'Grammar Focus', students: 7, avgProgress: 58, trend: 'down' }
])

const navigateToClasses = () => {
  router.push('/teacher/classes')
}

const viewClassDetails = (classId) => {
  router.push(`/teacher/classes/${classId}`)
}

const navigateToVocabulary = () => {
  router.push('/teacher/vocabulary')
}

const navigateToCreateClass = () => {
  router.push('/teacher/classes')
}

const getActivityIcon = (type) => {
  switch (type) {
    case 'completion': return '🎉'
    case 'enrollment': return '👥'
    case 'content': return '📚'
    case 'achievement': return '🏆'
    default: return '📝'
  }
}

const getPriorityColor = (priority) => {
  switch (priority) {
    case 'high': return 'badge-error'
    case 'medium': return 'badge-warning'
    case 'low': return 'badge-info'
    default: return 'badge-neutral'
  }
}

const getTrendIcon = (trend) => {
  switch (trend) {
    case 'up': return '📈'
    case 'down': return '📉'
    case 'stable': return '➡️'
    default: return '➡️'
  }
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Welcome Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Welcome back, {{ authStore.user?.name }}! 👋</h1>
        <p class="text-base-content/70 mt-1">Here's what's happening in your classes today</p>
      </div>
      <button 
        @click="navigateToCreateClass"
        class="btn btn-primary gap-2"
      >
        <PlusIcon class="w-5 h-5" />
        Create New Class
      </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Students</div>
        <div class="stat-value text-primary">{{ teacherStats.totalStudents }}</div>
        <div class="stat-desc">Across all classes</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <AcademicCapIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Active Classes</div>
        <div class="stat-value text-secondary">{{ teacherStats.totalClasses }}</div>
        <div class="stat-desc">Currently teaching</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-accent">
          <BookOpenIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Vocabulary Levels</div>
        <div class="stat-value text-accent">{{ teacherStats.vocabularyLevels }}</div>
        <div class="stat-desc">Content created</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-success">
          <ChartBarIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Avg Progress</div>
        <div class="stat-value text-success">{{ teacherStats.averageProgress }}%</div>
        <div class="stat-desc">Student completion</div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Class Performance -->
      <div class="lg:col-span-2">
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <div class="flex items-center justify-between mb-4">
              <h2 class="card-title">Class Performance</h2>
              <button 
                @click="navigateToClasses"
                class="btn btn-outline btn-sm"
              >
                View All
              </button>
            </div>
            
            <div class="space-y-4">
              <div
                v-for="(classItem, index) in classPerformance"
                :key="classItem.name"
                @click="viewClassDetails(index + 1)"
                class="flex items-center justify-between p-4 bg-base-200 rounded-lg cursor-pointer hover:bg-base-300 transition-colors"
              >
                <div class="flex-1">
                  <h3 class="font-semibold">{{ classItem.name }}</h3>
                  <p class="text-sm text-base-content/70">{{ classItem.students }} students</p>
                  
                  <div class="flex items-center gap-2 mt-2">
                    <progress 
                      class="progress progress-primary w-32" 
                      :value="classItem.avgProgress" 
                      max="100"
                    ></progress>
                    <span class="text-sm font-medium">{{ classItem.avgProgress }}%</span>
                    <span class="text-lg">{{ getTrendIcon(classItem.trend) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity & Tasks -->
      <div class="space-y-6">
        <!-- Recent Activity -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title">Recent Activity</h2>
            
            <div class="space-y-3">
              <div 
                v-for="activity in recentActivity.slice(0, 4)" 
                :key="activity.id"
                class="flex items-start gap-3 p-3 bg-base-200 rounded-lg"
              >
                <span class="text-lg">{{ getActivityIcon(activity.type) }}</span>
                <div class="flex-1">
                  <p class="text-sm">{{ activity.action }}</p>
                  <p class="text-xs text-base-content/60">{{ activity.time }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Tasks -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title">Upcoming Tasks</h2>
            
            <div class="space-y-3">
              <div 
                v-for="task in upcomingTasks.slice(0, 4)" 
                :key="task.id"
                class="flex items-center justify-between p-3 bg-base-200 rounded-lg"
              >
                <div class="flex-1">
                  <p class="text-sm font-medium">{{ task.task }}</p>
                  <p class="text-xs text-base-content/60">Due: {{ task.dueDate }}</p>
                </div>
                <div class="badge badge-sm" :class="getPriorityColor(task.priority)">
                  {{ task.priority }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title mb-4">Quick Actions</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <button 
            @click="navigateToClasses"
            class="btn btn-outline gap-2 justify-start"
          >
            <AcademicCapIcon class="w-5 h-5" />
            Manage Classes
          </button>
          
          <button 
            @click="navigateToVocabulary"
            class="btn btn-outline gap-2 justify-start"
          >
            <BookOpenIcon class="w-5 h-5" />
            Create Vocabulary
          </button>
          
          <button 
            class="btn btn-outline gap-2 justify-start"
          >
            <ChartBarIcon class="w-5 h-5" />
            View Reports
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
