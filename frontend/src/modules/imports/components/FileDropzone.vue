<script setup lang="ts">
import { ref } from 'vue'
import { FileUp, LoaderCircle } from 'lucide-vue-next'
import { ACCEPTED_EXTENSIONS, MAX_FILE_SIZE_BYTES } from '@/api/imports'

const props = defineProps<{ busy?: boolean }>()
const emit = defineEmits<{ select: [file: File] }>()

const input = ref<HTMLInputElement | null>(null)
const dragging = ref(false)
const error = ref<string | null>(null)

const accept = ACCEPTED_EXTENSIONS.map((ext) => `.${ext}`).join(',')

function validate(file: File): string | null {
  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''
  if (!(ACCEPTED_EXTENSIONS as readonly string[]).includes(extension)) {
    return 'Obsługiwane formaty to CSV, JSON i XML.'
  }
  if (file.size > MAX_FILE_SIZE_BYTES) {
    return 'Plik jest za duży (maksymalnie 10 MB).'
  }
  return null
}

function handle(files: FileList | null | undefined): void {
  const file = files?.[0]
  if (!file || props.busy) {
    return
  }

  error.value = validate(file)
  if (error.value === null) {
    emit('select', file)
  }
}

function onDrop(event: DragEvent): void {
  dragging.value = false
  handle(event.dataTransfer?.files)
}

function onChange(): void {
  handle(input.value?.files)
  if (input.value) {
    input.value.value = ''
  }
}
</script>

<template>
  <div>
    <label
      class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-10 text-center transition focus-within:ring-2 focus-within:ring-sky-500"
      :class="[
        dragging ? 'border-sky-500 bg-sky-50' : 'border-slate-300 bg-white hover:border-slate-400',
        busy && 'pointer-events-none opacity-60',
      ]"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <LoaderCircle v-if="busy" class="size-8 animate-spin text-sky-600" aria-hidden="true" />
      <FileUp v-else class="size-8 text-slate-400" aria-hidden="true" />
      <span class="text-sm font-medium text-slate-700">
        {{ busy ? 'Wysyłanie pliku…' : 'Przeciągnij plik tutaj lub kliknij, aby wybrać' }}
      </span>
      <span class="text-xs text-slate-500">CSV, JSON lub XML, maksymalnie 10 MB</span>
      <input
        ref="input"
        type="file"
        class="sr-only"
        :accept="accept"
        :disabled="busy"
        data-testid="file-input"
        @change="onChange"
      />
    </label>
    <p v-if="error" class="mt-2 text-sm text-rose-600" role="alert">{{ error }}</p>
  </div>
</template>
