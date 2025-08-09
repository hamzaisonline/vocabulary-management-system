<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { useAuthStore } from "../stores/authStore";

const router = useRouter();
const toast = useToast();

// Loading states
const isLoading = ref(false);
const isAdminLoading = ref(false);

// Form state
const email = ref(""); // Use this as username input
const password = ref(""); // Optional — not used for dummy login

const authStore = useAuthStore();

// Main Login Handler
const handleLogin = async () => {
  try {
    isLoading.value = true;
    const response = await authStore.login({
      username: email.value.toLowerCase(),
      password: password.value
    });

    // Role-based redirection
    if (authStore.role === "student") {
      router.push("/student");
    } else if (authStore.role === "teacher") {
      router.push("/teacher");
    } else if (authStore.role === "admin") {
      router.push("/admin");
    }
  } catch (error) {
    toast.error("Invalid username or password. Try again.");
    console.error(error);
  } finally {
    isLoading.value = false;
    password.value = "";
  }
};

const handleAdminLogin = async () => {
  try {
    isAdminLoading.value = true;
    const response = await authStore.login({
      username: "admin"
    });

    if (authStore.role === "admin") {
      router.push("/admin");
    }
  } catch (error) {
    toast.error("Admin login failed.");
    console.error(error);
  } finally {
    isAdminLoading.value = false;
  }
};
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
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
        <!-- Username Field -->
        <div>
          <label for="email" class="block text-sm font-medium">Username</label>
          <input
            type="text"
            id="email"
            v-model="email"
            placeholder="Username"
            class="input input-bordered w-full"
            required
          />
        </div>

        <!-- Dummy Password (not used) -->
        <div>
          <label for="password" class="block text-sm font-medium">Password</label>
          <input
            type="password"
            id="password"
            v-model="password"
            placeholder="********"
            class="input input-bordered w-full"
          />
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary w-full">
          <span class="loading loading-spinner" v-show="isLoading"></span>
          Login
        </button>

        <!-- Direct Admin Login -->
        <button type="button" class="btn btn-secondary w-full" @click="handleAdminLogin">
          <span class="loading loading-spinner" v-show="isAdminLoading"></span>
          Quick Admin Login
        </button>
      </form>

      <p class="mt-4 text-sm text-center">
        Don't have an account?
        <router-link to="/create-account" class="text-primary hover:underline">
          Sign up
        </router-link>
      </p>
    </div>
  </div>
</template>