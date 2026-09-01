<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { BookOpenIcon, ChartBarIcon, PlayIcon, TrophyIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/authStore'
import { useDashboardStore } from '@/stores/dashboardStore'
import DashboardMetricCard from '@/components/dashboard/DashboardMetricCard.vue'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const dashboardStore = useDashboardStore()
const dashboard = computed(() => dashboardStore.studentDashboard)

const loadDashboard = async () => {
  if (authStore.userRole !== 'student') return
  try {
    await dashboardStore.fetchStudentDashboard()
  } catch {
    toast.error(dashboardStore.error || 'Unable to load dashboard.')
  }
}

onMounted(loadDashboard)
watch(() => authStore.userRole, (role) => {
  if (role !== 'student') dashboardStore.reset()
})
</script>

<template>
  <div class="p-4 sm:p-6 space-y-6 overflow-x-hidden">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="break-words text-2xl font-bold text-primary sm:text-3xl">Welcome back, {{ authStore.user?.name || 'Student' }}!</h1>
        <p class="text-base-content/70">Your current learning overview</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button class="btn btn-primary gap-2" @click="router.push('/student/practice')"><PlayIcon class="w-4 h-4" /> Practice</button>
        <button class="btn btn-outline gap-2" @click="router.push('/student/vocabulary-flow')"><BookOpenIcon class="w-4 h-4" /> Vocabulary</button>
      </div>
    </div>

    <div v-if="dashboardStore.loading" class="text-center py-12"><span class="loading loading-spinner loading-lg"></span></div>
    <div v-else-if="dashboardStore.error && !dashboard" class="alert alert-error"><span>{{ dashboardStore.error }}</span></div>

    <template v-else-if="dashboard">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <DashboardMetricCard title="Total XP" :value="dashboard.total_xp" description="Experience earned" :icon="TrophyIcon" card-class="bg-success text-success-content" />
        <DashboardMetricCard title="Accessible Levels" :value="dashboard.accessible_vocabulary_levels_count" description="Available to learn" :icon="BookOpenIcon" card-class="bg-info text-info-content" />
        <DashboardMetricCard title="Completed Levels" :value="dashboard.completed_vocabulary_levels_count" description="Fully mastered" :icon="TrophyIcon" card-class="bg-primary text-primary-content" />
        <DashboardMetricCard title="Mastered Words" :value="dashboard.mastered_words_count" description="At 100% mastery" :icon="BookOpenIcon" card-class="bg-accent text-accent-content" />
        <DashboardMetricCard title="Unmastered Words" :value="dashboard.current_unmastered_words_count" description="Currently in progress" :icon="ChartBarIcon" card-class="bg-info text-info-content" />
        <DashboardMetricCard title="Average Mastery" :value="`${dashboard.average_mastery_across_accessible_vocabulary}%`" description="Across accessible vocabulary" :icon="ChartBarIcon" card-class="bg-primary text-primary-content" />
        <DashboardMetricCard title="Reviewable Words" :value="dashboard.recent_reviewable_words_count" description="Ready for review" :icon="BookOpenIcon" card-class="bg-success text-success-content" />
      </div>

      <div class="card border border-base-300 bg-base-100 shadow-sm"><div class="card-body p-4 sm:p-6">
          <h2 class="card-title"><ChartBarIcon class="w-5 h-5" /> Recent Practice</h2>
          <div v-if="dashboard.recent_practice_sessions.length" class="space-y-3">
            <div v-for="session in dashboard.recent_practice_sessions" :key="session.id" class="flex min-w-0 flex-col gap-2 rounded-lg bg-base-200 p-3 sm:flex-row sm:items-center sm:justify-between">
              <div class="min-w-0"><p class="break-words font-semibold">{{ session.level_title || 'Vocabulary level' }}</p><p class="text-xs text-base-content/60">{{ session.started_at ? new Date(session.started_at).toLocaleString() : '' }}</p></div>
              <span class="badge" :class="session.completed_at ? 'badge-success' : 'badge-warning'">{{ session.completed_at ? `${session.score_percent}%` : 'In progress' }}</span>
            </div>
          </div>
          <p v-else class="text-base-content/60">No practice sessions yet.</p>
        <div class="card-actions justify-end"><button class="btn btn-sm btn-outline" @click="router.push('/student/review')">Open Review</button></div>
      </div></div>
    </template>
  </div>
</template>
