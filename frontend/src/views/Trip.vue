<script setup>
import {ref, onMounted} from 'vue';
import axios from '@/api/axios';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';
import router from "../router/index.js";

const tokenInput = ref('');
const trips = ref([]);
const loading = ref(true);
const showInviteMenu = ref(false);
const error = ref(null);
const success = ref('');

const fetchTrips = async () => {
  try {
    const response = await axios.get('/api/trips');
    console.log('response', response.data)
    trips.value = response.data;
  } catch (e) {
    error.value = 'Nie udało się pobrać listy wycieczek.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const inviteUser = async (inviteLink) => {
  try {
    router.push(`/trips/${inviteLink}`)
  } catch (e) {
    console.error(e);
    alert('Nie udało się dołączyć.');
  }
}

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('pl-PL', options);
}

onMounted(() => {
  const granimInstance = new Granim({
    element: '#granim-canvas',
    name: 'granim',
    direction: 'top-bottom',
    isPausedWhenNotInView: false,
    image : {
      source: forestImg,
      blendingMode: 'hard-light', // blendowanie z gradientem
    },
    states : {
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
  fetchTrips();
});
</script>

<template>
  <!-- Wrapper całego ekranu -->
  <div class="relative min-h-screen">

    <!-- Tło -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Treść -->
    <div class="relative z-10 flex justify-center pt-16 min-h-screen h-auto">
      <div class="w-4/5 bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg">

        <div class="bg-gradient-to-r from-violet-950 via-violet-900 to-gray-900 p-6 rounded-xl shadow-lg mb-8">
          <h1 class="text-4xl font-bold text-violet-200">Lista wycieczek</h1>
          <p class="text-gray-300 mt-1">Przeglądaj wszystkie wycieczki lub dołącz do nowej</p>

          <!-- Przyciski: Dołącz / Utwórz -->
          <div class="flex flex-wrap gap-4 mb-6">
            <!-- Dołącz dropdown -->
            <div class="relative inline-block">
              <button
                  @click="showInviteMenu = !showInviteMenu"
                  class="bg-violet-800 hover:bg-violet-950 text-white px-4 py-2 rounded-lg shadow-md transition"
              >
                Dołącz do wycieczki
              </button>
              <div
                  v-if="showInviteMenu"
                  class="absolute mt-2 w-72 bg-gray-800 border border-gray-700 rounded-lg shadow-lg p-4 z-20"
              >
                <input
                    v-model="tokenInput"
                    type="text"
                    placeholder="Wpisz token zaproszenia"
                    data-testid="invite-token-input"
                    class="w-full px-4 py-2 bg-gray-900 text-gray-200 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-600 mb-3"
                />
                <button
                    @click="inviteUser(tokenInput)"
                    :disabled="loading"
                    data-testid="invite-token-submit"
                    class="w-full bg-violet-800 hover:bg-violet-950 text-white px-4 py-2 rounded-lg shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ loading ? 'Dołączanie...' : 'Potwierdź' }}
                </button>
                <p v-if="error" class="text-red-500 mt-2">{{ error }}</p>
                <p v-if="success" class="text-green-500 mt-2">{{ success }}</p>
              </div>
            </div>

            <!-- Utwórz wycieczkę -->
            <router-link
                to="/trips/createtrip"
                data-testid="trips-create-link"
                class="bg-violet-800 hover:bg-violet-950 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center"
            >
              Utwórz Wycieczkę
            </router-link>
          </div>
        </div>

        <!-- Status ładowania / lista wycieczek -->
        <div v-if="loading" class="text-center text-gray-400 mt-4">Ładowanie...</div>
        <div v-if="error" class="text-center text-red-500 mt-4">{{ error }}</div>
        <div v-if="!loading && trips.length === 0" data-testid="trips-empty" class="text-center text-gray-500 mt-6">
          Brak wycieczek do wyświetlenia.
        </div>

        <ul v-if="!loading && trips.length > 0" class="space-y-4 mt-6" data-testid="trips-list">
          <li
              v-for="trip in trips"
              :key="trip.id"
              :data-testid="`trip-item-${trip.id}`"
              class="p-5 bg-gray-800 border border-gray-700 rounded-xl shadow-md hover:bg-gray-700 transition cursor-pointer"
          >
            <router-link :to="{ name: 'TripDetails', params: { id: trip.id } }" class="block">
              <div class="flex justify-between items-start">
                <div>
                  <h2 class="text-xl font-semibold text-violet-200" data-testid="trip-item-title">{{ trip.title }}</h2>
                  <p class="text-gray-300 mt-1">{{ trip.description }}</p>
                </div>
                <div class="text-right space-y-1 text-sm text-gray-400">
                  <p><strong>Utworzono przez:</strong> {{ trip.user_name }}</p>
                  <p><strong>Data dodania:</strong> {{ formatDate(trip.created_at) }}</p>
                  <p><strong>Start:</strong> {{ trip.start_date }}</p>
                  <p><strong>Koniec:</strong> {{ trip.end_date }}</p>
                </div>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span
                    v-for="user in trip.trip_users"
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

