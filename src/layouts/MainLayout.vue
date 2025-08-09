<script setup>
import { ref } from "vue";
import { useRoute } from "vue-router";
import AppNavbar from "../components/AppNavbar.vue";
import AvatarDropdown from "../components/AvatarDropdown.vue";
import ToggleTheme from "../components/ToggleTheme.vue";

const route = useRoute()

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
  <div class="flex h-screen">
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
    <aside
      class="fixed z-50 inset-0 bg-base-300 transform -translate-x-full transition-transform lg:hidden"
      :class="{ 'translate-x-0': isSidebarOpen }"
    >
      <div class="p-4 flex justify-between items-center border-b border-base-200">
        <img src="../assets/images/logo-1.jpeg" width="100px" />
        <button
          class="btn btn-sm btn-ghost text-base-content"
          @click="toggleSidebar"
        >
          ✖
        </button>
      </div>
      
      <AppNavbar />

    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1">
      <!-- Top Navbar -->
      <header
        class="flex items-center justify-between px-6 py-4 bg-base-100 border-b border-base-200 shadow-sm"
      >
        <div class="lg:hidden flex items-center gap-1">
          <button
            class="btn btn-ghost btn-sm text-base-content"
            @click="toggleSidebar"
          >
            ☰
          </button>
          <h2 class="text-sm font-semibold text-base-content">{{ route.name }}</h2>
        </div>

        <div class="hidden lg:block">
          <h2 class="text-lg font-semibold text-base-content">{{ route.name }}</h2>
        </div>

        <div class="flex items-center space-x-6">
          <ToggleTheme />
          <AvatarDropdown />          
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="flex-1 p-6 bg-base-200">
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
