<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useStudentStore } from '@/stores/studentStore'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { 
  ArrowLeftIcon, 
  ChartBarIcon, 
  AcademicCapIcon, 
  UserGroupIcon,
  TrophyIcon,
  ArrowDownTrayIcon 
} from '@heroicons/vue/24/outline'

const router = useRouter()
const studentStore = useStudentStore()
const vocabularyStore = useVocabularyStore()

// Mock report data
const reportData = ref({
  totalClasses: 4,
  totalStudents: 42,
  totalVocabularyLevels: vocabularyStore.levels.length,
  averageClassProgress: 75,
  mostActiveStudents: [
    { name: 'Ana Martinez', xp: 420, progress: 95 },
    { name: 'Maria Garcia', xp: 340, progress: 88 },
    { name: 'Carlos Rodriguez', xp: 280, progress: 73 }
  ],
  classPerformance: [
    { name: 'Spanish Basics', students: 15, avgProgress: 85, completionRate: 92 },
    { name: 'Advanced Conversation', students: 8, avgProgress: 92, completionRate: 87 },
    { name: 'Intermediate Spanish', students: 12, avgProgress: 67, completionRate: 75 },
    { name: 'Grammar Focus', students: 7, avgProgress: 58, completionRate: 64 }
  ],
  vocabularyStats: [
    { level: 'Pets', totalWords: 4, avgMastery: 88 },
    { level: 'Colors', totalWords: 4, avgMastery: 82 },
    { level: 'Family', totalWords: 4, avgMastery: 76 }
  ]
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
    'Top Students:',
    ...reportData.value.mostActiveStudents.map((student, index) => 
      `${index + 1}. ${student.name}: ${student.xp} XP (${student.progress}%)`
    )
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
                <th>Completion Rate</th>
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
            :key="student.name"
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
            :key="vocab.level"
            class="flex items-center justify-between p-4 bg-base-200 rounded-lg"
          >
            <div>
              <h3 class="font-semibold">{{ vocab.level }}</h3>
              <p class="text-sm text-base-content/70">{{ vocab.totalWords }} words</p>
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
        </div>
      </div>
    </div>

    <!-- Recommendations -->
    <div class="card bg-warning text-warning-content shadow-md">
      <div class="card-body">
        <h2 class="card-title">📊 Recommendations</h2>
        <ul class="list-disc list-inside space-y-2">
          <li>Focus on improving "Grammar Focus" class - consider additional support materials</li>
          <li>Recognize top performers Ana, Maria, and Carlos for their excellent progress</li>
          <li>Consider creating more vocabulary levels as students are progressing well</li>
          <li>Family vocabulary level needs more practice exercises</li>
        </ul>
      </div>
    </div>
  </div>
</template>
