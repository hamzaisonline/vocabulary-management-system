<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { AcademicCapIcon, BookOpenIcon, ChartBarIcon, UserGroupIcon, UsersIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/authStore'
import { useDashboardStore } from '@/stores/dashboardStore'
import DashboardMetricCard from '@/components/dashboard/DashboardMetricCard.vue'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const dashboardStore = useDashboardStore()
const dashboard = computed(() => dashboardStore.adminDashboard)

onMounted(async () => {
  if (authStore.userRole !== 'admin') return
  try { await dashboardStore.fetchAdminDashboard() }
  catch { toast.error(dashboardStore.error || 'Unable to load dashboard.') }
})
watch(() => authStore.userRole, (role) => {
  if (role !== 'admin') dashboardStore.reset()
})
</script>

<template>
  <div class="p-4 sm:p-6 space-y-6 overflow-x-hidden">
    <div><h1 class="text-3xl font-bold text-primary">Admin Dashboard</h1><p class="text-base-content/70">Platform overview</p></div>
    <div v-if="dashboardStore.loading" class="text-center py-12"><span class="loading loading-spinner loading-lg"></span></div>
    <div v-else-if="dashboardStore.error && !dashboard" class="alert alert-error"><span>{{ dashboardStore.error }}</span></div>
    <template v-else-if="dashboard">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <DashboardMetricCard title="Users" :value="dashboard.total_users" description="Registered accounts" :icon="UsersIcon" card-class="bg-info text-info-content" />
        <DashboardMetricCard title="Students" :value="dashboard.total_students" description="Student profiles" :icon="UserGroupIcon" card-class="bg-primary text-primary-content" />
        <DashboardMetricCard title="Teachers" :value="dashboard.total_teachers" description="Teacher profiles" :icon="UsersIcon" card-class="bg-accent text-accent-content" />
        <DashboardMetricCard title="Classes" :value="dashboard.total_classes" description="Learning classes" :icon="AcademicCapIcon" card-class="bg-success text-success-content" />
        <DashboardMetricCard title="Vocabulary Levels" :value="dashboard.total_vocabulary_levels" description="Available levels" :icon="BookOpenIcon" card-class="bg-primary text-primary-content" />
        <DashboardMetricCard title="Vocabulary Words" :value="dashboard.total_vocabulary_words" description="Managed words" :icon="BookOpenIcon" card-class="bg-info text-info-content" />
        <DashboardMetricCard title="Practice Sessions" :value="dashboard.total_practice_sessions" description="Sessions created" :icon="ChartBarIcon" card-class="bg-accent text-accent-content" />
        <DashboardMetricCard title="Average Mastery" :value="`${dashboard.average_student_mastery}%`" description="Across students" :icon="ChartBarIcon" card-class="bg-success text-success-content" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card bg-base-100 shadow-md"><div class="card-body"><h2 class="card-title">Recently Created Users</h2>
          <div v-if="dashboard.recently_created_users.length" class="space-y-2"><div v-for="user in dashboard.recently_created_users" :key="user.id" class="min-w-0 p-3 bg-base-200 rounded-lg"><p class="break-words font-semibold">{{ user.name }}</p><p class="break-all text-sm">{{ user.email }}</p><p class="text-xs text-base-content/60">{{ user.created_at ? new Date(user.created_at).toLocaleString() : '' }}</p></div></div>
          <p v-else class="text-base-content/60">No users found.</p>
        </div></div>
        <div class="card bg-base-100 shadow-md"><div class="card-body"><h2 class="card-title">Recently Created Classes</h2>
          <div v-if="dashboard.recently_created_classes.length" class="space-y-2"><button v-for="item in dashboard.recently_created_classes" :key="item.id" class="block w-full text-left p-3 bg-base-200 rounded-lg" @click="router.push(`/admin/classes/${item.id}`)"><p class="font-semibold">{{ item.name }}</p><p class="text-xs text-base-content/60">{{ item.created_at ? new Date(item.created_at).toLocaleString() : '' }}</p></button></div>
          <p v-else class="text-base-content/60">No classes found.</p>
        </div></div>
      </div>

      <div class="card bg-base-100 shadow-md"><div class="card-body"><h2 class="card-title">Management</h2><div class="flex flex-wrap gap-3">
        <button class="btn btn-outline" @click="router.push('/admin/classes')">Classes</button><button class="btn btn-outline" @click="router.push('/admin/words')">Vocabulary</button>
      </div></div></div>
    </template>
  </div>
</template>
