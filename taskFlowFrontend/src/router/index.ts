import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const Layout = () => import('@/layout/index.vue')

export const constantRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/index.vue'),
    meta: { hidden: true, noAuth: true },
  },
  {
    path: '/redirect/:pathMatch(.*)*',
    name: 'Redirect',
    component: Layout,
    meta: { hidden: true, noAuth: true },
    beforeEnter: (to, _from, next) => {
      const pathMatch = to.params.pathMatch
      const path = Array.isArray(pathMatch) ? pathMatch.join('/') : pathMatch
      const targetPath = '/' + path
      next(targetPath)
    },
  },
  {
    path: '/',
    name: 'Layout',
    component: Layout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: 'dashboard.title', icon: 'Odometer', affix: true },
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('@/views/profile/index.vue'),
        meta: { title: 'profile.title' },
      },
      {
        path: 'password',
        name: 'Password',
        component: () => import('@/views/profile/password.vue'),
        meta: { title: 'password.title' },
      },
      {
        path: ':pathMatch(.*)*',
        name: 'CatchAll',
        component: { template: '<div />' },
        meta: { hidden: true },
      },
    ],
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/views/error/403.vue'),
    meta: { hidden: true, noAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes: constantRoutes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  },
})

export default router
