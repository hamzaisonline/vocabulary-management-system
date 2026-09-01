<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeftIcon, ChartBarIcon, BookOpenIcon, TrophyIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/authStore'
import { useReportStore } from '@/stores/reportStore'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const reportStore = useReportStore()
const report = computed(() => reportStore.studentReport)

onMounted(async () => {
  if (authStore.userRole !== 'student') return
  try { await reportStore.fetchStudentReport() }
  catch { toast.error(reportStore.error || 'Unable to load report.') }
})
watch(() => authStore.userRole, (role) => {
  if (role !== 'student') reportStore.reset()
})
</script>

<template>
  <div class="min-w-0 space-y-4 p-0 sm:space-y-6 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div><h1 class="text-2xl font-bold text-primary sm:text-3xl">Performance Report</h1><p class="text-base-content/70">Your vocabulary learning results</p></div>
      <button class="btn btn-ghost gap-2" @click="router.push('/student')"><ArrowLeftIcon class="w-4 h-4" /> Back to Dashboard</button>
    </div>

    <div v-if="reportStore.loading" class="text-center py-8"><span class="loading loading-spinner loading-lg"></span></div>
    <div v-else-if="reportStore.error && !report" class="alert alert-error"><span>{{ reportStore.error }}</span></div>

    <template v-else-if="report">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="stat bg-primary text-primary-content shadow-md rounded-lg"><div class="stat-figure"><TrophyIcon class="w-8 h-8" /></div><div class="stat-title text-primary-content/80">Total XP</div><div class="stat-value">{{ report.total_xp }}</div><div class="stat-desc text-primary-content/60">Experience earned</div></div>
        <div class="stat bg-secondary text-secondary-content shadow-md rounded-lg"><div class="stat-figure"><ChartBarIcon class="w-8 h-8" /></div><div class="stat-title text-secondary-content/80">Average Mastery</div><div class="stat-value">{{ report.average_mastery }}%</div><div class="stat-desc text-secondary-content/60">Across vocabulary</div></div>
        <div class="stat bg-accent text-accent-content shadow-md rounded-lg"><div class="stat-figure"><BookOpenIcon class="w-8 h-8" /></div><div class="stat-title text-accent-content/80">Completed Levels</div><div class="stat-value">{{ report.completed_levels }}</div><div class="stat-desc text-accent-content/60">of {{ report.current_levels }} accessible</div></div>
        <div class="stat bg-success text-success-content shadow-md rounded-lg"><div class="stat-figure"><TrophyIcon class="w-8 h-8" /></div><div class="stat-title text-success-content/80">Practice Sessions</div><div class="stat-value">{{ report.practice_statistics.total_sessions }}</div><div class="stat-desc text-success-content/60">Completed and active</div></div>
      </div>

      <div class="card bg-base-100 shadow-md"><div class="card-body">
        <h2 class="card-title">Practice & Mastery Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 bg-base-200 rounded-lg"><p class="text-sm text-base-content/70">Average Practice Score</p><p class="text-2xl font-bold">{{ report.practice_statistics.average_score }}%</p></div>
          <div class="p-4 bg-base-200 rounded-lg"><p class="text-sm text-base-content/70">Mastered Words</p><p class="text-2xl font-bold text-success">{{ report.practice_statistics.mastered_words }}</p></div>
          <div class="p-4 bg-base-200 rounded-lg"><p class="text-sm text-base-content/70">Unmastered Words</p><p class="text-2xl font-bold text-warning">{{ report.practice_statistics.unmastered_words }}</p></div>
        </div>
      </div></div>
    </template>
  </div>
</template>
