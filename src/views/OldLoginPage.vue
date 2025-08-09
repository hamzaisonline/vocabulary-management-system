<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { useAuthStore } from "../stores/authStore";

const router = useRouter();
const toast = useToast();

// Loading state
const isLoading = ref(false)
const isAdminLoading = ref(false)

// Form state
const email = ref("");
const password = ref("");

const authStore = useAuthStore();

// Login handler
const handleLogin = async () => {
  // Add your login logic here (e.g., call API)
  try {
    isLoading.value = true
    const response = await authStore.login({
      email: email.value,
      password: password.value,
    });
    if (response.success) {
      console.log(authStore.role)
      if (authStore.role === "student" || authStore.role === "teacher" || authStore.role === "admin") {
        authStore.role = 'student'
        router.push("/student");
      }
    }
  } catch (error) {
    toast.error(error.response.data.error);
    console.log(error);
  } finally {
    isLoading.value = false
    password.value = "";
  }
  console.log("Logging in with:", email.value, password.value);
}



const handleAdminLogin = async () => {
  // Add your login logic here (e.g., call API)
  try {
    isAdminLoading.value = true
    const response = await authStore.login({
      email: 'webstudent@gmail.com',
      password: 'webstudent123',
    });
    if (response.success) {
      console.log(authStore.role)
      if (authStore.role === "student" || authStore.role === "teacher" || authStore.role === "admin") {
        authStore.role = 'admin'
        router.push("/admin");
      }
    }
  } catch (error) {
    toast.error(error.response.data.error);
    console.log(error);
  } finally {
    isAdminLoading.value = true
    password.value = "";
  }
  console.log("Logging in with:", email.value, password.value);
};
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
      <!-- App Name -->
      <img
        src="../assets/images/logo-1.jpeg"
        width="180px"
        style="margin: auto"
      />
      <p class="mt-2 text-sm text-center text-gray-500">
        Welcome back! Please login to your account
      </p>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="mt-6 space-y-4">
        <!-- Email Field -->
        <div>
          <label for="email" class="block text-sm font-medium">Email</label>
          <input
            type="email"
            id="email"
            v-model="email"
            placeholder="Enter your email"
            class="input input-bordered w-full"
            required
          />
        </div>

        <!-- Password Field -->
        <div>
          <label for="password" class="block text-sm font-medium"
            >Password</label
          >
          <input
            type="password"
            id="password"
            v-model="password"
            placeholder="Enter your password"
            class="input input-bordered w-full"
            required
          />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
          <label class="flex items-center space-x-2">
            <input type="checkbox" class="checkbox checkbox-primary" />
            <span class="text-sm">Remember me</span>
          </label>
          <router-link
            to="/forgot-password"
            class="text-sm text-primary hover:underline"
          >
            Forgot password?
          </router-link>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary w-full">
          <span class="loading loading-spinner" v-show="isLoading"></span>
          Login
        </button>
        <button type="button" class="btn btn-primary w-full" @click="handleAdminLogin">
          <span class="loading loading-spinner" v-show="isAdminLoading"></span>
          Admin Login
        </button>
      </form>

      <!-- Register Link -->
      <p class="mt-4 text-sm text-center">
        Don't have an account?
        <router-link to="/create-account" class="text-primary hover:underline">
          Sign up
        </router-link>
      </p>
    </div>
  </div>
</template>
