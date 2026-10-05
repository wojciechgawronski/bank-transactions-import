import { createMemoryHistory, createRouter } from 'vue-router'

/** Router with the real route names, for components that link to or close the drawer. */
export function createTestRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/imports',
        name: 'imports',
        component: { template: '<router-view />' },
        children: [{ path: ':id', name: 'import-details', component: { template: '<div />' } }],
      },
    ],
  })
}
