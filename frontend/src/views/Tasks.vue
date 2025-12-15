<script setup>
import {ref, onMounted} from 'vue';
import axios from '@/api/axios';

const tasks = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchTasks = async () => {
  try {
    const response = await axios.get('/api/tasks');
    tasks.value = Array.isArray(response.data) ? response.data : [];
  } catch (e) {
    error.value = 'Nie udało się pobrać listy zadań.';
    console.error('fetchTasks error:', e);
    throw e;
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  try {
    await fetchTasks();
  } catch (e) {
    // Dodatkowe zabezpieczenie — log i ustawienie error jeśli fetchTasks wyrzuci błąd
    error.value = error.value || 'Wystąpił błąd podczas inicjalizacji komponentu.';
    console.error('onMounted fetchTasks failed:', e);
  }
});
</script>

<template>
  <div class="container p-8 mx-auto">
    <h1 class="mb-6 text-3xl font-bold text-purple-600">Lista Zadań</h1>
    <div v-if="loading" class="text-center">Ładowanie...</div>
    <div v-if="error" class="text-center text-red-500">{{ error }}</div>
    <div v-if="!loading && tasks.length === 0" class="text-center text-gray-500">
      Brak zadań do wyświetlenia.
    </div>
    <ul v-if="!loading && tasks.length > 0" class="space-y-4">
      <li v-for="task in tasks" :key="task.id" class="p-4 bg-white rounded-lg shadow-md">
        <router-link :to="{ name: 'TripDetails', params: { id: task.trip_id } }">
          <h2 class="text-xl font-semibold">{{ task.title }}</h2>
          <p class="text-gray-600">Priorytet: {{ task.priority }}</p>
          <p class="text-sm text-gray-500">Utworzono: {{ task.created_at }}</p>
        </router-link>
      </li>
    </ul>
  </div>
</template>
