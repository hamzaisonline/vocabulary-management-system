<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStudentProgressStore } from '@/stores/studentProgressStore'
import { TrophyIcon, ArrowLeftIcon, BookOpenIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const progressStore = useStudentProgressStore()

const goToDashboard = () => {
  router.push('/student')
}

const goToVocabulary = () => {
  router.push('/student/vocabulary-flow')
}

onMounted(() => progressStore.fetchProgress().catch(() => {}))
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Celebration Header -->
    <div class="text-center space-y-4">
      <TrophyIcon class="w-24 h-24 mx-auto text-warning" />
      <h1 class="text-4xl font-bold text-primary">🎉 Congratulations! 🎉</h1>
      <p class="text-xl text-base-content/80">You've completed all available vocabulary levels!</p>
    </div>

    <!-- Stats Summary -->
    <div class="card bg-gradient-to-r from-primary to-secondary text-primary-content shadow-xl">
      <div class="card-body text-center">
        <h2 class="card-title justify-center text-2xl mb-4">Your Achievement Summary</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="stat">
            <div class="stat-title text-primary-content/80">Total XP</div>
            <div class="stat-value text-accent">{{ progressStore.totalXp }}</div>
            <div class="stat-desc text-primary-content/60">Reported by the server</div>
          </div>
          
          <div class="stat">
            <div class="stat-title text-primary-content/80">Words Mastered</div>
            <div class="stat-value text-accent">{{ progressStore.masteredWords }}</div>
            <div class="stat-desc text-primary-content/60">Vocabulary learned</div>
          </div>
          
          <div class="stat">
            <div class="stat-title text-primary-content/80">Levels Completed</div>
            <div class="stat-value text-accent">{{ progressStore.completedLevels.length }}</div>
            <div class="stat-desc text-primary-content/60">Reported complete</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Level Summary -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">Levels Completed</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="level in progressStore.completedLevels"
            :key="level.id"
            class="card bg-success text-success-content shadow-sm"
          >
            <div class="card-body">
              <h3 class="card-title text-lg">{{ level.title }}</h3>
              <div class="flex items-center gap-2">
                <span class="text-sm">{{ level.mastered_words || 0 }} / {{ level.total_words || 0 }} words</span>
                <div class="badge badge-accent">{{ level.progress_percent || 0 }}% Complete</div>
              </div>
              <div class="flex items-center gap-1 mt-2">
                <TrophyIcon class="w-4 h-4" />
                <span class="text-sm">Mastered!</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Options -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body text-center">
        <h2 class="card-title justify-center mb-4">What's Next?</h2>
        <p class="text-base-content/70 mb-6">
          You've done an amazing job! Here are your options to continue learning:
        </p>
        
        <div class="flex flex-wrap justify-center gap-4">
          <button @click="goToDashboard" class="btn btn-primary gap-2">
            <ArrowLeftIcon class="w-4 h-4" />
            Back to Dashboard
          </button>
          
          <button @click="goToVocabulary" class="btn btn-outline gap-2">
            <BookOpenIcon class="w-4 h-4" />
            Review Vocabulary
          </button>
          
        </div>
      </div>
    </div>

    <!-- Motivational Message -->
    <div class="alert alert-success">
      <div class="flex flex-col text-center">
        <h3 class="font-bold">Keep up the great work! 🌟</h3>
        <p class="text-sm">Learning a new language is a journey. You've made excellent progress, and with continued practice, you'll become even more fluent!</p>
      </div>
    </div>
  </div>
</template>
