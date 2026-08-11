/**
 * Дата в ru-RU для интерфейса. `withTime` — когда важны часы и минуты
 * (время создания записи), иначе только день.
 */
export function formatDate(value: string | null | undefined, withTime = false): string {
  if (!value) return '—'

  return new Date(value).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    ...(withTime ? { hour: '2-digit' as const, minute: '2-digit' as const } : {})
  })
}

const dayMonth = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long' })
const dayMonthTime = new Intl.DateTimeFormat('ru-RU', {
  day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit'
})
const weekday = new Intl.DateTimeFormat('ru-RU', { weekday: 'long' })

/** «12 сентября» — как дату называют в разговоре, а не «12.09.2026». */
export function formatDay(value: string | null | undefined): string {
  return value ? dayMonth.format(new Date(value)) : ''
}

export function formatDayTime(value: string | null | undefined): string {
  return value ? dayMonthTime.format(new Date(value)) : ''
}

export function formatWeekday(value: string | null | undefined): string {
  return value ? weekday.format(new Date(value)) : ''
}

/**
 * Даты мероприятия одной строкой.
 *
 * У однодневного показываем время — человеку важно, к какому часу приходить.
 * У многодневного время только мешает: важен диапазон дней.
 */
export function formatRange(startsAt: string | null | undefined, endsAt?: string | null): string {
  if (!startsAt) return ''
  if (!endsAt) return formatDayTime(startsAt)

  return `${formatDay(startsAt)} — ${formatDay(endsAt)}`
}

/**
 * Русское склонение после числительного: 1 отзыв, 2 отзыва, 5 отзывов.
 *
 * Нужно везде, где рядом с числом стоит существительное: «3 отзывов»
 * на странице мастера читается как недоделка, а не как опечатка.
 */
export function plural(count: number, one: string, few: string, many: string): string {
  const rest = Math.abs(count) % 100

  if (rest > 10 && rest < 20) return many

  const last = rest % 10

  if (last === 1) return one
  if (last >= 2 && last <= 4) return few

  return many
}

export function initials(name: string): string {
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map(part => part.charAt(0).toUpperCase())
    .join('')
}
