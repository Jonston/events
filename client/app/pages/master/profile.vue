<script setup lang="ts">
import type { ValidationErrors } from '~/types'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Моя страница'
})

const { fetchProfile, saveProfile } = useMasterApi()
const toast = useToast()

const { data: profile, refresh } = await useAsyncData('master-profile', fetchProfile)

const form = reactive({
  name: profile.value?.name ?? '',
  headline: profile.value?.headline ?? '',
  bio: profile.value?.bio ?? '',
  city: profile.value?.city ?? '',
  isActive: profile.value?.isActive ?? false
})

const photo = ref<File | null>(null)
const cover = ref<File | null>(null)
const removePhoto = ref(false)
const removeCover = ref(false)

const photoPreview = computed(() => photo.value ? URL.createObjectURL(photo.value) : null)
const coverPreview = computed(() => cover.value ? URL.createObjectURL(cover.value) : null)
const currentPhoto = computed(() => removePhoto.value ? null : (profile.value?.photo ?? null))
const currentCover = computed(() => removeCover.value ? null : (profile.value?.cover ?? null))

const saving = ref(false)
const errors = ref<ValidationErrors>({})

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

function pick(target: Ref<File | null>, flag: Ref<boolean>) {
  return (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null
    target.value = file
    if (file) flag.value = false
  }
}

const onPhoto = pick(photo, removePhoto)
const onCover = pick(cover, removeCover)

async function submit() {
  saving.value = true
  errors.value = {}

  try {
    await saveProfile(toFormData({
      ...form,
      ...(photo.value ? { photo: photo.value } : {}),
      ...(cover.value ? { cover: cover.value } : {}),
      ...(removePhoto.value ? { removePhoto: true } : {}),
      ...(removeCover.value ? { removeCover: true } : {})
    }))

    photo.value = null
    cover.value = null
    removePhoto.value = false
    removeCover.value = false
    await refresh()

    toast.add({ title: 'Страница сохранена', color: 'success' })
  } catch (error: unknown) {
    const failure = error as { data?: { errors?: ValidationErrors, message?: string } }
    errors.value = failure?.data?.errors ?? {}
    toast.add({ title: 'Не сохранилось', description: failure?.data?.message, color: 'error' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form
    class="max-w-3xl space-y-8"
    onsubmit="return false"
    @submit.prevent="submit"
  >
    <UAlert
      v-if="!form.isActive"
      color="warning"
      variant="subtle"
      icon="i-lucide-eye-off"
      title="Страница скрыта"
      description="Пока она не опубликована, вас нет в каталоге, а мероприятия видны только по прямой ссылке."
    />

    <section class="space-y-4">
      <h2 class="font-display text-xl text-highlighted">
        Как вас видят
      </h2>

      <UFormField
        label="Имя"
        name="name"
        required
        :error="fieldError('name')"
      >
        <UInput
          v-model="form.name"
          size="lg"
          class="w-full"
        />
      </UFormField>

      <UFormField
        label="Чем занимаетесь"
        name="headline"
        description="Одна строка под именем в каталоге."
        :error="fieldError('headline')"
      >
        <UInput
          v-model="form.headline"
          size="lg"
          class="w-full"
          placeholder="Телесные практики и работа с дыханием"
        />
      </UFormField>

      <UFormField
        label="Город"
        name="city"
        :error="fieldError('city')"
      >
        <UInput
          v-model="form.city"
          size="lg"
          class="w-full"
          placeholder="Киев"
        />
      </UFormField>

      <UFormField
        label="О себе"
        name="bio"
        description="Кому и чем вы помогаете, как ведёте занятия."
        :error="fieldError('bio')"
      >
        <UTextarea
          v-model="form.bio"
          :rows="8"
          size="lg"
          class="w-full"
        />
      </UFormField>
    </section>

    <USeparator />

    <section class="space-y-5">
      <h2 class="font-display text-xl text-highlighted">
        Фотографии
      </h2>
      <p class="text-sm text-muted">
        Портрет идёт в круглый аватар, обложка — в широкую шапку страницы.
        Без них место держит ботанический рисунок.
      </p>

      <UFormField
        label="Портрет"
        name="photo"
        :error="fieldError('photo')"
      >
        <div class="flex items-center gap-4">
          <div class="size-20 shrink-0 overflow-hidden rounded-full bg-elevated">
            <img
              v-if="photoPreview || currentPhoto"
              :src="photoPreview ?? currentPhoto ?? undefined"
              alt=""
              class="size-full object-cover"
            >
            <BotanicalMark
              v-else
              class="size-full p-3 text-forest-400"
              variant="seal"
            />
          </div>
          <div class="space-y-2">
            <input
              type="file"
              accept="image/*"
              class="block text-sm file:mr-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
              @change="onPhoto"
            >
            <UButton
              v-if="currentPhoto || photo"
              color="neutral"
              variant="ghost"
              size="xs"
              icon="i-lucide-trash-2"
              @click="photo = null; removePhoto = true"
            >
              Убрать
            </UButton>
          </div>
        </div>
      </UFormField>

      <UFormField
        label="Обложка"
        name="cover"
        :error="fieldError('cover')"
      >
        <div class="space-y-3">
          <div class="h-36 overflow-hidden rounded-lg bg-forest-950">
            <img
              v-if="coverPreview || currentCover"
              :src="coverPreview ?? currentCover ?? undefined"
              alt=""
              class="size-full object-cover"
            >
            <BotanicalScene
              v-else
              :seed="profile?.id ?? 1"
              class="size-full"
            />
          </div>
          <div class="flex items-center gap-3">
            <input
              type="file"
              accept="image/*"
              class="block text-sm file:mr-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
              @change="onCover"
            >
            <UButton
              v-if="currentCover || cover"
              color="neutral"
              variant="ghost"
              size="xs"
              icon="i-lucide-trash-2"
              @click="cover = null; removeCover = true"
            >
              Убрать
            </UButton>
          </div>
        </div>
      </UFormField>
    </section>

    <USeparator />

    <section class="space-y-4">
      <USwitch
        v-model="form.isActive"
        label="Показывать страницу в каталоге"
        description="Скрытая страница не удаляется: мероприятия и отзывы остаются на месте."
      />

      <div class="flex flex-wrap items-center gap-3">
        <UButton
          type="submit"
          color="secondary"
          size="lg"
          :loading="saving"
          class="rounded-full"
        >
          Сохранить
        </UButton>

        <UButton
          v-if="profile?.isActive"
          :to="profile.publicUrl"
          target="_blank"
          color="neutral"
          variant="ghost"
          size="lg"
          trailing-icon="i-lucide-external-link"
        >
          Посмотреть на витрине
        </UButton>
      </div>
    </section>
  </form>
</template>
