<template>
  <section class="on-dark relative overflow-hidden bg-forest-800 text-forest-100">
    <BotanicalMark
      class="pointer-events-none absolute -right-12 -top-20 h-72 w-72 text-forest-600/40"
      variant="branch"
    />

    <div class="relative mx-auto flex max-w-page flex-col gap-5 px-6 py-10 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="font-display text-3xl text-white">
          Не знаете, с чего начать?
        </h2>
        <p class="mt-2 max-w-md text-forest-200">
          Посмотрите ближайшие даты — у каждого мероприятия открыта цена,
          размер предоплаты и условия возврата.
        </p>
      </div>

      <UButton
        to="/events"
        color="secondary"
        size="xl"
        trailing-icon="i-lucide-arrow-right"
        class="shrink-0 rounded-full"
      >
        Смотреть мероприятия
      </UButton>
    </div>
  </section>
</template>
