<script setup lang="ts">
import type { EventSessionForm, GalleryItem, MasterEvent, ValidationErrors } from '~/types'

const props = defineProps<{ event?: MasterEvent | null }>()

const { createEvent, updateEvent } = useMasterApi()
const toast = useToast()
const router = useRouter()

const form = reactive({
  title: props.event?.title ?? '',
  summary: props.event?.summary ?? '',
  description: props.event?.description ?? '',
  location: props.event?.location ?? '',
  latitude: props.event?.latitude ?? null as number | null,
  longitude: props.event?.longitude ?? null as number | null,
  startsAt: props.event?.startsAt ?? '',
  endsAt: props.event?.endsAt ?? '',
  price: props.event?.price ?? null as number | null,
  // Хранится долей, показывается процентами: «30 %» человек называет сам,
  // а «0.3» ему приходится переводить в уме.
  depositPercent: props.event?.depositRate == null ? 30 : Math.round(props.event.depositRate * 100),
  capacity: props.event?.capacity ?? null as number | null,
  isPublished: props.event?.isPublished ?? false
})

const sessions = ref<EventSessionForm[]>(
  props.event?.sessions.map(session => ({ ...session })) ?? []
)

// Уже загруженные снимки приходят с адресами и без файла: перезаливать их
// ради перестановки незачем, серверу хватит id.
const gallery = ref<GalleryItem[]>(
  props.event?.gallery.map(photo => ({
    key: `saved-${photo.id}`,
    id: photo.id,
    file: null,
    thumb: photo.thumb,
    full: photo.full
  })) ?? []
)

const image = ref<File | null>(null)
const removeImage = ref(false)
const imagePreview = computed(() => image.value ? URL.createObjectURL(image.value) : null)
const currentImage = computed(() => removeImage.value ? null : (props.event?.image ?? null))

const saving = ref(false)
const errors = ref<ValidationErrors>({})

/**
 * Форма разложена по вкладкам: у мероприятия четыре разных занятия —
 * описать, назначить время, поставить на карту и собрать медиа, — и держать
 * их одной простынёй значило бы прокручивать полтора экрана ради правки
 * одного поля.
 */
const TABS = [
  { value: 'main', label: 'Основное', icon: 'i-lucide-text', fields: ['title', 'summary', 'description', 'price', 'depositRate', 'capacity'] },
  { value: 'schedule', label: 'Программа', icon: 'i-lucide-calendar-days', fields: ['startsAt', 'endsAt', 'sessions'] },
  { value: 'location', label: 'Локация', icon: 'i-lucide-map-pin', fields: ['location', 'latitude', 'longitude'] },
  { value: 'media', label: 'Медиа', icon: 'i-lucide-image', fields: ['image', 'gallery'] }
]

const tab = ref('main')

/** Сколько ошибок прячется на вкладке — иначе они молча остаются за кадром. */
function errorCount(value: string): number {
  const fields = TABS.find(item => item.value === value)?.fields ?? []

  return Object.keys(errors.value)
    .filter(key => fields.some(field => key === field || key.startsWith(`${field}.`)))
    .length
}

const tabItems = computed(() => TABS.map(item => ({
  ...item,
  badge: errorCount(item.value) || undefined
})))

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

/** Ошибки по пунктам программы приходят ключами вида `sessions.0.title`. */
const scheduleError = computed(() => {
  const key = Object.keys(errors.value).find(name => name.startsWith('sessions'))

  return key ? errors.value[key]?.[0] : undefined
})

const deposit = computed(() => {
  if (!form.price) return null

  const amount = Math.round((form.price * (form.depositPercent ?? 0)) / 100)

  return { deposit: amount, rest: Math.round(form.price - amount) }
})

function onFile(event: Event): void {
  const file = (event.target as HTMLInputElement).files?.[0] ?? null
  image.value = file
  if (file) removeImage.value = false
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    title: form.title,
    summary: form.summary,
    description: form.description,
    location: form.location,
    latitude: form.latitude,
    longitude: form.longitude,
    startsAt: form.startsAt,
    endsAt: form.endsAt,
    price: form.price,
    depositRate: form.depositPercent === null ? null : form.depositPercent / 100,
    capacity: form.capacity,
    isPublished: form.isPublished,
    sessions: sessions.value,
    // Галерея уходит целиком и по порядку: уже загруженный снимок —
    // одним id, только что выбранный — файлом.
    gallery: gallery.value.map(item => item.file ? { file: item.file } : { id: item.id }),
    ...(image.value ? { image: image.value } : {}),
    ...(removeImage.value ? { removeImage: true } : {})
  }

  try {
    const saved = props.event
      ? await updateEvent(props.event.id, toFormData(payload))
      : await createEvent(toFormData(payload))

    toast.add({
      title: props.event ? 'Мероприятие сохранено' : 'Мероприятие создано',
      description: saved.isPublished ? 'Оно уже на витрине.' : 'Черновик виден только вам.',
      color: 'success'
    })

    await router.push('/master/events')
  } catch (error: unknown) {
    const failure = error as { data?: { errors?: ValidationErrors, message?: string } }
    errors.value = failure?.data?.errors ?? {}

    // Переключаемся на вкладку с первой ошибкой: иначе человек смотрит
    // на форму без замечаний и не понимает, что не так.
    const broken = TABS.find(item => errorCount(item.value) > 0)
    if (broken) tab.value = broken.value

    toast.add({
      title: 'Не сохранилось',
      description: Object.keys(errors.value).length
        ? 'Проверьте выделенные поля.'
        : (failure?.data?.message ?? 'Попробуйте ещё раз.'),
      color: 'error'
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form
    class="max-w-4xl"
    onsubmit="return false"
    @submit.prevent="submit"
  >
    <UTabs
      v-model="tab"
      :items="tabItems"
      :content="false"
      class="mb-6"
    />

    <!-- ── Основное ── -->
    <section
      v-show="tab === 'main'"
      class="space-y-4"
    >
      <UFormField
        label="Название"
        name="title"
        required
        :error="fieldError('title')"
      >
        <UInput
          v-model="form.title"
          size="lg"
          class="w-full"
          placeholder="Дыхание выходного дня"
        />
      </UFormField>

      <UFormField
        label="Короткое описание"
        name="summary"
        description="Одна строка под названием в каталоге."
        :error="fieldError('summary')"
      >
        <UInput
          v-model="form.summary"
          size="lg"
          class="w-full"
        />
      </UFormField>

      <UFormField
        label="Подробно"
        name="description"
        :error="fieldError('description')"
      >
        <UTextarea
          v-model="form.description"
          :rows="10"
          size="lg"
          class="w-full"
          placeholder="Для кого это, что будет происходить, что взять с собой."
        />
      </UFormField>

      <USeparator />

      <div class="grid gap-4 sm:grid-cols-3">
        <UFormField
          label="Стоимость, ₴"
          name="price"
          required
          :error="fieldError('price')"
        >
          <UInputNumber
            v-model="form.price"
            :min="0"
            :step="100"
            size="lg"
            class="w-full"
          />
        </UFormField>

        <UFormField
          label="Предоплата, %"
          name="depositRate"
          :error="fieldError('depositRate')"
        >
          <UInputNumber
            v-model="form.depositPercent"
            :min="0"
            :max="100"
            :step="5"
            size="lg"
            class="w-full"
          />
        </UFormField>

        <UFormField
          label="Мест в группе"
          name="capacity"
          :error="fieldError('capacity')"
        >
          <UInputNumber
            v-model="form.capacity"
            :min="1"
            size="lg"
            class="w-full"
          />
        </UFormField>
      </div>

      <p
        v-if="deposit"
        class="text-sm text-muted"
      >
        Участник платит {{ deposit.deposit }} ₴ при бронировании и {{ deposit.rest }} ₴ на месте.
      </p>
    </section>

    <!-- ── Программа ── -->
    <section
      v-show="tab === 'schedule'"
      class="space-y-5"
    >
      <div class="grid gap-4 sm:grid-cols-2">
        <UFormField
          label="Начало"
          name="startsAt"
          required
          :error="fieldError('startsAt')"
        >
          <DateTimeField v-model="form.startsAt" />
        </UFormField>

        <UFormField
          label="Окончание"
          name="endsAt"
          :error="fieldError('endsAt')"
        >
          <DateTimeField
            v-model="form.endsAt"
            default-time="16:00"
          />
        </UFormField>
      </div>

      <USeparator />

      <MasterScheduleEditor
        v-model="sessions"
        :starts-at="form.startsAt"
        :error="scheduleError"
      />
    </section>

    <!-- ── Локация ──
         Карта монтируется только при открытой вкладке: в скрытом
         контейнере у неё нулевой размер и пустое полотно. -->
    <section v-show="tab === 'location'">
      <ClientOnly>
        <MasterLocationPicker
          v-model:address="form.location"
          v-model:latitude="form.latitude"
          v-model:longitude="form.longitude"
          :active="tab === 'location'"
          :error="fieldError('location') ?? fieldError('latitude') ?? fieldError('longitude')"
        />
        <template #fallback>
          <div class="h-80 animate-pulse rounded-lg bg-elevated" />
        </template>
      </ClientOnly>
    </section>

    <!-- ── Медиа ── -->
    <section
      v-show="tab === 'media'"
      class="space-y-4"
    >
      <UFormField
        label="Обложка"
        name="image"
        description="Необязательно. Без неё на карточке остаётся ботанический знак."
        :error="fieldError('image')"
      >
        <div class="flex items-center gap-4">
          <div class="h-32 w-44 shrink-0 overflow-hidden rounded-lg bg-elevated">
            <img
              v-if="imagePreview || currentImage"
              :src="imagePreview ?? currentImage ?? undefined"
              alt=""
              class="size-full object-cover"
            >
            <BotanicalMark
              v-else
              class="size-full p-5 text-forest-300"
              variant="seal"
            />
          </div>

          <div class="space-y-2">
            <input
              type="file"
              accept="image/*"
              class="block text-sm file:mr-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
              @change="onFile"
            >
            <UButton
              v-if="currentImage || image"
              color="neutral"
              variant="ghost"
              size="xs"
              icon="i-lucide-trash-2"
              @click="image = null; removeImage = true"
            >
              Убрать обложку
            </UButton>
          </div>
        </div>
      </UFormField>

      <USeparator />

      <UFormField
        label="Галерея"
        name="gallery"
        description="Снимки с прошлых встреч. Первый идёт крупным кадром на странице мероприятия."
      >
        <MasterEventGallery
          v-model="gallery"
          :errors="errors"
        />
      </UFormField>
    </section>

    <!-- ── Общий подвал: он один на все вкладки ──
         Сохранять с каждой вкладки отдельно нельзя: мероприятие уходит
         на сервер целиком, и кнопка на каждой вкладке обещала бы обратное. -->
    <USeparator class="my-6" />

    <div class="space-y-4">
      <USwitch
        v-model="form.isPublished"
        label="Опубликовать на витрине"
        description="Снятое с публикации остаётся в кабинете — его видите только вы."
      />

      <div class="flex flex-wrap gap-3">
        <UButton
          type="submit"
          color="secondary"
          size="lg"
          :loading="saving"
          class="rounded-full"
        >
          {{ event ? 'Сохранить' : 'Создать мероприятие' }}
        </UButton>

        <UButton
          to="/master/events"
          color="neutral"
          variant="ghost"
          size="lg"
        >
          Отмена
        </UButton>
      </div>
    </div>
  </form>
</template>
