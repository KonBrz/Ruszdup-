<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "@/api/axios";
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const route = useRoute();
const router = useRouter();

const trip = ref(null);

const loading = ref(true);
const saving = ref(false);
const error = ref(null);

const form = ref({
  title: "",
  description: "",
  destination: "",
  start_date: "",
  end_date: ""
});

const fetchTrip = async () => {
  try {
    const response = await axios.get(`/api/trips/${route.params.id}`);
    trip.value = response.data;

    form.value.title = trip.value.title;
    form.value.description = trip.value.description;
    form.value.destination = trip.value.destination;
    form.value.start_date = trip.value.start_date;
    form.value.end_date = trip.value.end_date;

  } catch (e) {
    console.error(e);
    error.value = "Nie udało się pobrać danych wycieczki.";
  } finally {
    loading.value = false;
  }
};

const saveTrip = async () => {
  saving.value = true;
  try {
    await axios.put(`/api/trips/${route.params.id}`, form.value);

    router.push(`/trips/${route.params.id}`); // powrót do szczegółów tripa

  } catch (e) {
    console.error(e);
    error.value = "Nie udało się zapisać zmian.";
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
  fetchTrip();
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

        <div v-if="trip">

          <!-- Nagłówek -->
          <h1 class="text-3xl font-bold text-violet-300 mb-6 text-center">
            Edytuj wycieczkę
          </h1>

          <!-- Formularz -->
          <form @submit.prevent="saveTrip" class="space-y-5" data-testid="trip-edit-form">

            <!-- Tytuł -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Tytuł</label>
              <input
                  v-model="form.title"
                  data-testid="trip-edit-title"
                  type="text"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Opis -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Opis</label>
              <textarea
                  v-model="form.description"
                  data-testid="trip-edit-description"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
                  rows="4"
              ></textarea>
            </div>

            <!-- Cel podróży -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Cel podróży</label>
              <input
                  v-model="form.destination"
                  data-testid="trip-edit-destination"
                  type="text"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Data startu -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Data rozpoczęcia</label>
              <input
                  v-model="form.start_date"
                  data-testid="trip-edit-start-date"
                  type="date"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Data końca -->
            <div>
              <label class="block mb-1 text-gray-300 font-medium">Data zakończenia</label>
              <input
                  v-model="form.end_date"
                  data-testid="trip-edit-end-date"
                  type="date"
                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                       focus:outline-none focus:border-violet-600"
              />
            </div>

            <!-- Przycisk -->
            <button
                type="submit"
                :disabled="saving"
                data-testid="trip-edit-submit"
                class="w-full bg-violet-800 hover:bg-violet-950 transition text-white py-3
                     rounded-lg font-medium shadow-md disabled:opacity-50"
            >
              {{ saving ? "Zapisywanie..." : "Zapisz zmiany" }}
            </button>
          </form>

          <!-- Link powrotny -->
          <router-link
              :to="`/trips/${trip.id}`"
              class="inline-block mt-6 text-violet-300 hover:underline"
          >
            ← Powrót do szczegółów wycieczki
          </router-link>

        </div>
      </div>
    </div>
  </div>
</template>
