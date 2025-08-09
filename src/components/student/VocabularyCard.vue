<script setup>
import { ref, watch } from "vue"
import { useVocabularyStore } from "@/stores/vocabularyStore"
import { useToast } from "vue-toastification"

const emit = defineEmits(['onPronounceSuccess']) // Emit success signal

const toast = useToast()
const vocabularyStore = useVocabularyStore()

const isSpeaking = ref(false)
const matchScore = ref(null)
const spokenText = ref("")
const matched = ref(false)

// Reset on word change
watch(
  () => vocabularyStore.currentWord,
  () => {
    matchScore.value = null
    spokenText.value = ""
    matched.value = false
  },
  { immediate: true }
)

const playAudio = () => {
  const word = vocabularyStore.currentWord
  if (word?.audio) {
    const audio = new Audio(word.audio)
    audio
      .play()
      .catch((e) => {
        toast.error("Please interact with the page to enable audio.")
        console.warn(e)
      })
  }
}

const similarityScore = (a, b) => {
  const len = Math.max(a.length, b.length)
  let matches = 0
  for (let i = 0; i < Math.min(a.length, b.length); i++) {
    if (a[i] === b[i]) matches++
  }
  return matches / len
}

const recordAndCheck = (correctWord) => {
  const SpeechRecognition =
    window.SpeechRecognition || window.webkitSpeechRecognition
  if (!SpeechRecognition) {
    toast.error("Speech recognition not supported in this browser.")
    return
  }

  const recognition = new SpeechRecognition()
  recognition.lang = "es-ES"
  recognition.interimResults = false
  recognition.maxAlternatives = 1

  isSpeaking.value = true
  recognition.start()

  recognition.onresult = (event) => {
    isSpeaking.value = false
    const spoken = event.results[0][0].transcript.trim().toLowerCase()
    const target = correctWord.toLowerCase()
    const score = similarityScore(spoken, target)

    spokenText.value = spoken
    matchScore.value = score
    matched.value = score >= 0.8

    if (matched.value) {
      toast.success(`✅ Pronunciation matched! (${score.toFixed(2)})`)
      emit("onPronounceSuccess")

      // Optional: play celebration sound
      const audio = new Audio("/sounds/success.mp3")
      audio.play()
    } else {
      toast.warning(`❌ Try again — heard: "${spoken}" (Score: ${score.toFixed(2)})`)
    }
  }

  recognition.onerror = (event) => {
    isSpeaking.value = false
    toast.error(`Error: ${event.error}`)
  }
}
</script>

<template>
  <div class="card bg-base-100 p-6 shadow space-y-4">
    <h2 class="text-xl font-semibold">
      Word: {{ vocabularyStore.currentWord.word }}
    </h2>

    <p class="text-gray-500 flex items-center gap-2">
      Translation: {{ vocabularyStore.currentWord.translation }}
      <button @click="playAudio" class="btn btn-sm btn-outline">🔊 Play</button>
    </p>

    <button
      class="btn btn-secondary"
      @click="recordAndCheck(vocabularyStore.currentWord.word)"
    >
      🎤 Pronounce this word
    </button>

    <div v-if="spokenText" class="text-sm mt-2">
      You said: <strong>{{ spokenText }}</strong><br />
      Score: {{ (matchScore * 100).toFixed(0) }}%
    </div>

    <div v-if="matched" class="text-green-600 font-semibold">
      ✅ Good job! You can proceed.
    </div>
  </div>
</template>
