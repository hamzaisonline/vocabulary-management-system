import api from '@/api/api';

/**
 * Class and Enrollment API service
 * Handles all backend communication for classes and student enrollments
 */

const classService = {
  /**
   * Get all classes
   * - Admin: returns all classes
   * - Teacher: returns only their classes
   * - Student: returns only enrolled classes
   */
  async getClasses() {
    const response = await api.get('/classes');
    return response?.data?.data ?? response?.data ?? [];
  },

  /**
   * Get a single class by ID
   */
  async getClass(classId) {
    const response = await api.get(`/classes/${classId}`);
    return response?.data?.data ?? response?.data ?? null;
  },

  /**
   * Create a new class
   * Teacher: teacher_id is set automatically by backend
   * Admin: can optionally specify teacher_id
   */
  async createClass(payload) {
    const response = await api.post('/classes', payload);
    return response?.data?.data ?? response?.data ?? null;
  },

  /**
   * Update a class (PATCH)
   */
  async updateClass(classId, payload) {
    const response = await api.patch(`/classes/${classId}`, payload);
    return response?.data?.data ?? response?.data ?? null;
  },

  /**
   * Delete a class
   */
  async deleteClass(classId) {
    const response = await api.delete(`/classes/${classId}`);
    return response?.data ?? { success: true };
  },

  /**
   * Enroll a student in a class
   * Payload: { student_id: 123 }
   */
  async enrollStudent(classId, studentId) {
    const response = await api.post(`/classes/${classId}/students`, {
      student_id: studentId,
    });
    return response?.data?.data ?? response?.data ?? null;
  },

  /**
   * Remove a student from a class
   */
  async removeStudent(classId, studentId) {
    const response = await api.delete(`/classes/${classId}/students/${studentId}`);
    return response?.data ?? { success: true };
  },
};

export default classService;
