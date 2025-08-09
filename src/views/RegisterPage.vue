<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { useAuthStore } from "../stores/authStore";
import { useUserStore } from "../stores/userStore";

const router = useRouter()
const userStore = useUserStore();
const authStore = useAuthStore();
const toast = useToast();

// Form state
const name = ref("");
const email = ref("");
const password = ref("");
const confirmPassword = ref("");

// Register handler
const handleRegister = async () => {
  if (password.value !== confirmPassword.value) {
    toast.error("Passwords do not match!");
    return;
  }

  try {
    const createUser = await userStore.storeUser({
      name: name.value,
      email: email.value,
      password: password.value,
      role: 'student',
      type: 'student'
    });
    console.log(createUser)
    if (createUser.success) {
      console.log('yes')
      const response = await authStore.login({
        email: email.value,
        password: password.value,
      });
  
      if( response.success ){
        if( authStore.role === 'student' ){
          router.push('/student')
        }       
      }

    }
  } catch (error) {
    toast.error(error?.response?.data?.error);
    console.log(error);
  } finally {
    password.value = "";
    confirmPassword.value = "";
  }

  // Add your register logic here (e.g., call API)
  console.log("Registering user:", {
    name: name.value,
    email: email.value,
    password: password.value,
  });
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
        Create your account and start your journey
      </p>

      <!-- Register Form -->
      <form @submit.prevent="handleRegister" class="mt-6 space-y-4">
        <!-- Name Field -->
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

        <!-- Confirm Password Field -->
        <div>
          <label for="confirm-password" class="block text-sm font-medium"
            >Confirm Password</label
          >
          <input
            type="password"
            id="confirm-password"
            v-model="confirmPassword"
            placeholder="Confirm your password"
            class="input input-bordered w-full"
            required
          />
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary w-full">
          Create Account
        </button>
      </form>

      <!-- Login Link -->
      <p class="mt-4 text-sm text-center">
        Already have an account?
        <router-link to="/" class="text-primary hover:underline">
          Log in
        </router-link>
      </p>
    </div>
  </div>
</template>
