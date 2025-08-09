<script setup>
import { ref } from 'vue';

const words = ref([
  { id: 1, word: 'Cat', translation: 'Gato', language: 'Spanish' },
  { id: 2, word: 'Dog', translation: 'Perro', language: 'Spanish' },
]);

const newWord = ref('');
const newTranslation = ref('');
const newLanguage = ref('');

function addWord() {
  if (newWord.value && newTranslation.value && newLanguage.value) {
    words.value.push({
      id: Date.now(),
      word: newWord.value,
      translation: newTranslation.value,
      language: newLanguage.value,
    });
    newWord.value = '';
    newTranslation.value = '';
    newLanguage.value = '';
  }
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Manage Words</h1>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Word</span>
      </label>
      <input type="text" class="input input-bordered" v-model="newWord" placeholder="Word" />
    </div>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Translation</span>
      </label>
      <input type="text" class="input input-bordered" v-model="newTranslation" placeholder="Translation" />
    </div>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Language</span>
      </label>
      <input type="text" class="input input-bordered" v-model="newLanguage" placeholder="Language" />
    </div>
    <button class="btn btn-primary mb-4" @click="addWord">Add Word</button>

    <h2 class="text-xl font-bold mt-6 mb-4">Word List</h2>
    <table class="table w-full">
      <thead>
        <tr>
          <th>Word</th>
          <th>Translation</th>
          <th>Language</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="word in words" :key="word.id">
          <td>{{ word.word }}</td>
          <td>{{ word.translation }}</td>
          <td>{{ word.language }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
