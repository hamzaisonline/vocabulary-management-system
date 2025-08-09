<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

import CompletionCard from "@/components/student/CompletionCard.vue";
import MasteryCard from "@/components/student/MasteryCard.vue";
import PracticeQuestion from "@/components/student/PracticeQuestion.vue";
import VocabularyCard from "@/components/student/VocabularyCard.vue";
import MedalBadge from "@/components/student/MedalBadge.vue";
import { useVocabularyStore } from "@/stores/vocabularyStore";

const toast = useToast();
const router = useRouter();
const vocabularyStore = useVocabularyStore();

const currentPhase = computed({
  get: () => vocabularyStore.phase,
  set: (val) => vocabularyStore.setPhase(val),
});

const isWordMastered = computed(() => {
  return vocabularyStore.currentWord.mastery >= 100;
});

const masteredWordsCount = computed(() =>
  vocabularyStore.words.filter(w => w.mastery === 100).length
)

const currentWordMedal = computed(() =>
  vocabularyStore.getMedal(vocabularyStore.currentWord.id)
)


const isPronounced = ref(false);

// Dynamic question for practice
const practiceQuestion = computed(() => {
  const word = vocabularyStore.currentWord;
  const correct = word.translation;
  const options = generateOptions(correct);
  return {
    question: `Translate "${word.word}"`,
    options,
    answer: correct,
  };
});

// Generate random options (1 correct + 2 wrong)
function generateOptions(correctAnswer) {
  const translations = vocabularyStore.words.map((w) => w.translation);
  const wrong = translations.filter((t) => t !== correctAnswer);
  const shuffled = wrong.sort(() => 0.5 - Math.random()).slice(0, 2);
  return [...shuffled, correctAnswer].sort(() => 0.5 - Math.random());
}

function handleAnswer(isCorrect) {
  if (isCorrect) {
    vocabularyStore.incrementMastery(vocabularyStore.currentWord.id);
    // Check if word is now 100% mastered
    if (vocabularyStore.currentWord.mastery >= 100) {
      vocabularyStore.nextUnmasteredWord();
    }
    toast.success("✅ Correct Answer!");
  } else {
    toast.warning("❌ Try Again!");
  }
}

function handlePronounceSuccess() {
  isPronounced.value = true;
}

function proceedToNextPhase() {
  if (currentPhase.value === "learn") {
    currentPhase.value = "practice";
    isPronounced.value = false;
  } else if (currentPhase.value === "practice") {
    currentPhase.value = "review";
  } else if (currentPhase.value === "review") {
    currentPhase.value = "completed";
    toast.success("🎉 Congratulations! Level completed");
  }
}

onMounted(() => {
  if (!vocabularyStore.currentLevelId) {
    const level = vocabularyStore.nextPendingLevel
    if (level) {
      vocabularyStore.setLevel(level.id)
    } else {
      router.push('/student/completed')
    }
  }
})
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-3xl font-bold">
      Vocabulary Flow — {{ vocabularyStore.currentLevel?.title }}
    </h1>

<!-- Mastery Summary & Medal -->
<div class="flex flex-wrap items-center justify-between gap-4">
  <p class="text-sm text-gray-600">
    Words Mastered: {{ masteredWordsCount }} / {{ vocabularyStore.words.length }}
  </p>
  <div class="flex items-center gap-2">
    <span class="text-sm">Your Medal:</span>
    <MedalBadge :medal="currentWordMedal" />
  </div>
</div>

<progress
  class="progress progress-success w-full"
  :value="masteredWordsCount"
  :max="vocabularyStore.words.length"
/>



    <!-- LEARN Phase -->
    <VocabularyCard
      v-if="currentPhase === 'learn' && vocabularyStore.currentWord.word"
      :word="vocabularyStore.currentWord"
      @onPronounceSuccess="handlePronounceSuccess"
    />

    <!-- PRACTICE Phase -->
    <PracticeQuestion
      v-if="currentPhase === 'practice'"
      :question="practiceQuestion.question"
      :options="practiceQuestion.options"
      :answer="practiceQuestion.answer"
      :onAnswer="handleAnswer"
    />

    <!-- REVIEW Phase -->
    <div
      v-if="currentPhase === 'review'"
      class="grid grid-cols-1 md:grid-cols-2 gap-6"
    >
      <MasteryCard
        v-for="word in vocabularyStore.words"
        :key="word.id"
        :word="word"
      />
    </div>

    <!-- COMPLETION Phase -->
    <div v-if="currentPhase === 'completed'">
      <CompletionCard />
    </div>

    <!-- NEXT BUTTON -->
    <button
      class="btn btn-primary mt-4"
      v-if="
        currentPhase !== 'completed' &&
        (currentPhase !== 'learn' || isPronounced)
      "
      @click="proceedToNextPhase"
    >
      Next Phase →
    </button>
  </div>
</template>
