<script setup>
import { computed } from "vue"
import { useVocabularyStore } from "@/stores/vocabularyStore"
import { useRouter } from "vue-router"

const store = useVocabularyStore()
const router = useRouter()

const assignedLevels = computed(() => store.levels)

// Calculate progress per level
const getProgress = (level) => {
  if (!level.words.length) return 0
  const total = level.words.length * 100
  const earned = level.words.reduce((acc, w) => acc + w.mastery, 0)
  return Math.round((earned / total) * 100)
}

// Go to next unmastered word from a level
const goToLevelFlow = (levelId) => {
  router.push(`/student/flow/${levelId}`)
}
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-3xl font-bold">Welcome, Student! 👋</h1>
    <p>🎯 <strong>Total XP:</strong> {{ store.xp }}</p>

    <!-- XP Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card bg-primary text-primary-content shadow-md">
        <div class="card-body">
          <h2 class="card-title">Your XP</h2>
          <p>Gain XP by completing vocabulary mastery!</p>
          <div class="radial-progress text-secondary" style="--value: 100;">
            {{ store.xp }} XP
          </div>
        </div>
      </div>
    </div>

    <!-- Assigned Levels -->
    <h2 class="text-xl font-bold mt-6">Your Levels</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="level in assignedLevels"
        :key="level.id"
        class="card bg-base-100 shadow-md hover:shadow-lg"
      >
        <div class="card-body">
          <h3 class="card-title">{{ level.title }}</h3>

          <progress
            class="progress progress-success"
            :value="getProgress(level)"
            max="100"
          ></progress>

          <p>{{ getProgress(level) }}% Completed</p>

          <button
            class="btn btn-primary btn-sm mt-2"
            @click="goToLevelFlow(level.id)"
          >
            {{ getProgress(level) === 100 ? "Review" : "Continue Learning" }}
          </button>
        </div>
      </div>
    </div>

    <!-- Summary List -->
    <h2 class="mt-6 text-lg font-semibold">Progress Summary</h2>
    <ul class="list-disc list-inside">
      <li
        v-for="level in assignedLevels"
        :key="level.id"
        class="text-sm text-gray-700"
      >
        {{ level.title }} —
        <span v-if="getProgress(level) === 100">✅ Completed</span>
        <span v-else>⏳ In Progress ({{ getProgress(level) }}%)</span>
      </li>
    </ul>
  </div>
</template>
