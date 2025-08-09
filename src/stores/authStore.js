import { defineStore } from "pinia";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    authToken: null,
    user: null,
    role: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.authToken,
    userRole: (state) => state.role,
  },
  actions: {
    async login(credentials) {
      try {
        const dummyUsers = {
          admin: {
            name: "Admin User",
            token: "admin-token",
            role: "admin",
            password: "admin123",
          },
          teacher: {
            name: "Teacher User",
            token: "teacher-token",
            role: "teacher",
            password: "teacher123",
          },
          student: {
            name: "Student User",
            token: "student-token",
            role: "student",
            password: "student123",
          },
        };

        const user = dummyUsers[credentials.username.toLowerCase()];
        if (!user || credentials.password !== user.password) {
          throw new Error("Invalid username or password");
        }

        this.authToken = user.token;
        this.user = user;
        this.role = user.role;

        return user;
      } catch (error) {
        console.error("Login failed:", error);
        throw error;
      }
    },

    async logout() {
      this.authToken = null;
      this.user = null;
      this.role = null;
    },

    async clearAuth() {
      this.authToken = null;
      this.user = null;
      this.role = null;
    },
  },
  persist: {
    enabled: true,
    strategies: [
      {
        key: "authStore",
        storage: localStorage,
        paths: ["authToken", "role"],
      },
    ],
  },
});
