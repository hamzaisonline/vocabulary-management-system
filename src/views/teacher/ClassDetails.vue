<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useClassStore } from '@/stores/classStore'
import { useAuthStore } from '@/stores/authStore'
import { useToast } from 'vue-toastification'
import vocabularyService from '@/service/vocabularyService'
import classService from '@/service/classService'
import { TrashIcon, ArrowLeftIcon, UserPlusIcon, ArrowUpTrayIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const classStore = useClassStore()
const authStore = useAuthStore()
const toast = useToast()

// Get class ID from route
const classId = Number(route.params.id)
const classesPath = computed(() => authStore.role === 'admin' ? '/admin/classes' : '/teacher/classes')
const searchQuery = ref('')
const showAddStudentModal = ref(false)
const selectedStudentId = ref('')
const showImportModal = ref(false)
const importFile = ref(null)
const importSummary = ref(null)
const hasImportFile = computed(() => Boolean(
  importFile.value &&
  typeof importFile.value === 'object' &&
  typeof importFile.value.name === 'string'
))
const assignedVocabularyLevels = ref([])
const availableVocabularyLevels = ref([])
const selectedVocabularyLevelId = ref('')
const vocabularyLoading = ref(false)

// Get class from store
const selectedClass = computed(() => classStore.selectedClass)

// Students from class data
const students = computed(() => selectedClass.value?.students || [])
const assignableVocabularyLevels = computed(() => {
  const assignedIds = new Set(assignedVocabularyLevels.value.map((level) => String(level.id)))
  return availableVocabularyLevels.value.filter((level) => !assignedIds.has(String(level.id)))
})

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  return students.value.filter(student =>
    (student.name && student.name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
    (student.email && student.email.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

// Load class details
const loadClassDetails = async () => {
  try {
    classStore.loading = true
    await Promise.all([
      classStore.fetchClass(classId),
      loadVocabularyAssignments(),
    ])
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You do not have permission to view this class')
      router.push(classesPath.value)
    } else if (error?.response?.status === 404) {
      toast.error('Class not found')
      router.push(classesPath.value)
    } else {
      toast.error(classStore.error || 'Failed to load class details')
    }
  } finally {
    classStore.loading = false
  }
}

const loadVocabularyAssignments = async () => {
  const [assigned, available] = await Promise.all([
    classService.getVocabularyLevels(classId),
    vocabularyService.getLevels(authStore.role === 'admin' ? 'all' : 'all'),
  ])
  assignedVocabularyLevels.value = assigned
  availableVocabularyLevels.value = available
}

const assignVocabularyLevel = async () => {
  if (!selectedVocabularyLevelId.value) return
  vocabularyLoading.value = true
  try {
    await classService.assignVocabularyLevel(classId, selectedVocabularyLevelId.value)
    await loadVocabularyAssignments()
    selectedVocabularyLevelId.value = ''
    toast.success('Vocabulary set assigned to class')
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to assign vocabulary set')
  } finally {
    vocabularyLoading.value = false
  }
}

const removeVocabularyLevel = async (level) => {
  vocabularyLoading.value = true
  try {
    await classService.removeVocabularyLevel(classId, level.id)
    await loadVocabularyAssignments()
    toast.success('Vocabulary set removed from class')
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to remove vocabulary set')
  } finally {
    vocabularyLoading.value = false
  }
}

// Enroll student
const enrollStudent = async () => {
  if (!selectedStudentId.value) {
    toast.error('Please select a student')
    return
  }

  try {
    classStore.loading = true
    await classStore.enrollStudent(classId, parseInt(selectedStudentId.value))
    toast.success('Student enrolled successfully')
    showAddStudentModal.value = false
    selectedStudentId.value = ''
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You can only manage students in your own classes')
    } else if (error?.response?.status === 422) {
      toast.error(error?.response?.data?.message || 'Student may already be enrolled')
    } else {
      toast.error('Failed to enroll student')
    }
  } finally {
    classStore.loading = false
  }
}

// Remove student
const removeStudent = async (studentId) => {
  if (!confirm('Are you sure you want to remove this student from the class?')) {
    return
  }

  try {
    classStore.loading = true
    await classStore.removeStudent(classId, studentId)
    toast.success('Student removed successfully')
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You can only remove students from your own classes')
    } else {
      toast.error('Failed to remove student')
    }
  } finally {
    classStore.loading = false
  }
}

const handleImportFile = (event) => {
  importFile.value = event.target.files?.[0] ?? null
  importSummary.value = null
}

const importStudents = async () => {
  if (!hasImportFile.value) return toast.error('Please choose a CSV file')
  try {
    importSummary.value = await classStore.importStudents(classId, importFile.value)
    toast.success('Student import completed')
  } catch (error) {
    if (error?.response?.status === 403) toast.error('You can only import students into classes you manage')
    else if (error?.response?.status === 422) toast.error(error?.response?.data?.errors?.file?.[0] || 'The CSV file is invalid')
    else toast.error(classStore.error || 'Failed to import students')
  }
}

const closeImportModal = () => {
  showImportModal.value = false
  importFile.value = null
  importSummary.value = null
}

const downloadTemplate = () => {
  const csv = 'name,email,password\nJohn Smith,john@example.com,student123\nMaría López,maria@example.com,student123\n'
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = 'student-import-template.csv'
  link.click()
  URL.revokeObjectURL(url)
}

const goBackToClasses = () => {
  router.push(classesPath.value)
}

onMounted(loadClassDetails)
</script>

<template>
  <div class="min-w-0 space-y-4 p-0 sm:space-y-6 sm:p-6">
    <!-- Loading State -->
    <div v-if="classStore.loading && !selectedClass" class="text-center py-12">
      <div class="loading loading-spinner loading-lg mx-auto"></div>
      <p class="text-base-content/70 mt-4">Loading class details...</p>
    </div>

    <!-- Error State -->
    <div v-if="classStore.error" class="alert alert-error">
      <div>
        <span>{{ classStore.error }}</span>
      </div>
    </div>

    <!-- Content (when loaded) -->
    <template v-if="selectedClass">
      <!-- Header -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <h1 class="break-words text-2xl font-bold text-primary sm:text-3xl">{{ selectedClass.name }}</h1>
          <p class="text-base-content/70 mt-1">{{ selectedClass.description || 'No description' }}</p>
          <div v-if="selectedClass.language" class="mt-2">
            <span class="badge badge-outline">{{ selectedClass.language }}</span>
          </div>
        </div>
        <button @click="goBackToClasses" class="btn btn-ghost gap-2">
          <ArrowLeftIcon class="w-4 h-4" />
          Back
        </button>
      </div>

      <!-- Class Info -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-base-content/70">Total Students</p>
              <p class="text-2xl font-bold">{{ students.length }}</p>
            </div>
            <div>
              <p class="text-sm text-base-content/70">Created</p>
              <p class="text-2xl font-bold">{{ selectedClass.created_at ? new Date(selectedClass.created_at).toLocaleDateString() : 'Unknown' }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title">Assigned Vocabulary Sets</h2>
          <div class="flex flex-col sm:flex-row gap-2">
            <select v-model="selectedVocabularyLevelId" class="select select-bordered flex-1" :disabled="vocabularyLoading">
              <option value="">Select an available vocabulary set</option>
              <option v-for="level in assignableVocabularyLevels" :key="level.id" :value="level.id">
                {{ level.title }}{{ !level.is_owner && level.owner ? ` — Shared by ${level.owner.name}` : '' }}
              </option>
            </select>
            <button class="btn btn-primary" :disabled="vocabularyLoading || !selectedVocabularyLevelId" @click="assignVocabularyLevel">Assign Set</button>
          </div>
          <div v-if="!assignedVocabularyLevels.length" class="text-base-content/70 py-3">No vocabulary sets assigned.</div>
          <div v-else class="overflow-x-auto">
            <table class="table table-zebra">
              <thead><tr><th>Set</th><th>Stage</th><th>Owner</th><th>Words</th><th>Actions</th></tr></thead>
              <tbody><tr v-for="level in assignedVocabularyLevels" :key="level.id">
                <td class="font-semibold">{{ level.title }}</td>
                <td>{{ level.stage || 'Not specified' }}</td>
                <td>{{ level.owner?.name || 'Legacy set' }}</td>
                <td>{{ level.word_count || 0 }}</td>
                <td><button class="btn btn-ghost btn-xs text-error" :disabled="vocabularyLoading" @click="removeVocabularyLevel(level)">Remove</button></td>
              </tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Student Management -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h2 class="card-title">Enrolled Students</h2>
            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
              <button
                @click="showImportModal = true"
                :disabled="classStore.loading"
                class="btn btn-outline btn-sm flex-1 gap-2 sm:flex-none"
              >
                <ArrowUpTrayIcon class="w-4 h-4" />
                Bulk Import Students
              </button>
              <button
                @click="showAddStudentModal = true"
                :disabled="classStore.loading"
                class="btn btn-primary btn-sm flex-1 gap-2 sm:flex-none"
              >
                <UserPlusIcon class="w-4 h-4" />
                Enroll Student
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

          <!-- Students List -->
          <div v-if="students.length === 0" class="text-center py-8 text-base-content/70">
            <p>No students enrolled yet</p>
            <button 
              @click="showAddStudentModal = true"
              class="btn btn-primary btn-sm mt-4 gap-2"
            >
              <UserPlusIcon class="w-4 h-4" />
              Enroll First Student
            </button>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="table table-zebra">
              <thead>
                <tr>
                  <th>Student Name</th>
                  <th>Email</th>
                  <th>Enrolled Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="student in filteredStudents" :key="student.id">
                  <td>
                    <span class="font-semibold">{{ student.name }}</span>
                  </td>
                  <td>{{ student.email }}</td>
                  <td>{{ student.pivot?.created_at ? new Date(student.pivot.created_at).toLocaleDateString() : 'Recently' }}</td>
                  <td>
                    <button 
                      @click="removeStudent(student.id)"
                      :disabled="classStore.loading"
                      class="btn btn-ghost btn-xs text-error"
                    >
                      <TrashIcon class="w-4 h-4" />
                      Remove
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Add Student Modal -->
      <div v-if="showAddStudentModal" class="modal modal-open">
        <div class="modal-box">
          <h3 class="font-bold text-lg mb-4">Enroll Student</h3>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text">Student ID</span>
            </label>
            <input 
              v-model="selectedStudentId"
              type="number"
              class="input input-bordered"
              placeholder="Enter student ID"
              :disabled="classStore.loading"
            />
            <label class="label">
              <span class="label-text-alt text-warning">Note: Student enrollment requires the student ID from your system</span>
            </label>
          </div>

          <div class="modal-action">
            <button 
              @click="showAddStudentModal = false"
              :disabled="classStore.loading"
              class="btn btn-ghost"
            >
              Cancel
            </button>
            <button 
              @click="enrollStudent"
              :disabled="classStore.loading || !selectedStudentId"
              class="btn btn-primary"
            >
              <span v-if="classStore.loading" class="loading loading-spinner loading-sm"></span>
              {{ classStore.loading ? 'Enrolling...' : 'Enroll' }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="showImportModal" class="modal modal-open">
        <div class="modal-box w-11/12 max-w-3xl">
          <h3 class="font-bold text-lg">Bulk Import Students</h3>
          <p class="text-sm text-base-content/70 mt-2">CSV columns: name,email,password. Password may be blank for existing students or generated for new students.</p>

          <div class="form-control mt-4">
            <input type="file" accept=".csv,text/csv" class="file-input file-input-bordered w-full" :disabled="classStore.loading" @change="handleImportFile" />
            <span v-if="importFile" class="text-sm text-base-content/70 mt-2">Selected: {{ importFile.name }}</span>
          </div>

          <button class="btn btn-ghost btn-sm gap-2 mt-2" @click="downloadTemplate">
            <ArrowDownTrayIcon class="w-4 h-4" /> Download CSV Template
          </button>

          <div v-if="importSummary" class="mt-5 space-y-4">
            <div class="stats stats-vertical sm:stats-horizontal shadow w-full">
              <div class="stat"><div class="stat-title">Created</div><div class="stat-value text-primary">{{ importSummary.created }}</div></div>
              <div class="stat"><div class="stat-title">Existing</div><div class="stat-value text-secondary">{{ importSummary.enrolled_existing }}</div></div>
              <div class="stat"><div class="stat-title">Already Enrolled</div><div class="stat-value">{{ importSummary.already_enrolled }}</div></div>
              <div class="stat"><div class="stat-title">Failed</div><div class="stat-value text-error">{{ importSummary.failed }}</div></div>
            </div>

            <div v-if="importSummary.temporary_passwords?.length" class="alert alert-warning">
              <div class="w-full">
                <p class="font-semibold">Temporary passwords — copy these now; they are returned only for this import.</p>
                <div class="overflow-x-auto mt-2">
                  <table class="table table-xs"><tbody><tr v-for="item in importSummary.temporary_passwords" :key="item.email"><td>{{ item.email }}</td><td class="font-mono">{{ item.password }}</td></tr></tbody></table>
                </div>
              </div>
            </div>

            <div v-if="importSummary.errors?.length" class="overflow-x-auto max-h-64">
              <table class="table table-zebra table-sm">
                <thead><tr><th>Row</th><th>Email</th><th>Error</th></tr></thead>
                <tbody>
                  <tr v-for="error in importSummary.errors" :key="`${error.row}-${error.email}`">
                    <td>{{ error.row }}</td>
                    <td>{{ error.email || '—' }}</td>
                    <td>{{ Object.values(error.errors || {}).flat().join(' ') }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-action">
            <button class="btn btn-ghost" :disabled="classStore.loading" @click="closeImportModal">Close</button>
            <button class="btn btn-primary" :disabled="classStore.loading || !hasImportFile" @click="importStudents">
              <span v-if="classStore.loading" class="loading loading-spinner loading-sm"></span>
              {{ classStore.loading ? 'Importing...' : 'Import Students' }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
