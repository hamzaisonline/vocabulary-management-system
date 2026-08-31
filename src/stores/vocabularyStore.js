import { defineStore } from 'pinia';
import vocabularyService from '@/service/vocabularyService';

const errorMessage = (error, fallback) =>
  error?.response?.data?.message || error?.message || fallback;

export const useVocabularyStore = defineStore('vocabularyStore', {
  state: () => ({
    levels: [],
    selectedLevel: null,
    loading: false,
    error: null,
    importSummary: null,

    // Local navigation state only; learning progress is stored separately.
    currentLevelId: null,
    currentWordIndex: 0,
    phase: 'learn',
  }),

  getters: {
    currentLevel(state) {
      return state.levels.find(
        (level) => String(level.id) === String(state.currentLevelId)
      ) || null;
    },
    words(state) {
      return this.currentLevel?.words || [];
    },
    currentWord() {
      return this.words[this.currentWordIndex] || {};
    },
    nextPendingLevel(state) {
      return state.levels.find((level) => (level.words || []).length > 0) || null;
    },
  },

  actions: {
    normalizeWord(word) {
      return {
        ...word,
        audio: word.audio_url || '',
      };
    },

    normalizeLevel(level) {
      return {
        ...level,
        words: Array.isArray(level.words)
          ? level.words.map((word) => this.normalizeWord(word))
          : [],
      };
    },

    replaceLevel(level) {
      const normalized = this.normalizeLevel(level);
      const index = this.levels.findIndex((item) => String(item.id) === String(normalized.id));
      if (index === -1) this.levels.push(normalized);
      else this.levels[index] = normalized;
      return normalized;
    },

    async fetchLevels(scope = null) {
      this.loading = true;
      this.error = null;
      try {
        const levels = await vocabularyService.getLevels(scope);
        this.levels = levels.map((level) => this.normalizeLevel(level));
        return this.levels;
      } catch (error) {
        this.error = errorMessage(error, 'Failed to load vocabulary levels');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchLevel(id) {
      this.loading = true;
      this.error = null;
      if (String(this.selectedLevel?.id) !== String(id)) this.selectedLevel = null;
      try {
        const level = await vocabularyService.getLevel(id);
        this.selectedLevel = this.replaceLevel(level);
        return this.selectedLevel;
      } catch (error) {
        this.error = errorMessage(error, 'Failed to load vocabulary level');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createLevel(payload) {
      this.loading = true;
      this.error = null;
      try {
        const level = this.replaceLevel(await vocabularyService.createLevel(payload));
        return level;
      } catch (error) {
        this.error = errorMessage(error, 'Failed to create vocabulary level');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateLevel(id, payload) {
      this.loading = true;
      this.error = null;
      try {
        await vocabularyService.updateLevel(id, payload);
        return await this.fetchLevel(id);
      } catch (error) {
        this.error = errorMessage(error, 'Failed to update vocabulary level');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteLevel(id) {
      this.loading = true;
      this.error = null;
      try {
        await vocabularyService.deleteLevel(id);
        this.levels = this.levels.filter((level) => String(level.id) !== String(id));
        if (String(this.selectedLevel?.id) === String(id)) this.selectedLevel = null;
      } catch (error) {
        this.error = errorMessage(error, 'Failed to delete vocabulary level');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createWord(levelId, payload) {
      this.loading = true;
      this.error = null;
      try {
        await vocabularyService.createWord(levelId, payload);
        return await this.fetchLevel(levelId);
      } catch (error) {
        this.error = errorMessage(error, 'Failed to create vocabulary word');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateWord(wordId, payload) {
      this.loading = true;
      this.error = null;
      try {
        const word = await vocabularyService.updateWord(wordId, payload);
        const levelId = word?.vocabulary_level_id || this.selectedLevel?.id;
        if (levelId) await this.fetchLevel(levelId);
        return word;
      } catch (error) {
        this.error = errorMessage(error, 'Failed to update vocabulary word');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteWord(wordId) {
      this.loading = true;
      this.error = null;
      try {
        await vocabularyService.deleteWord(wordId);
        const levelId = this.selectedLevel?.id;
        if (levelId) await this.fetchLevel(levelId);
      } catch (error) {
        this.error = errorMessage(error, 'Failed to delete vocabulary word');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async importWords(levelId, file) {
      this.loading = true;
      this.error = null;
      this.importSummary = null;
      try {
        this.importSummary = await vocabularyService.importWords(levelId, file);
        await this.fetchLevel(levelId);
        return this.importSummary;
      } catch (error) {
        this.error = errorMessage(error, 'Failed to import vocabulary words');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    setLevel(levelId) {
      this.currentLevelId = levelId;
      this.currentWordIndex = 0;
    },
    nextWord() {
      if (this.currentWordIndex < this.words.length - 1) this.currentWordIndex++;
    },
    setPhase(phase) {
      this.phase = phase;
    },
  },

});
