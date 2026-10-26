<script setup lang="ts">
import type { CalendarOptions, EventClickArg } from '@fullcalendar/core'
import ruLocale from '@fullcalendar/core/locales/ru'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import timeGridPlugin from '@fullcalendar/timegrid'
import FullCalendar from '@fullcalendar/vue3'
import type { MasterEvent } from '~/types'

const props = defineProps<{ events: MasterEvent[] }>()

const router = useRouter()

/**
 * Черновик и опубликованное различаются цветом, а не подписью: в сетке
 * календаря на подпись нет места, а разницу видеть нужно с первого взгляда.
 */
const calendarEvents = computed(() => props.events.map(event => ({
  id: String(event.id),
  title: event.title,
  start: event.startsAt,
  end: event.endsAt ?? undefined,
  backgroundColor: event.isPublished ? 'var(--color-forest-600)' : 'var(--color-stone-400)',
  borderColor: event.isPublished ? 'var(--color-forest-700)' : 'var(--color-stone-500)',
  textColor: 'white',
  extendedProps: { published: event.isPublished }
})))

const options = computed<CalendarOptions>(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: ruLocale,
  // Европейское время: «18:00», а не «6 PM».
  slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  firstDay: 1,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,listMonth'
  },
  buttonText: {
    today: 'Сегодня',
    month: 'Месяц',
    week: 'Неделя',
    list: 'Списком'
  },
  height: 'auto',
  events: calendarEvents.value,
  // Клик по мероприятию ведёт в его правку: календарь здесь — способ
  // добраться до записи, а не отдельный экран для разглядывания.
  eventClick: (info: EventClickArg) => {
    info.jsEvent.preventDefault()
    router.push(`/master/events/${info.event.id}`)
  },
  noEventsText: 'В этом месяце ничего не запланировано'
}))
</script>

<template>
  <div class="event-calendar">
    <FullCalendar :options="options" />

    <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-muted">
      <span class="inline-flex items-center gap-2">
        <span class="size-3 rounded-sm bg-forest-600" />
        На витрине
      </span>
      <span class="inline-flex items-center gap-2">
        <span class="size-3 rounded-sm bg-stone-400" />
        Черновик
      </span>
    </div>
  </div>
</template>

<style scoped>
/*
 * FullCalendar приносит свои цвета и кегль. Переопределяем токенами темы,
 * чтобы календарь читался частью кабинета, а не вставленным виджетом:
 * иначе рядом с остальными экранами он выглядит чужим.
 */
.event-calendar :deep(.fc) {
  --fc-border-color: var(--ui-border);
  --fc-page-bg-color: transparent;
  --fc-neutral-bg-color: var(--ui-bg-elevated);
  --fc-today-bg-color: color-mix(in oklab, var(--ui-primary) 8%, transparent);
  --fc-list-event-hover-bg-color: var(--ui-bg-elevated);
  font-family: inherit;
  font-size: 0.9375rem;
}

.event-calendar :deep(.fc .fc-toolbar-title) {
  font-family: var(--font-heading);
  font-size: 1.375rem;
}

/* Только первая буква: `capitalize` поднимал бы и «г.» в «август 2026 г.». */
.event-calendar :deep(.fc .fc-toolbar-title)::first-letter {
  text-transform: uppercase;
}

.event-calendar :deep(.fc .fc-button) {
  background: var(--ui-bg-elevated);
  border-color: var(--ui-border);
  color: var(--ui-text);
  font-weight: 500;
  text-transform: none;
  box-shadow: none;
}

.event-calendar :deep(.fc .fc-button:hover) {
  background: var(--ui-bg-accented);
}

.event-calendar :deep(.fc .fc-button-primary:not(:disabled).fc-button-active) {
  background: var(--ui-primary);
  border-color: var(--ui-primary);
  color: white;
}

.event-calendar :deep(.fc .fc-button:focus-visible) {
  outline: 2px solid var(--ui-primary);
  outline-offset: 2px;
  box-shadow: none;
}

/*
 * Клетка по умолчанию — в высоту одной строки: две записи в дне уже
 * сворачивались в «ещё 1». Держим запас на три-четыре, чтобы месяц читался
 * расписанием, а не схемой.
 */
.event-calendar :deep(.fc .fc-daygrid-day-frame) {
  min-height: 7.5rem;
}

.event-calendar :deep(.fc .fc-daygrid-day-number) {
  padding: 0.375rem 0.5rem;
  font-size: 0.9375rem;
}

.event-calendar :deep(.fc .fc-col-header-cell-cushion) {
  padding: 0.625rem 0.25rem;
  font-weight: 600;
}

/*
 * Полоса часа в «Неделе» ростом в 1.5em обрезала подпись. Прибавляем ровно
 * настолько, чтобы строка помещалась: сутки и так разворачиваются целиком,
 * и лишние пиксели множатся на 24.
 */
.event-calendar :deep(.fc .fc-timegrid-slot) {
  height: 2.25rem;
}

.event-calendar :deep(.fc-event) {
  cursor: pointer;
  padding: 3px 6px;
  font-size: 1rem;
  line-height: 1.4;
}

.event-calendar :deep(.fc .fc-event-time) {
  font-weight: 500;
}

/*
 * Название переносится, а не обрезается многоточием: в клетке теперь есть
 * запас по высоте, и «Практика на рассвете» полезнее, чем «Практика на…».
 */
.event-calendar :deep(.fc .fc-event-title) {
  white-space: normal;
  overflow-wrap: break-word;
}

.event-calendar :deep(.fc .fc-daygrid-dot-event) {
  align-items: flex-start;
}

.event-calendar :deep(.fc .fc-daygrid-event-dot) {
  margin-top: 0.4em;
}

.event-calendar :deep(.fc .fc-daygrid-event) {
  margin-block: 2px;
}

.event-calendar :deep(.fc .fc-daygrid-more-link) {
  font-size: 0.875rem;
}

/* Заголовок панели на узком экране иначе уезжает за край. */
@media (max-width: 640px) {
  .event-calendar :deep(.fc .fc-toolbar) {
    flex-direction: column;
    gap: 0.75rem;
  }

  /* Клетка в семь раз уже — тот же запас по высоте превращает месяц
     в полотно из пустых строк. */
  .event-calendar :deep(.fc .fc-daygrid-day-frame) {
    min-height: 5rem;
  }

  /*
   * В колонку шириной в палец время съедало подпись целиком: в дне
   * оставалось «10:00» без названия. Час виден в «Неделе» и «Списком».
   */
  .event-calendar :deep(.fc-daygrid-event .fc-event-time) {
    display: none;
  }

  /* Перенос в колонке шириной в три буквы ставит слово в столбик —
     начало названия с многоточием читается лучше. */
  .event-calendar :deep(.fc .fc-daygrid-event .fc-event-title) {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
}
</style>
