<script setup>
import { ref, computed } from 'vue'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { PlusIcon, PencilIcon, TrashIcon, BookOpenIcon, PlayIcon, PauseIcon, ArrowUpTrayIcon, ArrowDownTrayIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

const vocabularyStore = useVocabularyStore()

// Local state
const searchQuery = ref('')
const showCreateLevelModal = ref(false)
const showEditLevelModal = ref(false)
const showAddWordModal = ref(false)
const showBulkImportModal = ref(false)
const selectedLevel = ref(null)
const selectedWord = ref(null)
const audioPlaying = ref(null)
const bulkVocabularyText = ref('')
const csvFile = ref(null)

// New level form
const newLevel = ref({
  title: '',
  description: '',
  difficulty: 'beginner'
})

// New word form
const newWord = ref({
  word: '',
  translation: '',
  audio: '',
  example: '',
  notes: ''
})

const filteredLevels = computed(() => {
  if (!searchQuery.value) return vocabularyStore.levels
  return vocabularyStore.levels.filter(level => 
    level.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    level.words.some(word => 
      word.word.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      word.translation.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  )
})

const totalWords = computed(() => {
  return vocabularyStore.levels.reduce((total, level) => total + level.words.length, 0)
})

const createLevel = () => {
  const newLevelData = {
    id: String(Date.now()),
    title: newLevel.value.title,
    description: newLevel.value.description,
    difficulty: newLevel.value.difficulty,
    words: []
  }
  
  vocabularyStore.levels.push(newLevelData)
  
  // Reset form
  newLevel.value = {
    title: '',
    description: '',
    difficulty: 'beginner'
  }
  
  showCreateLevelModal.value = false
}

const editLevel = (level) => {
  selectedLevel.value = { ...level }
  showEditLevelModal.value = true
}

const updateLevel = () => {
  const index = vocabularyStore.levels.findIndex(level => level.id === selectedLevel.value.id)
  if (index !== -1) {
    vocabularyStore.levels[index] = { ...selectedLevel.value }
  }
  showEditLevelModal.value = false
  selectedLevel.value = null
}

const deleteLevel = (levelId) => {
  if (confirm('Are you sure you want to delete this level? All words in this level will be lost.')) {
    const index = vocabularyStore.levels.findIndex(level => level.id === levelId)
    if (index !== -1) {
      vocabularyStore.levels.splice(index, 1)
    }
  }
}

const openAddWordModal = (level) => {
  selectedLevel.value = level
  newWord.value = {
    word: '',
    translation: '',
    audio: '',
    example: '',
    notes: ''
  }
  showAddWordModal.value = true
}

const addWordToLevel = () => {
  const newWordData = {
    id: Date.now(),
    word: newWord.value.word,
    translation: newWord.value.translation,
    audio: newWord.value.audio || '/audio/default.mp3',
    example: newWord.value.example,
    notes: newWord.value.notes,
    mastery: 0
  }
  
  selectedLevel.value.words.push(newWordData)
  
  // Update the level in the store
  const levelIndex = vocabularyStore.levels.findIndex(level => level.id === selectedLevel.value.id)
  if (levelIndex !== -1) {
    vocabularyStore.levels[levelIndex] = selectedLevel.value
  }
  
  showAddWordModal.value = false
}

const deleteWord = (levelId, wordId) => {
  if (confirm('Are you sure you want to delete this word?')) {
    const level = vocabularyStore.levels.find(level => level.id === levelId)
    if (level) {
      level.words = level.words.filter(word => word.id !== wordId)
    }
  }
}

const playAudio = (audioPath, wordId) => {
  if (audioPlaying.value === wordId) {
    // Stop audio
    audioPlaying.value = null
    return
  }
  
  // Play audio
  audioPlaying.value = wordId
  const audio = new Audio(audioPath)
  audio.play().catch(() => {
    // Audio failed to play
    console.log('Audio playback failed')
  }).finally(() => {
    audioPlaying.value = null
  })
}

const getDifficultyColor = (difficulty) => {
  switch (difficulty) {
    case 'beginner': return 'badge-success'
    case 'intermediate': return 'badge-warning'
    case 'advanced': return 'badge-error'
    default: return 'badge-neutral'
  }
}

const getWordsCount = (level) => {
  return level.words.length
}

const getCompletionRate = (level) => {
  if (level.words.length === 0) return 0
  const completedWords = level.words.filter(word => word.mastery >= 100).length
  return Math.round((completedWords / level.words.length) * 100)
}

// Bulk import functions
const csvTemplate = 'Word,Translation,Example,Notes\nGato,Cat,El gato está durmiendo,A common pet\nPerro,Dog,Mi perro corre,Man\'s best friend'

const processBulkImport = () => {
  if (!selectedLevel.value) {
    alert('Please select a level first')
    return
  }

  let wordsData = []

  try {
    if (csvFile.value) {
      // Process CSV file
      const reader = new FileReader()
      reader.onload = (e) => {
        const csvText = e.target.result
        wordsData = parseCSV(csvText)
        addBulkWords(wordsData)
      }
      reader.readAsText(csvFile.value)
    } else if (bulkVocabularyText.value) {
      // Process manual text input
      const lines = bulkVocabularyText.value.trim().split('\n')
      wordsData = lines.map(line => {
        const [word, translation, example, notes] = line.split(',').map(s => s?.trim())
        return { word, translation, example: example || '', notes: notes || '' }
      }).filter(wordData => wordData.word && wordData.translation)

      addBulkWords(wordsData)
    }
  } catch (error) {
    console.error('Error processing vocabulary data:', error)
    alert('Error processing vocabulary data. Please check the format.')
  }
}

const parseCSV = (csvText) => {
  const lines = csvText.trim().split('\n')
  const headers = lines[0].split(',').map(h => h.trim().toLowerCase())

  return lines.slice(1).map(line => {
    const values = line.split(',').map(v => v?.trim())
    const wordData = {}

    headers.forEach((header, index) => {
      if (header === 'word') wordData.word = values[index]
      if (header === 'translation') wordData.translation = values[index]
      if (header === 'example') wordData.example = values[index] || ''
      if (header === 'notes') wordData.notes = values[index] || ''
    })

    return wordData
  }).filter(wordData => wordData.word && wordData.translation)
}

const addBulkWords = (wordsData) => {
  if (wordsData.length === 0) {
    alert('No valid vocabulary data found')
    return
  }

  wordsData.forEach(wordData => {
    const newWordData = {
      id: Date.now() + Math.random(),
      word: wordData.word,
      translation: wordData.translation,
      audio: `/audio/${wordData.word.toLowerCase()}.mp3`,
      example: wordData.example,
      notes: wordData.notes,
      mastery: 0
    }

    selectedLevel.value.words.push(newWordData)
  })

  // Update the level in the store
  const levelIndex = vocabularyStore.levels.findIndex(level => level.id === selectedLevel.value.id)
  if (levelIndex !== -1) {
    vocabularyStore.levels[levelIndex] = selectedLevel.value
  }

  showBulkImportModal.value = false
  bulkVocabularyText.value = ''
  csvFile.value = null

  alert(`Successfully added ${wordsData.length} words to ${selectedLevel.value.title}!`)
}

const openBulkImportModal = (level) => {
  selectedLevel.value = level
  showBulkImportModal.value = true
}

const downloadVocabularyTemplate = () => {
  const blob = new Blob([csvTemplate], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'vocabulary-template.csv'
  a.click()
  window.URL.revokeObjectURL(url)
}

const exportLevelVocabulary = (level) => {
  const csvContent = [
    'Word,Translation,Example,Notes',
    ...level.words.map(word =>
      `${word.word},${word.translation},${word.example || ''},${word.notes || ''}`
    )
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${level.title}-vocabulary.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

const goToDashboard = () => {
  router.push('/teacher')
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-primary">Vocabulary Management</h1>
        <p class="text-base-content/70 mt-1">Create and manage vocabulary levels for your students</p>
      </div>
      <div class="flex gap-2">
        <button @click="goToDashboard" class="btn btn-ghost gap-2">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Dashboard
        </button>
        <button
          @click="downloadVocabularyTemplate"
          class="btn btn-outline gap-2"
        >
          <ArrowDownTrayIcon class="w-5 h-5" />
          Download Template
        </button>
        <button
          @click="showCreateLevelModal = true"
          class="btn btn-primary gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Create New Level
        </button>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary">
          <BookOpenIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Levels</div>
        <div class="stat-value text-primary">{{ vocabularyStore.levels.length }}</div>
        <div class="stat-desc">Vocabulary levels created</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary">
          <BookOpenIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Total Words</div>
        <div class="stat-value text-secondary">{{ totalWords }}</div>
        <div class="stat-desc">Across all levels</div>
      </div>

      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-accent">
          <BookOpenIcon class="w-8 h-8" />
        </div>
        <div class="stat-title">Average Level Size</div>
        <div class="stat-value text-accent">{{ vocabularyStore.levels.length ? Math.round(totalWords / vocabularyStore.levels.length) : 0 }}</div>
        <div class="stat-desc">Words per level</div>
      </div>
    </div>

    <!-- Search -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <div class="form-control w-full max-w-md">
          <label class="label">
            <span class="label-text">Search Vocabulary</span>
          </label>
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search levels or words..." 
            class="input input-bordered w-full" 
          />
        </div>
      </div>
    </div>

    <!-- Vocabulary Levels -->
    <div class="space-y-6">
      <div 
        v-for="level in filteredLevels" 
        :key="level.id"
        class="card bg-base-100 shadow-md"
      >
        <div class="card-body">
          <!-- Level Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <h3 class="card-title">{{ level.title }}</h3>
              <div class="badge" :class="getDifficultyColor(level.difficulty || 'beginner')">
                {{ level.difficulty || 'beginner' }}
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <span class="text-sm text-base-content/70">{{ getWordsCount(level) }} words</span>
              <button
                @click="openAddWordModal(level)"
                class="btn btn-ghost btn-sm gap-1"
              >
                <PlusIcon class="w-4 h-4" />
                Add Word
              </button>
              <button
                @click="openBulkImportModal(level)"
                class="btn btn-ghost btn-sm gap-1"
              >
                <ArrowUpTrayIcon class="w-4 h-4" />
                Bulk Import
              </button>
              <button
                @click="exportLevelVocabulary(level)"
                class="btn btn-ghost btn-sm gap-1"
              >
                <ArrowDownTrayIcon class="w-4 h-4" />
                Export
              </button>
              <button
                @click="editLevel(level)"
                class="btn btn-ghost btn-sm gap-1"
              >
                <PencilIcon class="w-4 h-4" />
              </button>
              <button
                @click="deleteLevel(level.id)"
                class="btn btn-error btn-ghost btn-sm gap-1"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Level Description -->
          <p v-if="level.description" class="text-base-content/70 mb-4">{{ level.description }}</p>

          <!-- Progress -->
          <div class="flex items-center gap-3 mb-4">
            <span class="text-sm font-medium">Student Completion:</span>
            <progress 
              class="progress progress-primary flex-1" 
              :value="getCompletionRate(level)" 
              max="100"
            ></progress>
            <span class="text-sm font-bold">{{ getCompletionRate(level) }}%</span>
          </div>

          <!-- Words List -->
          <div v-if="level.words.length > 0" class="space-y-2">
            <h4 class="font-semibold text-sm text-base-content/80 mb-3">Words in this level:</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              <div 
                v-for="word in level.words" 
                :key="word.id"
                class="flex items-center justify-between p-3 bg-base-200 rounded-lg"
              >
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold">{{ word.word }}</span>
                    <button 
                      @click="playAudio(word.audio, word.id)"
                      class="btn btn-ghost btn-xs"
                    >
                      <PlayIcon v-if="audioPlaying !== word.id" class="w-3 h-3" />
                      <PauseIcon v-else class="w-3 h-3" />
                    </button>
                  </div>
                  <p class="text-sm text-base-content/70">{{ word.translation }}</p>
                  <p v-if="word.example" class="text-xs text-base-content/60 italic">{{ word.example }}</p>
                </div>
                
                <button 
                  @click="deleteWord(level.id, word.id)"
                  class="btn btn-error btn-ghost btn-xs"
                >
                  <TrashIcon class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>

          <!-- Empty State for Words -->
          <div v-else class="text-center py-8 bg-base-200 rounded-lg">
            <BookOpenIcon class="w-12 h-12 mx-auto text-base-content/30 mb-3" />
            <p class="text-base-content/70 mb-3">No words in this level yet</p>
            <button 
              @click="openAddWordModal(level)"
              class="btn btn-primary btn-sm gap-2"
            >
              <PlusIcon class="w-4 h-4" />
              Add First Word
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State for Levels -->
    <div v-if="filteredLevels.length === 0" class="text-center py-12">
      <BookOpenIcon class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
      <h3 class="text-lg font-semibold mb-2">No vocabulary levels found</h3>
      <p class="text-base-content/70 mb-6">
        {{ searchQuery ? 'Try adjusting your search terms' : 'Create your first vocabulary level to get started' }}
      </p>
      <button 
        v-if="!searchQuery"
        @click="showCreateLevelModal = true"
        class="btn btn-primary gap-2"
      >
        <PlusIcon class="w-5 h-5" />
        Create Your First Level
      </button>
    </div>

    <!-- Create Level Modal -->
    <dialog :class="{ 'modal modal-open': showCreateLevelModal }" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Create New Vocabulary Level</h3>
        
        <form @submit.prevent="createLevel" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Level Title *</span>
            </label>
            <input 
              v-model="newLevel.title"
              type="text" 
              placeholder="e.g., Animals, Food, Travel" 
              class="input input-bordered" 
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
            </label>
            <textarea 
              v-model="newLevel.description"
              class="textarea textarea-bordered" 
              placeholder="Describe what students will learn in this level"
              rows="2"
            ></textarea>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Difficulty Level</span>
            </label>
            <select v-model="newLevel.difficulty" class="select select-bordered">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>

          <div class="modal-action">
            <button type="button" @click="showCreateLevelModal = false" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Create Level
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showCreateLevelModal = false">close</button>
      </form>
    </dialog>

    <!-- Edit Level Modal -->
    <dialog :class="{ 'modal modal-open': showEditLevelModal }" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Edit Vocabulary Level</h3>
        
        <form @submit.prevent="updateLevel" class="space-y-4" v-if="selectedLevel">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Level Title *</span>
            </label>
            <input 
              v-model="selectedLevel.title"
              type="text" 
              placeholder="e.g., Animals, Food, Travel" 
              class="input input-bordered" 
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
            </label>
            <textarea 
              v-model="selectedLevel.description"
              class="textarea textarea-bordered" 
              placeholder="Describe what students will learn in this level"
              rows="2"
            ></textarea>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Difficulty Level</span>
            </label>
            <select v-model="selectedLevel.difficulty" class="select select-bordered">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>

          <div class="modal-action">
            <button type="button" @click="showEditLevelModal = false" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Update Level
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showEditLevelModal = false">close</button>
      </form>
    </dialog>

    <!-- Add Word Modal -->
    <dialog :class="{ 'modal modal-open': showAddWordModal }" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Add New Word</h3>
        <p class="text-sm text-base-content/70 mb-4">Adding to: <strong>{{ selectedLevel?.title }}</strong></p>

        <form @submit.prevent="addWordToLevel" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control">
              <label class="label">
                <span class="label-text">Word *</span>
              </label>
              <input
                v-model="newWord.word"
                type="text"
                placeholder="e.g., Gato"
                class="input input-bordered"
                required
              />
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Translation *</span>
              </label>
              <input
                v-model="newWord.translation"
                type="text"
                placeholder="e.g., Cat"
                class="input input-bordered"
                required
              />
            </div>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Audio File Path</span>
            </label>
            <input
              v-model="newWord.audio"
              type="text"
              placeholder="e.g., /audio/gato.mp3"
              class="input input-bordered"
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Example Sentence</span>
            </label>
            <input
              v-model="newWord.example"
              type="text"
              placeholder="e.g., El gato está durmiendo"
              class="input input-bordered"
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Notes</span>
            </label>
            <textarea
              v-model="newWord.notes"
              class="textarea textarea-bordered"
              placeholder="Additional notes for students"
              rows="2"
            ></textarea>
          </div>

          <div class="modal-action">
            <button type="button" @click="showAddWordModal = false" class="btn btn-ghost">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Add Word
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showAddWordModal = false">close</button>
      </form>
    </dialog>

    <!-- Bulk Import Modal -->
    <dialog :class="{ 'modal modal-open': showBulkImportModal }" class="modal">
      <div class="modal-box w-11/12 max-w-4xl">
        <h3 class="font-bold text-lg mb-4">Bulk Import Vocabulary</h3>
        <p class="text-sm text-base-content/70 mb-4">Adding to: <strong>{{ selectedLevel?.title }}</strong></p>

        <div class="tabs tabs-boxed mb-4">
          <a class="tab tab-active" onclick="document.getElementById('manual-vocab-tab').style.display='block'; document.getElementById('csv-vocab-tab').style.display='none'">Manual Entry</a>
          <a class="tab" onclick="document.getElementById('csv-vocab-tab').style.display='block'; document.getElementById('manual-vocab-tab').style.display='none'">CSV Upload</a>
        </div>

        <!-- Manual Entry Tab -->
        <div id="manual-vocab-tab">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Enter vocabulary data (Word, Translation, Example, Notes per line)</span>
            </label>
            <textarea
              v-model="bulkVocabularyText"
              class="textarea textarea-bordered h-32"
              placeholder="Gato, Cat, El gato está durmiendo, A common pet&#10;Perro, Dog, Mi perro corre, Man's best friend&#10;Casa, House, Mi casa es grande, Place where we live"
            ></textarea>
            <label class="label">
              <span class="label-text-alt">Format: Word, Translation, Example, Notes (one per line)</span>
            </label>
          </div>
        </div>

        <!-- CSV Upload Tab -->
        <div id="csv-vocab-tab" style="display: none;">
          <div class="space-y-4">
            <div class="alert alert-info">
              <span>Upload a CSV file with Word, Translation, Example, and Notes columns. Download the template below if needed.</span>
            </div>

            <button @click="downloadVocabularyTemplate" class="btn btn-outline btn-sm gap-2">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Download Template
            </button>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Select CSV File</span>
              </label>
              <input
                type="file"
                accept=".csv"
                @change="csvFile = $event.target.files[0]"
                class="file-input file-input-bordered"
              />
            </div>
          </div>
        </div>

        <div class="modal-action">
          <button @click="showBulkImportModal = false" class="btn btn-ghost">
            Cancel
          </button>
          <button @click="processBulkImport" class="btn btn-primary">
            Import Vocabulary
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showBulkImportModal = false">close</button>
      </form>
    </dialog>
  </div>
</template>
