<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useClassStore } from '@/stores/classStore'
import { useToast } from 'vue-toastification'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const classStore = useClassStore()
const toast = useToast()
const searchQuery = ref('')

const filteredClasses = computed(() => {
  if (!searchQuery.value) return classStore.classes
  return classStore.classes.filter(
    (cls) =>
      cls.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (cls.description && cls.description.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

const loadClasses = async () => {
  try {
    classStore.loading = true
    await classStore.fetchClasses()
  } catch (error) {
    toast.error(classStore.error || 'Failed to load your classes')
  } finally {
    classStore.loading = false
  }
}

const viewClassDetails = (classId) => {
  classStore.selectClass(classId)
  router.push(`/student/classes/${classId}`)
}

const goToDashboard = () => {
  router.push('/student')
}

onMounted(loadClasses)
</script>

<template>
  <div class="min-w-0 space-y-4 p-0 sm:space-y-6 sm:p-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-primary sm:text-3xl">Your Classes</h1>
        <p class="text-base-content/70 mt-1">Classes you are enrolled in</p>
      </div>
      <button @click="goToDashboard" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Dashboard
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="classStore.loading && classStore.classes.length === 0" class="text-center py-12">
      <div class="loading loading-spinner loading-lg mx-auto"></div>
      <p class="text-base-content/70 mt-4">Loading your classes...</p>
    </div>

    <!-- Error State -->
    <div v-if="classStore.error" class="alert alert-error">
      <div>
        <span>{{ classStore.error }}</span>
      </div>
    </div>

    <!-- Search -->
    <div v-if="classStore.classes.length > 0" class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="form-control w-full max-w-md">
          <label class="label">
            <span class="label-text">Search Classes</span>
          </label>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name or description..."
            class="input input-bordered w-full"
          />
        </div>
      </div>
    </div>

    <!-- Classes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="classItem in filteredClasses"
        :key="classItem.id"
        class="card bg-base-100 shadow-md hover:shadow-lg transition-shadow cursor-pointer"
        @click="viewClassDetails(classItem.id)"
      >
        <div class="card-body">
          <h2 class="card-title">{{ classItem.name }}</h2>
          <p class="text-sm text-base-content/70">{{ classItem.description || 'No description' }}</p>

          <div v-if="classItem.language" class="mt-4">
            <span class="badge badge-outline">{{ classItem.language }}</span>
          </div>

          <div class="card-actions justify-end mt-4">
            <button
              @click.stop="viewClassDetails(classItem.id)"
              class="btn btn-primary btn-sm"
            >
              View Class
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!classStore.loading && classStore.classes.length === 0 && !classStore.error" class="text-center py-12">
      <div class="text-6xl mb-4">📚</div>
      <h3 class="text-lg font-semibold mb-2">No classes yet</h3>
      <p class="text-base-content/70 mb-6">You are not enrolled in any classes. Contact your teacher or administrator to join a class.</p>
      <button @click="goToDashboard" class="btn btn-primary gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Dashboard
      </button>
    </div>
  </div>
</template>
