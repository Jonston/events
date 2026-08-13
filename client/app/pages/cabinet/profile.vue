<script setup lang="ts">
import type { ValidationErrors } from '~/types'

definePageMeta({
  // Роли нет намеренно: свои данные есть у любого вошедшего.
  middleware: 'sanctum:auth',
  layout: 'cabinet',
  pageTitle: 'Мои данные'
})

const { fetchProfile, saveProfile } = useMemberApi()
const { refreshIdentity } = useAuth()
const toast = useToast()

const { data: profile, refresh } = await useAsyncData('member-profile', fetchProfile)

const name = ref(profile.value?.name ?? '')

/**
 * Номера правятся списком, а не по одному: строка с пустым номером —
 * это «сейчас впишу», и отправлять её на сервер до того, как её заполнили,
 * незачем.
 */
const phones = ref(
  (profile.value?.phones ?? []).map((phone, index) => ({ key: index, phone, isPrimary: index === 0 }))
)

let nextKey = phones.value.length

const saving = ref(false)
const errors = ref<ValidationErrors>({})

function add(): void {
  phones.value.push({ key: nextKey++, phone: '', isPrimary: phones.value.length === 0 })
}

function remove(index: number): void {
  const [gone] = phones.value.splice(index, 1)

  // Убрали основной — основным становится первый оставшийся: заявке нужен
  // номер по умолчанию, и выбирать его молча лучше, чем не подставить ничего.
  if (gone?.isPrimary && phones.value[0]) phones.value[0].isPrimary = true
}

function makePrimary(index: number): void {
  phones.value.forEach((item, position) => {
    item.isPrimary = position === index
  })
}

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

async function submit() {
  saving.value = true
  errors.value = {}

  try {
    await saveProfile({
      name: name.value,
      phones: phones.value
        .filter(item => item.phone.trim() !== '')
        .map(item => ({ phone: item.phone.trim(), isPrimary: item.isPrimary }))
    })

    await refresh()
    // Шапка и форма заявки читают вошедшего из своего состояния — без этого
    // они показывали бы старое имя до перезагрузки страницы.
    await refreshIdentity()

    toast.add({ title: 'Сохранено', color: 'success' })
  } catch (error: unknown) {
    const failure = error as { data?: { errors?: ValidationErrors, message?: string } }
    errors.value = failure?.data?.errors ?? {}

    toast.add({
      title: 'Не сохранилось',
      description: Object.keys(errors.value).length ? 'Проверьте выделенные поля.' : failure?.data?.message,
      color: 'error'
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form
    onsubmit="return false"
    @submit.prevent="submit"
  >
    <div class="grid gap-8 lg:grid-cols-2">
      <FormSection
        title="Кто вы"
        description="Так вас увидит мастер в заявке."
      >
        <UFormField
          label="Имя"
          name="name"
          required
          :error="fieldError('name')"
        >
          <UInput
            v-model="name"
            size="lg"
            class="w-full"
          />
        </UFormField>

        <UFormField
          label="Почта"
          description="Это ваш логин — сменить его пока нельзя."
        >
          <UInput
            :model-value="profile?.email"
            size="lg"
            class="w-full"
            disabled
          />
        </UFormField>
      </FormSection>

      <FormSection
        title="Телефоны"
        description="Основной подставится в заявку сам. Остальные пригодятся, если записываетесь не на свой номер — выберете нужный прямо в форме."
      >
        <div
          v-for="(item, index) in phones"
          :key="item.key"
          class="flex flex-wrap items-start gap-3"
        >
          <UFormField
            class="min-w-56 flex-1"
            :name="`phones.${index}.phone`"
            :error="fieldError(`phones.${index}.phone`)"
          >
            <UInput
              v-model="item.phone"
              size="lg"
              class="w-full"
              placeholder="+380 (67) 123-45-67"
              :aria-label="`Телефон ${index + 1}`"
            />
          </UFormField>

          <UButton
            :color="item.isPrimary ? 'primary' : 'neutral'"
            :variant="item.isPrimary ? 'soft' : 'ghost'"
            size="lg"
            :icon="item.isPrimary ? 'i-lucide-star' : 'i-lucide-star-off'"
            @click="makePrimary(index)"
          >
            {{ item.isPrimary ? 'Основной' : 'Сделать основным' }}
          </UButton>

          <UButton
            color="neutral"
            variant="ghost"
            size="lg"
            icon="i-lucide-trash-2"
            :aria-label="`Убрать телефон ${index + 1}`"
            @click="remove(index)"
          />
        </div>

        <p
          v-if="!phones.length"
          class="text-sm text-dimmed"
        >
          Номера нет — заявку придётся заполнять руками каждый раз.
        </p>

        <UButton
          color="neutral"
          variant="outline"
          icon="i-lucide-plus"
          @click="add"
        >
          Добавить номер
        </UButton>
      </FormSection>
    </div>

    <!-- Только на клиенте: серверная разметка телепорта уезжает мимо
         своей цели, и после гидрации Vue пересобирает панель заново. -->
    <ClientOnly>
      <Teleport
        to="#cabinet-actions"
        defer
      >
        <UButton
          color="secondary"
          size="lg"
          :loading="saving"
          class="rounded-full"
          @click="submit"
        >
          Сохранить
        </UButton>
      </Teleport>
    </ClientOnly>
  </form>
</template>
