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

const { sendLead, checkLead } = useShowcaseApi()
const { currentUser } = useAuth()
const { ensure: guestKey } = useGuestKey()

const form = reactive({
  name: '',
  phone: '',
  email: '',
  message: '',
  // Ловушка для ботов: человек этого поля не видит и не заполняет.
  company: ''
})

/**
 * Вошедшему подставляем то, что о нём уже известно: имя, почту и основной
 * телефон. Переписывать свои же контакты в каждой заявке — работа,
 * которую площадка может сделать за человека; остаётся только сообщение.
 *
 * Заполненное руками не трогаем: подстановка приходит после гидрации,
 * и затирать ею начатый текст нельзя.
 */
watch(currentUser, (user) => {
  if (!user) return

  if (!form.name) form.name = user.name
  if (!form.email) form.email = user.email
  if (!form.phone && user.phone) form.phone = user.phone
}, { immediate: true })

/** Свои номера — списком: записаться можно и не на свой телефон. */
const phoneOptions = computed(() => currentUser.value?.phones ?? [])

const sending = ref(false)
const success = ref<string | null>(null)
const errors = ref<ValidationErrors>({})

/**
 * Заявка на это мероприятие уже оставлена.
 *
 * Спрашиваем при открытии страницы, а не после отправки: узнать об этом,
 * заполнив всю форму, — худший момент из возможных. Только на клиенте:
 * ответ зависит от метки в браузере, и в общем кэше страницы ему не место.
 */
const applied = ref(false)

onMounted(async () => {
  if (!props.eventId) return

  try {
    applied.value = (await checkLead(props.eventId, guestKey())).applied
  } catch {
    // Не ответили — показываем форму. Лишняя заявка лучше, чем закрытая
    // дорога к мастеру из-за упавшей проверки.
  }
})

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
      masterId: props.masterId,
      guestKey: guestKey()
    })

    success.value = result.message

    // На мероприятие записываются один раз — форму дальше показывать незачем.
    if (props.eventId) applied.value = true

    // Вошедшему возвращаем его же контакты: он мог захотеть записать
    // ещё кого-то, и стирать имя с телефоном ради этого незачем.
    Object.assign(form, {
      name: currentUser.value?.name ?? '',
      phone: currentUser.value?.phone ?? '',
      email: currentUser.value?.email ?? '',
      message: ''
    })
  } catch (error: unknown) {
    const failure = error as { data?: { errors?: ValidationErrors, message?: string, applied?: boolean } }

    // Сервер узнал человека по аккаунту или телефону, хотя метки браузера
    // у нас не было: показываем то же, что показали бы сразу.
    if (failure?.data?.applied) {
      applied.value = true
      success.value = failure.data.message ?? null

      return
    }

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
    <!-- ── Заявка отправлена или уже была ──
         Вместо формы — отметка: повторная заявка ничего не добавит,
         а пустая форма под сообщением об успехе выглядит как приглашение
         отправить ещё раз. -->
    <div
      v-if="applied || success"
      class="py-6 text-center"
    >
      <span class="mx-auto flex size-20 items-center justify-center rounded-full bg-forest-50 text-forest-700">
        <UIcon
          name="i-lucide-check"
          class="size-11"
        />
      </span>

      <h2 class="mt-5 font-display text-2xl text-forest-900">
        {{ eventId ? 'Вы записаны' : 'Заявка отправлена' }}
      </h2>
      <p class="mx-auto mt-2 max-w-sm leading-relaxed text-stone-600">
        {{ success ?? 'Заявка уже у мастера — он свяжется с вами.' }}
      </p>

      <p
        v-if="!currentUser"
        class="mx-auto mt-4 max-w-sm text-sm text-stone-500"
      >
        Войдите, чтобы видеть свои записи в кабинете.
      </p>
    </div>

    <template v-else>
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
            icon="i-lucide-help-circle"
            aria-label="Об условиях заявки"
          />
          <template #content>
            <p class="max-w-64 px-4 py-3 text-sm leading-relaxed text-stone-600">
              {{ note }}
            </p>
          </template>
        </UPopover>
      </div>

      <p
        v-if="currentUser"
        class="mb-4 flex flex-wrap items-center gap-x-2 text-sm text-stone-500"
      >
        <UIcon
          name="i-lucide-user-check"
          class="size-4 text-forest-600"
        />
        Ваши данные подставлены.
        <NuxtLink
          to="/cabinet/profile"
          class="underline decoration-stone-300 underline-offset-2 hover:text-forest-800"
        >
          Изменить
        </NuxtLink>
      </p>

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
          <!-- У вошедшего с несколькими номерами — поле со списком своих:
               набрать другой всё равно можно, но обычно нужен один из них. -->
          <UInputMenu
            v-if="phoneOptions.length > 1"
            v-model="form.phone"
            :items="phoneOptions"
            size="lg"
            class="w-full"
            placeholder="+380"
            autocomplete="tel"
          />
          <UInput
            v-else
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
    </template>
  </section>
</template>
