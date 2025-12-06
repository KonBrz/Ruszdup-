<script setup>
import {ref, onMounted} from 'vue';
import axios from '@/api/axios';

const trips = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchTrips = async () => {
  try {
    const response = await axios.get('/api/trips');
    console.log('response',response.data)
    trips.value = response.data;
  } catch (e) {
    error.value = 'Nie udało się pobrać listy wycieczek.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchTrips();
});
</script>

<template>
  <div class="container p-8 mx-auto">
    <h1 class="mb-6 text-3xl font-bold text-purple-600">Lista wycieczek</h1>
    <router-link to="/trips/createtrip" class="hover:text-gray-300">Utwórz Wycieczkę</router-link>
    <div v-if="loading" class="text-center">Ładowanie...</div>
    <div v-if="error" class="text-center text-red-500">{{ error }}</div>
    <div v-if="!loading && trips.length === 0" class="text-center text-gray-500">
      Brak wycieczek do wyświetlenia.
    </div>
    <ul v-if="!loading && trips.length > 0" class="space-y-4">
      <li v-for="trip in trips" :key="trip.id" class="p-4 bg-white rounded-lg shadow-md">
        <router-link :to="{ name: 'TripDetails', params: { id: trip.id } }">
          <h2 class="text-xl font-semibold">{{ trip.title }}</h2>
          <p class="text-gray-600">Opis: {{ trip.description }}</p>
          <p class="text-sm text-gray-500">Utworzono: {{ trip.created_at }}</p>
        </router-link>
      </li>
    </ul>
  </div>
</template>
