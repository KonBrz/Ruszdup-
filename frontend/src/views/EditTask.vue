<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/api/axios';
import { useAuthStore } from '@/stores/auth';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();


const task = ref(null);
const loading = ref(true);
const error = ref(null);
const saving = ref(false);

// Dane do edycji
const form = ref({
  title: '',
  priority: '',
  deadline: '',
  completed: false,
  ignored: false,
  user_ids: [],
});

const fetchTask = async () => {
  try {
    const response = await axios.get(`/api/tasks/${route.params.id}`);
    task.value = response.data;
    const currentUser = task.value.task_users.find(u => u.id === authStore.user.id);

    // Wypełniamy formularz
    form.value.title = task.value.title;
    form.value.priority = task.value.priority;
    form.value.deadline = task.value.deadline;
    form.value.completed = Boolean(currentUser?.pivot.completed);
    form.value.ignored = Boolean(currentUser?.pivot.ignored);
    form.value.user_ids = task.value.task_users.map(user => user.id);
  } catch (e) {
    console.error(e);
    error.value = 'Nie udało się pobrać zadania.';
  } finally {
    loading.value = false;
  }
};

const saveTask = async () => {
  saving.value = true;
  try {
    await axios.put(`/api/tasks/${route.params.id}`, form.value);
    router.push(`/trips/${task.value.trip_id}`); // wracamy do szczegółów tripa
  } catch (e) {
    console.error(e);
    error.value = 'Nie udało się zapisać zmian.';
  } finally {
    saving.value = false;
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
  fetchTask();
});
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Tło animowane -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Kontener główny -->
    <div class="relative min-h-screen flex items-start justify-center z-10 pt-24">
      <div class="w-4/5 max-w-2xl bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg">

        <!-- Ładowanie / błąd -->
        <div v-if="loading" class="text-center text-gray-300">Ładowanie...</div>
        <div v-if="error" class="text-center text-red-500">{{ error }}</div>

        <div v-if="task">

          <!-- Nagłówek -->
          <h1 class="text-3xl font-bold text-violet-300 mb-6 text-center">
            Edytuj zadanie
          </h1>

          <!-- Formularz -->
          <form @submit.prevent="saveTask" class="space-y-5">

            <!-- Tytuł -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Zadanie</label>
              <input
                  v-model="form.title"
                  type="text"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Priorytet -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Priorytet</label>
              <select
                  v-model="form.priority"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-violet-600"
              >
                <option :value="null">Brak priorytetu</option>
                <option value="niski">Niski</option>
                <option value="średni">Średni</option>
                <option value="wysoki">Wysoki</option>
              </select>
            </div>

            <!-- Deadline -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Deadline</label>
              <input
                  v-model="form.deadline"
                  type="date"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Użytkownicy -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Przydziel użytkowników</label>

              <div class="space-y-2 bg-gray-800 p-3 rounded-lg border border-gray-700">
                <div
                    v-for="user in task.trip.trip_users"
                    :key="user.id"
                    class="flex items-center gap-2"
                >
                  <input
                      type="checkbox"
                      :value="user.id"
                      v-model="form.user_ids"
                      class="peer w-5 h-5 rounded-md border border-violet-700 bg-gray-900 appearance-none transition-all duration-200 checked:bg-violet-600 checked:border-violet-800"/>
                  <svg
                      class="absolute pointer-events-none w-5 h-5 text-white scale-0 transition-transform duration-200 peer-checked:scale-100"
                      fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"
                  >
                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span>{{ user.name }}</span>
                </div>
              </div>
            </div>

            <!-- Przycisk -->
            <button
                type="submit"
                :disabled="saving"
                class="w-full bg-violet-800 hover:bg-violet-950 transition text-white py-3 rounded-lg font-medium shadow-md disabled:opacity-50"
            >
              {{ saving ? 'Zapisywanie...' : 'Zapisz zmiany' }}
            </button>
          </form>

          <!-- Link powrotny -->
          <router-link
              :to="`/trips/${task.trip_id}`"
              class="inline-block mt-6 text-violet-300 hover:underline"
          >
            ← Powrót do szczegółów wycieczki
          </router-link>

        </div>
      </div>
    </div>
  </div>
</template>
