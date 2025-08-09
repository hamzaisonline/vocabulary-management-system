<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore()
const router = useRouter()

const logout = async () => {
  const response = await authStore.logout()
  router.push("/");
  // if( response.success ) {
  //   router.push("/");
  // }
}
</script>

<template>
  <div class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="avatar placeholder">
      <div class="bg-neutral text-neutral-content w-8 rounded-full">
        <span class="text-xs">AD</span>
      </div>
    </div>
    <ul
      tabindex="0"
      class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow"
    >
      <li><router-link to="/profile">Profile</router-link></li>
      <li><button @click="logout">logout</button></li>
    </ul>
  </div>
</template>
