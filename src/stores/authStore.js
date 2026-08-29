import api from '@/api/api';
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    authToken: null,
    user: null,
    role: null,
    loading: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.authToken,
    userRole: (state) => state.role ?? state.user?.role?.name ?? null,
  },
  actions: {
    setSession(token, user) {
      this.authToken = token;
      this.user = user;
      this.role = user?.role?.name ?? user?.role ?? null;
    },

    async register(payload) {
      this.loading = true;

      try {
        const response = await api.post('/auth/register', {
          name: payload.name,
          email: payload.email,
          password: payload.password,
        });

        const token = response?.data?.token ?? response?.token ?? null;
        const user = response?.data?.user ?? response?.user ?? null;

        if (!token || !user) {
          throw new Error('Registration response was incomplete.');
        }

        this.setSession(token, user);
        return response;
      } finally {
        this.loading = false;
      }
    },

    async login(credentials) {
      this.loading = true;

      try {
        const email = credentials.email ?? credentials.username;
        const response = await api.post('/auth/login', {
          email,
          password: credentials.password,
        });

        const token = response?.data?.token ?? response?.token ?? null;
        const user = response?.data?.user ?? response?.user ?? null;

        if (!token || !user) {
          throw new Error('Login response was incomplete.');
        }

        this.setSession(token, user);
        return response;
      } finally {
        this.loading = false;
      }
    },

    async fetchCurrentUser() {
      if (!this.authToken) {
        return null;
      }

      try {
        const response = await api.get('/auth/me');
        const user = response?.data?.user ?? response?.user ?? null;

        if (!user) {
          this.clearAuth();
          return null;
        }

        this.user = user;
        this.role = user.role?.name ?? user.role ?? null;
        return user;
      } catch (error) {
        this.clearAuth();
        throw error;
      }
    },

    async logout() {
      if (!this.authToken) {
        this.clearAuth();
        return { success: true };
      }

      try {
        await api.post('/auth/logout');
      } catch (error) {
        if (error?.response?.status !== 401) {
          throw error;
        }
      } finally {
        this.clearAuth();
      }

      return { success: true };
    },

    async restoreSession() {
      if (!this.authToken) {
        return false;
      }

      try {
        await this.fetchCurrentUser();
        return true;
      } catch (error) {
        this.clearAuth();
        return false;
      }
    },

    clearAuth() {
      this.authToken = null;
      this.user = null;
      this.role = null;
    },
  },
  persist: {
    enabled: true,
    strategies: [
      {
        key: 'authStore',
        storage: localStorage,
        paths: ['authToken', 'user', 'role'],
      },
    ],
  },
});
