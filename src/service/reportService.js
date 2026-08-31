import api from '@/api/api';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

const reportService = {
  async getStudentReport() {
    return unwrap(await api.get('/reports/student'));
  },
  async getTeacherReport() {
    return unwrap(await api.get('/reports/teacher'));
  },
  async getAdminReport() {
    return unwrap(await api.get('/reports/admin'));
  },
};

export default reportService;
