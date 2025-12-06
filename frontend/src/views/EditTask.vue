<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/api/axios';
import { useAuthStore } from '@/stores/auth';

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

onMounted(() => fetchTask());
</script>

<template>
  <div class="container p-8 mx-auto">
    <div v-if="loading" class="text-center">Ładowanie...</div>
    <div v-if="error" class="text-center text-red-500">{{ error }}</div>

    <div v-if="task">
      <h1 class="text-3xl font-bold text-purple-600 mb-4">Edytuj zadanie</h1>

      <form @submit.prevent="saveTask" class="space-y-4">
        <div>
          <label class="block mb-1 font-semibold">Zadanie:</label>
          <input v-model="form.title" type="text" class="border p-2 w-full rounded" />
        </div>

        <div>
          <label class="block mb-1 font-semibold">Priorytet:</label>
          <select v-model="form.priority" class="border p-2 w-full rounded">
            <option :value="null">Nie ma priorytetu</option>
            <option value="niski">Niski</option>
            <option value="średni">Średni</option>
            <option value="wysoki">Wysoki</option>
          </select>
        </div>

        <div>
          <label class="block mb-1 font-semibold">Deadline:</label>
          <input v-model="form.deadline" type="date" class="border p-2 w-full rounded" />
        </div>

        <div>
          <label class="inline-flex items-center">
            <input v-model="form.completed" type="checkbox" class="mr-2" />
            Ukończone
          </label>
        </div>

        <div>
          <label class="inline-flex items-center">
            <input v-model="form.ignored" type="checkbox" class="mr-2" />
            Zignoruj
          </label>
        </div>

        <div>
          <button type="submit" :disabled="saving" class="bg-purple-600 text-white px-4 py-2 rounded">
            {{ saving ? 'Zapisywanie...' : 'Zapisz zmiany' }}
          </button>
        </div>
      </form>

      <h2 class="mt-6 text-xl font-semibold text-purple-600">Przydzieleni użytkownicy</h2>
      <ul class="list-disc list-inside">
        <li v-for="user in task.task_users" :key="user.id">
          {{ user.name }}
        </li>
      </ul>

      <div v-for="user in task.trip.trip_users" :key="user.id">
        <label>
          <input
              type="checkbox"
              :value="user.id"
              v-model="form.user_ids"
          >
          {{ user.name }}
        </label>
      </div>

      <router-link
          :to="`/trips/${task.trip_id}`"
          class="inline-block mt-4 text-purple-600 hover:underline"
      >
        ← Powrót do szczegółów wycieczki
      </router-link>
    </div>
  </div>
</template>
