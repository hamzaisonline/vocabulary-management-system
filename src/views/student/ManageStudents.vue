<script setup>
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'vue-toastification';
import { KeyIcon, PencilIcon, PlusIcon, TrashIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { useAdminStudentStore } from '@/stores/adminStudentStore';

const store = useAdminStudentStore();
const toast = useToast();
const search = ref('');
const showForm = ref(false);
const showPasswordForm = ref(false);
const editing = ref(null);
const selected = ref(null);
const form = ref({ name: '', email: '', password: '' });
const password = ref('');
const filtered = computed(() => {
  const query = search.value.trim().toLowerCase();
  return query ? store.students.filter((item) => item.name?.toLowerCase().includes(query) || item.email?.toLowerCase().includes(query)) : store.students;
});
const errorText = (error) => {
  const errors = error?.response?.data?.errors;
  return errors ? Object.values(errors).flat()[0] : store.error;
};
const openCreate = () => { editing.value = null; form.value = { name: '', email: '', password: '' }; showForm.value = true; };
const openEdit = (student) => { editing.value = student; form.value = { name: student.name, email: student.email, password: '' }; showForm.value = true; };
const saveStudent = async () => {
  try {
    if (editing.value) { await store.updateStudent(editing.value.id, { name: form.value.name, email: form.value.email }); toast.success('Student updated successfully'); }
    else { await store.createStudent(form.value); toast.success('Student created successfully'); }
    showForm.value = false;
  } catch (error) { toast.error(errorText(error) || 'Unable to save student'); }
};
const openPassword = (student) => { selected.value = student; password.value = ''; showPasswordForm.value = true; };
const resetPassword = async () => {
  try { await store.resetPassword(selected.value.id, password.value); showPasswordForm.value = false; toast.success('Student password updated successfully'); }
  catch (error) { toast.error(errorText(error) || 'Unable to update password'); }
};
const deleteStudent = async (student) => {
  if (!confirm(`Delete ${student.name}? This action cannot be undone.`)) return;
  try { await store.deleteStudent(student.id); toast.success('Student deleted successfully'); }
  catch (error) { toast.error(errorText(error) || 'Unable to delete student'); }
};
onMounted(async () => { try { await store.fetchStudents(); } catch { toast.error(store.error || 'Unable to load students'); } });
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div><h1 class="text-3xl font-bold text-primary">Manage Students</h1><p class="mt-1 text-base-content/70">Create and manage student accounts</p></div>
      <button class="btn btn-primary gap-2" :disabled="store.saving" @click="openCreate"><PlusIcon class="w-5 h-5" /> Add Student</button>
    </div>
    <div class="stat bg-base-100 shadow-md rounded-lg max-w-md"><div class="stat-figure text-primary"><UserGroupIcon class="w-8 h-8" /></div><div class="stat-title">Total Students</div><div class="stat-value text-primary">{{ store.students.length }}</div></div>
    <div class="card bg-base-100 shadow-md"><div class="card-body"><label class="form-control max-w-md"><span class="label-text mb-2">Search Students</span><input v-model="search" class="input input-bordered" placeholder="Search by name or email..." /></label></div></div>
    <div v-if="store.loading" class="py-12 text-center"><span class="loading loading-spinner loading-lg"></span><p class="mt-3 text-base-content/70">Loading students...</p></div>
    <div v-else-if="store.error && !store.students.length" class="alert alert-error">{{ store.error }}</div>
    <div v-else class="card bg-base-100 shadow-md"><div class="card-body overflow-x-auto"><table class="table table-zebra">
      <thead><tr><th>Name</th><th>Email</th><th>Total XP</th><th>Classes</th><th>Created</th><th>Actions</th></tr></thead><tbody>
        <tr v-for="student in filtered" :key="student.id"><td class="font-semibold">{{ student.name }}</td><td>{{ student.email }}</td><td>{{ student.total_xp ?? 0 }}</td><td><span class="badge badge-outline">{{ student.enrolled_classes_count ?? 0 }}</span></td><td>{{ student.created_at ? new Date(student.created_at).toLocaleDateString() : '—' }}</td><td><div class="flex flex-wrap gap-2">
          <button class="btn btn-outline btn-xs gap-1" @click="openEdit(student)"><PencilIcon class="w-4 h-4" /> Edit</button>
          <button class="btn btn-ghost btn-xs gap-1" @click="openPassword(student)"><KeyIcon class="w-4 h-4" /> Password</button>
          <button class="btn btn-error btn-outline btn-xs gap-1" :disabled="store.saving" @click="deleteStudent(student)"><TrashIcon class="w-4 h-4" /> Delete</button>
        </div></td></tr><tr v-if="!filtered.length"><td colspan="6" class="py-10 text-center text-base-content/60">No students found.</td></tr>
      </tbody></table></div></div>
    <dialog class="modal" :class="{ 'modal-open': showForm }"><div class="modal-box"><h3 class="text-lg font-bold">{{ editing ? 'Edit Student' : 'Create Student' }}</h3><form class="mt-4 space-y-4" @submit.prevent="saveStudent">
      <label class="form-control"><span class="label-text mb-2">Name</span><input v-model.trim="form.name" required maxlength="255" class="input input-bordered" /></label>
      <label class="form-control"><span class="label-text mb-2">Email</span><input v-model.trim="form.email" required type="email" class="input input-bordered" /></label>
      <label v-if="!editing" class="form-control"><span class="label-text mb-2">Password</span><input v-model="form.password" required minlength="8" type="password" class="input input-bordered" /></label>
      <div class="modal-action"><button type="button" class="btn" :disabled="store.saving" @click="showForm = false">Cancel</button><button class="btn btn-primary" :disabled="store.saving">{{ store.saving ? 'Saving...' : 'Save Student' }}</button></div>
    </form></div><form method="dialog" class="modal-backdrop"><button @click="showForm = false">close</button></form></dialog>
    <dialog class="modal" :class="{ 'modal-open': showPasswordForm }"><div class="modal-box"><h3 class="text-lg font-bold">Reset {{ selected?.name }}'s Password</h3><form class="mt-4" @submit.prevent="resetPassword">
      <label class="form-control"><span class="label-text mb-2">New Password</span><input v-model="password" required minlength="8" type="password" class="input input-bordered" /></label>
      <div class="modal-action"><button type="button" class="btn" :disabled="store.saving" @click="showPasswordForm = false">Cancel</button><button class="btn btn-primary" :disabled="store.saving">{{ store.saving ? 'Updating...' : 'Update Password' }}</button></div>
    </form></div><form method="dialog" class="modal-backdrop"><button @click="showPasswordForm = false">close</button></form></dialog>
  </div>
</template>
