<script setup>
import { ref, computed, onMounted } from 'vue'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { useToast } from 'vue-toastification'
import { PlayIcon, CheckIcon, XMarkIcon, ArrowPathIcon, MicrophoneIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  activityType: {
    type: String,
    default: 'multiple-choice',
    validator: (value) => ['multiple-choice', 'audio-recognition', 'speech-recognition', 'sentence-reconstruction', 'word-match'].includes(value)
  },
  onComplete: Function
})

const vocabularyStore = useVocabularyStore()
const toast = useToast()

// Activity state
const selectedAnswer = ref(null)
const isAnswered = ref(false)
const isCorrect = ref(false)
const audioPlaying = ref(false)
const attempts = ref(0)
const maxAttempts = 3

// Sentence reconstruction state
const shuffledWords = ref([])
const reconstructedSentence = ref([])
const draggedWord = ref(null)

// Word matching state
const matchingPairs = ref([])
const selectedWords = ref([])
const matchedPairs = ref([])

// Speech recognition state
const isListening = ref(false)
const spokenText = ref('')
const recognition = ref(null)
const speechSupported = ref(false)

// Sample sentences for reconstruction activity
const sampleSentences = {
  'Gato': 'El gato está durmiendo en la cama',
  'Perro': 'Mi perro corre en el parque',
  'Rojo': 'La rosa roja es muy bonita',
  'Azul': 'El cielo azul es hermoso hoy',
  'Casa': 'Mi casa tiene un jardín grande',
  'Agua': 'Necesito beber agua fresca',
  'Comida': 'La comida está muy deliciosa',
  'Libro': 'Leo un libro interesante cada noche'
}

const currentWord = computed(() => vocabularyStore.currentWord)

// Multiple choice activity
const multipleChoiceQuestion = computed(() => {
  if (!currentWord.value.word) return null
  
  const correct = currentWord.value.translation
  const options = generateOptions(correct)
  
  return {
    question: `What does "${currentWord.value.word}" mean?`,
    options,
    answer: correct
  }
})

// Audio recognition activity  
const audioRecognitionQuestion = computed(() => {
  if (!currentWord.value.word) return null
  
  const correct = currentWord.value.translation
  const options = generateOptions(correct)
  
  return {
    question: "Listen to the audio and select the correct translation:",
    options,
    answer: correct,
    audio: currentWord.value.audio
  }
})

// Sentence reconstruction activity
const sentenceReconstructionData = computed(() => {
  if (!currentWord.value.word) return null
  
  const sentence = sampleSentences[currentWord.value.word] || `${currentWord.value.word} means ${currentWord.value.translation}`
  const words = sentence.split(' ')
  
  return {
    originalSentence: sentence,
    words: words,
    hint: `Reconstruct the sentence containing "${currentWord.value.word}"`
  }
})

// Word matching activity
const wordMatchingData = computed(() => {
  const currentLevel = vocabularyStore.currentLevel
  if (!currentLevel || currentLevel.words.length < 2) return null
  
  const words = currentLevel.words.slice(0, 4) // Use up to 4 words for matching
  const pairs = words.map(word => ({
    id: word.id,
    foreign: word.word,
    translation: word.translation,
    matched: false
  }))
  
  return {
    pairs: pairs,
    question: "Match the foreign words with their translations:"
  }
})

function generateOptions(correctAnswer) {
  const allTranslations = vocabularyStore.levels.flatMap(level => 
    level.words.map(w => w.translation)
  ).filter(t => t !== correctAnswer)
  
  const wrongOptions = allTranslations
    .sort(() => 0.5 - Math.random())
    .slice(0, 3)
  
  return [correctAnswer, ...wrongOptions].sort(() => 0.5 - Math.random())
}

function checkMultipleChoice() {
  if (!selectedAnswer.value) return
  
  isAnswered.value = true
  isCorrect.value = selectedAnswer.value === multipleChoiceQuestion.value.answer
  attempts.value++
  
  if (isCorrect.value) {
    vocabularyStore.incrementMastery(currentWord.value.id)
    toast.success("🎉 Correct! Well done!")
    setTimeout(() => {
      props.onComplete?.(true)
    }, 1500)
  } else {
    toast.error("❌ Incorrect. Try again!")
    if (attempts.value >= maxAttempts) {
      toast.info(`💡 The correct answer is: ${multipleChoiceQuestion.value.answer}`)
      setTimeout(() => {
        props.onComplete?.(false)
      }, 2000)
    }
  }
}

function checkAudioRecognition() {
  if (!selectedAnswer.value) return
  
  isAnswered.value = true
  isCorrect.value = selectedAnswer.value === audioRecognitionQuestion.value.answer
  attempts.value++
  
  if (isCorrect.value) {
    vocabularyStore.incrementMastery(currentWord.value.id)
    toast.success("🎉 Excellent listening! Correct!")
    setTimeout(() => {
      props.onComplete?.(true)
    }, 1500)
  } else {
    toast.error("❌ Incorrect. Listen again!")
    if (attempts.value >= maxAttempts) {
      toast.info(`💡 The correct answer is: ${audioRecognitionQuestion.value.answer}`)
      setTimeout(() => {
        props.onComplete?.(false)
      }, 2000)
    }
  }
}

function playAudio() {
  let audioPath = null

  if (props.activityType === 'audio-recognition' && audioRecognitionQuestion.value?.audio) {
    audioPath = audioRecognitionQuestion.value.audio
  } else if (props.activityType === 'speech-recognition' && currentWord.value?.audio) {
    audioPath = currentWord.value.audio
  }

  if (!audioPath) {
    toast.error("Audio not available")
    return
  }

  audioPlaying.value = true
  const audio = new Audio(audioPath)

  audio.play().catch(() => {
    toast.error("Audio not available")
  }).finally(() => {
    audioPlaying.value = false
  })
}

function initializeSentenceReconstruction() {
  if (!sentenceReconstructionData.value) return
  
  shuffledWords.value = [...sentenceReconstructionData.value.words]
    .sort(() => 0.5 - Math.random())
  reconstructedSentence.value = []
}

function addWordToSentence(word, index) {
  reconstructedSentence.value.push(word)
  shuffledWords.value.splice(index, 1)
}

function removeWordFromSentence(index) {
  const word = reconstructedSentence.value[index]
  shuffledWords.value.push(word)
  reconstructedSentence.value.splice(index, 1)
}

function checkSentenceReconstruction() {
  const reconstructed = reconstructedSentence.value.join(' ')
  const original = sentenceReconstructionData.value.originalSentence
  
  isAnswered.value = true
  isCorrect.value = reconstructed === original
  attempts.value++
  
  if (isCorrect.value) {
    vocabularyStore.incrementMastery(currentWord.value.id)
    toast.success("🎉 Perfect sentence! Well done!")
    setTimeout(() => {
      props.onComplete?.(true)
    }, 1500)
  } else {
    toast.error("❌ Sentence order is incorrect. Try again!")
    if (attempts.value >= maxAttempts) {
      toast.info(`💡 Correct sentence: ${original}`)
      setTimeout(() => {
        props.onComplete?.(false)
      }, 2000)
    }
  }
}

function initializeWordMatching() {
  if (!wordMatchingData.value) return
  
  matchingPairs.value = [...wordMatchingData.value.pairs]
  selectedWords.value = []
  matchedPairs.value = []
}

function selectWordForMatching(word, type) {
  const selection = { word, type }
  
  if (selectedWords.value.length === 0) {
    selectedWords.value.push(selection)
  } else if (selectedWords.value.length === 1) {
    const firstSelection = selectedWords.value[0]
    
    // Check if it's a valid pair (different types, same word)
    if (firstSelection.type !== type && firstSelection.word.id === word.id) {
      // Correct match
      matchedPairs.value.push(word.id)
      selectedWords.value = []
      toast.success(`✅ Matched: ${word.foreign} = ${word.translation}`)
      
      // Check if all pairs are matched
      if (matchedPairs.value.length === matchingPairs.value.length) {
        vocabularyStore.incrementMastery(currentWord.value.id)
        toast.success("🎉 All words matched! Excellent!")
        setTimeout(() => {
          props.onComplete?.(true)
        }, 1500)
      }
    } else {
      // Wrong match
      selectedWords.value = [selection]
      toast.error("❌ Not a match. Try again!")
    }
  }
}

function isWordSelected(word, type) {
  return selectedWords.value.some(sel => sel.word.id === word.id && sel.type === type)
}

function isWordMatched(wordId) {
  return matchedPairs.value.includes(wordId)
}

// Speech Recognition Functions
function initializeSpeechRecognition() {
  speechSupported.value = 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window

  if (speechSupported.value) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
    recognition.value = new SpeechRecognition()

    recognition.value.continuous = false
    recognition.value.interimResults = false
    recognition.value.lang = 'es-ES' // Spanish recognition, can be made dynamic

    recognition.value.onstart = () => {
      isListening.value = true
    }

    recognition.value.onresult = (event) => {
      const result = event.results[0][0].transcript.toLowerCase().trim()
      spokenText.value = result
      checkSpeechRecognition(result)
    }

    recognition.value.onerror = (event) => {
      isListening.value = false
      toast.error('Speech recognition error. Please try again.')
    }

    recognition.value.onend = () => {
      isListening.value = false
    }
  }
}

function startSpeechRecognition() {
  if (!speechSupported.value) {
    toast.error('Speech recognition not supported in this browser')
    return
  }

  if (recognition.value) {
    spokenText.value = ''
    recognition.value.start()
  }
}

function checkSpeechRecognition(spokenWord) {
  const targetWord = currentWord.value.word.toLowerCase().trim()
  const similarity = calculateSimilarity(spokenWord, targetWord)

  isAnswered.value = true
  attempts.value++

  // Allow for some pronunciation variations - 70% similarity threshold
  if (similarity >= 0.7) {
    isCorrect.value = true
    vocabularyStore.incrementMastery(currentWord.value.id)
    toast.success(`🎉 Perfect pronunciation! You said: "${spokenWord}"`)
    setTimeout(() => {
      props.onComplete?.(true)
    }, 2000)
  } else {
    isCorrect.value = false
    toast.error(`❌ Try again! You said: "${spokenWord}", expected: "${targetWord}"`)
    if (attempts.value >= maxAttempts) {
      toast.info(`💡 The correct pronunciation is: ${targetWord}`)
      setTimeout(() => {
        props.onComplete?.(false)
      }, 2000)
    }
  }
}

function calculateSimilarity(str1, str2) {
  // Simple similarity calculation using Levenshtein distance
  const longer = str1.length > str2.length ? str1 : str2
  const shorter = str1.length > str2.length ? str2 : str1

  if (longer.length === 0) return 1.0

  const distance = levenshteinDistance(longer, shorter)
  return (longer.length - distance) / longer.length
}

function levenshteinDistance(str1, str2) {
  const matrix = []

  for (let i = 0; i <= str2.length; i++) {
    matrix[i] = [i]
  }

  for (let j = 0; j <= str1.length; j++) {
    matrix[0][j] = j
  }

  for (let i = 1; i <= str2.length; i++) {
    for (let j = 1; j <= str1.length; j++) {
      if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1]
      } else {
        matrix[i][j] = Math.min(
          matrix[i - 1][j - 1] + 1,
          matrix[i][j - 1] + 1,
          matrix[i - 1][j] + 1
        )
      }
    }
  }

  return matrix[str2.length][str1.length]
}

function resetActivity() {
  selectedAnswer.value = null
  isAnswered.value = false
  isCorrect.value = false
  attempts.value = 0
  spokenText.value = ''
  isListening.value = false

  // Reset specific activity data
  if (props.activityType === 'sentence-reconstruction') {
    initializeSentenceReconstruction()
  } else if (props.activityType === 'word-match') {
    initializeWordMatching()
  } else if (props.activityType === 'speech-recognition') {
    initializeSpeechRecognition()
  }
}

onMounted(() => {
  resetActivity()
  initializeSpeechRecognition()
})
</script>

<template>
  <div class="card bg-base-100 shadow-lg">
    <div class="card-body">
      <!-- Multiple Choice Activity -->
      <div v-if="activityType === 'multiple-choice' && multipleChoiceQuestion" class="space-y-4">
        <h3 class="text-xl font-bold text-primary">{{ multipleChoiceQuestion.question }}</h3>
        
        <div class="grid grid-cols-1 gap-3">
          <label 
            v-for="option in multipleChoiceQuestion.options" 
            :key="option"
            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-base-200 transition-colors"
            :class="{ 
              'border-success bg-success/10': isAnswered && option === multipleChoiceQuestion.answer,
              'border-error bg-error/10': isAnswered && option === selectedAnswer && option !== multipleChoiceQuestion.answer,
              'border-primary': !isAnswered && selectedAnswer === option 
            }"
          >
            <input 
              type="radio" 
              :value="option" 
              v-model="selectedAnswer" 
              class="radio radio-primary"
              :disabled="isAnswered"
            />
            <span class="text-lg">{{ option }}</span>
            <CheckIcon v-if="isAnswered && option === multipleChoiceQuestion.answer" class="w-5 h-5 text-success ml-auto" />
            <XMarkIcon v-if="isAnswered && option === selectedAnswer && option !== multipleChoiceQuestion.answer" class="w-5 h-5 text-error ml-auto" />
          </label>
        </div>

        <div class="flex gap-3">
          <button 
            @click="checkMultipleChoice" 
            :disabled="!selectedAnswer || isAnswered"
            class="btn btn-primary"
          >
            Check Answer
          </button>
          <button 
            v-if="isAnswered && !isCorrect && attempts < maxAttempts" 
            @click="resetActivity"
            class="btn btn-outline gap-2"
          >
            <ArrowPathIcon class="w-4 h-4" />
            Try Again
          </button>
        </div>
      </div>

      <!-- Audio Recognition Activity -->
      <div v-if="activityType === 'audio-recognition' && audioRecognitionQuestion" class="space-y-4">
        <h3 class="text-xl font-bold text-primary">{{ audioRecognitionQuestion.question }}</h3>

        <div class="flex justify-center">
          <button
            @click="playAudio"
            :disabled="audioPlaying"
            class="btn btn-circle btn-lg btn-primary"
          >
            <PlayIcon v-if="!audioPlaying" class="w-8 h-8" />
            <span v-else class="loading loading-spinner"></span>
          </button>
        </div>

        <div class="grid grid-cols-1 gap-3">
          <label
            v-for="option in audioRecognitionQuestion.options"
            :key="option"
            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-base-200 transition-colors"
            :class="{
              'border-success bg-success/10': isAnswered && option === audioRecognitionQuestion.answer,
              'border-error bg-error/10': isAnswered && option === selectedAnswer && option !== audioRecognitionQuestion.answer,
              'border-primary': !isAnswered && selectedAnswer === option
            }"
          >
            <input
              type="radio"
              :value="option"
              v-model="selectedAnswer"
              class="radio radio-primary"
              :disabled="isAnswered"
            />
            <span class="text-lg">{{ option }}</span>
            <CheckIcon v-if="isAnswered && option === audioRecognitionQuestion.answer" class="w-5 h-5 text-success ml-auto" />
            <XMarkIcon v-if="isAnswered && option === selectedAnswer && option !== audioRecognitionQuestion.answer" class="w-5 h-5 text-error ml-auto" />
          </label>
        </div>

        <div class="flex gap-3">
          <button
            @click="checkAudioRecognition"
            :disabled="!selectedAnswer || isAnswered"
            class="btn btn-primary"
          >
            Check Answer
          </button>
          <button
            v-if="isAnswered && !isCorrect && attempts < maxAttempts"
            @click="resetActivity"
            class="btn btn-outline gap-2"
          >
            <ArrowPathIcon class="w-4 h-4" />
            Try Again
          </button>
        </div>
      </div>

      <!-- Speech Recognition Activity -->
      <div v-if="activityType === 'speech-recognition'" class="space-y-4">
        <h3 class="text-xl font-bold text-primary">Practice Pronunciation</h3>
        <p class="text-base-content/70">Say the word out loud: <strong class="text-2xl text-primary">{{ currentWord.word }}</strong></p>

        <!-- Word Display -->
        <div class="text-center p-6 bg-primary/10 rounded-lg">
          <div class="text-4xl font-bold text-primary mb-2">{{ currentWord.word }}</div>
          <div class="text-lg text-base-content/70">{{ currentWord.translation }}</div>
          <div v-if="currentWord.example" class="text-sm text-base-content/60 italic mt-2">
            "{{ currentWord.example }}"
          </div>
        </div>

        <!-- Speech Recognition Controls -->
        <div class="text-center space-y-4">
          <button
            @click="startSpeechRecognition"
            :disabled="isListening || isAnswered"
            class="btn btn-circle btn-lg"
            :class="isListening ? 'btn-error animate-pulse' : 'btn-primary'"
          >
            <span v-if="isListening" class="text-2xl">🎤</span>
            <span v-else class="text-2xl">🎙️</span>
          </button>

          <div class="text-sm">
            <span v-if="isListening" class="text-error font-semibold">🔴 Listening... Speak now!</span>
            <span v-else-if="!speechSupported" class="text-warning">Speech recognition not supported</span>
            <span v-else class="text-base-content/70">Click the microphone and speak the word</span>
          </div>
        </div>

        <!-- Speech Results -->
        <div v-if="spokenText" class="text-center p-4 bg-base-200 rounded-lg">
          <p class="text-sm text-base-content/70">You said:</p>
          <p class="text-lg font-semibold">"{{ spokenText }}"</p>
        </div>

        <!-- Feedback -->
        <div v-if="isAnswered" class="text-center">
          <div v-if="isCorrect" class="alert alert-success">
            <CheckIcon class="w-6 h-6" />
            <span>Excellent pronunciation! 🎉</span>
          </div>
          <div v-else class="alert alert-error">
            <XMarkIcon class="w-6 h-6" />
            <span>Try again! Listen to the pronunciation and practice.</span>
          </div>
        </div>

        <!-- Audio Playback for Reference -->
        <div class="text-center">
          <button
            @click="playAudio"
            :disabled="audioPlaying"
            class="btn btn-outline btn-sm gap-2"
          >
            <PlayIcon class="w-4 h-4" />
            <span v-if="audioPlaying">Playing...</span>
            <span v-else>Listen to Pronunciation</span>
          </button>
        </div>

        <div class="flex gap-3 justify-center">
          <button
            v-if="isAnswered && !isCorrect && attempts < maxAttempts"
            @click="resetActivity"
            class="btn btn-outline gap-2"
          >
            <ArrowPathIcon class="w-4 h-4" />
            Try Again
          </button>
        </div>
      </div>

      <!-- Sentence Reconstruction Activity -->
      <div v-if="activityType === 'sentence-reconstruction' && sentenceReconstructionData" class="space-y-4">
        <h3 class="text-xl font-bold text-primary">Sentence Reconstruction</h3>
        <p class="text-base-content/70">{{ sentenceReconstructionData.hint }}</p>
        
        <!-- Reconstructed Sentence Area -->
        <div class="p-4 border-2 border-dashed border-primary/30 rounded-lg min-h-16 bg-primary/5">
          <p class="text-sm text-base-content/60 mb-2">Reconstructed sentence:</p>
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="(word, index) in reconstructedSentence" 
              :key="`reconstructed-${index}`"
              @click="removeWordFromSentence(index)"
              class="badge badge-primary cursor-pointer hover:badge-primary-focus text-lg p-3"
            >
              {{ word }}
            </span>
            <span v-if="reconstructedSentence.length === 0" class="text-base-content/40 italic">
              Click words below to build your sentence...
            </span>
          </div>
        </div>

        <!-- Available Words -->
        <div class="space-y-2">
          <p class="text-sm font-medium">Available words:</p>
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="(word, index) in shuffledWords" 
              :key="`shuffled-${index}`"
              @click="addWordToSentence(word, index)"
              class="badge badge-outline cursor-pointer hover:badge-primary text-lg p-3"
            >
              {{ word }}
            </span>
          </div>
        </div>

        <div class="flex gap-3">
          <button 
            @click="checkSentenceReconstruction" 
            :disabled="reconstructedSentence.length === 0 || isAnswered"
            class="btn btn-primary"
          >
            Check Sentence
          </button>
          <button 
            v-if="isAnswered && !isCorrect && attempts < maxAttempts" 
            @click="resetActivity"
            class="btn btn-outline gap-2"
          >
            <ArrowPathIcon class="w-4 h-4" />
            Try Again
          </button>
        </div>
      </div>

      <!-- Word Matching Activity -->
      <div v-if="activityType === 'word-match' && wordMatchingData" class="space-y-4">
        <h3 class="text-xl font-bold text-primary">{{ wordMatchingData.question }}</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Foreign Words Column -->
          <div class="space-y-3">
            <h4 class="font-semibold text-center">Foreign Words</h4>
            <div class="space-y-2">
              <button
                v-for="pair in matchingPairs"
                :key="`foreign-${pair.id}`"
                @click="selectWordForMatching(pair, 'foreign')"
                :disabled="isWordMatched(pair.id)"
                class="btn btn-outline w-full text-lg"
                :class="{
                  'btn-primary': isWordSelected(pair, 'foreign'),
                  'btn-success': isWordMatched(pair.id),
                  'btn-disabled': isWordMatched(pair.id)
                }"
              >
                {{ pair.foreign }}
              </button>
            </div>
          </div>

          <!-- Translation Column -->
          <div class="space-y-3">
            <h4 class="font-semibold text-center">Translations</h4>
            <div class="space-y-2">
              <button
                v-for="pair in matchingPairs"
                :key="`translation-${pair.id}`"
                @click="selectWordForMatching(pair, 'translation')"
                :disabled="isWordMatched(pair.id)"
                class="btn btn-outline w-full text-lg"
                :class="{
                  'btn-primary': isWordSelected(pair, 'translation'),
                  'btn-success': isWordMatched(pair.id),
                  'btn-disabled': isWordMatched(pair.id)
                }"
              >
                {{ pair.translation }}
              </button>
            </div>
          </div>
        </div>

        <div class="text-center">
          <p class="text-sm text-base-content/70">
            Matched: {{ matchedPairs.length }} / {{ matchingPairs.length }}
          </p>
        </div>
      </div>

      <!-- Progress indicator -->
      <div v-if="attempts > 0" class="text-center text-sm text-base-content/60">
        Attempt {{ attempts }} of {{ maxAttempts }}
      </div>
    </div>
  </div>
</template>
