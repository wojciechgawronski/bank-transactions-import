<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { FileSpreadsheet } from 'lucide-vue-next'
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center px-6 py-4">
        <RouterLink to="/" class="flex items-center gap-2 font-semibold text-slate-800">
          <FileSpreadsheet class="size-5 text-sky-600" aria-hidden="true" />
          Import transakcji
        </RouterLink>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-8">
      <slot />
    </main>
  </div>
</template>
