import { defineStore } from "pinia";

export const useVocabularyStore = defineStore("vocabularyStore", {
  state: () => ({
    levels: [
      {
        id: "1",
        title: "Pets",
        description: "Learn about common pets and animals",
        difficulty: "beginner",
        words: [
          {
            id: 1,
            word: "Gato",
            translation: "Cat",
            audio: "/audio/gato.mp3",
            mastery: 0,
            example: "El gato está durmiendo en la cama",
            notes: "A common household pet"
          },
          {
            id: 2,
            word: "Perro",
            translation: "Dog",
            audio: "/audio/perro.mp3",
            mastery: 0,
            example: "Mi perro corre en el parque",
            notes: "Man's best friend"
          },
          {
            id: 5,
            word: "Pez",
            translation: "Fish",
            audio: "/audio/pez.mp3",
            mastery: 0,
            example: "El pez nada en el agua",
            notes: "Lives in water"
          },
          {
            id: 6,
            word: "Pájaro",
            translation: "Bird",
            audio: "/audio/pajaro.mp3",
            mastery: 0,
            example: "El pájaro vuela en el cielo",
            notes: "Can fly in the sky"
          },
        ],
      },
      {
        id: "2",
        title: "Colors",
        description: "Basic colors in Spanish",
        difficulty: "beginner",
        words: [
          {
            id: 3,
            word: "Rojo",
            translation: "Red",
            audio: "/audio/rojo.mp3",
            mastery: 0,
            example: "La rosa roja es muy bonita",
            notes: "Color of roses and fire"
          },
          {
            id: 4,
            word: "Azul",
            translation: "Blue",
            audio: "/audio/azul.mp3",
            mastery: 0,
            example: "El cielo azul es hermoso hoy",
            notes: "Color of the sky and ocean"
          },
          {
            id: 7,
            word: "Verde",
            translation: "Green",
            audio: "/audio/verde.mp3",
            mastery: 0,
            example: "Las hojas verdes son del árbol",
            notes: "Color of grass and leaves"
          },
          {
            id: 8,
            word: "Amarillo",
            translation: "Yellow",
            audio: "/audio/amarillo.mp3",
            mastery: 0,
            example: "El sol amarillo brilla mucho",
            notes: "Color of the sun and bananas"
          },
        ],
      },
      {
        id: "3",
        title: "Family",
        description: "Family members and relationships",
        difficulty: "intermediate",
        words: [
          {
            id: 9,
            word: "Madre",
            translation: "Mother",
            audio: "/audio/madre.mp3",
            mastery: 0,
            example: "Mi madre cocina muy bien",
            notes: "Female parent"
          },
          {
            id: 10,
            word: "Padre",
            translation: "Father",
            audio: "/audio/padre.mp3",
            mastery: 0,
            example: "Mi padre trabaja en la oficina",
            notes: "Male parent"
          },
          {
            id: 11,
            word: "Hermano",
            translation: "Brother",
            audio: "/audio/hermano.mp3",
            mastery: 0,
            example: "Mi hermano juega fútbol conmigo",
            notes: "Male sibling"
          },
          {
            id: 12,
            word: "Hermana",
            translation: "Sister",
            audio: "/audio/hermana.mp3",
            mastery: 0,
            example: "Mi hermana estudia en la universidad",
            notes: "Female sibling"
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
