import { defineStore } from "pinia";

export const useVocabularyStore = defineStore("vocabularyStore", {
  state: () => ({
    levels: [
      {
        id: "1",
        title: "Pets",
        words: [
          {
            id: 1,
            word: "Gato",
            translation: "Cat",
            audio: "/audio/gato.mp3",
            mastery: 0,
          },
          {
            id: 2,
            word: "Perro",
            translation: "Dog",
            audio: "/audio/perro.mp3",
            mastery: 0,
          },
        ],
      },
      {
        id: "2",
        title: "Colors",
        words: [
          {
            id: 3,
            word: "Rojo",
            translation: "Red",
            audio: "/audio/rojo.mp3",
            mastery: 0,
          },
          {
            id: 4,
            word: "Azul",
            translation: "Blue",
            audio: "/audio/azul.mp3",
            mastery: 0,
          },
        ],
      },
    ],
    currentLevelId: null,
    currentWordIndex: 0,
    phase: "learn", // 'learn', 'practice', 'review'
    xp: 0,
  }),
  getters: {
    currentLevel(state) {
      return (
        state.levels.find((level) => level.id === state.currentLevelId) || null
      );
    },
    words(state) {
      return state.currentLevel?.words || [];
    },
    currentWord(state) {
      return state.words[state.currentWordIndex] || {};
    },
    totalMasteredWords(state) {
      return state.levels.reduce(
        (count, level) =>
          count + level.words.filter((w) => w.mastery === 100).length,
        0
      );
    },
    hasUnseenWords(state) {
      return state.levels.some((level) =>
        level.words.some((word) => word.mastery === 0)
      );
    },
    isLevelCompleted(state) {
      const words = state.words;
      return words.length > 0 && words.every((word) => word.mastery >= 100);
    },

    nextPendingLevel(state) {
      return state.levels.find((level) =>
        level.words.some((word) => word.mastery < 100)
      );
    },
    getMedal: (state) => (wordId) => {
      const word = state.words.find((w) => w.id === wordId);
      if (!word) return "none";
      const mastery = word.mastery;
      if (mastery >= 100) return "platinum";
      if (mastery >= 75) return "gold";
      if (mastery >= 50) return "silver";
      if (mastery >= 25) return "bronze";
      return "none";
    },
  },
  actions: {
    setLevel(levelId) {
      this.currentLevelId = levelId;
      this.currentWordIndex = 0;
    },
    completeCurrentLevel(state) {
      const level = state.levels.find((l) => l.id === state.currentLevelId);
      if (level) {
        level.words.forEach((word) => (word.mastery = 100));
      }
    },
    incrementMastery(wordId) {
      const word = this.words.find((w) => w.id === wordId);
      if (word && word.mastery < 100) {
        word.mastery += 25;
        this.xp += 10;
      }
    },
    nextWord() {
      if (this.currentWordIndex < this.words.length - 1) {
        this.currentWordIndex++;
      }
    },
    nextUnmasteredWord() {
      const index = this.words.findIndex((w) => w.mastery < 100);
      if (index !== -1) {
        this.currentWordIndex = index;
      }
    },
    resetProgress() {
      this.levels.forEach((level) => {
        level.words.forEach((w) => {
          w.mastery = 0;
        });
      });
      this.xp = 0;
    },
    setPhase(newPhase) {
      this.phase = newPhase;
    },
  },
  persist: {
    enabled: true,
    strategies: [
      {
        key: "vocabularyProgress",
        storage: localStorage,
        paths: ["currentLevelId", "currentWordIndex", "phase", "levels", "xp"],
      },
    ],
  },
});
