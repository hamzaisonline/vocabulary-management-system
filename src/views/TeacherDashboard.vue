<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { AcademicCapIcon, BookOpenIcon, ChartBarIcon, PlusIcon, UserGroupIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/authStore'
import { useDashboardStore } from '@/stores/dashboardStore'
import DashboardMetricCard from '@/components/dashboard/DashboardMetricCard.vue'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const dashboardStore = useDashboardStore()
const dashboard = computed(() => dashboardStore.teacherDashboard)

onMounted(async () => {
  if (authStore.userRole !== 'teacher') return
  try { await dashboardStore.fetchTeacherDashboard() }
  catch { toast.error(dashboardStore.error || 'Unable to load dashboard.') }
})
watch(() => authStore.userRole, (role) => {
  if (role !== 'teacher') dashboardStore.reset()
})
</script>

<template>
  <div class="p-4 sm:p-6 space-y-6 overflow-x-hidden">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0"><h1 class="break-words text-2xl font-bold text-primary sm:text-3xl">Welcome back, {{ authStore.user?.name || 'Teacher' }}!</h1><p class="text-base-content/70">Your classes and student activity</p></div>
      <button class="btn btn-primary gap-2" @click="router.push('/teacher/classes/create')"><PlusIcon class="w-5 h-5" /> Create Class</button>
    </div>
    <div v-if="dashboardStore.loading" class="text-center py-12"><span class="loading loading-spinner loading-lg"></span></div>
    <div v-else-if="dashboardStore.error && !dashboard" class="alert alert-error"><span>{{ dashboardStore.error }}</span></div>
    <template v-else-if="dashboard">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <DashboardMetricCard title="Classes" :value="dashboard.total_classes" description="Currently teaching" :icon="AcademicCapIcon" card-class="bg-info text-info-content" />
        <DashboardMetricCard title="Students" :value="dashboard.total_enrolled_students" description="Across your classes" :icon="UserGroupIcon" card-class="bg-primary text-primary-content" />
        <DashboardMetricCard title="Vocabulary Levels" :value="dashboard.total_assigned_vocabulary_levels" description="Assigned to classes" :icon="BookOpenIcon" card-class="bg-accent text-accent-content" />
        <DashboardMetricCard title="Average Progress" :value="`${dashboard.average_student_progress}%`" description="Student mastery" :icon="ChartBarIcon" card-class="bg-success text-success-content" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card bg-base-100 shadow-md"><div class="card-body">
          <div class="flex flex-wrap justify-between gap-2"><h2 class="card-title">Class Performance</h2><button class="btn btn-sm btn-outline" @click="router.push('/teacher/classes')">View All</button></div>
          <div v-if="dashboard.class_summaries.length" class="space-y-3">
            <button v-for="item in dashboard.class_summaries" :key="item.class_id" class="w-full text-left p-4 bg-base-200 rounded-lg" @click="router.push(`/teacher/classes/${item.class_id}`)">
              <div class="flex min-w-0 justify-between gap-3"><span class="min-w-0 break-words font-semibold">{{ item.name }}</span><span class="shrink-0">{{ item.average_mastery }}%</span></div>
              <progress class="progress progress-primary w-full" :value="item.average_mastery" max="100"></progress>
              <p class="text-sm text-base-content/70">{{ item.student_count }} students · {{ item.assigned_vocabulary_level_count }} levels · {{ item.mastered_words }} mastered</p>
            </button>
          </div>
          <p v-else class="text-base-content/60">No classes yet.</p>
        </div></div>

        <div class="card bg-base-100 shadow-md"><div class="card-body">
          <h2 class="card-title">Recent Practice</h2>
          <div v-if="dashboard.recent_practice_activity.length" class="space-y-3">
            <div v-for="activity in dashboard.recent_practice_activity" :key="activity.id" class="p-3 bg-base-200 rounded-lg">
              <p class="font-medium">{{ activity.student_name || 'Student' }}</p><p class="text-sm">{{ activity.level_title || 'Vocabulary' }} · {{ activity.score_percent }}%</p><p class="text-xs text-base-content/60">{{ activity.started_at ? new Date(activity.started_at).toLocaleString() : '' }}</p>
            </div>
          </div>
          <p v-else class="text-base-content/60">No recent practice activity.</p>
        </div></div>
      </div>
    </template>
  </div>
</template>
