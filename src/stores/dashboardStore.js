import { defineStore } from 'pinia';
import dashboardService from '@/service/dashboardService';
import { useAuthStore } from '@/stores/authStore';

const messageFor = (error, fallback) =>
  error?.response?.data?.message || error?.message || fallback;

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    studentDashboard: null,
    teacherDashboard: null,
    adminDashboard: null,
    loading: false,
    error: null,
  }),
  actions: {
    assertRole(role) {
      if (useAuthStore().userRole !== role) throw new Error(`The ${role} dashboard is not available for this account.`);
    },
    async fetchStudentDashboard() {
      this.assertRole('student');
      this.loading = true;
      this.error = null;
      this.studentDashboard = null;
      try {
        this.studentDashboard = await dashboardService.getStudentDashboard();
        return this.studentDashboard;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load student dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async fetchTeacherDashboard() {
      this.assertRole('teacher');
      this.loading = true;
      this.error = null;
      this.teacherDashboard = null;
      try {
        this.teacherDashboard = await dashboardService.getTeacherDashboard();
        return this.teacherDashboard;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load teacher dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async fetchAdminDashboard() {
      this.assertRole('admin');
      this.loading = true;
      this.error = null;
      this.adminDashboard = null;
      try {
        this.adminDashboard = await dashboardService.getAdminDashboard();
        return this.adminDashboard;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load admin dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },
    reset() {
      this.studentDashboard = null;
      this.teacherDashboard = null;
      this.adminDashboard = null;
      this.loading = false;
      this.error = null;
    },
  },
});
