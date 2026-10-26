<script setup lang="ts">
/**
 * Обложка: широкий кадр над содержимым.
 *
 * Одна на страницу мастера и на мероприятие — по смыслу это одно и то же,
 * а выглядели они по-разному: у мастера широкая полоса с ботанической
 * заглушкой, у мероприятия маленький прямоугольник с печатью. Человек
 * не должен догадываться, что кадр попадёт в шапку, по размеру превью.
 */
const file = defineModel<File | null>('file', { default: null })
/** Убрать уже загруженную обложку. Флаг, а не пустой файл: сервер отличает. */
const remove = defineModel<boolean>('remove', { default: false })

const props = defineProps<{
  /** Что лежит на сервере сейчас. */
  current?: string | null
  /** Отличает ботанические заглушки друг от друга. */
  seed?: number
  label?: string
  name?: string
  error?: string
}>()

const preview = computed(() => file.value ? URL.createObjectURL(file.value) : null)
const shown = computed(() => preview.value ?? (remove.value ? null : (props.current ?? null)))

function onPick(event: Event): void {
  const picked = (event.target as HTMLInputElement).files?.[0] ?? null

  file.value = picked
  // Выбрали новый кадр — «убрать» отменяется само: иначе сервер получил бы
  // и файл, и просьбу очистить поле.
  if (picked) remove.value = false
}

onBeforeUnmount(() => {
  if (preview.value) URL.revokeObjectURL(preview.value)
})
</script>

<template>
  <UFormField
    :label="label"
    :name="name"
    :error="error"
  >
    <div class="space-y-3">
      <div class="h-36 overflow-hidden rounded-lg bg-forest-950">
        <img
          v-if="shown"
          :src="shown"
          alt=""
          class="size-full object-cover"
        >
        <BotanicalScene
          v-else
          :seed="seed ?? 1"
          class="size-full"
        />
      </div>

      <div class="flex items-center gap-3">
        <input
          type="file"
          accept="image/*"
          class="block text-sm file:mr-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
          @change="onPick"
        >
        <UButton
          v-if="shown"
          color="neutral"
          variant="ghost"
          size="xs"
          icon="i-lucide-trash-2"
          @click="file = null; remove = true"
        >
          Убрать
        </UButton>
      </div>
    </div>
  </UFormField>
</template>
