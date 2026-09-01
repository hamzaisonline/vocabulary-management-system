<script setup>
import { ref, watch } from "vue";
import { useRoute } from "vue-router";
import AppNavbar from "../components/AppNavbar.vue";
import AvatarDropdown from "../components/AvatarDropdown.vue";
import ToggleTheme from "../components/ToggleTheme.vue";

const route = useRoute()

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

watch(() => route.fullPath, () => { isSidebarOpen.value = false; });
</script>

<template>
  <div class="flex min-h-dvh w-full overflow-hidden">
    <!-- Sidebar -->
    <aside
      class="hidden lg:flex lg:flex-col w-64 bg-base-100 border-r shadow-md"
    >
    <div class="p-4">
      <img src="../assets/images/logo-1.jpeg" width="100px" />
    </div>
    
      <AppNavbar />
    </aside>

    <!-- Mobile Sidebar -->
    <div v-if="isSidebarOpen" class="fixed inset-0 z-40 bg-black/40 lg:hidden" aria-hidden="true" @click="toggleSidebar"></div>
    <aside
      class="fixed inset-y-0 left-0 z-50 flex w-[min(20rem,88vw)] flex-col overflow-y-auto bg-base-100 shadow-xl transition-transform duration-200 lg:hidden"
      :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
      :aria-hidden="!isSidebarOpen"
    >
      <div class="p-4 flex justify-between items-center border-b border-base-200">
        <img src="../assets/images/logo-1.jpeg" width="100px" />
        <button
          class="btn btn-sm btn-ghost text-base-content"
          @click="toggleSidebar"
          aria-label="Close navigation"
        >
          ✖
        </button>
      </div>
      
      <AppNavbar />

    </aside>

    <!-- Main Content -->
    <div class="flex min-w-0 flex-col flex-1">
      <!-- Top Navbar -->
      <header
        class="flex min-w-0 items-center justify-between gap-2 px-3 py-3 sm:px-6 sm:py-4 bg-base-100 border-b border-base-200 shadow-sm"
      >
        <div class="lg:hidden flex items-center gap-1">
          <button
            class="btn btn-ghost btn-sm text-base-content"
            @click="toggleSidebar"
            aria-label="Open navigation"
            :aria-expanded="isSidebarOpen"
          >
            ☰
          </button>
          <h2 class="max-w-36 truncate text-sm font-semibold text-base-content sm:max-w-none">{{ route.name }}</h2>
        </div>

        <div class="hidden lg:block">
          <h2 class="text-lg font-semibold text-base-content">{{ route.name }}</h2>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-6">
          <ToggleTheme />
          <AvatarDropdown />          
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="flex-1 min-w-0 overflow-x-hidden overflow-y-auto p-3 sm:p-6 bg-base-200">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Hide scrollbar for sidebar on smaller screens */
aside::-webkit-scrollbar {
  display: none;
}
aside {
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}
</style>
