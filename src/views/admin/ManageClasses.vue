<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useClassStore } from '@/stores/classStore';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from 'vue-toastification';
import { PlusIcon, PencilIcon, TrashIcon, UserGroupIcon, EyeIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const classStore = useClassStore();
const authStore = useAuthStore();
const toast = useToast();

const searchQuery = ref('');
const showEditModal = ref(false);
const editingClass = ref(null);

// Edit form
const editForm = ref({
  name: '',
  description: '',
  language: '',
  teacher_id: ''
});

const filteredClasses = computed(() => {
  if (!searchQuery.value) return classStore.classes;
  return classStore.classes.filter(
    (cls) =>
      cls.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (cls.description && cls.description.toLowerCase().includes(searchQuery.value.toLowerCase()))
  );
});

const totalStudents = computed(() => {
  return classStore.classes.reduce((total, cls) => total + (cls.students?.length || 0), 0);
});

const loadClasses = async () => {
  try {
    classStore.loading = true;
    await classStore.fetchClasses();
  } catch (error) {
    if (error?.response?.status === 403) {
      toast.error('You do not have permission to manage classes');
    } else {
      toast.error(classStore.error || 'Failed to load classes');
    }
  } finally {
    classStore.loading = false;
  }
};

const openCreateClass = () => {
  router.push('/teacher/classes/create');
};

const openEditModal = (classItem) => {
  editingClass.value = classItem;
  editForm.value = {
    name: classItem.name || '',
    description: classItem.description || '',
    language: classItem.language || '',
    teacher_id: classItem.teacher_id || ''
  };
  showEditModal.value = true;
};

const updateClass = async () => {
  if (!editForm.value.name) {
    toast.error('Class name is required');
    return;
  }

  try {
    classStore.loading = true;
    await classStore.updateClass(editingClass.value.id, {
      name: editForm.value.name,
      description: editForm.value.description,
      language: editForm.value.language,
      teacher_id: editForm.value.teacher_id ? parseInt(editForm.value.teacher_id) : undefined
    });
    toast.success('Class updated successfully');
    showEditModal.value = false;
  } catch (error) {
    if (error?.response?.status === 422) {
      toast.error(error?.response?.data?.message || 'Validation error');
    } else {
      toast.error('Failed to update class');
    }
  } finally {
    classStore.loading = false;
  }
};

const deleteClass = async (classItem) => {
  if (!confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
    return;
  }

  try {
    classStore.loading = true;
    await classStore.deleteClass(classItem.id);
    toast.success('Class deleted successfully');
  } catch (error) {
    toast.error('Failed to delete class');
  } finally {
    classStore.loading = false;
  }
};

const viewClassDetails = (classId) => {
  router.push(`/admin/classes/${classId}`);
};

const goToDashboard = () => {
  router.push('/admin');
};

onMounted(loadClasses);
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Manage Classes</h1>
        <p class="text-base-content/70 mt-1">View and manage all classes in the system</p>
      </div>
      <div class="flex gap-2">
        <button @click="goToDashboard" class="btn btn-ghost gap-2">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Dashboard
        </button>
        <button @click="openCreateClass" :disabled="classStore.loading" class="btn btn-primary gap-2">
          <PlusIcon class="w-5 h-5" />
          Create Class
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

    <!-- Stats -->
    <div v-if="classStore.classes.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Classes</div>
        <div class="stat-value text-primary">{{ classStore.classes.length }}</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <UserGroupIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Students</div>
        <div class="stat-value text-secondary">{{ totalStudents }}</div>
      </div>
    </div>

    <!-- Search -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="form-control w-full max-w-md">
          <label class="label">
            <span class="label-text">Search Classes</span>
          </label>
          <input v-model="searchQuery" type="text" placeholder="Search by name or description..." class="input input-bordered w-full" />
        </div>
      </div>
    </div>

    <!-- Classes Table -->
    <div v-if="classStore.classes.length > 0" class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="overflow-x-auto">
          <table class="table table-zebra w-full">
            <thead>
              <tr>
                <th>Class Name</th>
                <th>Language</th>
                <th>Students</th>
                <th>Teacher ID</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="classItem in filteredClasses" :key="classItem.id">
                <td>
                  <div>
                    <div class="font-semibold">{{ classItem.name }}</div>
                    <div class="text-sm text-base-content/70">{{ classItem.description || 'No description' }}</div>
                  </div>
                </td>
                <td>{{ classItem.language || 'N/A' }}</td>
                <td>
                  <span class="badge badge-outline">{{ classItem.students?.length || 0 }}</span>
                </td>
                <td>{{ classItem.teacher_id || 'N/A' }}</td>
                <td>{{ classItem.created_at ? new Date(classItem.created_at).toLocaleDateString() : 'Unknown' }}</td>
                <td>
                  <div class="flex gap-2">
                    <button @click="viewClassDetails(classItem.id)" :disabled="classStore.loading" class="btn btn-ghost btn-xs gap-1">
                      <EyeIcon class="w-4 h-4" />
                      View
                    </button>
                    <button @click="openEditModal(classItem)" :disabled="classStore.loading" class="btn btn-outline btn-xs gap-1">
                      <PencilIcon class="w-4 h-4" />
                      Edit
                    </button>
                    <button @click="deleteClass(classItem)" :disabled="classStore.loading" class="btn btn-error btn-outline btn-xs gap-1">
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!classStore.loading && classStore.classes.length === 0 && !classStore.error" class="text-center py-12">
      <UserGroupIcon class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
      <h3 class="text-lg font-semibold mb-2">No classes found</h3>
      <p class="text-base-content/70 mb-6">Create a new class to get started</p>
      <button @click="openCreateClass" class="btn btn-primary gap-2">
        <PlusIcon class="w-5 h-5" />
        Create Class
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
            <input v-model="editForm.name" type="text" class="input input-bordered" placeholder="Class name" />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
            </label>
            <textarea v-model="editForm.description" class="textarea textarea-bordered h-24" placeholder="Class description"></textarea>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Language</span>
            </label>
            <input v-model="editForm.language" type="text" class="input input-bordered" placeholder="e.g., Spanish" />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Teacher ID</span>
            </label>
            <input v-model="editForm.teacher_id" type="number" class="input input-bordered" placeholder="Teacher ID" />
          </div>
        </div>

        <div class="modal-action">
          <button @click="showEditModal = false" class="btn btn-ghost">
            Cancel
          </button>
          <button @click="updateClass" :disabled="classStore.loading" class="btn btn-primary">
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
