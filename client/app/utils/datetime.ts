import { CalendarDate, Time } from '@internationalized/date'

/**
 * Мост между строками, которые ходят по сети, и объектами дат, которые
 * понимают компоненты Nuxt UI.
 *
 * Наружу всегда уходит строка без часового пояса: мероприятие начинается
 * в семь вечера по местному времени, а не в момент абсолютного времени,
 * и пересчёт в UTC сдвинул бы его при первом же переезде сервера.
 */

/** `2026-10-15` → объект даты. Пустая или битая строка — `undefined`. */
export function parseDate(value: string | null | undefined): CalendarDate | undefined {
  if (!value) return undefined

  const [year, month, day] = value.slice(0, 10).split('-').map(Number)

  if (!year || !month || !day) return undefined

  return new CalendarDate(year, month, day)
}

/** `18:30` → объект времени. */
export function parseTime(value: string | null | undefined): Time | undefined {
  if (!value) return undefined

  const [hour, minute] = value.split(':').map(Number)

  if (Number.isNaN(hour) || Number.isNaN(minute)) return undefined

  return new Time(hour, minute)
}

export function formatDateValue(date: CalendarDate | undefined | null): string {
  if (!date) return ''

  return [
    String(date.year).padStart(4, '0'),
    String(date.month).padStart(2, '0'),
    String(date.day).padStart(2, '0')
  ].join('-')
}

export function formatTimeValue(time: Time | undefined | null): string {
  if (!time) return ''

  return `${String(time.hour).padStart(2, '0')}:${String(time.minute).padStart(2, '0')}`
}

/** Разбирает `2026-10-15T18:30` на день и время. */
export function splitDateTime(value: string | null | undefined): { date: string, time: string } {
  if (!value) return { date: '', time: '' }

  const [date = '', time = ''] = value.split('T')

  return { date, time: time.slice(0, 5) }
}

/**
 * Собирает `2026-10-15T18:30`. Без даты возвращает пустую строку: время
 * без дня — не момент, и отправлять такое на сервер нечего.
 */
export function joinDateTime(date: string, time: string): string {
  if (!date) return ''

  return `${date}T${time || '00:00'}`
}

/** Подпись даты по-русски: «15 октября 2026». */
export function labelDate(value: string | null | undefined): string {
  if (!value) return ''

  return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}
