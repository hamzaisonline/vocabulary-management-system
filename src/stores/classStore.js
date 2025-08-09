import { defineStore } from "pinia";

export const useClassStore = defineStore("classStore", {
  state: () => ({
    classes: [
      { id: 1, name: "Spanish Basics", course: "Spanish 101", progress: 50 },
    ],
    selectedClassId: null,
  }),
  getters: {
    selectedClass(state) {
      return state.classes.find((cls) => cls.id === state.selectedClassId);
    },
  },
  actions: {
    selectClass(classId) {
      this.selectedClassId = classId;
    },
  },
});
