import { useAuthStore } from '@/stores/authStore';
import { createRouter, createWebHistory } from "vue-router";

// Import Layouts
import MainLayout from "@/layouts/MainLayout.vue";

// Import Views
import AdminSettings from "@/views/admin/AdminSettings.vue";
import ManageClasses from "@/views/admin/ManageClasses.vue";
import AdminDashboard from "@/views/AdminDashboard.vue";
import LoginPage from "@/views/LoginPage.vue";
import NotFoundPage from "@/views/NotFoundPage.vue";
import RegisterPage from "@/views/RegisterPage.vue";
import ClassOverview from "@/views/student/ClassOverview.vue";
import LearningPage from "@/views/student/LearningPage.vue";
import ManageStudents from "@/views/student/ManageStudents.vue";
import PracticePage from "@/views/student/PracticePage.vue";
import ReviewPage from "@/views/student/ReviewPage.vue";
import StudentDashboard from "@/views/student/StudentDashboard.vue";
import VocabularyFlow from "@/views/student/VocabularyFlow.vue";
import ManageTeachers from "@/views/teacher/ManageTeachers.vue";
import TeacherDashboard from "@/views/TeacherDashboard.vue";
import TeacherClasses from "@/views/teacher/TeacherClasses.vue";
import TeacherVocabulary from "@/views/teacher/TeacherVocabulary.vue";
import ClassDetails from "@/views/teacher/ClassDetails.vue";
import StudentDetails from "@/views/teacher/StudentDetails.vue";
import UnauthorizedPage from "@/views/UnauthorizedPage.vue";
import ManageWords from "@/views/word/ManageWords.vue";

// Define routes
const routes = [
  // Public Routes (Authentication Pages)
  {
    path: "",
    name: "Login",
    component: LoginPage,
  },
  {
    path: "/create-account",
    name: "Register",
    component: RegisterPage,
  },
  {
    path: "/unauthorized",
    name: "Unauthorized",
    component: UnauthorizedPage,
  },

  // Admin Routes
  {
    path: "/admin",
    component: MainLayout, // Use MainLayout for admin pages
    meta: { requiresAuth: true, role: "admin" },
    children: [
      {
        path: "",
        name: "AdminDashboard",
        component: AdminDashboard,
      },
      {
        path: "classes",
        name: "ManageClasses",
        component: ManageClasses,
      },
      {
        path: "words",
        name: "ManageWords",
        component: ManageWords,
      },
      {
        path: "students",
        name: "ManageStudents",
        component: ManageStudents,
      },
      {
        path: "teachers",
        name: "ManageTeachers",
        component: ManageTeachers,
      },
      {
        path: "settings",
        name: "AdminSettings",
        component: AdminSettings,
      },
    ],
  },

  // Teacher Routes
  {
    path: "/teacher",
    component: MainLayout, // Use MainLayout for teacher pages
    meta: { requiresAuth: true, role: "teacher" },
    children: [
      {
        path: "",
        name: "TeacherDashboard",
        component: TeacherDashboard,
      },
      {
        path: "classes",
        name: "TeacherClasses",
        component: TeacherClasses,
      },
      {
        path: "classes/:id",
        name: "ClassDetails",
        component: ClassDetails,
      },
      {
        path: "students/:id",
        name: "StudentDetails",
        component: StudentDetails,
      },
      {
        path: "vocabulary",
        name: "TeacherVocabulary",
        component: TeacherVocabulary,
      },
    ],
  },

  // Student Routes
  {
    path: "/student",
    component: MainLayout, // Use MainLayout for student pages
    meta: { requiresAuth: true, role: "student" },
    children: [
      {
        path: "",
        name: "StudentDashboard",
        component: StudentDashboard,
      },
      {
        path: "classes",
        name: "ClassOverview",
        component: ClassOverview,
      },
      {
        path: "vocabulary-flow",
        name: "VocabularyFlowMain",
        component: VocabularyFlow,
      },
      {
        path: "flow/:id",
        name: "VocabularyFlow",
        component: VocabularyFlow,
      },
      {
        path: "learn/:id",
        name: "LearningPage",
        component: LearningPage,
      },
      {
        path: "practice",
        name: "PracticePage",
        component: PracticePage,
      },
      {
        path: "review",
        name: "ReviewPage",
        component: ReviewPage,
      },
    ],
  },

  // Fallback (404) Route
  {
    path: "/:pathMatch(.*)*",
    name: "NotFound",
    component: NotFoundPage,
  },
];

// Create router instance
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Global Navigation Guard for Authentication and Authorization
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore(); // Pinia store for user state
  const isLoggedIn = authStore.isAuthenticated;
  const userRole = authStore.role; // e.g., "admin", "teacher", "student"

  // Check if the route requires authentication
  if (to.meta.requiresAuth) {
    if (!isLoggedIn) {
      return next({ name: "Login" }); // Redirect to Login if not authenticated
    }

    // Check if the user has the required role
    if (to.meta.role && to.meta.role !== userRole) {
      return next({ name: "Unauthorized" }); // Redirect to Unauthorized if role mismatch
    }
  }

  next(); // Allow navigation
});

export default router;
