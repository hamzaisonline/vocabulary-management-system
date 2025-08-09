<script setup>
import { ref } from 'vue';

const teachers = ref([
  { id: 1, name: 'John Doe', subject: 'Spanish' },
  { id: 2, name: 'Jane Smith', subject: 'French' },
]);

const newTeacherName = ref('');
const newSubject = ref('');

function addTeacher() {
  if (newTeacherName.value && newSubject.value) {
    teachers.value.push({
      id: Date.now(),
      name: newTeacherName.value,
      subject: newSubject.value,
    });
    newTeacherName.value = '';
    newSubject.value = '';
  }
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Manage Teachers</h1>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Teacher Name</span>
      </label>
      <input type="text" class="input input-bordered" v-model="newTeacherName" placeholder="Teacher Name" />
    </div>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Subject</span>
      </label>
      <input type="text" class="input input-bordered" v-model="newSubject" placeholder="Subject" />
    </div>
    <button class="btn btn-primary mb-4" @click="addTeacher">Add Teacher</button>

    <h2 class="text-xl font-bold mt-6 mb-4">Teacher List</h2>
    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Subject</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="teacher in teachers" :key="teacher.id">
          <td>{{ teacher.name }}</td>
          <td>{{ teacher.subject }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
