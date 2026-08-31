import api from '@/api/api';
const unwrap = (response) => response?.data?.data ?? response?.data ?? null;
export default {
  async getStudents() { return unwrap(await api.get('/admin/students')) ?? []; },
  async createStudent(payload) { return unwrap(await api.post('/admin/students', payload)); },
  async updateStudent(id, payload) { return unwrap(await api.patch(`/admin/students/${id}`, payload)); },
  async resetPassword(id, password) { return unwrap(await api.patch(`/admin/students/${id}/password`, { password })); },
  async deleteStudent(id) { return unwrap(await api.delete(`/admin/students/${id}`)); },
};
