<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../stores/authStore';

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();

const isLoading = ref(false);
const email = ref('');
const password = ref('');

const getErrorMessage = (error) => {
  if (error?.response?.data?.message) {
    return error.response.data.message;
  }

  if (error?.response?.data?.errors) {
    const firstError = Object.values(error.response.data.errors).flat()[0];
    if (firstError) {
      return firstError;
    }
  }

  return error?.message || 'Unable to log in right now.';
};

const handleLogin = async () => {
  const trimmedEmail = email.value.trim();
  const trimmedPassword = password.value.trim();

  if (!trimmedEmail || !trimmedPassword) {
    toast.error('Please enter both email and password.');
    return;
  }

  try {
    isLoading.value = true;
    await authStore.login({
      email: trimmedEmail,
      password: trimmedPassword,
    });

    const role = authStore.user?.role?.name ?? authStore.role;

    if (role === 'student') {
      router.push('/student');
      return;
    }

    if (role === 'teacher') {
      router.push('/teacher');
      return;
    }

    if (role === 'admin') {
      router.push('/admin');
      return;
    }

    authStore.clearAuth();
    toast.error('This account role is not available in the app.');
  } catch (error) {
    toast.error(getErrorMessage(error));
  } finally {
    isLoading.value = false;
    password.value = '';
  }
};
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
      <img src="../assets/images/logo-1.jpeg" width="180px" style="margin: auto" />
      <p class="mt-2 text-sm text-center text-gray-500">
        Welcome back! Please login to your account
      </p>

      <form @submit.prevent="handleLogin" class="mt-6 space-y-4">
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

        <div>
          <label for="password" class="block text-sm font-medium">Password</label>
          <input
            type="password"
            id="password"
            v-model="password"
            placeholder="Enter password"
            class="input input-bordered w-full"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <span class="loading loading-spinner" v-show="isLoading"></span>
          Login
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
