import { defineStore } from 'pinia';
import reportService from '@/service/reportService';
import { useAuthStore } from '@/stores/authStore';

const messageFor = (error, fallback) => error?.response?.data?.message || error?.message || fallback;

export const useReportStore = defineStore('report', {
  state: () => ({
    studentReport: null,
    teacherReport: null,
    adminReport: null,
    loading: false,
    error: null,
  }),
  actions: {
    assertRole(role) {
      if (useAuthStore().userRole !== role) throw new Error(`The ${role} report is not available for this account.`);
    },
    async fetchStudentReport() {
      this.assertRole('student');
      return this.fetchReport('studentReport', () => reportService.getStudentReport(), 'Failed to load student report');
    },
    async fetchTeacherReport() {
      this.assertRole('teacher');
      return this.fetchReport('teacherReport', () => reportService.getTeacherReport(), 'Failed to load teacher report');
    },
    async fetchAdminReport() {
      this.assertRole('admin');
      return this.fetchReport('adminReport', () => reportService.getAdminReport(), 'Failed to load admin report');
    },
    async fetchReport(key, request, fallback) {
      this.loading = true;
      this.error = null;
      this[key] = null;
      try {
        this[key] = await request();
        return this[key];
      } catch (error) {
        this.error = messageFor(error, fallback);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    reset() {
      this.studentReport = null;
      this.teacherReport = null;
      this.adminReport = null;
      this.loading = false;
      this.error = null;
    },
  },
});
