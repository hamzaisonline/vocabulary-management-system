import api from '@/api/api';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

export default {
  async getTeachers() { return unwrap(await api.get('/admin/teachers')) ?? []; },
  async getTeacher(id) { return unwrap(await api.get(`/admin/teachers/${id}`)); },
  async createTeacher(payload) { return unwrap(await api.post('/admin/teachers', payload)); },
  async updateTeacher(id, payload) { return unwrap(await api.patch(`/admin/teachers/${id}`, payload)); },
  async resetPassword(id, password) { return unwrap(await api.patch(`/admin/teachers/${id}/password`, { password })); },
  async deleteTeacher(id) { return unwrap(await api.delete(`/admin/teachers/${id}`)); },
};
