<script setup>
import {ref, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import axios from '@/api/axios';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const route = useRoute();
const router = useRouter();

const trip = ref(null);
const loading = ref(true);
const error = ref(null);
const saving = ref(false);

// Formularz nowego taska
const form = ref({
  title: '',
  priority: null,
  deadline: '',
  user_ids: [],
});

const fetchTripUsers = async () => {
  try {
    const response = await axios.get(`/api/trips/${route.params.id}`);
    trip.value = response.data;
  } catch (e) {
    console.error(e);
    error.value = "Nie udało się pobrać użytkowników wycieczki.";
  } finally {
    loading.value = false;
  }
};

const createTask = async () => {
  saving.value = true;

  try {
    await axios.post('/api/tasks', {
      ...form.value,

      trip_id: route.params.id,
    });

    router.push(`/trips/${route.params.id}`);
  } catch (e) {
    console.error(e);
    error.value = "Nie udało się utworzyć zadania.";
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
  fetchTripUsers();
});
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Tło animowane -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Kontener główny -->
    <div class="relative min-h-screen flex items-start justify-center z-10 pt-24">
      <div class="w-4/5 max-w-2xl bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg">

        <!-- Loading / Error -->
        <div v-if="loading" class="text-center text-gray-300">Ładowanie...</div>
        <div v-if="error" class="text-center text-red-500">{{ error }}</div>

        <!-- Główna sekcja -->
        <div v-if="trip">

          <h1 class="text-3xl font-bold text-violet-300 mb-6 text-center">
            Dodaj zadanie
          </h1>

          <!-- Formularz -->
          <form @submit.prevent="createTask" class="space-y-5">

            <!-- Tytuł -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Tytuł</label>
              <input
                  type="text"
                  v-model="form.title"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Priorytet -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Priorytet</label>
              <select
                  v-model="form.priority"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
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
                  type="date"
                  v-model="form.deadline"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Użytkownicy -->
            <div>
              <label class="block mb-2 text-gray-300 font-medium text-lg">Przypisz użytkowników</label>

              <div class="space-y-3 bg-gray-800 p-4 rounded-lg border border-gray-700">
                <div
                    v-for="user in trip.trip_users"
                    :key="user.id"
                    class="flex items-center gap-3 relative"
                >
                  <input
                      type="checkbox"
                      :value="user.id"
                      v-model="form.user_ids"
                      class="peer w-5 h-5 rounded-md border border-violet-700 bg-gray-900 appearance-none
                           transition-all duration-200 checked:bg-violet-600 checked:border-violet-800"
                  />

                  <svg
                      class="absolute left-0 ml-0.5 pointer-events-none w-5 h-5 text-white scale-0
                           transition-transform duration-200 peer-checked:scale-100"
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
                class="w-full bg-violet-800 hover:bg-violet-950 transition text-white py-3
                     rounded-lg font-medium shadow-md disabled:opacity-50"
                :disabled="saving"
            >
              {{ saving ? "Tworzenie..." : "Utwórz zadanie" }}
            </button>

          </form>

          <!-- Powrót -->
          <router-link
              :to="`/trips/${route.params.id}`"
              class="inline-block mt-6 text-violet-300 hover:underline"
          >
            ← Powrót do szczegółów wycieczki
          </router-link>
        </div>

      </div>
    </div>
  </div>
</template>
