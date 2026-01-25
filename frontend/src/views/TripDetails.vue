<script setup>
import {ref, onMounted, onBeforeUnmount} from 'vue';
import {useRoute} from 'vue-router';
import axios from '@/api/axios';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';
import router from "../router/index.js";
import {useAuthStore} from "../stores/auth.js";

const route = useRoute();
const authStore = useAuthStore();

const trips = ref(null);
const loading = ref(true);
const saving = ref(false);
const error = ref(null);

const currentUser = authStore.user;

const form = ref({
  completed: false,
  ignored: false
});

const showTripMenu = ref(false);
const showTripDetails = ref(false)
const inviteLink = ref('');
const generating = ref(false);
const inviteError = ref('');
const token = new URLSearchParams(window.location.search).get('invite_token');
const aiResponse = ref('');
const askingAi = ref(false);

const fetchTrips = async () => {
  try {
    const response = await axios.get(`/api/trips/${route.params.id}`);
    trips.value = response.data;
    console.log('Response', response.data);
    trips.value.tasks.forEach(task => {
      const pivot = task.task_users.find(u => u.id === currentUser.id)?.pivot || {};
      task.currentUser = {
        completed: Boolean(pivot?.completed),
        ignored: Boolean(pivot?.ignored)
      };
    });
  } catch (e) {
    console.error(e);
    error.value = "Nie udało się pobrać szczegółów wycieczki.";
  } finally {
    loading.value = false;
  }
};

const updateTaskStatus = async (task) => {
  saving.value = true;

  try {
    await axios.put(`/api/tasks/update/${task.id}`, {
      completed: task.currentUser.completed ? 1 : 0,
      ignored: task.currentUser.ignored ? 1 : 0
    });
    await fetchTrips();

    alert("Zaktualizowano zadanie!");

    router.push({
      name: 'Trips',
      params: {id: trips.value.id},
      hash: '#task-' + task.id
    });

    setTimeout(() => router.go(0), 20);
  } catch (e) {
    console.error(e);
    error.value = "Nie udało się zapisać zmian.";
  } finally {
    saving.value = false;
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

const deleteUser = async (userId) => {
  if (!confirm("Na pewno chcesz usunąć użytkownika z wycieczki?")) return;

  try {
    await axios.delete(`/api/trips/${route.params.id}/deleteuser/${userId}`);
    await fetchTrips();
  } catch (e) {
    console.error(e);
    alert('Nie udało się usunąć użytkownika z wycieczki.');
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

const askAI = async () => {
  if (!trips.value) return;

  askingAi.value = true;
  try {
    const existingTasks = trips.value.tasks.map(t => t.title).join(', ');
    const response = await axios.post('/api/ai-chat', {
      prompt: `Zaproponuj jedno konkretne zadanie przygotowawcze do wycieczki do: ${trips.value.destination || 'nieznane miejsce'}.
      ${existingTasks ? `Unikaj duplikatów z tej listy zadań: ${existingTasks}.` : ''}
      Odpowiedz samym tytułem zadania, bez zbędnych opisów.`,
    });
    const suggestion = response.data.response || response.data.reply;
    const cleanSuggestion = suggestion ? suggestion.replace(/^["']|["']$/g, '').trim() : '';
    const exists = trips.value.tasks.some(t => t.title.toLowerCase() === cleanSuggestion.toLowerCase());

    if (exists) {
      alert(`AI zasugerowało "${cleanSuggestion}", ale to zadanie już istnieje.`);
    } else if (cleanSuggestion && confirm(`AI sugeruje zadanie: "${cleanSuggestion}".\nCzy chcesz je utworzyć?`)) {
      await axios.post('/api/tasks', {
        trip_id: trips.value.id,
        title: cleanSuggestion,
        priority: 'średni'
      });
      await fetchTrips();
      alert('Zadanie zostało utworzone.');
    }
  } catch (e) {
    console.error(e);
    alert('Nie udało się uzyskać pomocy od AI.');
  } finally {
    askingAi.value = false;
  }
};

const closeAllMenus = (e) => {
  if (!e.target.closest(".trip-menu-container")) {
    showTripMenu.value = false;
  }

  if (trips.value) {
    trips.value.trip_users.forEach(user => {
      if (!e.target.closest(`#user-menu-${user.id}`)) {
        user.showMenu = false;
      }
    });
  }

  if (trips.value) {
    trips.value.tasks.forEach(task => {
      if (!e.target.closest(`#task-menu-${task.id}`)) {
        task.showMenu = false;
      }
    });
  }
};


const getTaskProgress = (task) => {
  const assigned = task.task_users.length;
  if (!assigned) return 0;

  const done = task.task_users.filter(u =>
      u.pivot.completed === 1 || u.pivot.ignored === 1
  ).length;

  return Math.round((done / assigned) * 100);
};

const getTripProgress = () => {
  if (!trips.value || !trips.value.tasks.length) return 0;

  const taskPercents = trips.value.tasks.map(t => getTaskProgress(t));
  const avg = taskPercents.reduce((a, b) => a + b, 0) / taskPercents.length;
  return Math.round(avg);
};

onMounted(async () => {
  document.addEventListener('click', closeAllMenus);
  const token = new URLSearchParams(window.location.search).get('invite_token');
  const hash = window.location.hash.replace('#', '');

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

  if (hash) {
    const el = document.querySelector(`[data-anchor="${hash}"]`);
    if (el) el.scrollIntoView({behavior: 'smooth', block: 'center'});
  }

  if (token) {
    try {
      await axios.post('/api/trip-invite/accept', {token});
      alert('Dołączyłeś do wycieczki!');
      router.replace(`/trips/${route.params.id}`);
    } catch (e) {
      console.error(e);
      alert('Nie udało się dołączyć.');
    }
  }
  fetchTrips();
});
onBeforeUnmount(() => {
  document.removeEventListener('click', closeAllMenus);
});
</script>

<template>
  <div class="relative min-h-screen" data-testid="trip-details">
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <div class="relative min-h-screen flex items-start justify-center z-10 pt-4">
      <div v-if="trips && (trips.trip_users ?? []).some(u => u.id === currentUser.id)"
           class="w-full p-2 md:w-11/12 md:p-6 min-h-screen bg-gray-900 text-gray-100 rounded-xl shadow-lg">

        <!-- Nagłówek wycieczki -->
        <div class="relative bg-gradient-to-r from-violet-950 via-violet-900 to-gray-900 p-6 rounded-xl shadow-lg mb-6">
          <h1 class="text-3xl md:text-4xl font-bold" data-testid="trip-details-title">{{ trips.title }}</h1>
          <p class="text-gray-300 mt-1"><strong>{{ trips.start_date }}</strong> – <strong>{{ trips.end_date }}</strong>
          </p>
          <div class="mt-4">
            <div class="flex justify-between text-sm text-gray-300 mb-1"><span>Stopień przygotowania:</span>
              <span>{{ getTripProgress() }}%</span></div>
            <div class="w-full bg-gray-700 rounded h-4 shadow-inner">
              <div class="h-4 bg-violet-800 rounded" :style="{ width: getTripProgress() + '%' }"></div>
            </div>
          </div>

          <button @click="showTripDetails = !showTripDetails"
                  class="bg-violet-800 hover:bg-violet-950 text-white px-3 py-1 transition rounded text-sm w-max mt-1">
            Rozwiń szczegóły
          </button>

          <div :class="['overflow-hidden transition-all duration-200 mt-2', showTripDetails ? 'max-h-96' : 'max-h-0']">
            <p class="text-gray-100 mb-1"><strong>Utworzył:</strong> {{ trips.user?.name }}</p>
            <p class="text-gray-100 mb-1"><strong>Opis:</strong> {{ trips.description }}</p>
            <p class="text-gray-100 mb-1"><strong>Cel podróży:</strong> {{ trips.destination }}</p>
            <div v-if="inviteLink"><p>Token zaproszenia: <span data-testid="trip-invite-token">{{ inviteLink }}</span></p></div>
            <div v-if="inviteError" class="text-red-500"> {{ inviteError }}</div>
          </div> <!-- Menu 3-kropki dla wycieczki -->
          <div class="trip-menu-container absolute top-6 right-6">
            <button
                @click="showTripMenu = !showTripMenu"
                data-testid="trip-menu-button"
                class="px-2 py-1 text-gray-300 hover:text-white"
            >⋮</button>
            <div v-if="showTripMenu"
                 data-testid="trip-menu-dropdown"
                 class="absolute right-0 mt-1 bg-gray-900 border border-gray-700 rounded shadow-md text-sm z-10">
              <router-link
                  v-if="trips.can_edit_trip"
                  :to="{ name: 'EditTrip', params: { id: trips.id } }"
                  data-testid="trip-edit-link"
                  class="block px-4 py-2 hover:bg-gray-700 w-full text-left"
              >Edytuj
              </router-link>
              <button
                  v-if="trips.can_edit_trip"
                  @click="deleteTrip(trips.id)"
                  data-testid="trip-delete-button"
                  class="block px-4 py-2 hover:bg-gray-700 w-full text-left"
              >Usuń
              </button>
              <button
                  v-if="!trips.can_edit_trip"
                  @click="flagTrip"
                  data-testid="trip-flag"
                  class="block px-4 py-2 hover:bg-gray-700 w-full text-left"
              >Zgłoś
              </button>
              <button
                      @click="generateInvite"
                      :disabled="generating"
                      data-testid="trip-invite"
                      class="flex items-center justify-center px-4 py-2 hover:bg-gray-700 w-full text-left">
                <svg v-if="generating" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ generating ? 'Generowanie...' : 'Zaproś znajomych' }}
              </button>
            </div>
          </div>
        </div>
        <!-- Layout: Użytkownicy / Zadania -->
        <div class="flex flex-col md:flex-row gap-6 mt-6">

          <!-- Użytkownicy -->
          <div class="w-full md:w-1/3 bg-gray-800 p-4 rounded-xl shadow-md space-y-3">
            <h2 class="text-lg font-sans font-medium italic text-violet-200 mb-2">Użytkownicy</h2>
            <ul class="space-y-2">
              <li v-for="user in trips.trip_users" :key="user.id"
                  class="flex justify-between items-center p-2 bg-gray-700 rounded hover:bg-gray-600 transition">
                <span>{{ user.name }}</span>
                <div class="relative">
                  <button
                          @click="user.showMenu = !user.showMenu"
                          :data-testid="`user-menu-${user.id}`"
                          class="px-2 py-1 text-gray-300 hover:text-white"
                          :id="'user-menu-' + user.id">⋮
                  </button>
                  <div v-if="user.showMenu"
                       class="absolute right-0 mt-1 bg-gray-900 border border-gray-700 rounded shadow-md text-sm z-10">
                    <button
                        @click="flagUser(user.id)"
                        :data-testid="`user-flag-${user.id}`"
                        class="block px-4 py-2 hover:bg-gray-700 w-full text-left"
                    >Zgłoś
                    </button>
                    <button
                            v-if="trips.can_edit_trip && user.id !== currentUser.id"
                            @click="deleteUser(user.id)"
                            :data-testid="`user-remove-${user.id}`"
                            class="block px-4 py-2 hover:bg-gray-700 w-full text-left"
                    >Usuń
                    </button>
                  </div>
                </div>
              </li>
            </ul>
          </div>

          <!-- Zadania -->
          <div class="w-full md:w-2/3 bg-gray-800 p-4 rounded-xl shadow-md space-y-4" data-testid="trip-tasks-list">
            <div class="flex justify-between items-center mb-2">
              <h2 class="text-lg font-sans font-medium italic text-violet-200">Zadania</h2>
              <div class="flex gap-2 flex-wrap">
                <router-link :to="{ name: 'CreateTask', params: { id: trips.id } }"
                             data-testid="trip-add-task"
                             class="bg-violet-800 text-white px-3 py-1 rounded hover:bg-violet-950 transition text-sm">
                  Dodaj zadanie
                </router-link>
                <button @click="askAI" :disabled="askingAi"
                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                  <svg v-if="askingAi" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ askingAi ? 'AI myśli...' : 'Zapytaj AI o pomoc' }}
                </button>
              </div>
            </div>

            <div v-for="task in trips.tasks" :key="task.id"
                 class="p-4 rounded-lg bg-gray-700 hover:bg-gray-600 transition relative" :id="'task-menu-' + task.id"
                 :data-testid="`trip-task-item-${task.id}`"
                 data-anchor="task-123">
              <div class="flex justify-between items-start">
                <div>
                  <div class="w-full bg-gray-700 rounded h-3 mt-2">
                    <div
                        class="h-3 bg-violet-800 rounded"
                        :style="{ width: getTaskProgress(task) + '%' }"
                    ></div>
                  </div>
                  <p class="text-xs text-gray-400 mt-1">
                    {{ getTaskProgress(task) }}%
                  </p>
                  <p class="font-semibold text-white text-lg mb-1" data-testid="trip-task-title">Zadanie: {{ task.title }}</p>
                  <div class="flex flex-wrap gap-1 mb-1">
                <span v-for="user in task.task_users" :key="user.id"
                      class="bg-gray-600 px-2 py-1 rounded text-sm">{{ user.name }}</span>
                  </div>
                  <p class="text-sm" :class="{
                'text-green-400': task.priority === 'niski',
                'text-yellow-400': task.priority === 'średni',
                'text-red-400': task.priority === 'wysoki'
              }">Priorytet: {{ task.priority }}</p>
                  <p class="text-sm text-gray-300">Deadline: {{ task.deadline }}</p>
                </div>

                <!-- Menu 3-kropki dla zadania -->
                <div class="relative">
                  <button
                      @click="task.showMenu = !task.showMenu"
                      :data-testid="`task-menu-${task.id}`"
                      class="px-2 py-1 text-gray-300 hover:text-white"
                  >⋮
                  </button>
                  <div v-if="task.showMenu"
                       class="absolute right-0 mt-1 bg-gray-900 border border-gray-700 rounded shadow-md text-sm z-10">
                    <router-link v-if="task.can_edit_task" :to="{ name: 'EditTask', params: { id: task.id } }"
                                 class="block px-4 py-2 hover:bg-gray-700 w-full text-left">Edytuj
                    </router-link>
                    <button @click="deleteTask(task.id)" class="block px-4 py-2 hover:bg-gray-700 w-full text-left">Usuń
                    </button>
                    <button
                        @click="flagTask(task.id)"
                        :data-testid="`task-flag-${task.id}`"
                        class="block px-4 py-2 hover:bg-gray-700 w-full text-left"
                    >Zgłoś
                    </button>
                  </div>
                </div>
              </div>

              <!-- Checkboxy stylizowane -->
              <form v-if="task.task_users.some(u => u.id === currentUser.id)" @submit.prevent="updateTaskStatus(task)"
                    class="flex flex-col gap-2 mt-2">
                <label class="flex items-center gap-2">
                  <input
                      type="checkbox"
                      v-model="task.currentUser.completed"
                      @change="() => { if (task.currentUser.completed) task.currentUser.ignored = false; }"
                      :data-testid="`task-completed-${task.id}`"
                      class="peer w-5 h-5 rounded-md border border-violet-700 bg-gray-900 appearance-none transition-all duration-200 checked:bg-violet-600 checked:border-violet-800"/>
                  <svg
                      class="absolute pointer-events-none w-5 h-5 text-white scale-0 transition-transform duration-200 peer-checked:scale-100"
                      fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"
                  >
                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Ukończone
                </label>
                <label class="flex items-center gap-2">
                  <input
                      type="checkbox"
                      v-model="task.currentUser.ignored"
                      @change="() => { if (task.currentUser.ignored) task.currentUser.completed = false; }"
                      :data-testid="`task-ignored-${task.id}`"
                      class="peer w-5 h-5 rounded-md border border-violet-700 bg-gray-900 appearance-none transition-all duration-200 checked:bg-violet-600 checked:border-violet-800"/>
                  <svg
                      class="absolute pointer-events-none w-5 h-5 text-white scale-0 transition-transform duration-200 peer-checked:scale-100"
                      fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"
                  >
                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Zignoruj
                </label>
                <button
                    type="submit"
                    :disabled="saving"
                    :data-testid="`task-save-${task.id}`"
                        class="bg-violet-800 hover:bg-violet-950 text-white px-3 py-1 transition rounded text-sm w-max mt-1">Zapisz
                  zmiany
                </button>
              </form>
            </div>
          </div>
        </div>
        <router-link to="/trips" class="inline-block mt-4 text-purple-400 hover:underline">← Powrót do listy
        </router-link>
      </div>
      <div v-else class="min-h-screen bg-gray-900 text-gray-100 p-6">
        <h1 class="text-red-500 text-center text-2xl mt-10">Nie należysz do wycieczki</h1>
      </div>
    </div>
  </div>
</template>
