import { defineStore } from 'pinia';
import service from '@/service/adminStudentService';
const messageFor = (error, fallback) => error?.response?.data?.message || error?.message || fallback;
export const useAdminStudentStore = defineStore('adminStudents', {
  state: () => ({ students: [], loading: false, saving: false, error: null }),
  actions: {
    async fetchStudents() {
      this.loading = true; this.error = null;
      try { this.students = await service.getStudents(); return this.students; }
      catch (error) { this.error = messageFor(error, 'Failed to load students'); throw error; }
      finally { this.loading = false; }
    },
    async save(request, onSuccess = () => {}) {
      this.saving = true; this.error = null;
      try { const result = await request(); onSuccess(result); return result; }
      catch (error) { this.error = messageFor(error, 'Student operation failed'); throw error; }
      finally { this.saving = false; }
    },
    createStudent(payload) { return this.save(() => service.createStudent(payload), (student) => this.students.unshift(student)); },
    updateStudent(id, payload) { return this.save(() => service.updateStudent(id, payload), (student) => { const i = this.students.findIndex((item) => item.id === id); if (i !== -1) this.students[i] = student; }); },
    resetPassword(id, password) { return this.save(() => service.resetPassword(id, password)); },
    deleteStudent(id) { return this.save(() => service.deleteStudent(id), () => { this.students = this.students.filter((student) => student.id !== id); }); },
    reset() { this.students = []; this.loading = false; this.saving = false; this.error = null; },
  },
});
