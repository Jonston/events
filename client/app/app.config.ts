export default defineAppConfig({
  ui: {
    /*
     * Forest — основной цвет площадки, coral — акцент действия.
     *
     * Именно так, а не наоборот: коралловым помечается кнопка, ради которой
     * страница написана, и если раздать его целым секциям, у площадки станет
     * два бренда вместо одного. Кабинет живёт на той же паре — площадка одна,
     * и админка в чужих цветах читалась бы как другой продукт.
     */
    colors: {
      primary: 'forest',
      secondary: 'coral',
      success: 'emerald',
      warning: 'amber',
      error: 'rose',
      neutral: 'stone'
    },
    toaster: {
      defaultVariants: {
        position: 'bottom-right' as const
      }
    },
    /*
     * Размеры таблиц заданы здесь, а не в каждом экране: списки мастеров,
     * мероприятий и заявок должны выглядеть одинаково, а не повторять
     * одни и те же классы с расхождением в один шаг.
     *
     * Кегль строки — на ступень выше служебного: таблица здесь основное
     * содержимое экрана, а не подпись к нему. Шапка не жирная: колонок
     * шесть, и полужирный ряд поверх обычных строк перетягивает внимание
     * на себя вместо данных.
     */
    table: {
      slots: {
        th: 'px-3 py-2.5 text-base font-medium',
        td: 'px-3 py-2.5 text-base font-normal'
      }
    },
    navigationMenu: {
      slots: {
        link: 'font-normal text-base text-muted',
        label: 'text-md font-medium'
      },
      variants: {
        orientation: {
          vertical: {
            link: 'py-2'
          }
        }
      },
      compoundVariants: [
        {
          variant: 'pill',
          active: true,
          highlight: false,
          class: {
            link: 'before:bg-elevated/50'
          }
        }
      ]
    }
  }
})
