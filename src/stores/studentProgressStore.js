import { defineStore } from 'pinia';
import progressService from '@/service/progressService';

const messageFor = (error, fallback) =>
  error?.response?.data?.message || error?.message || fallback;

export const useStudentProgressStore = defineStore('studentProgress', {
  state: () => ({
    progressSummary: [],
    selectedLevelProgress: null,
    totalXp: 0,
    lastProgressUpdate: null,
    loading: false,
    submittingWordId: null,
    error: null,
  }),

  getters: {
    levelById: (state) => (levelId) =>
      state.progressSummary.find((level) => String(level.id) === String(levelId)) || null,
    wordById: (state) => (wordId) =>
      state.selectedLevelProgress?.words?.find(
        (word) => String(word.id) === String(wordId)
      ) || null,
    masteredWords: (state) => state.progressSummary.reduce(
      (total, level) => total + Number(level.mastered_words || 0), 0
    ),
    completedLevels: (state) => state.progressSummary.filter((level) => level.completed),
    submitting: (state) => state.submittingWordId !== null,
  },

  actions: {
    async fetchProgress() {
      this.loading = true;
      this.error = null;
      try {
        const progress = await progressService.getStudentProgress();
        this.totalXp = progress.total_xp;
        this.progressSummary = progress.levels;
        return this.progressSummary;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load student progress');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchLevelProgress(levelId) {
      this.loading = true;
      this.error = null;
      try {
        this.selectedLevelProgress = await progressService.getLevelProgress(levelId);
        return this.selectedLevelProgress;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load vocabulary progress');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async submitWordProgress(wordId, correct) {
      if (this.submittingWordId !== null) return null;

      this.submittingWordId = wordId;
      this.error = null;
      try {
        this.lastProgressUpdate = await progressService.updateWordProgress(wordId, correct);
        const levelId = this.selectedLevelProgress?.id;
        if (levelId) await this.fetchLevelProgress(levelId);
        await this.fetchProgress();
        return this.lastProgressUpdate;
      } catch (error) {
        this.error = messageFor(error, 'Failed to save vocabulary progress');
        throw error;
      } finally {
        this.submittingWordId = null;
      }
    },
  },
});
