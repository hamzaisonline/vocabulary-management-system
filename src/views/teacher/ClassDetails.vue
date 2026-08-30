<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useClassStore } from '@/stores/classStore'
import { useAuthStore } from '@/stores/authStore'
import { useToast } from 'vue-toastification'
import { TrashIcon, ArrowLeftIcon, UserPlusIcon } from '@heroicons/vue/24/outline'

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

// Get class from store
const selectedClass = computed(() => classStore.selectedClass)

// Students from class data
const students = computed(() => selectedClass.value?.students || [])

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
    await classStore.fetchClass(classId)
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

const goBackToClasses = () => {
  router.push(classesPath.value)
}

onMounted(loadClassDetails)
</script>

<template>
  <div class="p-6 space-y-6">
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
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-primary">{{ selectedClass.name }}</h1>
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

      <!-- Student Management -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h2 class="card-title">Enrolled Students</h2>
            <button 
              @click="showAddStudentModal = true"
              :disabled="classStore.loading"
              class="btn btn-primary btn-sm gap-2"
            >
              <UserPlusIcon class="w-4 h-4" />
              Enroll Student
            </button>
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
    </template>
  </div>
</template>
