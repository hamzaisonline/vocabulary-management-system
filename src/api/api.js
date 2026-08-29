import { useAuthStore } from '@/stores/authStore';
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  timeout: 30000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();

    if (authStore.authToken) {
      config.headers.Authorization = `Bearer ${authStore.authToken}`;
    }

    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const authStore = useAuthStore();
    const status = error?.response?.status;

    if (status === 401) {
      authStore.clearAuth();
    }

    return Promise.reject(error);
  }
);

export const get = async (url, config = {}) => {
  const response = await api.get(url, config);
  return response.data;
};

export const post = async (url, payload = {}, config = {}) => {
  const response = await api.post(url, payload, config);
  return response.data;
};

export const put = async (url, payload = {}, config = {}) => {
  const response = await api.put(url, payload, config);
  return response.data;
};

export const del = async (url, config = {}) => {
  const response = await api.delete(url, config);
  return response.data;
};

export default api;
