import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: { name: 'imports' } },
    {
      path: '/imports',
      name: 'imports',
      component: () => import('@/modules/imports/views/ImportsView.vue'),
    },
  ],
})

export default router
