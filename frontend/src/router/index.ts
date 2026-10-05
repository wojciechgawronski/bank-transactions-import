import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: { name: 'imports' } },
    {
      path: '/imports',
      name: 'imports',
      component: () => import('@/modules/imports/views/ImportsView.vue'),
      children: [
        {
          path: ':id(\\d+)',
          name: 'import-details',
          component: () => import('@/modules/imports/components/ImportLogsDrawer.vue'),
          props: true,
        },
      ],
    },
  ],
})

export default router
