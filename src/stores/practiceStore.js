import { defineStore } from 'pinia';
import practiceService from '@/service/practiceService';

const messageFor = (error, fallback) =>
  error?.response?.data?.message || error?.message || fallback;

export const usePracticeStore = defineStore('practice', {
  state: () => ({
    currentSession: null,
    questions: [],
    level: null,
    history: [],
    loading: false,
    submitting: false,
    completing: false,
    error: null,
  }),
  actions: {
    async startSession(levelId) {
      this.loading = true;
      this.error = null;
      try {
        const data = await practiceService.startSession(levelId);
        this.currentSession = data.session;
        this.level = data.level;
        this.questions = Array.isArray(data.questions) ? data.questions : [];
        return data;
      } catch (error) {
        this.error = messageFor(error, 'Failed to start practice session');
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async submitAttempt(payload) {
      if (!this.currentSession?.id || this.submitting || this.currentSession.is_completed) return null;
      this.submitting = true;
      this.error = null;
      try {
        return await practiceService.submitAttempt(this.currentSession.id, payload);
      } catch (error) {
        this.error = messageFor(error, 'Failed to submit practice answer');
        throw error;
      } finally {
        this.submitting = false;
      }
    },
    async completeSession() {
      if (!this.currentSession?.id || this.completing || this.currentSession.is_completed) return this.currentSession;
      this.completing = true;
      this.error = null;
      try {
        this.currentSession = await practiceService.completeSession(this.currentSession.id);
        return this.currentSession;
      } catch (error) {
        this.error = messageFor(error, 'Failed to complete practice session');
        throw error;
      } finally {
        this.completing = false;
      }
    },
    async fetchSession(sessionId) {
      this.loading = true;
      this.error = null;
      try {
        this.currentSession = await practiceService.getSession(sessionId);
        return this.currentSession;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load practice session');
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async fetchHistory() {
      this.error = null;
      try {
        this.history = await practiceService.getHistory();
        return this.history;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load practice history');
        throw error;
      }
    },
    resetCurrentSession() {
      this.currentSession = null;
      this.questions = [];
      this.level = null;
      this.error = null;
    },
  },
});
