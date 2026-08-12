<script setup lang="ts">
import draggable from 'vuedraggable'
import type { GalleryItem, ValidationErrors } from '~/types'

/**
 * Галерея мероприятия: снимки в том порядке, в каком они лягут на страницу.
 *
 * Порядок задаётся перетаскиванием — в сетке из плиток это единственный
 * способ, не требующий объяснений. Кнопки «раньше-позже» у каждого снимка
 * превратили бы перестановку десяти кадров в двадцать нажатий.
 *
 * `force-fallback` включён намеренно: браузерное drag-and-drop тащит
 * полупрозрачный слепок элемента мимо сетки и ведёт себя по-разному
 * в разных браузерах. Своя отрисовка перетаскивания одинакова везде,
 * включая касание. Задержка — только для пальца: без неё прокрутка
 * страницы по галерее превращается в перестановку снимков.
 */
const props = defineProps<{ errors?: ValidationErrors }>()

const items = defineModel<GalleryItem[]>({ default: () => [] })

const openAt = ref<number | null>(null)
const dragging = ref(false)

// Ключ должен пережить перестановку, а у нового снимка id ещё нет:
// без своего ключа плитки при перетаскивании меняются содержимым.
let nextKey = 1

/** Ошибки приходят ключами вида `gallery.2.file` — снимок называем по номеру. */
const messages = computed(() => Object.entries(props.errors ?? {})
  .filter(([key]) => key.startsWith('gallery'))
  .map(([key, list]) => {
    const position = Number(key.split('.')[1])

    return Number.isNaN(position)
      ? (list?.[0] ?? '')
      : `Снимок ${position + 1}: ${list?.[0] ?? ''}`
  }))

function add(event: Event): void {
  const input = event.target as HTMLInputElement

  Array.from(input.files ?? []).forEach((file) => {
    const preview = URL.createObjectURL(file)

    items.value.push({ key: `new-${nextKey++}`, id: null, file, thumb: preview, full: preview })
  })

  // Иначе повторный выбор того же файла не вызовет change.
  input.value = ''
}

function remove(position: number): void {
  const [gone] = items.value.splice(position, 1)

  // Ссылка на выбранный файл живёт до конца вкладки, если её не отозвать.
  if (gone?.file) URL.revokeObjectURL(gone.thumb)
}
</script>

<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <UButton
        color="neutral"
        variant="outline"
        icon="i-lucide-image-plus"
        as="label"
        class="cursor-pointer"
      >
        Добавить фото
        <input
          type="file"
          accept="image/*"
          multiple
          class="sr-only"
          @change="add"
        >
      </UButton>

      <p
        v-if="items.length > 1"
        class="text-sm text-muted"
      >
        Перетащите снимок, чтобы изменить порядок
      </p>
    </div>

    <p
      v-for="(message, position) in messages"
      :key="position"
      class="mt-3 text-sm text-error"
    >
      {{ message }}
    </p>

    <!-- ── Пусто ── -->
    <div
      v-if="!items.length"
      class="mt-4 rounded-xl border border-dashed border-default px-5 py-10 text-center"
    >
      <UIcon
        name="i-lucide-images"
        class="mx-auto size-7 text-forest-300"
      />
      <p class="mt-3 text-toned">
        Пока пусто.
      </p>
      <p class="mx-auto mt-1 max-w-md text-sm text-muted">
        Снимки с прошлых встреч убеждают сильнее описания: по ним видно место,
        свет и то, сколько людей приходит.
      </p>
    </div>

    <!-- ── Сетка ── -->
    <draggable
      v-else
      v-model="items"
      item-key="key"
      tag="ul"
      class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
      ghost-class="opacity-30"
      :animation="180"
      :force-fallback="true"
      :delay-on-touch-only="true"
      :delay="120"
      @start="dragging = true"
      @end="dragging = false"
    >
      <template #item="{ element, index }">
        <li
          class="group relative aspect-square overflow-hidden rounded-xl bg-elevated ring-1 ring-default"
          :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
        >
          <img
            :src="element.thumb"
            alt=""
            class="size-full object-cover"
            draggable="false"
          >

          <!-- Открыть во весь экран: выбирать порядок по обрезанным
               квадратам — значит выбирать его вслепую. -->
          <button
            type="button"
            class="absolute inset-0"
            :aria-label="`Открыть снимок ${index + 1} во весь экран`"
            @click="openAt = index"
          />

          <UButton
            class="absolute right-2 top-2 rounded-full"
            color="neutral"
            variant="solid"
            size="xs"
            icon="i-lucide-trash-2"
            :aria-label="`Удалить снимок ${index + 1}`"
            @click.stop="remove(index)"
          />
        </li>
      </template>
    </draggable>

    <Lightbox
      v-model="openAt"
      :items="items"
    />
  </div>
</template>
