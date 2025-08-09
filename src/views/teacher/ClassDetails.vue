<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStudentStore } from '@/stores/studentStore'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { useToast } from 'vue-toastification'
import { 
  PlusIcon, 
  TrashIcon, 
  EyeIcon, 
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  UserPlusIcon,
  ChartBarIcon,
  AcademicCapIcon,
  TrophyIcon
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const studentStore = useStudentStore()
const vocabularyStore = useVocabularyStore()
const toast = useToast()

// Get class ID from route
const classId = ref(parseInt(route.params.id) || 1)

// Mock class data (in real app, this would come from a class store)
const classInfo = ref({
  id: 1,
  name: 'Spanish Basics',
  description: 'Introduction to Spanish vocabulary and basic phrases',
  vocabularyLevels: ['Pets', 'Colors', 'Family'],
  created: '2024-01-15'
})

// State
const showBulkAddModal = ref(false)
const showAddStudentModal = ref(false)
const bulkStudentText = ref('')
const csvFile = ref(null)
const searchQuery = ref('')

// Single student form
const newStudent = ref({
  name: '',
  email: ''
})

// CSV template
const csvTemplate = 'Name,Email\nJohn Doe,john.doe@email.com\nJane Smith,jane.smith@email.com'

const students = computed(() => studentStore.getStudentsByClass(classId.value))
const classProgress = computed(() => studentStore.getClassProgress(classId.value))
const topPerformers = computed(() => studentStore.getTopPerformers(classId.value, 3))

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  return students.value.filter(student =>
    student.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    student.email.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const getStudentOverallProgress = (student) => {
  if (!student.progress || Object.keys(student.progress).length === 0) return 0
  const levels = Object.values(student.progress)
  return Math.round(levels.reduce((sum, level) => sum + level.score, 0) / levels.length)
}

const getProgressColor = (score) => {
  if (score >= 90) return 'text-success'
  if (score >= 70) return 'text-warning'
  return 'text-error'
}

const getStatusBadge = (student) => {
  const recentActivity = new Date(student.lastActivity)
  const daysSince = Math.floor((new Date() - recentActivity) / (1000 * 60 * 60 * 24))
  
  if (daysSince <= 1) return { text: 'Active', class: 'badge-success' }
  if (daysSince <= 7) return { text: 'Recent', class: 'badge-warning' }
  return { text: 'Inactive', class: 'badge-error' }
}

const addSingleStudent = () => {
  if (!newStudent.value.name || !newStudent.value.email) {
    toast.error('Please fill in all fields')
    return
  }
  
  studentStore.addStudent({
    ...newStudent.value,
    classId: classId.value
  })
  
  newStudent.value = { name: '', email: '' }
  showAddStudentModal.value = false
  toast.success('Student added successfully!')
}

const processBulkAdd = () => {
  let studentsData = []
  
  try {
    if (csvFile.value) {
      // Process CSV file
      const reader = new FileReader()
      reader.onload = (e) => {
        const csvText = e.target.result
        studentsData = parseCSV(csvText)
        addBulkStudents(studentsData)
      }
      reader.readAsText(csvFile.value)
    } else if (bulkStudentText.value) {
      // Process manual text input
      const lines = bulkStudentText.value.trim().split('\n')
      studentsData = lines.map(line => {
        const [name, email] = line.split(',').map(s => s.trim())
        return { name, email }
      }).filter(student => student.name && student.email)
      
      addBulkStudents(studentsData)
    }
  } catch (error) {
    toast.error('Error processing student data. Please check the format.')
  }
}

const parseCSV = (csvText) => {
  const lines = csvText.trim().split('\n')
  const headers = lines[0].split(',').map(h => h.trim().toLowerCase())
  
  return lines.slice(1).map(line => {
    const values = line.split(',').map(v => v.trim())
    const student = {}
    
    headers.forEach((header, index) => {
      if (header === 'name') student.name = values[index]
      if (header === 'email') student.email = values[index]
    })
    
    return student
  }).filter(student => student.name && student.email)
}

const addBulkStudents = (studentsData) => {
  if (studentsData.length === 0) {
    toast.error('No valid student data found')
    return
  }
  
  studentStore.bulkAddStudents(studentsData, classId.value)
  
  showBulkAddModal.value = false
  bulkStudentText.value = ''
  csvFile.value = null
  
  toast.success(`Successfully added ${studentsData.length} students!`)
}

const removeStudent = (studentId) => {
  if (confirm('Are you sure you want to remove this student from the class?')) {
    studentStore.removeStudent(studentId)
    toast.success('Student removed successfully')
  }
}

const downloadTemplate = () => {
  const blob = new Blob([csvTemplate], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'student-template.csv'
  a.click()
  window.URL.revokeObjectURL(url)
}

const exportStudents = () => {
  const csvContent = [
    'Name,Email,Total XP,Levels Completed,Current Level,Last Activity',
    ...students.value.map(student => 
      `${student.name},${student.email},${student.totalXP},${student.levelsCompleted},${student.currentLevel},${student.lastActivity}`
    )
  ].join('\n')
  
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${classInfo.value.name}-students.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

const viewStudentDetails = (studentId) => {
  router.push(`/teacher/students/${studentId}`)
}

onMounted(() => {
  // In a real app, load class details from API
  console.log('Loading class details for ID:', classId.value)
})
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">{{ classInfo.name }}</h1>
        <p class="text-base-content/70">{{ classInfo.description }}</p>
        <div class="flex gap-2 mt-2">
          <span 
            v-for="level in classInfo.vocabularyLevels" 
            :key="level"
            class="badge badge-outline"
          >
            {{ level }}
          </span>
        </div>
      </div>
      <button @click="router.back()" class="btn btn-ghost">
        ← Back to Classes
      </button>
    </div>

    <!-- Class Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <UserPlusIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Students</div>
        <div class="stat-value text-primary">{{ classProgress.totalStudents }}</div>
        <div class="stat-desc">Enrolled in class</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <ChartBarIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Average Progress</div>
        <div class="stat-value text-secondary">{{ classProgress.averageProgress }}%</div>
        <div class="stat-desc">Class completion</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-accent">
          <TrophyIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total XP</div>
        <div class="stat-value text-accent">{{ classProgress.totalXP }}</div>
        <div class="stat-desc">Experience earned</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-success">
          <AcademicCapIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Levels Completed</div>
        <div class="stat-value text-success">{{ classProgress.completedLevels }}</div>
        <div class="stat-desc">Total achievements</div>
      </div>
    </div>

    <!-- Top Performers -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Top Performers</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div 
            v-for="(student, index) in topPerformers" 
            :key="student.id"
            class="flex items-center gap-3 p-3 bg-base-200 rounded-lg"
          >
            <div class="badge badge-lg" :class="{
              'badge-warning': index === 0,
              'badge-neutral': index === 1,
              'badge-info': index === 2
            }">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <p class="font-semibold">{{ student.name }}</p>
              <p class="text-sm text-base-content/70">{{ student.totalXP }} XP</p>
            </div>
            <TrophyIcon class="w-5 h-5 text-warning" />
          </div>
        </div>
      </div>
    </div>

    <!-- Student Management Actions -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
          <h2 class="card-title">Student Management</h2>
          
          <div class="flex flex-wrap gap-2">
            <button 
              @click="showAddStudentModal = true"
              class="btn btn-primary btn-sm gap-2"
            >
              <UserPlusIcon class="w-4 h-4" />
              Add Student
            </button>
            
            <button 
              @click="showBulkAddModal = true"
              class="btn btn-outline btn-sm gap-2"
            >
              <ArrowUpTrayIcon class="w-4 h-4" />
              Bulk Add
            </button>
            
            <button 
              @click="exportStudents"
              class="btn btn-ghost btn-sm gap-2"
            >
              <ArrowDownTrayIcon class="w-4 h-4" />
              Export
            </button>
          </div>
        </div>

        <!-- Search -->
        <div class="form-control w-full max-w-md mb-4">
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search students..." 
            class="input input-bordered" 
          />
        </div>

        <!-- Students Table -->
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Student</th>
                <th>Progress</th>
                <th>XP</th>
                <th>Current Level</th>
                <th>Status</th>
                <th>Last Activity</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in filteredStudents" :key="student.id">
                <td>
                  <div>
                    <div class="font-semibold">{{ student.name }}</div>
                    <div class="text-sm text-base-content/70">{{ student.email }}</div>
                  </div>
                </td>
                <td>
                  <div class="flex items-center gap-2">
                    <progress 
                      class="progress progress-primary w-20" 
                      :value="getStudentOverallProgress(student)" 
                      max="100"
                    ></progress>
                    <span class="text-sm font-medium" :class="getProgressColor(getStudentOverallProgress(student))">
                      {{ getStudentOverallProgress(student) }}%
                    </span>
                  </div>
                </td>
                <td>
                  <span class="badge badge-outline">{{ student.totalXP }} XP</span>
                </td>
                <td>{{ student.currentLevel }}</td>
                <td>
                  <div class="badge badge-sm" :class="getStatusBadge(student).class">
                    {{ getStatusBadge(student).text }}
                  </div>
                </td>
                <td>{{ student.lastActivity }}</td>
                <td>
                  <div class="flex gap-1">
                    <button 
                      @click="viewStudentDetails(student.id)"
                      class="btn btn-ghost btn-xs"
                    >
                      <EyeIcon class="w-4 h-4" />
                    </button>
                    <button 
                      @click="removeStudent(student.id)"
                      class="btn btn-ghost btn-xs text-error"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredStudents.length === 0" class="text-center py-8">
          <UserPlusIcon class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
          <h3 class="text-lg font-semibold mb-2">No students found</h3>
          <p class="text-base-content/70 mb-4">
            {{ searchQuery ? 'Try adjusting your search terms' : 'Add students to get started' }}
          </p>
          <button 
            v-if="!searchQuery"
            @click="showAddStudentModal = true"
            class="btn btn-primary gap-2"
          >
            <UserPlusIcon class="w-5 h-5" />
            Add First Student
          </button>
        </div>
      </div>
    </div>

    <!-- Add Single Student Modal -->
    <dialog :class="{ 'modal modal-open': showAddStudentModal }" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Add New Student</h3>
        
        <form @submit.prevent="addSingleStudent" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Student Name *</span>
            </label>
            <input 
              v-model="newStudent.name"
              type="text" 
              placeholder="Enter student name" 
              class="input input-bordered" 
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Email Address *</span>
            </label>
            <input 
              v-model="newStudent.email"
              type="email" 
              placeholder="Enter email address" 
              class="input input-bordered" 
              required
            />
          </div>

          <div class="modal-action">
            <button type="button" @click="showAddStudentModal = false" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Add Student
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showAddStudentModal = false">close</button>
      </form>
    </dialog>

    <!-- Bulk Add Students Modal -->
    <dialog :class="{ 'modal modal-open': showBulkAddModal }" class="modal">
      <div class="modal-box w-11/12 max-w-4xl">
        <h3 class="font-bold text-lg mb-4">Bulk Add Students</h3>
        
        <div class="tabs tabs-boxed mb-4">
          <a class="tab tab-active" onclick="document.getElementById('manual-tab').style.display='block'; document.getElementById('csv-tab').style.display='none'">Manual Entry</a>
          <a class="tab" onclick="document.getElementById('csv-tab').style.display='block'; document.getElementById('manual-tab').style.display='none'">CSV Upload</a>
        </div>

        <!-- Manual Entry Tab -->
        <div id="manual-tab">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Enter student data (Name, Email per line)</span>
            </label>
            <textarea 
              v-model="bulkStudentText"
              class="textarea textarea-bordered h-32" 
              placeholder="John Doe, john.doe@email.com&#10;Jane Smith, jane.smith@email.com&#10;Bob Johnson, bob.johnson@email.com"
            ></textarea>
            <label class="label">
              <span class="label-text-alt">Format: Name, Email (one per line)</span>
            </label>
          </div>
        </div>

        <!-- CSV Upload Tab -->
        <div id="csv-tab" style="display: none;">
          <div class="space-y-4">
            <div class="alert alert-info">
              <span>Upload a CSV file with Name and Email columns. Download the template below if needed.</span>
            </div>
            
            <button @click="downloadTemplate" class="btn btn-outline btn-sm gap-2">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Download Template
            </button>
            
            <div class="form-control">
              <label class="label">
                <span class="label-text">Select CSV File</span>
              </label>
              <input 
                type="file" 
                accept=".csv"
                @change="csvFile = $event.target.files[0]"
                class="file-input file-input-bordered" 
              />
            </div>
          </div>
        </div>

        <div class="modal-action">
          <button @click="showBulkAddModal = false" class="btn btn-ghost">
            Cancel
          </button>
          <button @click="processBulkAdd" class="btn btn-primary">
            Add Students
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showBulkAddModal = false">close</button>
      </form>
    </dialog>
  </div>
</template>
