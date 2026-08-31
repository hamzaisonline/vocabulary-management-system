<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/authStore'
import { useReportStore } from '@/stores/reportStore'
import { 
  ArrowLeftIcon, 
  ChartBarIcon, 
  AcademicCapIcon, 
  UserGroupIcon,
  TrophyIcon,
  ArrowDownTrayIcon 
} from '@heroicons/vue/24/outline'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const reportStore = useReportStore()

const reportData = computed(() => {
  const report = reportStore.teacherReport
  const classes = report?.class_performance || []
  const masteryRows = report?.average_mastery_per_class || []
  const average = masteryRows.length
    ? Math.round(masteryRows.reduce((sum, item) => sum + Number(item.average_mastery || 0), 0) / masteryRows.length)
    : 0
  return {
    totalClasses: classes.length,
    totalStudents: (report?.students_per_class || []).reduce((sum, item) => sum + Number(item.student_count || 0), 0),
    totalVocabularyLevels: '—',
    averageClassProgress: average,
    totalVocabularyLevels: report?.vocabulary_levels_count ?? 0,
    mostActiveStudents: (report?.top_students || []).map((student) => ({
      id: student.student_id,
      name: student.name,
      xp: student.total_xp,
      progress: student.average_mastery,
    })),
    classPerformance: classes.map((item) => ({
      name: item.class_name,
      students: item.student_count,
      avgProgress: item.average_mastery,
      completionRate: item.average_practice_score,
    })),
    vocabularyStats: (report?.vocabulary_level_stats || []).map((level) => ({
      id: level.level_id,
      level: level.title,
      totalWords: level.total_words,
      avgMastery: level.average_mastery,
      masteredWords: level.mastered_words,
      unmasteredWords: level.unmastered_words,
    })),
  }
})

onMounted(async () => {
  if (authStore.userRole !== 'teacher') return
  try { await reportStore.fetchTeacherReport() }
  catch { toast.error(reportStore.error || 'Unable to load report.') }
})
watch(() => authStore.userRole, (role) => {
  if (role !== 'teacher') reportStore.reset()
})

const goBack = () => {
  router.push('/teacher')
}

const exportReport = () => {
  const reportContent = [
    'Teacher Performance Report',
    '========================',
    '',
    `Total Classes: ${reportData.value.totalClasses}`,
    `Total Students: ${reportData.value.totalStudents}`,
    `Vocabulary Levels: ${reportData.value.totalVocabularyLevels}`,
    `Average Progress: ${reportData.value.averageClassProgress}%`,
    '',
    'Class Performance:',
    ...reportData.value.classPerformance.map(cls => 
      `- ${cls.name}: ${cls.avgProgress}% (${cls.students} students)`
    ),
    '',
    `Average Practice Score: ${reportStore.teacherReport?.average_practice_score ?? 0}%`
  ].join('\n')
  
  const blob = new Blob([reportContent], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'teacher-report.txt'
  a.click()
  window.URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Performance Reports</h1>
        <p class="text-base-content/70">Overview of student progress and class performance</p>
      </div>
      <div class="flex gap-2">
        <button @click="exportReport" class="btn btn-outline gap-2">
          <ArrowDownTrayIcon class="w-4 h-4" />
          Export Report
        </button>
        <button @click="goBack" class="btn btn-ghost gap-2">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Dashboard
        </button>
      </div>
    </div>

    <div v-if="reportStore.loading" class="text-center py-8"><span class="loading loading-spinner loading-lg"></span></div>
    <div v-else-if="reportStore.error && !reportStore.teacherReport" class="alert alert-error"><span>{{ reportStore.error }}</span></div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <AcademicCapIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Classes</div>
        <div class="stat-value text-primary">{{ reportData.totalClasses }}</div>
        <div class="stat-desc">Active classes</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Students</div>
        <div class="stat-value text-secondary">{{ reportData.totalStudents }}</div>
        <div class="stat-desc">Enrolled students</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-accent">
          <ChartBarIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Avg Progress</div>
        <div class="stat-value text-accent">{{ reportData.averageClassProgress }}%</div>
        <div class="stat-desc">Class completion</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-success">
          <TrophyIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Vocabulary Levels</div>
        <div class="stat-value text-success">{{ reportData.totalVocabularyLevels }}</div>
        <div class="stat-desc">Content created</div>
      </div>
    </div>

    <!-- Class Performance -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Class Performance Analysis</h2>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Class Name</th>
                <th>Students</th>
                <th>Avg Progress</th>
                <th>Avg Practice Score</th>
                <th>Performance</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cls in reportData.classPerformance" :key="cls.name">
                <td class="font-semibold">{{ cls.name }}</td>
                <td>{{ cls.students }}</td>
                <td>
                  <div class="flex items-center gap-2">
                    <progress 
                      class="progress progress-primary w-20" 
                      :value="cls.avgProgress" 
                      max="100"
                    ></progress>
                    <span class="text-sm">{{ cls.avgProgress }}%</span>
                  </div>
                </td>
                <td>{{ cls.completionRate }}%</td>
                <td>
                  <div class="badge" :class="{
                    'badge-success': cls.avgProgress >= 80,
                    'badge-warning': cls.avgProgress >= 60,
                    'badge-error': cls.avgProgress < 60
                  }">
                    {{ cls.avgProgress >= 80 ? 'Excellent' : cls.avgProgress >= 60 ? 'Good' : 'Needs Attention' }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Top Performing Students -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Top Performing Students</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div 
            v-for="(student, index) in reportData.mostActiveStudents" 
            :key="student.id"
            class="card bg-base-200 shadow-sm"
          >
            <div class="card-body">
              <div class="flex items-center gap-3">
                <div class="badge badge-lg" :class="{
                  'badge-warning': index === 0,
                  'badge-neutral': index === 1,
                  'badge-info': index === 2
                }">
                  {{ index + 1 }}
                </div>
                <div>
                  <h3 class="font-semibold">{{ student.name }}</h3>
                  <p class="text-sm text-base-content/70">{{ student.xp }} XP</p>
                </div>
              </div>
              <progress 
                class="progress progress-success mt-2" 
                :value="student.progress" 
                max="100"
              ></progress>
              <p class="text-xs text-center mt-1">{{ student.progress }}% Complete</p>
            </div>
          </div>
          <p v-if="!reportData.mostActiveStudents.length" class="text-base-content/60">Top-student ranking is not available from the report API.</p>
        </div>
      </div>
    </div>

    <!-- Vocabulary Statistics -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Vocabulary Level Statistics</h2>
        <div class="space-y-4">
          <div 
            v-for="vocab in reportData.vocabularyStats" 
            :key="vocab.id"
            class="flex items-center justify-between p-4 bg-base-200 rounded-lg"
          >
            <div>
              <h3 class="font-semibold">{{ vocab.level }}</h3>
              <p class="text-sm text-base-content/70">{{ vocab.totalWords }} words</p>
              <p class="text-xs text-base-content/60">{{ vocab.masteredWords }} mastered · {{ vocab.unmasteredWords }} unmastered</p>
            </div>
            <div class="flex items-center gap-3">
              <progress 
                class="progress progress-primary w-32" 
                :value="vocab.avgMastery" 
                max="100"
              ></progress>
              <span class="text-sm font-medium">{{ vocab.avgMastery }}%</span>
            </div>
          </div>
          <p v-if="!reportData.vocabularyStats.length" class="text-base-content/60">Per-level vocabulary statistics are not available from the report API.</p>
        </div>
      </div>
    </div>

    <!-- Recommendations -->
    <div class="card bg-warning text-warning-content shadow-md">
      <div class="card-body">
        <h2 class="card-title">📊 Recommendations</h2>
        <p>Automated recommendations are not available from the report API.</p>
      </div>
    </div>
  </div>
</template>
