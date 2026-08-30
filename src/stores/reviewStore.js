import { defineStore } from 'pinia';
import reviewService from '@/service/reviewService';

const messageFor = (error, fallback) =>
  error?.response?.data?.message || error?.message || fallback;

const updateCollection = (items, result) => {
  if (result.completed) {
    return items.filter((item) => String(item.vocabulary_word_id) !== String(result.vocabulary_word_id));
  }
  return items.map((item) =>
    String(item.vocabulary_word_id) === String(result.vocabulary_word_id)
      ? { ...item, ...result }
      : item
  );
};

export const useReviewStore = defineStore('review', {
  state: () => ({
    queue: [],
    selectedLevelReview: [],
    loading: false,
    submittingWordId: null,
    error: null,
    lastReviewResult: null,
  }),
  actions: {
    async fetchQueue() {
      this.loading = true;
      this.error = null;
      try {
        this.queue = await reviewService.getReviewQueue();
        return this.queue;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load review queue');
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async fetchLevelReview(levelId) {
      this.loading = true;
      this.error = null;
      try {
        this.selectedLevelReview = await reviewService.getLevelReview(levelId);
        return this.selectedLevelReview;
      } catch (error) {
        this.error = messageFor(error, 'Failed to load level review');
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async submitReview(wordId, correct) {
      if (this.submittingWordId !== null) return null;
      this.submittingWordId = wordId;
      this.error = null;
      try {
        this.lastReviewResult = await reviewService.submitReview(wordId, correct);
        this.queue = updateCollection(this.queue, this.lastReviewResult);
        this.selectedLevelReview = updateCollection(this.selectedLevelReview, this.lastReviewResult);
        return this.lastReviewResult;
      } catch (error) {
        this.error = messageFor(error, 'Failed to submit review');
        throw error;
      } finally {
        this.submittingWordId = null;
      }
    },
    reset() {
      this.queue = [];
      this.selectedLevelReview = [];
      this.error = null;
      this.lastReviewResult = null;
      this.submittingWordId = null;
    },
  },
});
