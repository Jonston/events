<script setup lang="ts">
/**
 * Форма заявки.
 *
 * Просьба описать запрос — намеренное трение: чем больше усилий требует
 * заявка, тем мотивированнее заявитель, и на нашем объёме такой фильтр
 * даёт качество, которого реклама дать не может.
 *
 * Обязателен только телефон: по нему и перезванивают. Email нужен не всем
 * и стоит отдельной строкой — необязательное поле не должно делить строку
 * с обязательным и выглядеть таким же.
 */
import type { ValidationErrors } from '~/types'

const props = withDefaults(defineProps<{
  eventId?: number | null
  masterId?: number | null
  title?: string
  note?: string | null
}>(), {
  eventId: null,
  masterId: null,
  title: 'Оставить заявку',
  note: null
})

const { sendLead } = useShowcaseApi()

const form = reactive({
  name: '',
  phone: '',
  email: '',
  message: '',
  // Ловушка для ботов: человек этого поля не видит и не заполняет.
  company: ''
})

const sending = ref(false)
const success = ref<string | null>(null)
const errors = ref<ValidationErrors>({})

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

async function submit() {
  sending.value = true
  success.value = null
  errors.value = {}

  try {
    const result = await sendLead({
      name: form.name,
      phone: form.phone,
      email: form.email || undefined,
      message: form.message || undefined,
      eventId: props.eventId,
      masterId: props.masterId
    })

    success.value = result.message
    Object.assign(form, { name: '', phone: '', email: '', message: '' })
  } catch (error: unknown) {
    const failure = error as { data?: { errors?: ValidationErrors, message?: string } }
    errors.value = failure?.data?.errors ?? {}

    if (Object.keys(errors.value).length === 0) {
      errors.value = { form: [failure?.data?.message ?? 'Не удалось отправить заявку. Попробуйте ещё раз.'] }
    }
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <section class="rounded-3xl bg-white p-6 shadow-[0_18px_40px_-28px] shadow-forest-900/40 ring-1 ring-forest-100 sm:p-7">
    <div class="mb-5 flex items-center gap-2">
      <h2 class="font-display text-2xl text-forest-900">
        {{ title }}
      </h2>

      <!-- Пояснение живёт в подсказке, а не строкой под заголовком: оно
           снимает возражение у того, кто сомневается, а решившемуся
           заполнять — только отодвигает первое поле вниз. -->
      <UPopover
        v-if="note"
        mode="click"
      >
        <UButton
          color="neutral"
          variant="ghost"
          size="xs"
          icon="i-lucide-circle-help"
          aria-label="Об условиях заявки"
        />
        <template #content>
          <p class="max-w-64 px-4 py-3 text-sm leading-relaxed text-stone-600">
            {{ note }}
          </p>
        </template>
      </UPopover>
    </div>

    <UAlert
      v-if="success"
      color="primary"
      variant="subtle"
      icon="i-lucide-check"
      :description="success"
      class="mb-5"
    />

    <UAlert
      v-if="fieldError('form')"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :description="fieldError('form')"
      class="mb-5"
    />

    <!-- `onsubmit` статическим атрибутом: до гидрации обработчика Vue ещё
         нет, и браузер отправил бы форму сам — заявка ушла бы в адресную
         строку, а человек оказался бы на перезагруженной странице
         с потерянным текстом. -->
    <form
      class="space-y-4"
      novalidate
      onsubmit="return false"
      @submit.prevent="submit"
    >
      <UFormField
        label="Как вас зовут"
        name="name"
        :error="fieldError('name')"
      >
        <UInput
          v-model="form.name"
          size="lg"
          class="w-full"
          autocomplete="name"
        />
      </UFormField>

      <UFormField
        label="Телефон"
        name="phone"
        :error="fieldError('phone')"
      >
        <UInput
          v-model="form.phone"
          type="tel"
          size="lg"
          class="w-full"
          placeholder="+380"
          autocomplete="tel"
        />
      </UFormField>

      <!-- «Необязательно» стоит в подписи, а не звёздочкой у остальных:
           полей мало, и пометить одно исключение честнее, чем пометить
           всё правило. -->
      <UFormField
        name="email"
        :error="fieldError('email')"
      >
        <template #label>
          Email <span class="font-normal text-stone-400">— необязательно</span>
        </template>
        <UInput
          v-model="form.email"
          type="email"
          size="lg"
          class="w-full"
          autocomplete="email"
        />
      </UFormField>

      <UFormField
        label="Что вы ищете"
        name="message"
        :error="fieldError('message')"
      >
        <UTextarea
          v-model="form.message"
          :rows="4"
          size="lg"
          class="w-full"
        />
      </UFormField>

      <div
        class="hidden"
        aria-hidden="true"
      >
        <label for="lead-company">Компания</label>
        <input
          id="lead-company"
          v-model="form.company"
          type="text"
          tabindex="-1"
          autocomplete="off"
        >
      </div>

      <UButton
        type="submit"
        color="secondary"
        size="xl"
        :loading="sending"
        class="rounded-full"
      >
        Отправить заявку
      </UButton>
    </form>
  </section>
</template>
