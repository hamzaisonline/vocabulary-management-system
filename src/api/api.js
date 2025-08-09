// src/services/api.js
import { useAuthStore } from '@/stores/authStore';
import axios from 'axios';
import { ref } from 'vue';
import { useToast } from "vue-toastification";
const toast = useToast();

// const apiBaseUrl = import.meta.env.VUE_APP_API_URL
const apiBaseUrl = 'https://vms.smartpearlsolutions.com/api/'
// const apiBaseUrl = 'http://192.168.8.105:8000/api/v1/'

// Create Axios instance
const api = axios.create({
  baseURL: apiBaseUrl || 'http://localhost:3000/',
  timeout: 600000
})

const isLoading = ref(false)

// Request Interceptor
api.interceptors.request.use(
  (config) => {    
    isLoading.value = true
    const authStore = useAuthStore()
    if (authStore.authToken) {
      config.headers['Authorization'] = `Bearer ${authStore.authToken}`
    }

    if (!navigator.onLine) {
      return Promise.reject({ message: 'No internet connection' })
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor
api.interceptors.response.use(
  (response) => {
    isLoading.value = false 
    return response 
  },
  async (error) => {
    
    const authStore = useAuthStore()
    const originalRequest = error.config

    // Handle 401 Unauthorized
    if (error.response && error.response.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true

        authStore.clearAuth()
        // setTimeout(() => {
        //   window.location.href = '/'          
        // }, 3000);
    }

    // Handle General Errors
    if (!navigator.onLine) {
      toast.error('No internet connection')

    } else if (error.response) {
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          console.log(status, data)          
          break        
        case 400:        
          console.log(status, data)
          break
        case 403:
          console.log(status, data)               
          break
        case 404:
          console.log(status, data)
          break
        case 500:
          console.log(status, data)
          break
        default:
          console.log(status, data)
      }
    }

    return Promise.reject(error)
  }
)

// Utility function for GET request
export const get = async (url, params = {}, options = {}) => {
  try {
    const response = await api.get(url, { params, ...options })
    return response.data
  } catch (error) {
    handleError(error)
    throw error
  }
}

// Utility function for POST request
export const post = async (url, data = {}, options = {}) => {
  try {
    const response = await api.post(url, data, options)
    if (!response.data.success) {
      console.log('post request failed!')
      return response
    }
    return response.data
  } catch (error) {
    handleError(error)
    throw error
  }
}

// Utility function for PUT request
export const put = async (url, data = {}, options = {}) => {
  try {
    const response = await api.put(url, data, options)
    return response.data
  } catch (error) {
    handleError(error)
    throw error
  }
}

// Utility function for PUT request
export const patch = async (url, data = {}, options = {}) => {
  try {
    const response = await api.patch(url, data, options)
    return response.data
  } catch (error) {
    handleError(error)
    throw error
  }
}

// Utility function for DELETE request
export const del = async (url, options = {}) => {
  try {
    const response = await api.delete(url, options)
    return response.data
  } catch (error) {
    handleError(error)
    throw error
  }
}

// Global error handler to display custom error messages
const handleError = (error) => {
  if (error.response && error.response.data && error.response.data.message) {
    toast.error(error.response.data.message)
    console.log(error.response.data.message, 'error')
  } else {
    toast.error('An unexpected error occurred.')
  }
}

export default api
