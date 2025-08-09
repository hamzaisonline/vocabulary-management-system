<script setup>
import { ref } from "vue";

const students = ref([
  { id: 1, name: "Alice", username: "alice123", password: "12345" },
  { id: 2, name: "Bob", username: "bob456", password: "54321" },
]);
const newStudentName = ref("");

function addStudent() {
  if (newStudentName.value) {
    students.value.push({
      id: Date.now(),
      name: newStudentName.value,
      username: "",
      password: "",
    });
    newStudentName.value = "";
  }
}

function resetPassword(student) {
  student.password = "newpassword123"; // Example logic
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Manage Class</h1>

    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Add New Student</span>
      </label>
      <input
        type="text"
        class="input input-bordered"
        v-model="newStudentName"
        placeholder="Student Name"
      />
      <button class="btn btn-secondary mt-2" @click="addStudent">
        Add Student
      </button>
    </div>

    <h2 class="text-xl font-bold mt-6 mb-4">Students</h2>
    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>Password</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="student in students" :key="student.id">
          <td>{{ student.name }}</td>
          <td>{{ student.username }}</td>
          <td>{{ student.password }}</td>
          <td>
            <button
              class="btn btn-outline btn-sm"
              @click="resetPassword(student)"
            >
              Reset Password
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
