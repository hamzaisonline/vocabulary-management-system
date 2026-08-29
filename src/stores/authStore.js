import api from '@/api/api';
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    authToken: null,
    user: null,
    role: null,
    loading: false,
    isInitialized: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.authToken,
    userRole: (state) => state.role ?? state.user?.role?.name ?? null,
    isAdmin: (state) => state.role === 'admin',
    isTeacher: (state) => state.role === 'teacher',
    isStudent: (state) => state.role === 'student',
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
          password_confirmation: payload.password_confirmation,
        });

        const backendResponse = response?.data ?? response;
        const token = backendResponse?.data?.token ?? null;
        const user = backendResponse?.data?.user ?? null;

        if (!backendResponse?.success || !token || !user) {
          throw new Error(backendResponse?.message || 'Registration response was incomplete.');
        }

        this.setSession(token, user);
        return backendResponse;
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

        const backendResponse = response?.data ?? response;
        const token = backendResponse?.data?.token ?? null;
        const user = backendResponse?.data?.user ?? null;

        if (!backendResponse?.success || !token || !user) {
          throw new Error(backendResponse?.message || 'Login response was incomplete.');
        }

        this.setSession(token, user);
        return backendResponse;
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
        if (error?.response?.status === 401) {
          this.clearAuth();
        }

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
        this.isInitialized = true;
        return false;
      }

      try {
        const response = await api.get('/auth/me');
        const user = response?.data?.user ?? response?.user ?? null;

        if (!user) {
          this.clearAuth();
          return false;
        }

        this.user = user;
        this.role = user.role?.name ?? user.role ?? null;
        this.isInitialized = true;
        return true;
      } catch (error) {
        if (error?.response?.status === 401) {
          this.clearAuth();
        }

        this.isInitialized = true;
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
    key: 'auth',
    storage: localStorage,
    pick: ['authToken', 'user', 'role'],
  },
});
