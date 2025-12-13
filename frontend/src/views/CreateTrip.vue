<script setup>
import {onMounted, ref} from 'vue';
import axios from '@/api/axios';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const form = ref({
  title: '',
  destination: '',
  description: '',
  start_date: '',
  end_date: '',
  assigned_to: null,
});

const loading = ref(false);
const success = ref(null);
const error = ref(null);

const submitForm = async () => {
  loading.value = true;
  success.value = null;
  error.value = null;

  try {
    await axios.post('/api/trips', form.value);
    success.value = 'Wycieczka została pomyślnie utworzona!';

    // Czyścimy formularz po sukcesie
    form.value = {
      title: '',
      destination: '',
      description: '',
      start_date: '',
      end_date: '',
    };
  } catch (e) {
    console.error(e);

    if (e.response?.data?.errors) {
      error.value = Object.values(e.response.data.errors)
          .flat()
          .join(', ');
    } else {
      error.value = 'Nie udało się utworzyć wycieczki.';
    }
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
});
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Tło animowane -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Kontener -->
    <div class="relative min-h-screen flex items-start justify-center z-10 pt-24">
      <div class="w-4/5 max-w-2xl bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg">

        <!-- Nagłówek -->
        <h1 class="text-3xl font-bold text-violet-300 mb-6 text-center">
          Utwórz nową wycieczkę
        </h1>

        <!-- Success + Error -->
        <div
            v-if="success"
            class="p-3 mb-4 text-green-300 bg-green-900/40 border border-green-700 rounded-lg"
        >
          {{ success }}
        </div>

        <div
            v-if="error"
            class="p-3 mb-4 text-red-300 bg-red-900/40 border border-red-700 rounded-lg"
        >
          {{ error }}
        </div>

        <!-- Formularz -->
        <form @submit.prevent="submitForm" class="space-y-5">

          <!-- Tytuł -->
          <div>
            <label class="block mb-1 text-gray-300 font-medium">Tytuł</label>
            <input
                v-model="form.title"
                type="text"
                required
                class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                     focus:outline-none focus:border-violet-600"
            />
          </div>

          <!-- Destination -->
          <div>
            <label class="block mb-1 text-gray-300 font-medium">Cel podróży</label>
            <input
                v-model="form.destination"
                type="text"
                required
                class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                     focus:outline-none focus:border-violet-600"
            />
          </div>

          <!-- Opis -->
          <div>
            <label class="block mb-1 text-gray-300 font-medium">Opis (opcjonalne)</label>
            <textarea
                v-model="form.description"
                rows="4"
                class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                     focus:outline-none focus:border-violet-600"
            ></textarea>
          </div>

          <!-- Data startu -->
          <div>
            <label class="block mb-1 text-gray-300 font-medium">Data rozpoczęcia</label>
            <input
                v-model="form.start_date"
                type="date"
                required
                class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                     focus:outline-none focus:border-violet-600"
            />
          </div>

          <!-- Data końca -->
          <div>
            <label class="block mb-1 text-gray-300 font-medium">Data zakończenia</label>
            <input
                v-model="form.end_date"
                type="date"
                required
                class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg
                     focus:outline-none focus:border-violet-600"
            />
          </div>

          <!-- Przycisk -->
          <button
              type="submit"
              :disabled="loading"
              class="w-full bg-violet-800 hover:bg-violet-950 transition text-white py-3
                   rounded-lg font-medium shadow-md disabled:opacity-50"
          >
            {{ loading ? 'Wysyłanie...' : 'Utwórz wycieczkę' }}
          </button>
        </form>

        <!-- Link powrotu -->
        <router-link
            to="/trips"
            class="inline-block mt-6 text-violet-300 hover:underline"
        >
          ← Powrót do listy
        </router-link>

      </div>
    </div>
  </div>
</template>
