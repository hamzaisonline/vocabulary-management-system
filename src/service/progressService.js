import api from '@/api/api';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

const progressService = {
  async getStudentProgress() {
    const data = unwrap(await api.get('/student/progress'));
    return {
      total_xp: Number(data?.total_xp ?? 0),
      levels: Array.isArray(data?.levels) ? data.levels : [],
    };
  },

  async getLevelProgress(levelId) {
    return unwrap(await api.get(`/student/vocabulary-levels/${levelId}/progress`));
  },

  async updateWordProgress(wordId, correct) {
    return unwrap(await api.post(`/student/vocabulary-words/${wordId}/progress`, {
      correct: Boolean(correct),
    }));
  },
};

export default progressService;
