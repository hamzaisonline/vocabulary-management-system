<script setup>
defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], required: true },
  description: { type: String, default: '' },
  icon: { type: [Object, Function], required: true },
  cardClass: { type: String, default: 'bg-base-100 text-base-content' },
})
</script>

<template>
  <div class="card min-w-0 shadow-md transition-shadow hover:shadow-lg" :class="cardClass">
    <div class="card-body min-h-28 min-w-0 p-5">
      <div class="flex min-w-0 items-center justify-between gap-4">
        <div class="min-w-0">
          <p class="truncate text-sm font-medium leading-5 opacity-90" :title="title">{{ title }}</p>
          <p class="mt-1 min-w-0 break-words text-3xl font-bold leading-none tracking-tight sm:text-4xl">{{ value }}</p>
          <p v-if="description" class="mt-1.5 truncate text-xs leading-4 opacity-65" :title="description">{{ description }}</p>
        </div>
        <span class="grid h-10 w-10 shrink-0 place-items-center">
          <component :is="icon" class="h-8 w-8" />
        </span>
      </div>
    </div>
  </div>
</template>
