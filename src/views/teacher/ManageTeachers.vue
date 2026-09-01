<script setup>
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'vue-toastification';
import { KeyIcon, PencilIcon, PlusIcon, TrashIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { useAdminTeacherStore } from '@/stores/adminTeacherStore';

const store = useAdminTeacherStore();
const toast = useToast();
const search = ref('');
const showForm = ref(false);
const showPasswordForm = ref(false);
const editing = ref(null);
const selectedTeacher = ref(null);
const form = ref({ name: '', email: '', password: '' });
const password = ref('');

const filteredTeachers = computed(() => {
  const query = search.value.trim().toLowerCase();
  if (!query) return store.teachers;
  return store.teachers.filter((teacher) =>
    teacher.name?.toLowerCase().includes(query) || teacher.email?.toLowerCase().includes(query));
});
const errorText = (error) => {
  const errors = error?.response?.data?.errors;
  return errors ? Object.values(errors).flat()[0] : store.error;
};
const openCreate = () => {
  editing.value = null;
  form.value = { name: '', email: '', password: '' };
  showForm.value = true;
};
const openEdit = (teacher) => {
  editing.value = teacher;
  form.value = { name: teacher.name, email: teacher.email, password: '' };
  showForm.value = true;
};
const saveTeacher = async () => {
  try {
    if (editing.value) {
      await store.updateTeacher(editing.value.id, { name: form.value.name, email: form.value.email });
      toast.success('Teacher updated successfully');
    } else {
      await store.createTeacher(form.value);
      toast.success('Teacher created successfully');
    }
    showForm.value = false;
  } catch (error) { toast.error(errorText(error) || 'Unable to save teacher'); }
};
const openPassword = (teacher) => {
  selectedTeacher.value = teacher;
  password.value = '';
  showPasswordForm.value = true;
};
const resetPassword = async () => {
  try {
    await store.resetPassword(selectedTeacher.value.id, password.value);
    showPasswordForm.value = false;
    toast.success('Teacher password updated successfully');
  } catch (error) { toast.error(errorText(error) || 'Unable to update password'); }
};
const deleteTeacher = async (teacher) => {
  if (!confirm(`Delete ${teacher.name}? This action cannot be undone.`)) return;
  try {
    await store.deleteTeacher(teacher.id);
    toast.success('Teacher deleted successfully');
  } catch (error) { toast.error(errorText(error) || 'Unable to delete teacher'); }
};

onMounted(async () => {
  try { await store.fetchTeachers(); }
  catch { toast.error(store.error || 'Unable to load teachers'); }
});
</script>

<template>
  <div class="min-w-0 space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0"><h1 class="text-2xl font-bold text-primary sm:text-3xl">Manage Teachers</h1><p class="mt-1 text-base-content/70">Create and manage teacher accounts</p></div>
      <button class="btn btn-primary gap-2" :disabled="store.saving" @click="openCreate"><PlusIcon class="w-5 h-5" /> Add Teacher</button>
    </div>
    <div class="stat bg-base-100 shadow-md rounded-lg max-w-md">
      <div class="stat-figure text-primary"><UserGroupIcon class="w-8 h-8" /></div><div class="stat-title">Total Teachers</div><div class="stat-value text-primary">{{ store.teachers.length }}</div>
    </div>
    <div class="card bg-base-100 shadow-md"><div class="card-body"><label class="form-control max-w-md"><span class="label-text mb-2">Search Teachers</span><input v-model="search" class="input input-bordered" placeholder="Search by name or email..." /></label></div></div>
    <div v-if="store.loading" class="py-12 text-center"><span class="loading loading-spinner loading-lg"></span><p class="mt-3 text-base-content/70">Loading teachers...</p></div>
    <div v-else-if="store.error && !store.teachers.length" class="alert alert-error">{{ store.error }}</div>
    <div v-else class="card bg-base-100 shadow-md"><div class="card-body overflow-x-auto">
      <table class="table table-zebra"><thead><tr><th>Name</th><th>Email</th><th>Classes</th><th>Created</th><th>Actions</th></tr></thead><tbody>
        <tr v-for="teacher in filteredTeachers" :key="teacher.id"><td class="font-semibold">{{ teacher.name }}</td><td>{{ teacher.email }}</td><td><span class="badge badge-outline">{{ teacher.classes_count ?? 0 }}</span></td><td>{{ teacher.created_at ? new Date(teacher.created_at).toLocaleDateString() : '—' }}</td><td><div class="flex flex-wrap gap-2">
          <button class="btn btn-outline btn-xs gap-1" @click="openEdit(teacher)"><PencilIcon class="w-4 h-4" /> Edit</button>
          <button class="btn btn-ghost btn-xs gap-1" @click="openPassword(teacher)"><KeyIcon class="w-4 h-4" /> Password</button>
          <button class="btn btn-error btn-outline btn-xs gap-1" :disabled="store.saving" @click="deleteTeacher(teacher)"><TrashIcon class="w-4 h-4" /> Delete</button>
        </div></td></tr>
        <tr v-if="!filteredTeachers.length"><td colspan="5" class="py-10 text-center text-base-content/60">No teachers found.</td></tr>
      </tbody></table>
    </div></div>

    <dialog class="modal" :class="{ 'modal-open': showForm }"><div class="modal-box"><h3 class="text-lg font-bold">{{ editing ? 'Edit Teacher' : 'Create Teacher' }}</h3>
      <form class="mt-4 space-y-4" @submit.prevent="saveTeacher">
        <label class="form-control"><span class="label-text mb-2">Name</span><input v-model.trim="form.name" required maxlength="255" class="input input-bordered" /></label>
        <label class="form-control"><span class="label-text mb-2">Email</span><input v-model.trim="form.email" required type="email" class="input input-bordered" /></label>
        <label v-if="!editing" class="form-control"><span class="label-text mb-2">Password</span><input v-model="form.password" required minlength="8" type="password" class="input input-bordered" /></label>
        <div class="modal-action"><button type="button" class="btn" :disabled="store.saving" @click="showForm = false">Cancel</button><button class="btn btn-primary" :disabled="store.saving">{{ store.saving ? 'Saving...' : 'Save Teacher' }}</button></div>
      </form></div><form method="dialog" class="modal-backdrop"><button @click="showForm = false">close</button></form></dialog>

    <dialog class="modal" :class="{ 'modal-open': showPasswordForm }"><div class="modal-box"><h3 class="text-lg font-bold">Reset {{ selectedTeacher?.name }}'s Password</h3>
      <form class="mt-4" @submit.prevent="resetPassword"><label class="form-control"><span class="label-text mb-2">New Password</span><input v-model="password" required minlength="8" type="password" class="input input-bordered" /></label>
        <div class="modal-action"><button type="button" class="btn" :disabled="store.saving" @click="showPasswordForm = false">Cancel</button><button class="btn btn-primary" :disabled="store.saving">{{ store.saving ? 'Updating...' : 'Update Password' }}</button></div>
      </form></div><form method="dialog" class="modal-backdrop"><button @click="showPasswordForm = false">close</button></form></dialog>
  </div>
</template>
