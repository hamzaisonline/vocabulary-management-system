import { fetchUserProfile, login, logout } from '@/service/authService'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    authToken: null, // No need to get this from localStorage manually; the plugin will handle it
    user: null,
    role: null
  }),
  getters: {
    // A getter to check if the user is authenticated based on the presence of a token
    isAuthenticated: (state) => !!state.authToken,
    // A getter to return the user's role
    userRole: (state) => state.role
  },
  actions: {
    async login(credentials) {
      try {
        const data = await login(credentials)        
        this.authToken = data.token

        this.user = data.user
        
        // Assign user roles based on returned user data
        if (this.user.supervisor_code) {
          this.role = 'supervisor'
        } else if (this.user.staff_code) {
          this.role = 'beautician'
        } else if (this.user.cashier_code) {
          this.role = 'cashier'
        } else if (this.user.customer_code) {
          this.role = 'client'
        } else if (this.user.name && this.user.name === 'warehouse') {
          this.role = 'warehouse'
        } else {
          this.role = 'admin'
        }

        return data
      } catch (error) {
        console.error('Login failed:', error)
        throw error // Let the component handle the error
      }
    },

    async fetchUserProfile() {
      try {
        const data = await fetchUserProfile()
        this.user = data
        // Optionally update roles if necessary
      } catch (error) {
        console.error('Failed to fetch user profile:', error)
      }
    },

    async logout() {
      try {
        const response = await logout()

        // Clear the state
        this.authToken = null
        this.user = null
        this.role = null

        return response
        
      } catch (error) {
        console.error('Logout failed:', error)
      }
    },

    async forceLogin() {
      try {
        this.login()
      } catch(error){
        console.error('Error!', error)
      }
    },

    async clearAuth() {
      this.authToken = null
      this.user = null
      this.role = null
    }

  },
  persist: {
    enabled: true, // Enable persistence
    strategies: [
      {
        key: 'authStore', // Custom key in localStorage
        storage: localStorage, // Use localStorage to persist the store
        paths: ['authToken', 'role'] // Persist only 'authToken' and 'role'
      }
    ]
  }
})
