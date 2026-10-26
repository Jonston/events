/**
 * Кнопки листания — одни на все карусели и на лайтбокс.
 *
 * Раньше каждая лента описывала стрелки сама, и они разъезжались: в галерее
 * мероприятия — тонкая стрелка на белом кружке, в лайтбоксе — шеврон на
 * плотном. Один и тот же жест выглядел двумя разными, поэтому вид кнопки
 * лежит здесь, а не в разметке.
 */

/** Скруглённый квадрат, а не круг: кружок спорил с углами карточек и кадров. */
export const SHAPE = 'rounded-lg'

const arrow = { color: 'neutral', variant: 'soft', size: 'xl', class: SHAPE } as const

/** Нейтральная — поверх снимков, где цвет отвлекал бы от кадра. */
export const carouselArrow = arrow

/**
 * Обычная кнопка площадки — в подборках, где стрелка стоит на поле страницы,
 * а не на снимке. Заливка, наведение и выключенное состояние достаются
 * от темы: своя раскраска здесь означала бы, что на сайте два разных
 * зелёных, отличающихся на глаз.
 */
const quietArrow = { ...arrow, color: 'primary', variant: 'solid', class: `${SHAPE} size-11` } as const

/**
 * То же самое для `UCarousel`, который рисует стрелки сам. Форму приходится
 * повторять и в `ui`: свою `rounded-full` он ставит там, и снаружи её нечем
 * перебить — два разных набора классов не сливаются, спорит порядок правил.
 */
export const carouselArrows = {
  prevIcon: 'i-lucide-chevron-left',
  nextIcon: 'i-lucide-chevron-right',
  prev: arrow,
  next: arrow
} as const

export const carouselArrowsQuiet = {
  ...carouselArrows,
  prev: quietArrow,
  next: quietArrow
} as const
