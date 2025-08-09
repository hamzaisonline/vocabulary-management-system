<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useStudentStore } from '@/stores/studentStore'
import { 
  PlusIcon, 
  PencilIcon, 
  TrashIcon, 
  UserGroupIcon,
  ArrowLeftIcon,
  MagnifyingGlassIcon,
  EyeIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const studentStore = useStudentStore()

// Local state
const searchQuery = ref('')
const selectedClass = ref('all')
const showCreateModal = ref(false)
const showBulkImportModal = ref(false)
const selectedStudent = ref(null)

// New student form
const newStudent = ref({
  name: '',
  email: '',
  classId: 1,
  initialPassword: ''
})

// Bulk import
const bulkStudentText = ref('')
const csvFile = ref(null)

// Mock classes for dropdown
const classes = ref([
  { id: 'all', name: 'All Classes' },
  { id: 1, name: 'Spanish Basics' },
  { id: 2, name: 'Intermediate Spanish' },
  { id: 3, name: 'Advanced Conversation' },
  { id: 4, name: 'French Fundamentals' }
])

const allStudents = computed(() => studentStore.students)

const filteredStudents = computed(() => {
  let students = allStudents.value
  
  // Filter by class
  if (selectedClass.value !== 'all') {
    students = students.filter(student => student.classId === parseInt(selectedClass.value))
  }
  
  // Filter by search query
  if (searchQuery.value) {
    students = students.filter(student =>
      student.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      student.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }
  
  return students
})

const studentStats = computed(() => {
  const total = allStudents.value.length
  const active = allStudents.value.filter(s => {
    const lastActivity = new Date(s.lastActivity)
    const daysSince = Math.floor((new Date() - lastActivity) / (1000 * 60 * 60 * 24))
    return daysSince <= 7
  }).length
  
  const totalXP = allStudents.value.reduce((sum, student) => sum + student.totalXP, 0)
  const avgProgress = total > 0 ? Math.round(
    allStudents.value.reduce((sum, student) => {
      const levels = Object.values(student.progress || {})
      const avgScore = levels.length > 0 ? levels.reduce((acc, level) => acc + level.score, 0) / levels.length : 0
      return sum + avgScore
    }, 0) / total
  ) : 0
  
  return { total, active, totalXP, avgProgress }
})

const getStudentProgress = (student) => {
  if (!student.progress || Object.keys(student.progress).length === 0) return 0
  const levels = Object.values(student.progress)
  return Math.round(levels.reduce((sum, level) => sum + level.score, 0) / levels.length)
}

const getStatusBadge = (student) => {
  const recentActivity = new Date(student.lastActivity)
  const daysSince = Math.floor((new Date() - recentActivity) / (1000 * 60 * 60 * 24))
  
  if (daysSince <= 1) return { text: 'Active', class: 'badge-success' }
  if (daysSince <= 7) return { text: 'Recent', class: 'badge-warning' }
  return { text: 'Inactive', class: 'badge-error' }
}

const getClassName = (classId) => {
  const classObj = classes.value.find(c => c.id === classId)
  return classObj ? classObj.name : 'Unknown Class'
}

const createStudent = () => {
  if (!newStudent.value.name || !newStudent.value.email) {
    alert('Please fill in all required fields')
    return
  }
  
  studentStore.addStudent({
    ...newStudent.value,
    classId: parseInt(newStudent.value.classId)
  })
  
  newStudent.value = {
    name: '',
    email: '',
    classId: 1,
    initialPassword: ''
  }
  
  showCreateModal.value = false
}

const editStudent = (student) => {
  selectedStudent.value = { ...student }
  // Open edit modal (could be implemented)
}

const deleteStudent = (studentId) => {
  if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
    studentStore.removeStudent(studentId)
  }
}

const viewStudentDetails = (studentId) => {
  router.push(`/admin/students/${studentId}`)
}

const bulkImportStudents = () => {
  let studentsData = []
  
  try {
    if (csvFile.value) {
      const reader = new FileReader()
      reader.onload = (e) => {
        const csvText = e.target.result
        studentsData = parseCSV(csvText)
        addBulkStudents(studentsData)
      }
      reader.readAsText(csvFile.value)
    } else if (bulkStudentText.value) {
      const lines = bulkStudentText.value.trim().split('\n')
      studentsData = lines.map(line => {
        const [name, email, className] = line.split(',').map(s => s?.trim())
        const classObj = classes.value.find(c => c.name.toLowerCase() === className?.toLowerCase())
        return { 
          name, 
          email, 
          classId: classObj ? classObj.id : 1
        }
      }).filter(student => student.name && student.email)
      
      addBulkStudents(studentsData)
    }
  } catch (error) {
    alert('Error processing student data. Please check the format.')
  }
}

const parseCSV = (csvText) => {
  const lines = csvText.trim().split('\n')
  const headers = lines[0].split(',').map(h => h.trim().toLowerCase())
  
  return lines.slice(1).map(line => {
    const values = line.split(',').map(v => v?.trim())
    const student = {}
    
    headers.forEach((header, index) => {
      if (header === 'name') student.name = values[index]
      if (header === 'email') student.email = values[index]
      if (header === 'class') {
        const classObj = classes.value.find(c => c.name.toLowerCase() === values[index]?.toLowerCase())
        student.classId = classObj ? classObj.id : 1
      }
    })
    
    return student
  }).filter(student => student.name && student.email)
}

const addBulkStudents = (studentsData) => {
  if (studentsData.length === 0) {
    alert('No valid student data found')
    return
  }
  
  studentsData.forEach(studentData => {
    studentStore.addStudent(studentData)
  })
  
  showBulkImportModal.value = false
  bulkStudentText.value = ''
  csvFile.value = null
  
  alert(`Successfully added ${studentsData.length} students!`)
}

const exportStudents = () => {
  const csvContent = [
    'Name,Email,Class,Total XP,Levels Completed,Overall Progress,Last Activity',
    ...filteredStudents.value.map(student => 
      `${student.name},${student.email},${getClassName(student.classId)},${student.totalXP},${student.levelsCompleted},${getStudentProgress(student)}%,${student.lastActivity}`
    )
  ].join('\n')
  
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `students-export-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

const downloadTemplate = () => {
  const template = 'Name,Email,Class\nJohn Doe,john.doe@email.com,Spanish Basics\nJane Smith,jane.smith@email.com,Intermediate Spanish'
  const blob = new Blob([template], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'student-import-template.csv'
  a.click()
  window.URL.revokeObjectURL(url)
}

const goBack = () => {
  router.push('/admin')
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Student Management</h1>
        <p class="text-base-content/70">Manage all students across the platform</p>
      </div>
      <div class="flex gap-2">
        <button @click="goBack" class="btn btn-ghost gap-2">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Dashboard
        </button>
        <button @click="exportStudents" class="btn btn-outline gap-2">
          <ArrowDownTrayIcon class="w-4 h-4" />
          Export Students
        </button>
        <button @click="showBulkImportModal = true" class="btn btn-secondary gap-2">
          <ArrowUpTrayIcon class="w-4 h-4" />
          Bulk Import
        </button>
        <button @click="showCreateModal = true" class="btn btn-primary gap-2">
          <PlusIcon class="w-4 h-4" />
          Add Student
        </button>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="stat bg-primary text-primary-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-primary-content/80">Total Students</div>
        <div class="stat-value">{{ studentStats.total }}</div>
        <div class="stat-desc text-primary-content/60">Platform-wide</div>
      </div>

      <div class="stat bg-success text-success-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-success-content/80">Active Students</div>
        <div class="stat-value">{{ studentStats.active }}</div>
        <div class="stat-desc text-success-content/60">Active this week</div>
      </div>

      <div class="stat bg-warning text-warning-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-warning-content/80">Total XP</div>
        <div class="stat-value">{{ studentStats.totalXP.toLocaleString() }}</div>
        <div class="stat-desc text-warning-content/60">All students combined</div>
      </div>

      <div class="stat bg-info text-info-content shadow-md rounded-lg">
        <div class="stat-figure">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title text-info-content/80">Avg Progress</div>
        <div class="stat-value">{{ studentStats.avgProgress }}%</div>
        <div class="stat-desc text-info-content/60">Platform average</div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="flex flex-wrap gap-4 items-end">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Search Students</span>
            </label>
            <div class="input-group">
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Search by name or email..." 
                class="input input-bordered"
              />
              <span class="btn btn-square">
                <MagnifyingGlassIcon class="w-4 h-4" />
              </span>
            </div>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Filter by Class</span>
            </label>
            <select v-model="selectedClass" class="select select-bordered">
              <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                {{ cls.name }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Students Table -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Students ({{ filteredStudents.length }})</h2>
        
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Progress</th>
                <th>XP</th>
                <th>Levels</th>
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
                <td>{{ getClassName(student.classId) }}</td>
                <td>
                  <div class="flex items-center gap-2">
                    <progress 
                      class="progress progress-primary w-20" 
                      :value="getStudentProgress(student)" 
                      max="100"
                    ></progress>
                    <span class="text-sm font-medium">{{ getStudentProgress(student) }}%</span>
                  </div>
                </td>
                <td>
                  <span class="badge badge-outline">{{ student.totalXP }} XP</span>
                </td>
                <td>{{ student.levelsCompleted }}</td>
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
                      title="View Details"
                    >
                      <EyeIcon class="w-4 h-4" />
                    </button>
                    <button 
                      @click="editStudent(student)"
                      class="btn btn-ghost btn-xs"
                      title="Edit"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </button>
                    <button 
                      @click="deleteStudent(student.id)"
                      class="btn btn-ghost btn-xs text-error"
                      title="Delete"
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
          <UserGroupIcon class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
          <h3 class="text-lg font-semibold mb-2">No students found</h3>
          <p class="text-base-content/70 mb-4">
            {{ searchQuery || selectedClass !== 'all' ? 'Try adjusting your search or filters' : 'Add students to get started' }}
          </p>
          <button 
            v-if="!searchQuery && selectedClass === 'all'"
            @click="showCreateModal = true"
            class="btn btn-primary gap-2"
          >
            <PlusIcon class="w-5 h-5" />
            Add First Student
          </button>
        </div>
      </div>
    </div>

    <!-- Create Student Modal -->
    <dialog :class="{ 'modal modal-open': showCreateModal }" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Add New Student</h3>
        
        <form @submit.prevent="createStudent" class="space-y-4">
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

          <div class="form-control">
            <label class="label">
              <span class="label-text">Assign to Class</span>
            </label>
            <select v-model="newStudent.classId" class="select select-bordered">
              <option v-for="cls in classes.filter(c => c.id !== 'all')" :key="cls.id" :value="cls.id">
                {{ cls.name }}
              </option>
            </select>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Initial Password (Optional)</span>
            </label>
            <input 
              v-model="newStudent.initialPassword"
              type="password" 
              placeholder="Leave blank for auto-generated" 
              class="input input-bordered"
            />
          </div>

          <div class="modal-action">
            <button type="button" @click="showCreateModal = false" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Add Student
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showCreateModal = false">close</button>
      </form>
    </dialog>

    <!-- Bulk Import Modal -->
    <dialog :class="{ 'modal modal-open': showBulkImportModal }" class="modal">
      <div class="modal-box w-11/12 max-w-4xl">
        <h3 class="font-bold text-lg mb-4">Bulk Import Students</h3>
        
        <div class="tabs tabs-boxed mb-4">
          <a class="tab tab-active" onclick="document.getElementById('manual-tab').style.display='block'; document.getElementById('csv-tab').style.display='none'">Manual Entry</a>
          <a class="tab" onclick="document.getElementById('csv-tab').style.display='block'; document.getElementById('manual-tab').style.display='none'">CSV Upload</a>
        </div>

        <!-- Manual Entry Tab -->
        <div id="manual-tab">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Enter student data (Name, Email, Class per line)</span>
            </label>
            <textarea 
              v-model="bulkStudentText"
              class="textarea textarea-bordered h-32" 
              placeholder="John Doe, john.doe@email.com, Spanish Basics&#10;Jane Smith, jane.smith@email.com, Intermediate Spanish"
            ></textarea>
            <label class="label">
              <span class="label-text-alt">Format: Name, Email, Class (one per line)</span>
            </label>
          </div>
        </div>

        <!-- CSV Upload Tab -->
        <div id="csv-tab" style="display: none;">
          <div class="space-y-4">
            <div class="alert alert-info">
              <span>Upload a CSV file with Name, Email, and Class columns. Download the template below if needed.</span>
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
          <button @click="showBulkImportModal = false" class="btn btn-ghost">
            Cancel
          </button>
          <button @click="bulkImportStudents" class="btn btn-primary">
            Import Students
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showBulkImportModal = false">close</button>
      </form>
    </dialog>
  </div>
</template>
