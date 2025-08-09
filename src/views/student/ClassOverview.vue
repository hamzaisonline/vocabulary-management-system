<script setup>
import { useRouter } from 'vue-router'
import { useClassStore } from '@/stores/classStore'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const classStore = useClassStore()

function selectClass(classId) {
  classStore.selectClass(classId);
}

function goToDashboard() {
  router.push('/student');
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold text-primary">Your Classes</h1>
      <button @click="goToDashboard" class="btn btn-ghost gap-2">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Dashboard
      </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div
        v-for="cls in classStore.classes"
        :key="cls.id"
        class="card bg-base-100 shadow-md hover:shadow-lg"
      >
        <div class="card-body">
          <h2 class="card-title">{{ cls.name }}</h2>
          <p>Course: {{ cls.course }}</p>
          <progress class="progress progress-secondary" :value="cls.progress" max="100"></progress>
          <router-link
            :to="'/student/vocabulary-flow'"
            class="btn btn-primary btn-sm mt-4"
            @click="selectClass(cls.id)"
          >
            Start Course
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
