import { defineStore } from 'pinia';
import classService from '@/service/classService';

export const useClassStore = defineStore('classStore', {
  state: () => ({
    classes: [],
    selectedClass: null,
    loading: false,
    error: null,
  }),

  getters: {
    getClassById: (state) => (classId) => {
      return state.classes.find((cls) => cls.id === classId);
    },

    isEmpty: (state) => state.classes.length === 0,
  },

  actions: {
    /**
     * Fetch all classes
     * Role-aware: backend returns classes based on user role
     */
    async fetchClasses() {
      this.loading = true;
      this.error = null;

      try {
        this.classes = await classService.getClasses();
        return this.classes;
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Failed to fetch classes';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch a single class by ID
     */
    async fetchClass(classId) {
      this.loading = true;
      this.error = null;

      try {
        const classData = await classService.getClass(classId);
        this.selectedClass = classData;

        // Also update in classes array if present
        const index = this.classes.findIndex((cls) => cls.id === classId);
        if (index !== -1) {
          this.classes[index] = classData;
        } else {
          this.classes.push(classData);
        }

        return classData;
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Failed to fetch class';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Create a new class
     * Payload: { name, description, language } + optionally teacher_id for admin
     */
    async createClass(payload) {
      this.loading = true;
      this.error = null;

      try {
        const newClass = await classService.createClass(payload);
        this.classes.push(newClass);
        return newClass;
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Failed to create class';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Update a class
     */
    async updateClass(classId, payload) {
      this.loading = true;
      this.error = null;

      try {
        const updated = await classService.updateClass(classId, payload);

        // Update in classes array
        const index = this.classes.findIndex((cls) => cls.id === classId);
        if (index !== -1) {
          this.classes[index] = updated;
        }

        // Update selectedClass if it's the one being edited
        if (this.selectedClass?.id === classId) {
          this.selectedClass = updated;
        }

        return updated;
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Failed to update class';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Delete a class
     */
    async deleteClass(classId) {
      this.loading = true;
      this.error = null;

      try {
        await classService.deleteClass(classId);

        // Remove from classes array
        this.classes = this.classes.filter((cls) => cls.id !== classId);

        // Clear selectedClass if it was the deleted one
        if (this.selectedClass?.id === classId) {
          this.selectedClass = null;
        }

        return { success: true };
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Failed to delete class';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Enroll a student in a class
     */
    async enrollStudent(classId, studentId) {
      this.loading = true;
      this.error = null;

      try {
        const enrollment = await classService.enrollStudent(classId, studentId);

        // Refresh the selected class to get updated students
        if (this.selectedClass?.id === classId) {
          await this.fetchClass(classId);
        }

        return enrollment;
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Failed to enroll student';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Remove a student from a class
     */
    async removeStudent(classId, studentId) {
      this.loading = true;
      this.error = null;

      try {
        await classService.removeStudent(classId, studentId);

        // Refresh the selected class to get updated students
        if (this.selectedClass?.id === classId) {
          await this.fetchClass(classId);
        }

        return { success: true };
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Failed to remove student';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Clear error state
     */
    clearError() {
      this.error = null;
    },

    /**
     * Select a class (local state management)
     */
    selectClass(classId) {
      const cls = this.classes.find((c) => c.id === classId);
      if (cls) {
        this.selectedClass = cls;
      }
    },
  },

  persist: {
    key: 'classStore',
    storage: localStorage,
    pick: ['selectedClass'],
  },
});
