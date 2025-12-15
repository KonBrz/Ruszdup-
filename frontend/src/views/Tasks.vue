<script setup>
import { ref, onMounted } from 'vue';
import axios from '@/api/axios';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const tasks = ref([]);
const loading = ref(true);
const error = ref(null);

// Funkcja do formatowania dat
const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('pl-PL', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

// Pobieranie zadań
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
  const granimInstance = new Granim({
    element: '#granim-canvas',
    name: 'granim',
    direction: 'top-bottom',
    isPausedWhenNotInView: false,
    image: {
      source: forestImg,
      blendingMode: 'hard-light', // blendowanie z gradientem
    },
    states: {
      "default-state": {
        gradients: [
          ['#1e1b2d', '#3a2c5a'],
          ['#2c1f3b', '#4b3476'],
          ['#33264c', '#5a4b8c'],
          ['#1a1526', '#2e1f4a']
        ],
        transitionSpeed: 7000
      }
    }
  });
  fetchTasks();
});
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Tło animowane -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Zawartość -->
    <div class="relative z-10 flex justify-center pt-20 pb-12 min-h-screen">
      <div class="w-4/5 max-w-4xl bg-gray-900 text-gray-100 p-10 rounded-xl shadow-xl">

        <!-- Nagłówek -->
        <div class="bg-gradient-to-r from-violet-950 via-violet-900 to-gray-900 p-6 rounded-xl shadow-lg mb-8">
          <h1 class="text-4xl font-bold text-violet-200">Lista Zadań</h1>
          <p class="text-gray-300 mt-1">Przeglądaj swoje zadania i przejdź do odpowiedniej wycieczki</p>
        </div>

        <!-- Status -->
        <div v-if="loading" class="text-center text-gray-400">Ładowanie...</div>
        <div v-if="error" class="text-center text-red-500">{{ error }}</div>
        <div v-if="!loading && tasks.length === 0" class="text-center text-gray-500 mt-6">
          Brak zadań do wyświetlenia.
        </div>

        <!-- LISTA ZADAŃ -->
        <ul v-if="!loading && tasks.length > 0" class="space-y-4 mt-6">
          <li
              v-for="task in tasks"
              :key="task.id"
              class="p-5 bg-gray-800 border border-gray-700 rounded-xl shadow-md hover:bg-gray-700 transition cursor-pointer"
          >
            <router-link :to="{ name: 'TripDetails', params: { id: task.trip_id } }" class="block">

              <div class="flex justify-between items-start">
                <!-- Tytuł -->
                <h2 class="text-xl font-semibold text-violet-200">{{ task.title }}</h2>

                <!-- Meta -->
                <div class="text-right text-sm text-gray-400">
                  <p v-if="task.priority"><strong>Priorytet:</strong> {{ task.priority }}</p>
                  <p v-if="task.deadline"><strong>Deadline:</strong> {{ formatDate(task.deadline) }}</p>
                </div>
              </div>

              <!-- Użytkownicy -->
              <div v-if="task.task_users?.length" class="mt-3 flex flex-wrap gap-2">
                <span
                    v-for="user in task.task_users"
                    :key="user.id"
                    class="bg-gray-700 px-2 py-1 rounded text-sm text-gray-200"
                >
                  {{ user.name }}
                </span>
              </div>

            </router-link>
          </li>
        </ul>

      </div>
    </div>
  </div>
</template>

