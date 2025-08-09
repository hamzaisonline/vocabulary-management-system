import { defineStore } from 'pinia'

export const useStudentStore = defineStore('studentStore', {
  state: () => ({
    students: [
      {
        id: 1,
        name: 'Maria Garcia',
        email: 'maria.garcia@email.com',
        classId: 1,
        enrolledDate: '2024-01-20',
        lastActivity: '2024-08-09',
        totalXP: 340,
        levelsCompleted: 2,
        currentLevel: 'Family',
        progress: {
          'Pets': { completed: true, score: 95, timeSpent: 45 },
          'Colors': { completed: true, score: 88, timeSpent: 38 },
          'Family': { completed: false, score: 65, timeSpent: 22 }
        },
        activityHistory: [
          { date: '2024-08-09', activity: 'Completed Pets level', xpEarned: 50 },
          { date: '2024-08-08', activity: 'Practiced Colors vocabulary', xpEarned: 30 },
          { date: '2024-08-07', activity: 'Started Family level', xpEarned: 20 }
        ]
      },
      {
        id: 2,
        name: 'Carlos Rodriguez',
        email: 'carlos.rodriguez@email.com',
        classId: 1,
        enrolledDate: '2024-01-22',
        lastActivity: '2024-08-08',
        totalXP: 280,
        levelsCompleted: 1,
        currentLevel: 'Colors',
        progress: {
          'Pets': { completed: true, score: 92, timeSpent: 52 },
          'Colors': { completed: false, score: 73, timeSpent: 31 },
          'Family': { completed: false, score: 0, timeSpent: 0 }
        },
        activityHistory: [
          { date: '2024-08-08', activity: 'Practiced Colors vocabulary', xpEarned: 25 },
          { date: '2024-08-06', activity: 'Completed Pets level', xpEarned: 50 },
          { date: '2024-08-05', activity: 'Started Pets level', xpEarned: 15 }
        ]
      },
      {
        id: 3,
        name: 'Ana Martinez',
        email: 'ana.martinez@email.com',
        classId: 1,
        enrolledDate: '2024-01-25',
        lastActivity: '2024-08-09',
        totalXP: 420,
        levelsCompleted: 3,
        currentLevel: 'Advanced',
        progress: {
          'Pets': { completed: true, score: 98, timeSpent: 35 },
          'Colors': { completed: true, score: 94, timeSpent: 28 },
          'Family': { completed: true, score: 91, timeSpent: 42 }
        },
        activityHistory: [
          { date: '2024-08-09', activity: 'Completed Family level', xpEarned: 50 },
          { date: '2024-08-07', activity: 'Practiced Family vocabulary', xpEarned: 35 },
          { date: '2024-08-05', activity: 'Started Family level', xpEarned: 20 }
        ]
      },
      {
        id: 4,
        name: 'Luis Fernandez',
        email: 'luis.fernandez@email.com',
        classId: 2,
        enrolledDate: '2024-02-01',
        lastActivity: '2024-08-08',
        totalXP: 195,
        levelsCompleted: 1,
        currentLevel: 'Travel',
        progress: {
          'Food': { completed: true, score: 85, timeSpent: 48 },
          'Travel': { completed: false, score: 62, timeSpent: 25 },
          'Shopping': { completed: false, score: 0, timeSpent: 0 }
        },
        activityHistory: [
          { date: '2024-08-08', activity: 'Practiced Travel vocabulary', xpEarned: 20 },
          { date: '2024-08-06', activity: 'Completed Food level', xpEarned: 45 },
          { date: '2024-08-04', activity: 'Started Food level', xpEarned: 15 }
        ]
      }
    ]
  }),
  
  getters: {
    getStudentsByClass: (state) => (classId) => {
      return state.students.filter(student => student.classId === classId)
    },
    
    getClassProgress: (state) => (classId) => {
      const classStudents = state.students.filter(student => student.classId === classId)
      if (classStudents.length === 0) return { averageProgress: 0, totalStudents: 0 }
      
      const totalProgress = classStudents.reduce((sum, student) => {
        const levels = Object.values(student.progress)
        const avgScore = levels.reduce((acc, level) => acc + level.score, 0) / levels.length
        return sum + avgScore
      }, 0)
      
      return {
        averageProgress: Math.round(totalProgress / classStudents.length),
        totalStudents: classStudents.length,
        totalXP: classStudents.reduce((sum, student) => sum + student.totalXP, 0),
        completedLevels: classStudents.reduce((sum, student) => sum + student.levelsCompleted, 0)
      }
    },
    
    getTopPerformers: (state) => (classId, limit = 5) => {
      return state.students
        .filter(student => student.classId === classId)
        .sort((a, b) => b.totalXP - a.totalXP)
        .slice(0, limit)
    }
  },
  
  actions: {
    addStudent(studentData) {
      const newStudent = {
        id: Date.now(),
        ...studentData,
        enrolledDate: new Date().toISOString().split('T')[0],
        lastActivity: new Date().toISOString().split('T')[0],
        totalXP: 0,
        levelsCompleted: 0,
        currentLevel: 'Getting Started',
        progress: {},
        activityHistory: []
      }
      this.students.push(newStudent)
    },
    
    bulkAddStudents(studentsData, classId) {
      const newStudents = studentsData.map(studentData => ({
        id: Date.now() + Math.random(),
        name: studentData.name,
        email: studentData.email,
        classId: classId,
        enrolledDate: new Date().toISOString().split('T')[0],
        lastActivity: new Date().toISOString().split('T')[0],
        totalXP: 0,
        levelsCompleted: 0,
        currentLevel: 'Getting Started',
        progress: {},
        activityHistory: []
      }))
      
      this.students.push(...newStudents)
    },
    
    removeStudent(studentId) {
      const index = this.students.findIndex(student => student.id === studentId)
      if (index !== -1) {
        this.students.splice(index, 1)
      }
    },
    
    updateStudentProgress(studentId, levelId, progressData) {
      const student = this.students.find(s => s.id === studentId)
      if (student) {
        student.progress[levelId] = progressData
        student.lastActivity = new Date().toISOString().split('T')[0]
        
        // Update totals
        const levels = Object.values(student.progress)
        student.levelsCompleted = levels.filter(level => level.completed).length
        student.totalXP = levels.reduce((sum, level) => sum + (level.score * 5), 0)
      }
    }
  },
  
  persist: {
    enabled: true,
    strategies: [
      {
        key: 'studentStore',
        storage: localStorage,
        paths: ['students']
      }
    ]
  }
})
