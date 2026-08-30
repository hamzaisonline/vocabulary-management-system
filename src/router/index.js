import { useAuthStore } from '@/stores/authStore';
import { createRouter, createWebHistory } from 'vue-router';

import MainLayout from '@/layouts/MainLayout.vue';

import AdminSettings from '@/views/admin/AdminSettings.vue';
import ManageClasses from '@/views/admin/ManageClasses.vue';
import AdminReports from '@/views/admin/AdminReports.vue';
import AdminDashboard from '@/views/AdminDashboard.vue';
import LoginPage from '@/views/LoginPage.vue';
import NotFoundPage from '@/views/NotFoundPage.vue';
import RegisterPage from '@/views/RegisterPage.vue';
import ClassOverview from '@/views/student/ClassOverview.vue';
import StudentClassDetail from '@/views/student/StudentClassDetail.vue';
import LearningPage from '@/views/student/LearningPage.vue';
import ManageStudents from '@/views/student/ManageStudents.vue';
import PracticePage from '@/views/student/PracticePage.vue';
import ReviewPage from '@/views/student/ReviewPage.vue';
import StudentDashboard from '@/views/student/StudentDashboard.vue';
import VocabularyFlow from '@/views/student/VocabularyFlow.vue';
import CompletedPage from '@/views/student/CompletedPage.vue';
import ManageTeachers from '@/views/teacher/ManageTeachers.vue';
import TeacherDashboard from '@/views/TeacherDashboard.vue';
import TeacherClasses from '@/views/teacher/TeacherClasses.vue';
import TeacherVocabulary from '@/views/teacher/TeacherVocabulary.vue';
import ClassDetails from '@/views/teacher/ClassDetails.vue';
import StudentDetails from '@/views/teacher/StudentDetails.vue';
import TeacherReports from '@/views/teacher/TeacherReports.vue';
import CreateClass from '@/views/class/CreateClass.vue';
import UnauthorizedPage from '@/views/UnauthorizedPage.vue';

const routes = [
  {
    path: '',
    name: 'Login',
    component: LoginPage,
    meta: { guest: true },
  },
  {
    path: '/create-account',
    name: 'Register',
    component: RegisterPage,
    meta: { guest: true },
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: UnauthorizedPage,
  },
  {
    path: '/admin',
    component: MainLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: '', name: 'AdminDashboard', component: AdminDashboard },
      { path: 'classes', name: 'ManageClasses', component: ManageClasses },
      { path: 'classes/create', name: 'AdminCreateClass', component: CreateClass },
      { path: 'classes/:id', name: 'AdminClassDetails', component: ClassDetails },
      { path: 'words', name: 'ManageWords', component: TeacherVocabulary },
      { path: 'students', name: 'ManageStudents', component: ManageStudents },
      { path: 'teachers', name: 'ManageTeachers', component: ManageTeachers },
      { path: 'students/:id', name: 'AdminStudentDetails', component: StudentDetails },
      { path: 'settings', name: 'AdminSettings', component: AdminSettings },
      { path: 'reports', name: 'AdminReports', component: AdminReports },
    ],
  },
  {
    path: '/teacher',
    component: MainLayout,
    meta: { requiresAuth: true, role: 'teacher' },
    children: [
      { path: '', name: 'TeacherDashboard', component: TeacherDashboard },
      { path: 'classes', name: 'TeacherClasses', component: TeacherClasses },
      { path: 'classes/create', name: 'CreateClass', component: CreateClass },
      { path: 'classes/:id', name: 'ClassDetails', component: ClassDetails },
      { path: 'students/:id', name: 'StudentDetails', component: StudentDetails },
      { path: 'vocabulary', name: 'TeacherVocabulary', component: TeacherVocabulary },
      { path: 'reports', name: 'TeacherReports', component: TeacherReports },
    ],
  },
  {
    path: '/student',
    component: MainLayout,
    meta: { requiresAuth: true, role: 'student' },
    children: [
      { path: '', name: 'StudentDashboard', component: StudentDashboard },
      { path: 'classes', name: 'ClassOverview', component: ClassOverview },
      { path: 'classes/:id', name: 'StudentClassDetail', component: StudentClassDetail },
      { path: 'vocabulary-flow', name: 'VocabularyFlowMain', component: VocabularyFlow },
      { path: 'flow/:id', name: 'VocabularyFlow', component: VocabularyFlow },
      { path: 'learn/:id', name: 'LearningPage', component: LearningPage },
      { path: 'practice', name: 'PracticePage', component: PracticePage },
      { path: 'review', name: 'ReviewPage', component: ReviewPage },
      { path: 'completed', name: 'CompletedPage', component: CompletedPage },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFoundPage,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  if (!authStore.isInitialized) {
    await authStore.restoreSession();
  }

  const isLoggedIn = authStore.isAuthenticated;
  const userRole = authStore.user?.role?.name ?? authStore.role ?? null;

  if (to.meta.guest && isLoggedIn) {
    const dashboardPath =
      authStore.role === 'student' ? '/student' :
      authStore.role === 'teacher' ? '/teacher' :
      authStore.role === 'admin' ? '/admin' : '/';

    return next({ path: dashboardPath });
  }

  if (to.meta.requiresAuth) {
    if (!isLoggedIn) {
      if (to.name === 'Login') {
        return next();
      }

      return next({
        name: 'Login',
        query: { redirect: to.fullPath },
        replace: true,
      });
    }

    if (to.meta.role && to.meta.role !== userRole) {
      return next({ name: 'Unauthorized' });
    }
  }

  next();
});

export default router;
