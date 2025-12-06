<script setup>
import {ref, onMounted} from 'vue';
import {useRoute} from 'vue-router';
import axios from '@/api/axios';
import router from "../router/index.js";
import {useAuthStore} from "../stores/auth.js";

const route = useRoute();
const authStore = useAuthStore();

const trips = ref(null);
const loading = ref(true);
const error = ref(null);

const inviteLink = ref('');
const generating = ref(false);
const inviteError = ref('');
const token = new URLSearchParams(window.location.search).get('invite_token');
const fetchTrips = async () => {
  try {
    const response = await axios.get(`/api/trips/${route.params.id}`);
    trips.value = response.data;
  } catch (e) {
    console.error(e);
    error.value = "Nie udało się pobrać szczegółów wycieczki.";
  } finally {
    loading.value = false;
  }
};

const deleteTrip = async (id) => {
  if (!confirm("Na pewno usunąć wycieczkę?")) return;

  try {
    await axios.delete(`/api/trips/${id}`);
    router.push(`/trips`);
  } catch (e) {
    console.error(e);
    alert("Nie udało się usunąć wycieczki.");
  }
};

const deleteTask = async (id) => {
  if (!confirm("Na pewno chcesz usunąć task?")) return;

  try {
    await axios.delete(`/api/tasks/${id}`);
    await fetchTrips();
  } catch (e) {
    console.error(e);
    alert('Nie udało się usunąć zadania.');
  }
};

const flagTrip = async () => {
  const reason = prompt('Podaj powód zgłoszenia wycieczki:');
  if (!reason) return;

  try {
    await axios.post('/api/flagged', {
      trip_id: trips.value.id,
      reason: reason,
    });
    alert('Wycieczka została zgłoszona.');
  } catch (e) {
    console.error(e);
    alert('Nie udało się zgłosić wycieczki.');
  }
};
const flagTask = async (taskId) => {
  const reason = prompt('Podaj powód zgłoszenia zadania:');
  if (!reason) return;

  try {
    await axios.post('/api/flagged', {
      task_id: taskId,
      reason: reason,
    });
    alert('Zadanie zostało zgłoszone.');
  } catch (e) {
    console.error(e);
    alert('Nie udało się zgłosić zadania.');
  }
};
const flagUser = async (userId) => {
  const reason = prompt('Podaj powód zgłoszenia użytkownika:');
  if (!reason) return;

  try {
    await axios.post('/api/flagged', {
      user_id: userId,
      reason: reason,
    });
    alert('Użytkownik został zgłoszony.');
  } catch (e) {
    console.error(e);
    alert('Nie udało się zgłosić użytkownika.');
  }
};


onMounted(async () => {
  const token = new URLSearchParams(window.location.search).get('invite_token');

  if (token) {
    if (!authStore.user) {
      router.push(`/login?redirect=${route.fullPath}`);
      return;
    }

    try {
      await axios.post('/api/trip-invite/accept', { token });
      alert('Dołączyłeś do wycieczki!');
      router.replace(`/trips/${route.params.id}`);
    } catch (e) {
      console.error(e);
      alert('Nie udało się dołączyć.');
    }
  }

  fetchTrips();
});

const generateInvite = async () => {
  if (!trips.value) return;

  generating.value = true;
  inviteError.value = '';
  try {
    const response = await axios.post(`/api/trips/${trips.value.id}/invite`);
    inviteLink.value = response.data.link;
    await navigator.clipboard.writeText(inviteLink.value);
    alert('Link do zaproszenia skopiowany do schowka!');
  } catch (e) {
    console.error(e);
    inviteError.value = 'Nie udało się wygenerować linku.';
  } finally {
    generating.value = false;
  }
};
</script>

<template>
  <div class="container p-8 mx-auto">
    <div v-if="loading" class="text-center">Ładowanie...</div>
    <div v-if="error" class="text-center text-red-500">{{ error }}</div>
    <div v-if="inviteLink">
      <p>Link do zaproszenia: {{ inviteLink }}</p>
    </div>
    <div v-if="inviteError" class="text-red-500">
      {{ inviteError }}
    </div>

    <div v-if="trips" class="space-y-4">
      <!-- Tytuł -->
      <h1 class="text-4xl font-bold text-purple-600">
        {{ trips.title }}
      </h1>

      <!-- Kto utworzył -->
      <div v-if="trips && trips.user">
        <p class="text-gray-700 text-lg">
          <strong>Utworzył:</strong> {{ trips.user.name }}
        </p>
      </div>

      <!-- Opis -->
      <p class="text-gray-800 text-lg">
        <strong>Opis:</strong> {{ trips.description }}
      </p>

      <!-- Cel podróźy -->
      <p class="text-gray-800 text-lg">
        <strong>Cel podróży:</strong> {{ trips.destination }}
      </p>

      <!-- Daty -->
      <div class="text-gray-700 text-lg">
        <p><strong>Data rozpoczęcia:</strong> {{ trips.start_date }}</p>
        <p><strong>Data zakończenia:</strong> {{ trips.end_date }}</p>
      </div>

      <router-link
          v-if="trips.can_edit_trip"
          :to="{ name: 'EditTrip', params: { id: trips.id } }">
        Edytuj Wycieczkę
      </router-link>

      <button @click="flagTrip" class="bg-yellow-500 text-white px-3 py-1 rounded mb-4">
        Zgłoś wycieczkę
      </button>

      <button v-if="trips.can_edit_trip" @click="deleteTrip(trips.id)" class="bg-red-600 text-white px-3 py-1 rounded">
        Usuń wycieczkę
      </button>

      <button @click="generateInvite" :disabled="generating">
        {{ generating ? 'Generowanie...' : 'Zaproś znajomych' }}
      </button>
      <h2 class="text-4xl font-bold text-purple-600">
        Użytkownicy:
      </h2>
      <ul>
        <li v-for="user in trips.trip_users" :key="user.id">
          {{ user.name }}

          <button @click="flagUser(user.id)" class="bg-yellow-500 text-white px-3 py-1 rounded mb-4">
            Zgłoś Użytkownika
          </button>

        </li>
      </ul>

      <h2 class="text-4xl font-bold text-purple-600">
        Zadania
      </h2>
      <router-link :to="{ name: 'CreateTask', params: { id: trips.id } }">Utwórz zadanie</router-link>
      <div v-for="task in trips.tasks" :key="task.id" class="!border-t-0 text-lg">
        <div v-if="task.can_edit_task">
          <router-link
              :to="{ name: 'EditTask', params: { id: task.id } }">
            <p><strong>Zadanie:</strong> {{ task.title }}</p>
            <p><strong>Przydzielone do:</strong></p>
            <ul>
              <li v-for="user in task.task_users" :key="user.id">
                {{ user.name }}
              </li>
            </ul>
            <p><strong>Priorytet: </strong> {{ task.priority }}</p>
            <p><strong>Deadline: </strong> {{ task.deadline }}</p>
            <p><strong>Ukończone: </strong> {{ task.completed }}</p>
          </router-link>

          <button @click="deleteTask(task.id)" class="bg-red-500 text-white px-2 py-1 rounded">
            Usuń
          </button>
        </div>

        <div v-else>
          <p><strong>Zadanie:</strong> {{ task.title }}</p>
          <p><strong>Przydzielone do:</strong></p>
          <ul>
            <li v-for="user in task.task_users" :key="user.id">
              {{ user.name }}
            </li>
          </ul>
          <p><strong>Priorytet: </strong> {{ task.priority }}</p>
          <p><strong>Deadline: </strong> {{ task.deadline }}</p>
          <p><strong>Ukończone: </strong> {{ task.completed }}</p>
        </div>

        <button @click="flagTask(task.id)" class="bg-yellow-500 text-white px-3 py-1 rounded mb-4">
          Zgłoś zadanie
        </button>
      </div>
      <router-link
          to="/trips"
          class="inline-block mt-4 text-purple-600 hover:underline">
        ← Powrót do listy
      </router-link>
    </div>
  </div>
</template>