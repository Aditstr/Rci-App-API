import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '@/views/HomeView.vue';
import LoginView from '@/views/auth/LoginView.vue';
import RegisterView from '@/views/auth/RegisterView.vue';
import DashboardView from '@/views/client/DashboardView.vue';
import AiChatView from '@/views/client/AiChatView.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeView,
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guestOnly: true },
    },
    {
        path: '/register',
        name: 'register',
        component: RegisterView,
        meta: { guestOnly: true },
    },
    // ── Client Routes ──
    {
        path: '/client',
        name: 'client.dashboard',
        component: DashboardView,
        meta: { requiresAuth: true },
    },
    {
        path: '/client/ai-chat',
        name: 'client.ai-chat',
        component: AiChatView,
    },
    {
        path: '/client/cases/create',
        name: 'client.cases.create',
        component: () => import('@/views/client/CreateCaseView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/client/cases/:id',
        name: 'client.cases.detail',
        component: () => import('@/views/client/CaseDetailView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/client/cases/:id/chat',
        name: 'client.cases.chat',
        component: () => import('@/views/client/CaseChatView.vue'),
        meta: { requiresAuth: true },
    },
    // ── Paralegal Routes (Phase 2 — placeholder) ──
    {
        path: '/paralegal',
        name: 'paralegal.dashboard',
        component: DashboardView, // TODO: Replace with ParalegalDashboardView
        meta: { requiresAuth: true },
    },
    // ── Lawyer Routes (Phase 2 — placeholder) ──
    {
        path: '/lawyer',
        name: 'lawyer.dashboard',
        component: DashboardView, // TODO: Replace with LawyerDashboardView
        meta: { requiresAuth: true },
    },
    {
        // Catch-all route to home
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    },
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('rci_token');

    if (to.meta.requiresAuth && !token) {
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (to.meta.guestOnly && token) {
        next({ name: 'client.dashboard' });
    } else {
        next();
    }
});

export default router;
