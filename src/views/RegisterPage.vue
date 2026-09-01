<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../stores/authStore';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const name = ref('');
const email = ref('');
const password = ref('');
const confirmPassword = ref('');

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

  return error?.message || 'Unable to create your account right now.';
};

const handleRegister = async () => {
  if (!name.value.trim() || !email.value.trim() || !password.value.trim() || !confirmPassword.value.trim()) {
    toast.error('Please complete all required fields.');
    return;
  }

  if (password.value !== confirmPassword.value) {
    toast.error('Passwords do not match!');
    return;
  }

  try {
    const result = await authStore.register({
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: confirmPassword.value,
    });

    if (result?.success !== true) {
      toast.error(result?.message || 'Registration failed.');
      return;
    }

    const role = authStore.user?.role?.name ?? authStore.role;

    if (role === 'student') {
      toast.success(result.message || 'Registration successful.');
      router.push('/student');
      return;
    }

    toast.error('The registered account is not a student account.');
  } catch (error) {
    toast.error(getErrorMessage(error));
  } finally {
    password.value = '';
    confirmPassword.value = '';
  }
};
</script>

<template>
  <div class="flex min-h-dvh items-center justify-center bg-gray-100 p-3 sm:p-6">
    <div class="w-full max-w-md p-4 bg-white rounded-lg shadow-lg sm:p-6">
      <img src="../assets/images/logo-1.jpeg" width="180px" style="margin: auto" />
      <p class="mt-2 text-sm text-center text-gray-500">
        Create your account and start your journey
      </p>

      <form @submit.prevent="handleRegister" class="mt-6 space-y-4">
        <div>
          <label for="name" class="block text-sm font-medium">Name</label>
          <input
            type="text"
            id="name"
            v-model="name"
            placeholder="Enter your name"
            class="input input-bordered w-full"
            required
          />
        </div>

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
            placeholder="Enter your password"
            class="input input-bordered w-full"
            required
          />
        </div>

        <div>
          <label for="confirm-password" class="block text-sm font-medium">Confirm Password</label>
          <input
            type="password"
            id="confirm-password"
            v-model="confirmPassword"
            placeholder="Confirm your password"
            class="input input-bordered w-full"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary w-full">
          Create Account
        </button>
      </form>

      <p class="mt-4 text-sm text-center">
        Already have an account?
        <router-link to="/" class="text-primary hover:underline">
          Log in
        </router-link>
      </p>
    </div>
  </div>
</template>
