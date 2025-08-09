<script setup>
import { ref } from 'vue';

const students = ref([
  { id: 1, name: 'Alice', username: 'alice123' },
  { id: 2, name: 'Bob', username: 'bob456' },
]);

const newStudentName = ref('');
const newUsername = ref('');

function addStudent() {
  if (newStudentName.value && newUsername.value) {
    students.value.push({
      id: Date.now(),
      name: newStudentName.value,
      username: newUsername.value,
    });
    newStudentName.value = '';
    newUsername.value = '';
  }
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Manage Students</h1>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Student Name</span>
      </label>
      <input type="text" class="input input-bordered" v-model="newStudentName" placeholder="Student Name" />
    </div>
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Username</span>
      </label>
      <input type="text" class="input input-bordered" v-model="newUsername" placeholder="Username" />
    </div>
    <button class="btn btn-primary mb-4" @click="addStudent">Add Student</button>

    <h2 class="text-xl font-bold mt-6 mb-4">Student List</h2>
    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="student in students" :key="student.id">
          <td>{{ student.name }}</td>
          <td>{{ student.username }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
