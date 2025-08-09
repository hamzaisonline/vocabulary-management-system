<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
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

// Report data
const platformMetrics = ref({
  totalUsers: 260,
  activeUsers: 187,
  totalClasses: 12,
  completedSessions: 1247,
  averageSessionTime: 24,
  totalXPEarned: 45780,
  vocabularyWordsLearned: 12450,
  speechRecognitionSessions: 892
})

// Usage analytics
const usageData = ref([
  { period: 'This Week', users: 156, sessions: 423, avgTime: 26 },
  { period: 'Last Week', users: 142, sessions: 387, avgTime: 22 },
  { period: 'This Month', users: 187, sessions: 1247, avgTime: 24 },
  { period: 'Last Month', users: 168, sessions: 1156, avgTime: 21 }
])

// Class performance data
const classPerformance = ref([
  { name: 'Spanish Basics', students: 68, completion: 89, avgScore: 87, totalXP: 12340 },
  { name: 'Intermediate Spanish', students: 45, completion: 76, avgScore: 82, totalXP: 8920 },
  { name: 'Advanced Conversation', students: 32, completion: 94, avgScore: 91, totalXP: 9870 },
  { name: 'French Fundamentals', students: 28, completion: 81, avgScore: 85, totalXP: 6780 },
  { name: 'German Basics', students: 23, completion: 73, avgScore: 79, totalXP: 5210 }
])

// Teacher performance data
const teacherPerformance = ref([
  { name: 'Sarah Johnson', classes: 3, students: 78, avgProgress: 88, satisfaction: 4.8 },
  { name: 'Michael Chen', classes: 2, students: 52, avgProgress: 85, satisfaction: 4.7 },
  { name: 'Elena Rodriguez', classes: 2, students: 45, avgProgress: 90, satisfaction: 4.9 },
  { name: 'David Brown', classes: 1, students: 28, avgProgress: 82, satisfaction: 4.6 },
  { name: 'Maria Santos', classes: 1, students: 23, avgProgress: 87, satisfaction: 4.8 }
])

// Feature usage statistics
const featureUsage = ref([
  { feature: 'Multiple Choice', usage: 2340, percentage: 34 },
  { feature: 'Audio Recognition', usage: 1890, percentage: 27 },
  { feature: 'Speech Recognition', usage: 1234, percentage: 18 },
  { feature: 'Sentence Builder', usage: 892, percentage: 13 },
  { feature: 'Word Matching', usage: 567, percentage: 8 }
])

// System performance metrics
const systemMetrics = ref([
  { metric: 'Average Response Time', value: '180ms', status: 'good' },
  { metric: 'Uptime', value: '99.8%', status: 'excellent' },
  { metric: 'Error Rate', value: '0.2%', status: 'good' },
  { metric: 'Active Connections', value: '156', status: 'normal' },
  { metric: 'Data Storage Used', value: '2.4TB', status: 'normal' },
  { metric: 'Bandwidth Usage', value: '145GB', status: 'normal' }
])

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
        `Active Users: ${platformMetrics.value.activeUsers}`,
        `Total Classes: ${platformMetrics.value.totalClasses}`,
        `Completed Sessions: ${platformMetrics.value.completedSessions}`,
        `Average Session Time: ${platformMetrics.value.averageSessionTime} minutes`,
        `Total XP Earned: ${platformMetrics.value.totalXPEarned}`,
        `Vocabulary Words Learned: ${platformMetrics.value.vocabularyWordsLearned}`,
        `Speech Recognition Sessions: ${platformMetrics.value.speechRecognitionSessions}`
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
          `${teacher.name}: ${teacher.students} students, ${teacher.avgProgress}% avg progress, ${teacher.satisfaction}/5 satisfaction`
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

    <!-- Platform Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="stat bg-primary text-primary-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UsersIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-primary-content/80">Total Users</div>
        <div class="stat-value">{{ platformMetrics.totalUsers }}</div>
        <div class="stat-desc text-primary-content/60">{{ platformMetrics.activeUsers }} active</div>
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
        <div class="stat-value">{{ platformMetrics.averageSessionTime }}m</div>
        <div class="stat-desc text-accent-content/60">minutes per session</div>
      </div>

      <div class="stat bg-success text-success-content shadow-md rounded-lg">
        <div class="stat-figure">
          <TrophyIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-success-content/80">Total XP</div>
        <div class="stat-value text-lg">{{ platformMetrics.totalXPEarned.toLocaleString() }}</div>
        <div class="stat-desc text-success-content/60">Experience earned</div>
      </div>
    </div>

    <!-- Usage Analytics -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="flex items-center justify-between mb-4">
          <h2 class="card-title">Usage Analytics</h2>
          <button @click="exportReport('usage')" class="btn btn-sm btn-outline gap-2">
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
            <button @click="exportReport('classes')" class="btn btn-sm btn-outline gap-2">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Export
            </button>
          </div>
          <div class="space-y-4">
            <div v-for="cls in classPerformance" :key="cls.name" 
                 class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
              <div class="flex-1">
                <h3 class="font-semibold">{{ cls.name }}</h3>
                <p class="text-sm text-base-content/70">{{ cls.students }} students</p>
              </div>
              <div class="text-right">
                <div class="flex items-center gap-2">
                  <progress class="progress progress-primary w-20" :value="cls.completion" max="100"></progress>
                  <span class="text-sm font-bold" :class="getPerformanceColor(cls.completion)">
                    {{ cls.completion }}%
                  </span>
                </div>
                <p class="text-xs text-base-content/60">{{ cls.totalXP }} XP total</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Teacher Performance -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <div class="flex items-center justify-between mb-4">
            <h2 class="card-title">Teacher Performance</h2>
            <button @click="exportReport('teachers')" class="btn btn-sm btn-outline gap-2">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Export
            </button>
          </div>
          <div class="space-y-4">
            <div v-for="teacher in teacherPerformance" :key="teacher.name" 
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
                <p class="text-xs text-base-content/60">{{ teacher.satisfaction }}/5 ⭐</p>
              </div>
            </div>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
          <div class="bg-info-content/10 p-4 rounded-lg">
            <h3 class="font-bold">User Engagement</h3>
            <p class="text-sm opacity-90">87% of users are actively engaged. Consider gamification features to boost retention.</p>
          </div>
          <div class="bg-info-content/10 p-4 rounded-lg">
            <h3 class="font-bold">Speech Recognition</h3>
            <p class="text-sm opacity-90">Speech feature usage is growing. Invest in audio infrastructure improvements.</p>
          </div>
          <div class="bg-info-content/10 p-4 rounded-lg">
            <h3 class="font-bold">Teacher Support</h3>
            <p class="text-sm opacity-90">Top teachers achieve 90%+ student progress. Share best practices across platform.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
