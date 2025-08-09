<script setup>
const props = defineProps({
  medal: String
})

const medalColor = {
  bronze: 'bg-amber-700',
  silver: 'bg-gray-400',
  gold: 'bg-yellow-400',
  platinum: 'bg-green-400',
  none: 'bg-gray-200 text-gray-600'
}
</script>

<template>
  <span
    class="px-2 py-1 rounded text-xs font-semibold text-white"
    :class="medalColor[medal]"
  >
    {{ medal.toUpperCase() }}
  </span>
</template>