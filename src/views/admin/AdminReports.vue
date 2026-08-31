<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/authStore'
import { useReportStore } from '@/stores/reportStore'
import { 
  ChartBarIcon, 
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  UsersIcon,
  AcademicCapIcon,
  TrophyIcon,
  ClockIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const reportStore = useReportStore()

const platformMetrics = computed(() => {
  const report = reportStore.adminReport
  return {
    totalUsers: (report?.user_counts_by_role || []).reduce((sum, item) => sum + Number(item.user_count || 0), 0),
    activeUsers: null,
    totalClasses: report?.class_counts?.total_classes ?? 0,
    completedSessions: report?.practice_activity?.total_practice_sessions ?? 0,
    averageSessionTime: report?.average_session_duration_unit === 'seconds'
      ? Math.round(Number(report.average_session_duration || 0) / 60 * 10) / 10
      : report?.average_session_duration ?? 0,
    totalXPEarned: report?.total_xp ?? 0,
    vocabularyWordsLearned: report?.vocabulary_usage?.total_vocabulary_words ?? 0,
    speechRecognitionSessions: null,
  }
})

const usageData = computed(() => (reportStore.adminReport?.usage_timeseries || []).map((item) => ({
  period: item.date,
  users: item.new_users,
  sessions: item.practice_sessions,
  avgTime: 'N/A',
})))

// Class performance data
const classPerformance = computed(() => (reportStore.adminReport?.class_performance || []).map((item) => ({
  id: item.class_id,
  name: item.class_name,
  teacher: item.teacher_name,
  students: item.enrolled_students,
  completion: item.average_mastery,
  sessions: item.practice_sessions,
  avgScore: item.average_practice_score,
})))

// Teacher performance data
const teacherPerformance = computed(() => (reportStore.adminReport?.teacher_rankings || []).map((item) => ({
  id: item.teacher_id,
  name: item.name,
  students: item.student_count,
  classes: item.class_count,
  avgProgress: item.average_mastery,
})))

// Feature usage statistics
const featureUsage = computed(() => {
  const rows = reportStore.adminReport?.feature_usage || []
  const total = rows.reduce((sum, item) => sum + Number(item.count || 0), 0)
  return rows.map((item) => ({
    feature: item.feature === 'learning_progress_attempts' ? 'Learning Progress Attempts' : 'Practice Sessions',
    usage: item.count,
    percentage: total ? Math.round(Number(item.count || 0) / total * 100) : 0,
  }))
})

// System performance metrics
const systemMetrics = computed(() => {
  const report = reportStore.adminReport
  return [
    { metric: 'Average Mastery', value: `${report?.average_mastery ?? 0}%`, status: 'normal' },
    { metric: 'Student Enrollments', value: report?.class_counts?.total_students_enrolled ?? 0, status: 'normal' },
    { metric: 'Mastered Words', value: report?.completion_metrics?.mastered_words ?? 0, status: 'normal' },
    { metric: 'Unmastered Words', value: report?.completion_metrics?.unmastered_words ?? 0, status: 'normal' },
  ]
})

onMounted(async () => {
  if (authStore.userRole !== 'admin') return
  try { await reportStore.fetchAdminReport() }
  catch { toast.error(reportStore.error || 'Unable to load report.') }
})
watch(() => authStore.userRole, (role) => {
  if (role !== 'admin') reportStore.reset()
})

const goBack = () => {
  router.push('/admin')
}

const exportReport = (type) => {
  const timestamp = new Date().toISOString().split('T')[0]
  
  let reportContent = []
  
  switch (type) {
    case 'platform':
      reportContent = [
        'Platform Metrics Report',
        '====================',
        `Generated: ${timestamp}`,
        '',
        `Total Users: ${platformMetrics.value.totalUsers}`,
        `Total Classes: ${platformMetrics.value.totalClasses}`,
        `Practice Sessions: ${platformMetrics.value.completedSessions}`,
        `Vocabulary Words: ${platformMetrics.value.vocabularyWordsLearned}`,
        `Average Mastery: ${reportStore.adminReport?.average_mastery ?? 0}%`
      ]
      break
    case 'classes':
      reportContent = [
        'Class Performance Report',
        '========================',
        `Generated: ${timestamp}`,
        '',
        'Class Performance Data:',
        ...classPerformance.value.map(cls => 
          `${cls.name}: ${cls.students} students, ${cls.completion}% completion, ${cls.avgScore}% avg score`
        )
      ]
      break
    case 'teachers':
      reportContent = [
        'Teacher Performance Report',
        '==========================',
        `Generated: ${timestamp}`,
        '',
        'Teacher Performance Data:',
        ...teacherPerformance.value.map(teacher => 
          `${teacher.name}: ${teacher.students} students, ${teacher.classes} classes, ${teacher.avgProgress}% avg mastery`
        )
      ]
      break
  }
  
  const blob = new Blob([reportContent.join('\n')], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${type}-report-${timestamp}.txt`
  a.click()
  window.URL.revokeObjectURL(url)
}

const getPerformanceColor = (value, type = 'percentage') => {
  if (type === 'percentage') {
    if (value >= 90) return 'text-success'
    if (value >= 75) return 'text-warning'
    return 'text-error'
  }
  return 'text-info'
}

const getStatusColor = (status) => {
  switch (status) {
    case 'excellent': return 'badge-success'
    case 'good': return 'badge-info'
    case 'normal': return 'badge-neutral'
    case 'warning': return 'badge-warning'
    case 'critical': return 'badge-error'
    default: return 'badge-neutral'
  }
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">System Reports & Analytics</h1>
        <p class="text-base-content/70">Comprehensive platform performance and usage analytics</p>
      </div>
      <div class="flex gap-2">
        <button @click="exportReport('platform')" class="btn btn-outline gap-2">
          <ArrowDownTrayIcon class="w-4 h-4" />
          Export All
        </button>
        <button @click="goBack" class="btn btn-ghost gap-2">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Dashboard
        </button>
      </div>
    </div>

    <div v-if="reportStore.loading" class="text-center py-8"><span class="loading loading-spinner loading-lg"></span></div>
    <div v-else-if="reportStore.error && !reportStore.adminReport" class="alert alert-error"><span>{{ reportStore.error }}</span></div>

    <!-- Platform Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="stat bg-primary text-primary-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UsersIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-primary-content/80">Total Users</div>
        <div class="stat-value">{{ platformMetrics.totalUsers }}</div>
        <div class="stat-desc text-primary-content/60">Active-user count unavailable</div>
      </div>

      <div class="stat bg-secondary text-secondary-content shadow-md rounded-lg">
        <div class="stat-figure">
          <AcademicCapIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-secondary-content/80">Total Sessions</div>
        <div class="stat-value">{{ platformMetrics.completedSessions.toLocaleString() }}</div>
        <div class="stat-desc text-secondary-content/60">Learning sessions</div>
      </div>

      <div class="stat bg-accent text-accent-content shadow-md rounded-lg">
        <div class="stat-figure">
          <ClockIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-accent-content/80">Avg Session</div>
        <div class="stat-value">{{ platformMetrics.averageSessionTime ?? '—' }}</div>
        <div class="stat-desc text-accent-content/60">Session duration unavailable</div>
      </div>

      <div class="stat bg-success text-success-content shadow-md rounded-lg">
        <div class="stat-figure">
          <TrophyIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-success-content/80">Total XP</div>
        <div class="stat-value text-lg">{{ platformMetrics.totalXPEarned ?? '—' }}</div>
        <div class="stat-desc text-success-content/60">Platform XP unavailable</div>
      </div>
    </div>

    <!-- Usage Analytics -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="flex items-center justify-between mb-4">
          <h2 class="card-title">Usage Analytics</h2>
          <button @click="exportReport('usage')" :disabled="!usageData.length" class="btn btn-sm btn-outline gap-2">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Period</th>
                <th>Active Users</th>
                <th>Total Sessions</th>
                <th>Avg Session Time</th>
                <th>Growth</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(data, index) in usageData" :key="data.period">
                <td class="font-semibold">{{ data.period }}</td>
                <td>{{ data.users }}</td>
                <td>{{ data.sessions }}</td>
                <td>{{ data.avgTime }} minutes</td>
                <td>
                  <div class="badge" :class="{
                    'badge-success': index === 0 && data.users > usageData[1]?.users,
                    'badge-warning': index === 0 && data.users === usageData[1]?.users,
                    'badge-error': index === 0 && data.users < usageData[1]?.users,
                    'badge-neutral': index > 0
                  }">
                    <span v-if="index === 0 && usageData[1]">
                      {{ data.users > usageData[1].users ? '+' : data.users < usageData[1].users ? '-' : '=' }}{{ Math.abs(data.users - usageData[1].users) }}
                    </span>
                    <span v-else>-</span>
                  </div>
                </td>
              </tr>
              <tr v-if="!usageData.length"><td colspan="5" class="text-center text-base-content/60">Time-series usage analytics are not available from the report API.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Class and Teacher Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Class Performance -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <div class="flex items-center justify-between mb-4">
            <h2 class="card-title">Class Performance</h2>
            <button @click="exportReport('classes')" :disabled="!classPerformance.length" class="btn btn-sm btn-outline gap-2">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Export
            </button>
          </div>
          <div class="space-y-4">
            <div v-for="cls in classPerformance" :key="cls.id"
                 class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
              <div class="flex-1">
                <h3 class="font-semibold">{{ cls.name }}</h3>
                <p class="text-sm text-base-content/70">{{ cls.students }} students · {{ cls.teacher }}</p>
              </div>
              <div class="text-right">
                <div class="flex items-center gap-2">
                  <progress class="progress progress-primary w-20" :value="cls.completion" max="100"></progress>
                  <span class="text-sm font-bold" :class="getPerformanceColor(cls.completion)">
                    {{ cls.completion }}%
                  </span>
                </div>
                <p class="text-xs text-base-content/60">{{ cls.sessions }} practice sessions · {{ cls.avgScore }}% avg score</p>
              </div>
            </div>
            <p v-if="!classPerformance.length" class="text-base-content/60">Class performance is not available from the admin report API.</p>
          </div>
        </div>
      </div>

      <!-- Teacher Performance -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <div class="flex items-center justify-between mb-4">
            <h2 class="card-title">Teacher Performance</h2>
            <button @click="exportReport('teachers')" :disabled="!teacherPerformance.length" class="btn btn-sm btn-outline gap-2">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Export
            </button>
          </div>
          <div class="space-y-4">
            <div v-for="teacher in teacherPerformance" :key="teacher.id"
                 class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
              <div class="flex-1">
                <h3 class="font-semibold">{{ teacher.name }}</h3>
                <p class="text-sm text-base-content/70">{{ teacher.students }} students, {{ teacher.classes }} classes</p>
              </div>
              <div class="text-right">
                <div class="flex items-center gap-2">
                  <progress class="progress progress-secondary w-20" :value="teacher.avgProgress" max="100"></progress>
                  <span class="text-sm font-bold" :class="getPerformanceColor(teacher.avgProgress)">
                    {{ teacher.avgProgress }}%
                  </span>
                </div>
                <p class="text-xs text-base-content/60">{{ teacher.classes }} classes</p>
              </div>
            </div>
            <p v-if="!teacherPerformance.length" class="text-base-content/60">Teacher rankings are not available from the report API.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Feature Usage & System Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Feature Usage -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title">Feature Usage Statistics</h2>
          <div class="space-y-3">
            <div v-for="feature in featureUsage" :key="feature.feature" class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">{{ feature.feature }}</span>
                <div class="flex items-center gap-2">
                  <span class="text-sm">{{ feature.usage.toLocaleString() }}</span>
                  <span class="text-xs text-base-content/60">({{ feature.percentage }}%)</span>
                </div>
              </div>
              <progress class="progress progress-primary" :value="feature.percentage" max="100"></progress>
            </div>
            <p v-if="!featureUsage.length" class="text-base-content/60">Per-feature usage is not available from the report API.</p>
          </div>
        </div>
      </div>

      <!-- System Metrics -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title">System Performance</h2>
          <div class="space-y-3">
            <div v-for="metric in systemMetrics" :key="metric.metric" 
                 class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
              <span class="font-medium">{{ metric.metric }}</span>
              <div class="flex items-center gap-2">
                <span class="font-bold">{{ metric.value }}</span>
                <div class="badge badge-sm" :class="getStatusColor(metric.status)">
                  {{ metric.status }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Insights -->
    <div class="card bg-info text-info-content shadow-md">
      <div class="card-body">
        <h2 class="card-title">📊 Key Insights & Recommendations</h2>
        <p>Automated insights and recommendations are not available from the report API.</p>
      </div>
    </div>
  </div>
</template>
