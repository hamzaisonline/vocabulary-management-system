import api from '@/api/api';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

const practiceService = {
  async startSession(levelId) {
    return unwrap(await api.post(`/student/vocabulary-levels/${levelId}/practice`));
  },
  async submitAttempt(sessionId, payload) {
    return unwrap(await api.post(`/student/practice-sessions/${sessionId}/attempts`, {
      vocabulary_word_id: payload.vocabulary_word_id,
      submitted_answer: payload.submitted_answer,
    }));
  },
  async completeSession(sessionId) {
    return unwrap(await api.post(`/student/practice-sessions/${sessionId}/complete`));
  },
  async getSession(sessionId) {
    return unwrap(await api.get(`/student/practice-sessions/${sessionId}`));
  },
  async getHistory() {
    const data = unwrap(await api.get('/student/practice-sessions'));
    return Array.isArray(data) ? data : [];
  },
};

export default practiceService;
