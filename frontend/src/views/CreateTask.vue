<script setup>
import {ref, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import axios from '@/api/axios';

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

onMounted(fetchTripUsers);
</script>

<template>
  <div class="container p-8 mx-auto">

    <div v-if="loading">Ładowanie...</div>

    <div v-if="error" class="text-red-500">{{ error }}</div>

    <div v-if="trip">

      <h1 class="text-3xl font-bold text-purple-600 mb-4">Dodaj zadanie</h1>

      <form @submit.prevent="createTask" class="space-y-4">

        <div>
          <label class="block font-semibold">Tytuł:</label>
          <input type="text" v-model="form.title" class="border p-2 w-full rounded">
        </div>

        <div>
          <label class="block font-semibold">Priorytet:</label>
          <select v-model="form.priority" class="border p-2 w-full rounded">
            <option :value="null">Brak priorytetu</option>
            <option value="niski">Niski</option>
            <option value="średni">Średni</option>
            <option value="wysoki">Wysoki</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold">Deadline:</label>
          <input type="date" v-model="form.deadline" class="border p-2 w-full rounded">
        </div>

        <h2 class="font-semibold text-lg mt-4">Przypisz użytkowników</h2>

        <div v-for="user in trip.trip_users" :key="user.id">
          <label>
            <input
                type="checkbox"
                :value="user.id"
                v-model="form.user_ids"
            >
            {{ user.name }}
          </label>
        </div>

        <button
            class="bg-purple-600 text-white px-4 py-2 rounded"
            :disabled="saving"
        >
          {{ saving ? "Tworzenie..." : "Utwórz zadanie" }}
        </button>

      </form>

    </div>
    <router-link
        to="/trips/${route.params.id}"
        class="inline-block mt-4 text-purple-600 hover:underline">
      ← Powrót do listy
    </router-link>
  </div>
</template>