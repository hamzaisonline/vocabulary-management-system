import { defineStore } from 'pinia';
import adminTeacherService from '@/service/adminTeacherService';

const messageFor = (error, fallback) => error?.response?.data?.message || error?.message || fallback;

export const useAdminTeacherStore = defineStore('adminTeachers', {
  state: () => ({ teachers: [], loading: false, saving: false, error: null }),
  actions: {
    async fetchTeachers() {
      this.loading = true; this.error = null;
      try { this.teachers = await adminTeacherService.getTeachers(); return this.teachers; }
      catch (error) { this.error = messageFor(error, 'Failed to load teachers'); throw error; }
      finally { this.loading = false; }
    },
    async createTeacher(payload) {
      return this.save(() => adminTeacherService.createTeacher(payload), (teacher) => this.teachers.unshift(teacher));
    },
    async updateTeacher(id, payload) {
      return this.save(() => adminTeacherService.updateTeacher(id, payload), (teacher) => {
        const index = this.teachers.findIndex((item) => item.id === id);
        if (index !== -1) this.teachers[index] = teacher;
      });
    },
    async resetPassword(id, password) { return this.save(() => adminTeacherService.resetPassword(id, password)); },
    async deleteTeacher(id) {
      return this.save(() => adminTeacherService.deleteTeacher(id), () => {
        this.teachers = this.teachers.filter((teacher) => teacher.id !== id);
      });
    },
    async save(request, onSuccess = () => {}) {
      this.saving = true; this.error = null;
      try { const result = await request(); onSuccess(result); return result; }
      catch (error) { this.error = messageFor(error, 'Teacher operation failed'); throw error; }
      finally { this.saving = false; }
    },
    reset() { this.teachers = []; this.loading = false; this.saving = false; this.error = null; },
  },
});
