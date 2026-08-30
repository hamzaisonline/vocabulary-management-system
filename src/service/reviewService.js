import api from '@/api/api';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

const asArray = (data) => Array.isArray(data) ? data : [];

const reviewService = {
  async getReviewQueue() {
    return asArray(unwrap(await api.get('/student/review')));
  },
  async getLevelReview(levelId) {
    return asArray(unwrap(await api.get(`/student/vocabulary-levels/${levelId}/review`)));
  },
  async submitReview(wordId, correct) {
    return unwrap(await api.post(`/student/vocabulary-words/${wordId}/review`, {
      correct: Boolean(correct),
    }));
  },
};

export default reviewService;
