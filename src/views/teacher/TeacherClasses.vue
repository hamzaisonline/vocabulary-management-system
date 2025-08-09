<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon, PencilIcon, TrashIcon, UserGroupIcon, ChartBarIcon, EyeIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

const router = useRouter()

// Mock data for teacher's classes
const classes = ref([
  {
    id: 1,
    name: 'Spanish Basics',
    description: 'Introduction to Spanish vocabulary and basic phrases',
    students: 15,
    vocabularyLevels: ['Pets', 'Colors', 'Family'],
    progress: 85,
    created: '2024-01-15',
    lastActivity: '2024-08-09'
  },
  {
    id: 2,
    name: 'Intermediate Spanish',
    description: 'Building on basic Spanish with more complex vocabulary',
    students: 12,
    vocabularyLevels: ['Food', 'Travel', 'Shopping'],
    progress: 67,
    created: '2024-02-01',
    lastActivity: '2024-08-08'
  },
  {
    id: 3,
    name: 'Advanced Conversation',
    description: 'Advanced Spanish conversation and complex topics',
    students: 8,
    vocabularyLevels: ['Business', 'Academic', 'Culture'],
    progress: 92,
    created: '2024-01-20',
    lastActivity: '2024-08-09'
  },
  {
    id: 4,
    name: 'Grammar Focus',
    description: 'Intensive grammar practice and exercises',
    students: 7,
    vocabularyLevels: ['Verbs', 'Tenses', 'Conjugation'],
    progress: 58,
    created: '2024-03-10',
    lastActivity: '2024-08-07'
  }
])

const searchQuery = ref('')
const selectedClass = ref(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)

// New class form
const newClass = ref({
  name: '',
  description: '',
  vocabularyLevels: []
})

const filteredClasses = computed(() => {
  if (!searchQuery.value) return classes.value
  return classes.value.filter(cls => 
    cls.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    cls.description.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const totalStudents = computed(() => {
  return classes.value.reduce((total, cls) => total + cls.students, 0)
})

const avgProgress = computed(() => {
  if (classes.value.length === 0) return 0
  const total = classes.value.reduce((sum, cls) => sum + cls.progress, 0)
  return Math.round(total / classes.value.length)
})

const createClass = () => {
  const newClassData = {
    id: Date.now(),
    name: newClass.value.name,
    description: newClass.value.description,
    students: 0,
    vocabularyLevels: newClass.value.vocabularyLevels,
    progress: 0,
    created: new Date().toISOString().split('T')[0],
    lastActivity: new Date().toISOString().split('T')[0]
  }
  
  classes.value.push(newClassData)
  
  // Reset form
  newClass.value = {
    name: '',
    description: '',
    vocabularyLevels: []
  }
  
  showCreateModal.value = false
}

const editClass = (classItem) => {
  selectedClass.value = { ...classItem }
  showEditModal.value = true
}

const updateClass = () => {
  const index = classes.value.findIndex(cls => cls.id === selectedClass.value.id)
  if (index !== -1) {
    classes.value[index] = { ...selectedClass.value }
  }
  showEditModal.value = false
  selectedClass.value = null
}

const deleteClass = (classId) => {
  if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
    classes.value = classes.value.filter(cls => cls.id !== classId)
  }
}

const viewClassDetails = (classId) => {
  router.push(`/teacher/classes/${classId}`)
}

const getProgressColor = (progress) => {
  if (progress >= 80) return 'progress-success'
  if (progress >= 60) return 'progress-warning'
  return 'progress-error'
}

const goToDashboard = () => {
  router.push('/teacher')
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">My Classes</h1>
        <p class="text-base-content/70 mt-1">Manage your classes and track student progress</p>
      </div>
      <div class="flex gap-2">
        <button @click="goToDashboard" class="btn btn-ghost gap-2">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Dashboard
        </button>
        <button
          @click="showCreateModal = true"
          class="btn btn-primary gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Create New Class
        </button>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Classes</div>
        <div class="stat-value text-primary">{{ classes.length }}</div>
        <div class="stat-desc">Active classes</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Students</div>
        <div class="stat-value text-secondary">{{ totalStudents }}</div>
        <div class="stat-desc">Across all classes</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-accent">
          <ChartBarIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Average Progress</div>
        <div class="stat-value text-accent">{{ avgProgress }}%</div>
        <div class="stat-desc">Class completion</div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="card bg-base-100 shadow-md">
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
        class="card bg-base-100 shadow-md hover:shadow-lg transition-shadow"
      >
        <div class="card-body">
          <h3 class="card-title">{{ classItem.name }}</h3>
          <p class="text-sm text-base-content/70 mb-4">{{ classItem.description }}</p>
          
          <!-- Class Stats -->
          <div class="space-y-3 mb-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Students:</span>
              <span class="badge badge-outline">{{ classItem.students }}</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Progress:</span>
              <span class="text-sm font-bold">{{ classItem.progress }}%</span>
            </div>
            
            <progress 
              class="progress w-full" 
              :class="getProgressColor(classItem.progress)"
              :value="classItem.progress" 
              max="100"
            ></progress>
          </div>

          <!-- Vocabulary Levels -->
          <div class="mb-4">
            <p class="text-sm font-medium mb-2">Vocabulary Levels:</p>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="level in classItem.vocabularyLevels.slice(0, 3)" 
                :key="level"
                class="badge badge-sm badge-primary"
              >
                {{ level }}
              </span>
              <span 
                v-if="classItem.vocabularyLevels.length > 3"
                class="badge badge-sm badge-ghost"
              >
                +{{ classItem.vocabularyLevels.length - 3 }} more
              </span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="card-actions justify-end">
            <button 
              @click="viewClassDetails(classItem.id)"
              class="btn btn-ghost btn-sm gap-1"
            >
              <EyeIcon class="w-4 h-4" />
              View
            </button>
            <button 
              @click="editClass(classItem)"
              class="btn btn-outline btn-sm gap-1"
            >
              <PencilIcon class="w-4 h-4" />
              Edit
            </button>
            <button 
              @click="deleteClass(classItem.id)"
              class="btn btn-error btn-outline btn-sm gap-1"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredClasses.length === 0" class="text-center py-12">
      <UserGroupIcon class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
      <h3 class="text-lg font-semibold mb-2">No classes found</h3>
      <p class="text-base-content/70 mb-6">
        {{ searchQuery ? 'Try adjusting your search terms' : 'Create your first class to get started' }}
      </p>
      <button 
        v-if="!searchQuery"
        @click="showCreateModal = true"
        class="btn btn-primary gap-2"
      >
        <PlusIcon class="w-5 h-5" />
        Create Your First Class
      </button>
    </div>

    <!-- Create Class Modal -->
    <dialog :class="{ 'modal modal-open': showCreateModal }" class="modal">
      <div class="modal-box w-11/12 max-w-2xl">
        <h3 class="font-bold text-lg mb-4">Create New Class</h3>
        
        <form @submit.prevent="createClass" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Class Name *</span>
            </label>
            <input 
              v-model="newClass.name"
              type="text" 
              placeholder="Enter class name" 
              class="input input-bordered" 
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
            </label>
            <textarea 
              v-model="newClass.description"
              class="textarea textarea-bordered" 
              placeholder="Enter class description"
              rows="3"
            ></textarea>
          </div>

          <div class="modal-action">
            <button type="button" @click="showCreateModal = false" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Create Class
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showCreateModal = false">close</button>
      </form>
    </dialog>

    <!-- Edit Class Modal -->
    <dialog :class="{ 'modal modal-open': showEditModal }" class="modal">
      <div class="modal-box w-11/12 max-w-2xl">
        <h3 class="font-bold text-lg mb-4">Edit Class</h3>
        
        <form @submit.prevent="updateClass" class="space-y-4" v-if="selectedClass">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Class Name *</span>
            </label>
            <input 
              v-model="selectedClass.name"
              type="text" 
              placeholder="Enter class name" 
              class="input input-bordered" 
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
            </label>
            <textarea 
              v-model="selectedClass.description"
              class="textarea textarea-bordered" 
              placeholder="Enter class description"
              rows="3"
            ></textarea>
          </div>

          <div class="modal-action">
            <button type="button" @click="showEditModal = false" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Update Class
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showEditModal = false">close</button>
      </form>
    </dialog>
  </div>
</template>
