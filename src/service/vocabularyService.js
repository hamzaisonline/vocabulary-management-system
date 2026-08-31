import api from '@/api/api';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

export const buildVocabularyWordFormData = (payload, method = null) => {
  const formData = new FormData();
  if (method) formData.append('_method', method);
  ['word', 'translation', 'example', 'notes'].forEach((field) => {
    if (Object.prototype.hasOwnProperty.call(payload, field)) {
      formData.append(field, payload[field] ?? '');
    }
  });
  if (payload.audio instanceof File) formData.append('audio', payload.audio);
  return formData;
};

const vocabularyService = {
  async getLevels(scope = null) {
    const data = unwrap(await api.get('/vocabulary/levels', {
      params: scope ? { scope } : undefined,
    }));
    return Array.isArray(data) ? data : [];
  },

  async getLevel(id) {
    return unwrap(await api.get(`/vocabulary/levels/${id}`));
  },

  async createLevel(payload) {
    return unwrap(await api.post('/vocabulary/levels', payload));
  },

  async updateLevel(id, payload) {
    return unwrap(await api.patch(`/vocabulary/levels/${id}`, payload));
  },

  async deleteLevel(id) {
    return api.delete(`/vocabulary/levels/${id}`);
  },

  async createWord(levelId, payload) {
    return unwrap(await api.post(
      `/vocabulary/levels/${levelId}/words`,
      buildVocabularyWordFormData(payload)
    ));
  },

  async updateWord(wordId, payload) {
    return unwrap(await api.post(
      `/vocabulary/words/${wordId}`,
      buildVocabularyWordFormData(payload, 'PATCH')
    ));
  },

  async deleteWord(wordId) {
    return api.delete(`/vocabulary/words/${wordId}`);
  },

  async importWords(levelId, file) {
    const formData = new FormData();
    formData.append('file', file);
    return unwrap(await api.post(`/vocabulary/levels/${levelId}/import`, formData));
  },
};

export default vocabularyService;
