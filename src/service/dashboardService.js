import api from '@/api/api';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

const dashboardService = {
  async getStudentDashboard() {
    return unwrap(await api.get('/dashboard/student'));
  },
  async getTeacherDashboard() {
    return unwrap(await api.get('/dashboard/teacher'));
  },
  async getAdminDashboard() {
    return unwrap(await api.get('/dashboard/admin'));
  },
};

export default dashboardService;
