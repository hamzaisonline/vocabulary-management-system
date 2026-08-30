<script setup>
import { computed, onMounted, ref, shallowRef } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/authStore'
import { useVocabularyStore } from '@/stores/vocabularyStore'
import { ArrowLeftIcon, BookOpenIcon, PencilIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const vocabularyStore = useVocabularyStore()

const searchQuery = ref('')
const expandedLevelId = ref(null)
const showLevelModal = ref(false)
const showWordModal = ref(false)
const showImportModal = ref(false)
const editingLevelId = ref(null)
const editingWordId = ref(null)
const selectedAudioFile = shallowRef(null)
const importLevel = ref(null)
const importFile = shallowRef(null)
const importSummary = ref(null)

const levelForm = ref({ title: '', description: '', difficulty: 'beginner' })
const wordForm = ref({ word: '', translation: '', example: '', notes: '', audio_url: '' })

const canManage = computed(() => ['admin', 'teacher'].includes(authStore.role))
const filteredLevels = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return vocabularyStore.levels
  return vocabularyStore.levels.filter((level) =>
    level.title?.toLowerCase().includes(query) ||
    level.description?.toLowerCase().includes(query) ||
    (level.words || []).some((word) =>
      word.word?.toLowerCase().includes(query) || word.translation?.toLowerCase().includes(query)
    )
  )
})
const totalWords = computed(() => vocabularyStore.levels.reduce(
  (total, level) => total + (level.word_count ?? level.words?.length ?? 0), 0
))

const notifyError = (error, fallback) => {
  const status = error?.response?.status
  const validationMessage = Object.values(error?.response?.data?.errors || {}).flat()[0]
  const message = validationMessage || error?.response?.data?.message || vocabularyStore.error || fallback
  if (status === 403) toast.error('You do not have permission to manage vocabulary.')
  else if (status === 404) toast.error('Vocabulary item not found.')
  else if (status === 422) toast.error(message || 'Please check the submitted fields.')
  else toast.error(message)
}

const loadLevels = async () => {
  try {
    await vocabularyStore.fetchLevels()
  } catch (error) {
    notifyError(error, 'Failed to load vocabulary levels.')
  }
}

const toggleLevel = async (level) => {
  if (String(expandedLevelId.value) === String(level.id)) {
    expandedLevelId.value = null
    return
  }
  try {
    await vocabularyStore.fetchLevel(level.id)
    expandedLevelId.value = level.id
  } catch (error) {
    notifyError(error, 'Failed to open vocabulary level.')
  }
}

const openCreateLevel = () => {
  editingLevelId.value = null
  levelForm.value = { title: '', description: '', difficulty: 'beginner' }
  showLevelModal.value = true
}

const openEditLevel = (level) => {
  editingLevelId.value = level.id
  levelForm.value = {
    title: level.title || '',
    description: level.description || '',
    difficulty: level.difficulty || 'beginner',
  }
  showLevelModal.value = true
}

const saveLevel = async () => {
  if (!levelForm.value.title.trim()) return toast.error('Level title is required.')
  const payload = {
    title: levelForm.value.title.trim(),
    description: levelForm.value.description.trim() || null,
    difficulty: levelForm.value.difficulty || null,
  }
  try {
    if (editingLevelId.value) {
      await vocabularyStore.updateLevel(editingLevelId.value, payload)
      toast.success('Vocabulary level updated.')
    } else {
      await vocabularyStore.createLevel(payload)
      toast.success('Vocabulary level created.')
    }
    showLevelModal.value = false
  } catch (error) {
    notifyError(error, 'Failed to save vocabulary level.')
  }
}

const removeLevel = async (level) => {
  if (!confirm(`Delete “${level.title}” and all of its words?`)) return
  try {
    await vocabularyStore.deleteLevel(level.id)
    toast.success('Vocabulary level deleted.')
  } catch (error) {
    notifyError(error, 'Failed to delete vocabulary level.')
  }
}

const openCreateWord = async (level) => {
  if (!Array.isArray(level.words) || !level.words.length) {
    try { await vocabularyStore.fetchLevel(level.id) } catch (error) {
      return notifyError(error, 'Failed to open vocabulary level.')
    }
  }
  expandedLevelId.value = level.id
  editingWordId.value = null
  wordForm.value = { word: '', translation: '', example: '', notes: '', audio_url: '' }
  selectedAudioFile.value = null
  showWordModal.value = true
}

const openEditWord = (word) => {
  editingWordId.value = word.id
  wordForm.value = {
    word: word.word || '',
    translation: word.translation || '',
    example: word.example || '',
    notes: word.notes || '',
    audio_url: word.audio_url || '',
  }
  selectedAudioFile.value = null
  showWordModal.value = true
}

const selectAudio = (event) => {
  const file = event.target.files?.[0] || null
  if (!file) {
    selectedAudioFile.value = null
    return
  }
  const allowedExtensions = ['mp3', 'wav', 'm4a', 'ogg']
  const extension = file.name.split('.').pop()?.toLowerCase()
  if (!allowedExtensions.includes(extension)) {
    event.target.value = ''
    selectedAudioFile.value = null
    return toast.error('Choose an MP3, WAV, M4A, or OGG audio file.')
  }
  if (file.size > 10 * 1024 * 1024) {
    event.target.value = ''
    selectedAudioFile.value = null
    return toast.error('Audio files must be 10 MB or smaller.')
  }
  if (!(file instanceof File)) {
    event.target.value = ''
    selectedAudioFile.value = null
    return toast.error('The selected audio could not be read as a file.')
  }
  selectedAudioFile.value = file
}

const saveWord = async () => {
  if (!wordForm.value.word.trim() || !wordForm.value.translation.trim()) {
    return toast.error('Word and translation are required.')
  }
  const payload = {
    word: wordForm.value.word.trim(),
    translation: wordForm.value.translation.trim(),
    example: wordForm.value.example.trim() || null,
    notes: wordForm.value.notes.trim() || null,
    audio: selectedAudioFile.value,
  }
  try {
    if (editingWordId.value) {
      await vocabularyStore.updateWord(editingWordId.value, payload)
      toast.success('Vocabulary word updated.')
    } else {
      await vocabularyStore.createWord(expandedLevelId.value, payload)
      toast.success('Vocabulary word added.')
    }
    showWordModal.value = false
  } catch (error) {
    notifyError(error, 'Failed to save vocabulary word.')
  }
}

const removeWord = async (word) => {
  if (!confirm(`Delete “${word.word}”?`)) return
  try {
    await vocabularyStore.deleteWord(word.id)
    toast.success('Vocabulary word deleted.')
  } catch (error) {
    notifyError(error, 'Failed to delete vocabulary word.')
  }
}

const openImport = (level) => {
  importLevel.value = level
  importFile.value = null
  importSummary.value = null
  showImportModal.value = true
}

const closeImport = () => {
  showImportModal.value = false
  importLevel.value = null
  importFile.value = null
  importSummary.value = null
}

const selectImportFile = (event) => {
  const file = event.target.files?.[0] ?? null
  if (!file) {
    importFile.value = null
    return
  }
  if (!['csv', 'txt'].includes(file.name.split('.').pop()?.toLowerCase())) {
    event.target.value = ''
    importFile.value = null
    return toast.error('Choose a CSV file.')
  }
  if (file.size > 5 * 1024 * 1024) {
    event.target.value = ''
    importFile.value = null
    return toast.error('CSV files must be 5 MB or smaller.')
  }
  importFile.value = file
  importSummary.value = null
}

const runImport = async () => {
  if (!importLevel.value || !(importFile.value instanceof File)) {
    return toast.error('Choose a CSV file to import.')
  }
  try {
    importSummary.value = await vocabularyStore.importWords(importLevel.value.id, importFile.value)
    expandedLevelId.value = importLevel.value.id
    toast.success('Vocabulary import completed.')
  } catch (error) {
    notifyError(error, 'Failed to import vocabulary words.')
  }
}

const downloadImportTemplate = () => {
  const content = 'word,translation,example,notes\ndog,perro,The dog is friendly,Common pet\ncat,gato,The cat is sleeping,'
  const url = URL.createObjectURL(new Blob([content], { type: 'text/csv;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = 'vocabulary-import-template.csv'
  link.click()
  URL.revokeObjectURL(url)
}

const goBack = () => router.push(authStore.role === 'admin' ? '/admin' : '/teacher')
const difficultyClass = (difficulty) => ({
  beginner: 'badge-success', intermediate: 'badge-warning', advanced: 'badge-error',
}[difficulty] || 'badge-neutral')

onMounted(loadLevels)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-3xl font-bold text-primary">Vocabulary Management</h1>
        <p class="text-base-content/70 mt-1">Create and manage vocabulary levels and words</p>
      </div>
      <div class="flex gap-2">
        <button @click="goBack" class="btn btn-ghost gap-2"><ArrowLeftIcon class="w-4 h-4" />Back</button>
        <button v-if="canManage" @click="openCreateLevel" class="btn btn-primary gap-2">
          <PlusIcon class="w-5 h-5" />Create Level
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-primary"><BookOpenIcon class="w-8 h-8" /></div>
        <div class="stat-title">Total Levels</div><div class="stat-value text-primary">{{ vocabularyStore.levels.length }}</div>
      </div>
      <div class="stat bg-base-100 shadow-md rounded-lg">
        <div class="stat-figure text-secondary"><BookOpenIcon class="w-8 h-8" /></div>
        <div class="stat-title">Total Words</div><div class="stat-value text-secondary">{{ totalWords }}</div>
      </div>
    </div>

    <div class="card bg-base-100 shadow-md"><div class="card-body">
      <input v-model="searchQuery" class="input input-bordered w-full max-w-md" placeholder="Search levels or loaded words..." />
    </div></div>

    <div v-if="vocabularyStore.loading && !vocabularyStore.levels.length" class="text-center py-12">
      <span class="loading loading-spinner loading-lg"></span><p class="mt-3">Loading vocabulary...</p>
    </div>
    <div v-else-if="vocabularyStore.error && !vocabularyStore.levels.length" class="alert alert-error">{{ vocabularyStore.error }}</div>

    <div v-else class="space-y-4">
      <div v-for="level in filteredLevels" :key="level.id" class="card bg-base-100 shadow-md">
        <div class="card-body">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <button class="text-left" @click="toggleLevel(level)">
              <div class="flex items-center gap-2">
                <h2 class="card-title">{{ level.title }}</h2>
                <span class="badge" :class="difficultyClass(level.difficulty)">{{ level.difficulty || 'Unspecified' }}</span>
              </div>
              <p class="text-sm text-base-content/70">{{ level.description || 'No description' }}</p>
              <p class="text-xs mt-1">{{ level.word_count ?? level.words?.length ?? 0 }} words · Click to {{ String(expandedLevelId) === String(level.id) ? 'close' : 'open' }}</p>
            </button>
            <div v-if="canManage" class="flex gap-2">
              <button @click="openCreateWord(level)" class="btn btn-sm btn-primary"><PlusIcon class="w-4 h-4" />Add Word</button>
              <button @click="openImport(level)" class="btn btn-sm btn-outline">Import CSV</button>
              <button @click="openEditLevel(level)" class="btn btn-sm btn-outline"><PencilIcon class="w-4 h-4" /></button>
              <button @click="removeLevel(level)" class="btn btn-sm btn-error btn-outline"><TrashIcon class="w-4 h-4" /></button>
            </div>
          </div>

          <div v-if="String(expandedLevelId) === String(level.id)" class="mt-4 overflow-x-auto">
            <div v-if="vocabularyStore.loading" class="text-center py-5"><span class="loading loading-spinner"></span></div>
            <div v-else-if="!level.words?.length" class="text-center text-base-content/70 py-6">No words in this level.</div>
            <table v-else class="table table-zebra">
              <thead><tr><th>Word</th><th>Translation</th><th>Example</th><th>Notes</th><th>Audio</th><th v-if="canManage">Actions</th></tr></thead>
              <tbody><tr v-for="word in level.words" :key="word.id">
                <td class="font-semibold">{{ word.word }}</td><td>{{ word.translation }}</td>
                <td>{{ word.example || '—' }}</td><td>{{ word.notes || '—' }}</td>
                <td><audio v-if="word.audio_url" :src="word.audio_url" controls preload="none" class="w-48"></audio><span v-else>—</span></td>
                <td v-if="canManage"><div class="flex gap-1">
                  <button @click="openEditWord(word)" class="btn btn-xs btn-outline"><PencilIcon class="w-3 h-3" /></button>
                  <button @click="removeWord(word)" class="btn btn-xs btn-error btn-outline"><TrashIcon class="w-3 h-3" /></button>
                </div></td>
              </tr></tbody>
            </table>
          </div>
        </div>
      </div>
      <div v-if="!filteredLevels.length" class="text-center py-12 text-base-content/70">No vocabulary levels found.</div>
    </div>

    <div v-if="showLevelModal" class="modal modal-open"><div class="modal-box">
      <h3 class="font-bold text-lg mb-4">{{ editingLevelId ? 'Edit' : 'Create' }} Vocabulary Level</h3>
      <form @submit.prevent="saveLevel" class="space-y-4">
        <input v-model="levelForm.title" class="input input-bordered w-full" placeholder="Title" required />
        <textarea v-model="levelForm.description" class="textarea textarea-bordered w-full" placeholder="Description"></textarea>
        <select v-model="levelForm.difficulty" class="select select-bordered w-full">
          <option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option>
        </select>
        <div class="modal-action"><button type="button" @click="showLevelModal = false" class="btn btn-ghost">Cancel</button>
          <button class="btn btn-primary" :disabled="vocabularyStore.loading">Save</button></div>
      </form>
    </div></div>

    <div v-if="showWordModal" class="modal modal-open"><div class="modal-box">
      <h3 class="font-bold text-lg mb-4">{{ editingWordId ? 'Edit' : 'Add' }} Vocabulary Word</h3>
      <form @submit.prevent="saveWord" class="space-y-4">
        <input v-model="wordForm.word" class="input input-bordered w-full" placeholder="Word" required />
        <input v-model="wordForm.translation" class="input input-bordered w-full" placeholder="Translation" required />
        <textarea v-model="wordForm.example" class="textarea textarea-bordered w-full" placeholder="Example"></textarea>
        <textarea v-model="wordForm.notes" class="textarea textarea-bordered w-full" placeholder="Notes"></textarea>
        <div v-if="wordForm.audio_url" class="space-y-1">
          <span class="text-sm font-medium">Existing audio</span>
          <audio :src="wordForm.audio_url" controls class="w-full"></audio>
          <p class="text-xs text-base-content/60">This audio is kept unless you choose a replacement.</p>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Audio file (optional)</span></label>
          <input type="file" accept="audio/*,.mp3,.wav,.m4a,.ogg" class="file-input file-input-bordered w-full" @change="selectAudio" />
          <label v-if="selectedAudioFile" class="label"><span class="label-text-alt">Selected: {{ selectedAudioFile.name }}</span></label>
        </div>
        <div class="modal-action"><button type="button" @click="showWordModal = false" class="btn btn-ghost">Cancel</button>
          <button class="btn btn-primary" :disabled="vocabularyStore.loading">Save</button></div>
      </form>
    </div></div>

    <div v-if="showImportModal" class="modal modal-open"><div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg">Import CSV into {{ importLevel?.title }}</h3>
      <p class="text-sm text-base-content/70 mt-1">Required columns: word, translation. Optional: example, notes.</p>
      <pre class="bg-base-200 rounded p-3 text-xs overflow-x-auto mt-4">word,translation,example,notes
dog,perro,The dog is friendly,Common pet
cat,gato,The cat is sleeping,</pre>
      <button @click="downloadImportTemplate" class="btn btn-link btn-sm px-0">Download CSV template</button>
      <input type="file" accept=".csv,.txt,text/csv" class="file-input file-input-bordered w-full mt-3" @change="selectImportFile" />
      <p v-if="importFile" class="text-sm mt-2">Selected: {{ importFile.name }}</p>

      <div v-if="importSummary" class="mt-5 space-y-3">
        <div class="stats stats-horizontal shadow w-full">
          <div class="stat p-3"><div class="stat-title">Imported</div><div class="stat-value text-success text-2xl">{{ importSummary.imported }}</div></div>
          <div class="stat p-3"><div class="stat-title">Skipped</div><div class="stat-value text-warning text-2xl">{{ importSummary.skipped }}</div></div>
          <div class="stat p-3"><div class="stat-title">Failed</div><div class="stat-value text-error text-2xl">{{ importSummary.failed }}</div></div>
        </div>
        <div v-if="importSummary.errors?.length" class="max-h-48 overflow-y-auto">
          <div v-for="rowError in importSummary.errors" :key="rowError.row" class="alert alert-error mb-2 py-2">
            <span>Row {{ rowError.row }}: {{ Object.values(rowError.errors).flat().join(' ') }}</span>
          </div>
        </div>
      </div>

      <div class="modal-action">
        <button @click="closeImport" class="btn btn-ghost" :disabled="vocabularyStore.loading">Close</button>
        <button @click="runImport" class="btn btn-primary" :disabled="vocabularyStore.loading || !importFile">
          <span v-if="vocabularyStore.loading" class="loading loading-spinner loading-sm"></span>
          {{ vocabularyStore.loading ? 'Importing...' : 'Import CSV' }}
        </button>
      </div>
    </div></div>
  </div>
</template>
