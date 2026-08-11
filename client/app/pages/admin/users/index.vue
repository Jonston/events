<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'admin',
  layout: 'cabinet',
  pageTitle: 'Пользователи'
})

const { users, total, query, pending, setQuery, toggleSort } = useUsersList()

// Поле поиска не дёргает API на каждую букву: запрос уходит,
// когда человек перестал печатать.
const search = ref(query.value.search)
watch(search, useDebounceFn((value: string) => setQuery({ search: value }), 350))
watch(() => query.value.search, (value) => {
  if (value !== search.value) search.value = value
})

const page = computed({
  get: () => query.value.page,
  set: (value: number) => setQuery({ page: value })
})
</script>

<template>
  <div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <UInput
        v-model="search"
        icon="i-lucide-search"
        placeholder="Имя, email или телефон"
        size="lg"
        class="w-full sm:w-80"
        :loading="pending"
      />

      <p class="text-sm text-muted">
        Всего: {{ total }}
      </p>
    </div>

    <UsersTable
      :users="users"
      :pending="pending"
      :sort="query.sort"
      :direction="query.direction"
      @sort="toggleSort"
    />

    <div
      v-if="total > query.perPage"
      class="mt-4 flex justify-end"
    >
      <UPagination
        v-model:page="page"
        :items-per-page="query.perPage"
        :total="total"
      />
    </div>
  </div>
</template>
