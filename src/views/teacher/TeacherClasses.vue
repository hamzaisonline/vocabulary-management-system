<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useClassStore } from '@/stores/classStore'
import { useAuthStore } from '@/stores/authStore'
import { useToast } from 'vue-toastification'
import { PlusIcon, PencilIcon, TrashIcon, UserGroupIcon, ChartBarIcon, EyeIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const classStore = useClassStore()
const authStore = useAuthStore()
const toast = useToast()

const searchQuery = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingClass = ref(null)

// Edit form
const editForm = ref({
  name: '',
  description: '',
  language: ''
})

const filteredClasses = computed(() => {
  if (!searchQuery.value) return classStore.classes
  return classStore.classes.filter(cls => 
    cls.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (cls.description && cls.description.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

const totalStudents = computed(() => {
  return classStore.classes.reduce((total, cls) => total + (cls.students?.length || 0), 0)
})

const onMounted_load = async () => {
  try {
    await classStore.fetchClasses()
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You do not have permission to manage classes')
    } else {
      toast.error(classStore.error || 'Failed to load classes')
    }
  }
}

const openEditModal = (classItem) => {
  editingClass.value = classItem
  editForm.value = {
    name: classItem.name || '',
    description: classItem.description || '',
    language: classItem.language || ''
  }
  showEditModal.value = true
}

const updateClass = async () => {
  if (!editForm.value.name) {
    toast.error('Class name is required')
    return
  }

  try {
    classStore.loading = true
    await classStore.updateClass(editingClass.value.id, {
      name: editForm.value.name,
      description: editForm.value.description,
      language: editForm.value.language
    })
    toast.success('Class updated successfully')
    showEditModal.value = false
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You can only edit your own classes')
    } else if (error?.response?.status === 422) {
      toast.error(error?.response?.data?.message || 'Validation error')
    } else {
      toast.error('Failed to update class')
    }
  } finally {
    classStore.loading = false
  }
}

const deleteClass = async (classItem) => {
  if (!confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
    return
  }

  try {
    classStore.loading = true
    await classStore.deleteClass(classItem.id)
    toast.success('Class deleted successfully')
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You can only delete your own classes')
    } else {
      toast.error('Failed to delete class')
    }
  } finally {
    classStore.loading = false
  }
}

const viewClassDetails = (classId) => {
  router.push(`/teacher/classes/${classId}`)
}

const goToCreateClass = () => {
  router.push('/teacher/classes/create')
}

const goToDashboard = () => {
  router.push('/teacher')
}

onMounted(onMounted_load)
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
          @click="goToCreateClass"
          :disabled="classStore.loading"
          class="btn btn-primary gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Create New Class
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="classStore.loading && classStore.classes.length === 0" class="text-center py-12">
      <div class="loading loading-spinner loading-lg mx-auto"></div>
      <p class="text-base-content/70 mt-4">Loading classes...</p>
    </div>

    <!-- Error State -->
    <div v-if="classStore.error" class="alert alert-error">
      <div>
        <span>{{ classStore.error }}</span>
      </div>
    </div>

    <!-- Stats Overview -->
    <div v-if="classStore.classes.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Classes</div>
        <div class="stat-value text-primary">{{ classStore.classes.length }}</div>
        <div class="stat-desc">Your classes</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Students</div>
        <div class="stat-value text-secondary">{{ totalStudents }}</div>
        <div class="stat-desc">Across all classes</div>
      </div>
    </div>

    <!-- Search -->
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
          <p class="text-sm text-base-content/70 mb-4">{{ classItem.description || 'No description' }}</p>
          
          <!-- Class Stats -->
          <div class="space-y-3 mb-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Students:</span>
              <span class="badge badge-outline">{{ classItem.students?.length || 0 }}</span>
            </div>
            
            <div v-if="classItem.language" class="flex items-center justify-between">
              <span class="text-sm font-medium">Language:</span>
              <span class="text-sm">{{ classItem.language }}</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="card-actions justify-end gap-2">
            <button 
              @click="viewClassDetails(classItem.id)"
              :disabled="classStore.loading"
              class="btn btn-ghost btn-sm gap-1"
            >
              <EyeIcon class="w-4 h-4" />
              View
            </button>
            <button 
              @click="openEditModal(classItem)"
              :disabled="classStore.loading"
              class="btn btn-outline btn-sm gap-1"
            >
              <PencilIcon class="w-4 h-4" />
              Edit
            </button>
            <button 
              @click="deleteClass(classItem)"
              :disabled="classStore.loading"
              class="btn btn-error btn-outline btn-sm gap-1"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!classStore.loading && classStore.classes.length === 0 && !classStore.error" class="text-center py-12">
      <UserGroupIcon class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
      <h3 class="text-lg font-semibold mb-2">No classes yet</h3>
      <p class="text-base-content/70 mb-6">Create your first class to get started</p>
      <button 
        @click="goToCreateClass"
        class="btn btn-primary gap-2"
      >
        <PlusIcon class="w-5 h-5" />
        Create New Class
      </button>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="modal modal-open">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Edit Class</h3>
        
        <div class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Class Name</span>
            </label>
            <input 
              v-model="editForm.name"
              type="text" 
              class="input input-bordered"
              placeholder="Class name"
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
            </label>
            <textarea 
              v-model="editForm.description"
              class="textarea textarea-bordered h-24"
              placeholder="Class description"
            ></textarea>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Language</span>
            </label>
            <input 
              v-model="editForm.language"
              type="text" 
              class="input input-bordered"
              placeholder="e.g., Spanish"
            />
          </div>
        </div>

        <div class="modal-action">
          <button @click="showEditModal = false" class="btn btn-ghost">
            Cancel
          </button>
          <button 
            @click="updateClass"
            :disabled="classStore.loading"
            class="btn btn-primary"
          >
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
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
