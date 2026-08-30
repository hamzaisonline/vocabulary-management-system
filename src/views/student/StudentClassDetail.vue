<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useClassStore } from '@/stores/classStore'
import { useToast } from 'vue-toastification'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const classStore = useClassStore()
const toast = useToast()

const classId = ref(parseInt(route.params.id) || 1)
const selectedClass = computed(() => classStore.selectedClass)
const students = computed(() => selectedClass.value?.students || [])
const searchQuery = ref('')

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  return students.value.filter(
    (student) =>
      (student.name && student.name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (student.email && student.email.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

const loadClassDetails = async () => {
  try {
    classStore.loading = true
    await classStore.fetchClass(classId.value)
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You do not have access to this class')
      router.push('/student/classes')
    } else if (error?.response?.status === 404) {
      toast.error('Class not found')
      router.push('/student/classes')
    } else {
      toast.error(classStore.error || 'Failed to load class details')
    }
  } finally {
    classStore.loading = false
  }
}

const goBackToClasses = () => {
  router.push('/student/classes')
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

      <!-- Classmates -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h2 class="card-title mb-4">Your Classmates</h2>

          <!-- Search -->
          <div class="form-control w-full max-w-md mb-4">
            <input v-model="searchQuery" type="text" placeholder="Search classmates..." class="input input-bordered" />
          </div>

          <!-- Students List -->
          <div v-if="students.length === 0" class="text-center py-8 text-base-content/70">
            <p>No other students in this class yet</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="table table-zebra w-full">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Enrolled Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="student in filteredStudents" :key="student.id">
                  <td>
                    <span class="font-semibold">{{ student.name }}</span>
                  </td>
                  <td>{{ student.email }}</td>
                  <td>{{ student.pivot?.created_at ? new Date(student.pivot.created_at).toLocaleDateString() : 'Recently' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
