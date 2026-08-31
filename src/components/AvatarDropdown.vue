<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();
const router = useRouter();

const logout = async () => {
  try {
    await authStore.logout();
  } finally {
    await router.replace('/login');
  }
};

const initials = authStore.user?.name
  ?.split(' ')
  .filter(Boolean)
  .slice(0, 2)
  .map((part) => part[0]?.toUpperCase() ?? '')
  .join('') || 'U';
</script>

<template>
  <div class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="avatar placeholder">
      <div class="bg-neutral text-neutral-content w-8 rounded-full">
        <span class="text-xs">{{ initials }}</span>
      </div>
    </div>
    <ul
      tabindex="0"
      class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow"
    >
      <li class="pointer-events-none px-4 py-2 text-sm text-base-content/70">
        {{ authStore.user?.name || 'User' }}
      </li>
      <li class="pointer-events-none px-4 py-2 text-xs uppercase tracking-wide text-base-content/60">
        {{ authStore.role || 'guest' }}
      </li>
      <li><button @click="logout">logout</button></li>
    </ul>
  </div>
</template>
