<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useStudentStore } from '@/stores/studentStore'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import {
  PresentationChartBarIcon,
  Square3Stack3DIcon,
  UserGroupIcon,
  UsersIcon,
  WrenchScrewdriverIcon,
  ChartBarIcon,
  TrophyIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  AcademicCapIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  PlusIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const studentStore = useStudentStore()
const vocabularyStore = useVocabularyStore()

// System-wide statistics
const systemStats = ref({
  totalClasses: 12,
  totalStudents: 230,
  totalTeachers: 15,
  totalVocabularyWords: vocabularyStore.levels.reduce((total, level) => total + level.words.length, 0),
  activeUsers: 187,
  systemUptime: '99.8%',
  avgSessionTime: '24 mins',
  totalXPEarned: 45780
})

// Recent activity across the platform
const recentSystemActivity = ref([
  { id: 1, type: 'user_signup', message: 'New teacher Maria Rodriguez registered', time: '5 mins ago', icon: '👩‍🏫' },
  { id: 2, type: 'class_created', message: 'Class "Advanced French" created by John Smith', time: '12 mins ago', icon: '📚' },
  { id: 3, type: 'milestone', message: '50 students completed "Spanish Basics" level', time: '1 hour ago', icon: '🎉' },
  { id: 4, type: 'alert', message: 'Server maintenance scheduled for tomorrow', time: '2 hours ago', icon: '⚠️' },
  { id: 5, type: 'achievement', message: 'Platform reached 10,000 total XP milestone', time: '3 hours ago', icon: '🏆' }
])

// Platform alerts and notifications
const systemAlerts = ref([
  { id: 1, type: 'warning', title: 'Low Storage Space', message: 'Audio files storage at 85% capacity', priority: 'medium' },
  { id: 2, type: 'info', title: 'Scheduled Maintenance', message: 'System update planned for this weekend', priority: 'low' },
  { id: 3, type: 'success', title: 'Backup Complete', message: 'Daily backup completed successfully', priority: 'low' }
])

// Performance metrics
const performanceMetrics = ref([
  { name: 'Student Engagement', value: 87, trend: 'up', color: 'text-success' },
  { name: 'Teacher Activity', value: 92, trend: 'up', color: 'text-success' },
  { name: 'Vocabulary Completion', value: 74, trend: 'stable', color: 'text-warning' },
  { name: 'Platform Usage', value: 89, trend: 'up', color: 'text-success' }
])

// Top performing teachers
const topTeachers = ref([
  { name: 'Sarah Johnson', students: 45, avgProgress: 94, totalXP: 12500 },
  { name: 'Michael Chen', students: 38, avgProgress: 89, totalXP: 10200 },
  { name: 'Elena Rodriguez', students: 32, avgProgress: 91, totalXP: 9800 }
])

// System health indicators
const systemHealth = ref({
  status: 'healthy',
  uptime: '99.8%',
  responseTime: '180ms',
  errorRate: '0.2%',
  activeConnections: 156
})

const getTrendIcon = (trend) => {
  switch (trend) {
    case 'up': return ArrowTrendingUpIcon
    case 'down': return ArrowTrendingDownIcon
    default: return ChartBarIcon
  }
}

const getAlertColor = (type) => {
  switch (type) {
    case 'warning': return 'alert-warning'
    case 'error': return 'alert-error'
    case 'success': return 'alert-success'
    default: return 'alert-info'
  }
}

const navigateToManagement = (section) => {
  router.push(`/admin/${section}`)
}

const quickAction = (action) => {
  switch (action) {
    case 'create_teacher':
      router.push('/admin/teachers')
      break
    case 'create_class':
      router.push('/admin/classes')
      break
    case 'system_reports':
      router.push('/admin/reports')
      break
    case 'backup_data':
      // Trigger backup
      break
  }
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Welcome Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Admin Dashboard</h1>
        <p class="text-base-content/70">Platform overview and system management</p>
      </div>
      <div class="flex gap-2">
        <button @click="navigateToManagement('reports')" class="btn btn-outline gap-2">
          <ChartBarIcon class="w-4 h-4" />
          View Reports
        </button>
        <router-link to="/admin/settings" class="btn btn-primary gap-2">
          <WrenchScrewdriverIcon class="w-4 h-4" />
          Settings
        </router-link>
      </div>
    </div>

    <!-- System Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="stat bg-primary text-primary-content shadow-md rounded-lg">
        <div class="stat-figure">
          <AcademicCapIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-primary-content/80">Total Classes</div>
        <div class="stat-value">{{ systemStats.totalClasses }}</div>
        <div class="stat-desc text-primary-content/60">Active learning programs</div>
      </div>

      <div class="stat bg-secondary text-secondary-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-secondary-content/80">Total Students</div>
        <div class="stat-value">{{ systemStats.totalStudents }}</div>
        <div class="stat-desc text-secondary-content/60">{{ systemStats.activeUsers }} currently active</div>
      </div>

      <div class="stat bg-accent text-accent-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UsersIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-accent-content/80">Total Teachers</div>
        <div class="stat-value">{{ systemStats.totalTeachers }}</div>
        <div class="stat-desc text-accent-content/60">Educators on platform</div>
      </div>

      <div class="stat bg-success text-success-content shadow-md rounded-lg">
        <div class="stat-figure">
          <TrophyIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-success-content/80">Total XP</div>
        <div class="stat-value text-lg">{{ systemStats.totalXPEarned.toLocaleString() }}</div>
        <div class="stat-desc text-success-content/60">Platform-wide achievements</div>
      </div>
    </div>

    <!-- System Health & Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- System Health -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title">System Health</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span>Status</span>
              <div class="badge badge-success">{{ systemHealth.status }}</div>
            </div>
            <div class="flex items-center justify-between">
              <span>Uptime</span>
              <span class="font-bold">{{ systemHealth.uptime }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Response Time</span>
              <span class="font-bold">{{ systemHealth.responseTime }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Error Rate</span>
              <span class="font-bold">{{ systemHealth.errorRate }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Active Users</span>
              <span class="font-bold">{{ systemHealth.activeConnections }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Performance Metrics -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title">Performance Metrics</h2>
          <div class="space-y-4">
            <div v-for="metric in performanceMetrics" :key="metric.name" class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">{{ metric.name }}</span>
                <div class="flex items-center gap-2">
                  <component :is="getTrendIcon(metric.trend)" class="w-4 h-4" :class="metric.color" />
                  <span class="font-bold">{{ metric.value }}%</span>
                </div>
              </div>
              <progress class="progress progress-primary" :value="metric.value" max="100"></progress>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Management Quick Actions -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button @click="navigateToManagement('classes')" class="btn btn-outline gap-2 justify-start">
            <AcademicCapIcon class="w-5 h-5" />
            Manage Classes
          </button>
          <button @click="navigateToManagement('students')" class="btn btn-outline gap-2 justify-start">
            <UserGroupIcon class="w-5 h-5" />
            Manage Students
          </button>
          <button @click="navigateToManagement('teachers')" class="btn btn-outline gap-2 justify-start">
            <UsersIcon class="w-5 h-5" />
            Manage Teachers
          </button>
          <button @click="navigateToManagement('words')" class="btn btn-outline gap-2 justify-start">
            <Square3Stack3DIcon class="w-5 h-5" />
            Vocabulary Management
          </button>
        </div>
      </div>
    </div>

    <!-- Activity and Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Activity -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title">Recent System Activity</h2>
          <div class="space-y-3">
            <div v-for="activity in recentSystemActivity.slice(0, 5)" :key="activity.id" 
                 class="flex items-start gap-3 p-3 bg-base-200 rounded-lg">
              <span class="text-xl">{{ activity.icon }}</span>
              <div class="flex-1">
                <p class="text-sm font-medium">{{ activity.message }}</p>
                <p class="text-xs text-base-content/60">{{ activity.time }}</p>
              </div>
            </div>
          </div>
          <div class="card-actions justify-end mt-4">
            <button @click="navigateToManagement('activity')" class="btn btn-sm btn-outline">
              View All Activity
            </button>
          </div>
        </div>
      </div>

      <!-- System Alerts -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title">System Alerts</h2>
          <div class="space-y-3">
            <div v-for="alert in systemAlerts" :key="alert.id" 
                 class="alert alert-sm" :class="getAlertColor(alert.type)">
              <ExclamationTriangleIcon class="w-4 h-4" />
              <div>
                <div class="font-bold">{{ alert.title }}</div>
                <div class="text-xs">{{ alert.message }}</div>
              </div>
            </div>
          </div>
          <div class="card-actions justify-end mt-4">
            <button @click="navigateToManagement('alerts')" class="btn btn-sm btn-outline">
              Manage Alerts
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Performers -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Top Performing Teachers</h2>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Teacher</th>
                <th>Students</th>
                <th>Avg Progress</th>
                <th>Total XP Generated</th>
                <th>Performance</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="teacher in topTeachers" :key="teacher.name">
                <td class="font-semibold">{{ teacher.name }}</td>
                <td>{{ teacher.students }}</td>
                <td>
                  <div class="flex items-center gap-2">
                    <progress class="progress progress-primary w-20" :value="teacher.avgProgress" max="100"></progress>
                    <span class="text-sm">{{ teacher.avgProgress }}%</span>
                  </div>
                </td>
                <td>{{ teacher.totalXP.toLocaleString() }} XP</td>
                <td>
                  <div class="badge" :class="{
                    'badge-success': teacher.avgProgress >= 90,
                    'badge-warning': teacher.avgProgress >= 75,
                    'badge-error': teacher.avgProgress < 75
                  }">
                    {{ teacher.avgProgress >= 90 ? 'Excellent' : teacher.avgProgress >= 75 ? 'Good' : 'Needs Support' }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- System Statistics Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-info">
          <ClockIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Avg Session Time</div>
        <div class="stat-value text-info">{{ systemStats.avgSessionTime }}</div>
        <div class="stat-desc">Per student session</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-warning">
          <Square3Stack3DIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Vocabulary Words</div>
        <div class="stat-value text-warning">{{ systemStats.totalVocabularyWords }}</div>
        <div class="stat-desc">Across all levels</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-success">
          <ChartBarIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">System Uptime</div>
        <div class="stat-value text-success">{{ systemStats.systemUptime }}</div>
        <div class="stat-desc">This month</div>
      </div>
    </div>
  </div>
</template>
