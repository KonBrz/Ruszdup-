<script setup>
import {ref} from 'vue';
import axios from '@/api/axios';

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
</script>

<template>
  <div class="container p-8 mx-auto">
    <h1 class="mb-6 text-3xl font-bold text-purple-600">Utwórz nową wycieczkę</h1>

    <div v-if="success" class="p-3 mb-4 text-green-700 bg-green-200 rounded">
      {{ success }}
    </div>
    <div v-if="error" class="p-3 mb-4 text-red-700 bg-red-200 rounded">
      {{ error }}
    </div>

    <form @submit.prevent="submitForm" class="space-y-6 bg-white p-6 rounded-lg shadow-md">

      <div>
        <label class="block font-medium text-gray-700">Tytuł</label>
        <input v-model="form.title" type="text" class="w-full p-2 mt-1 border rounded" required>
      </div>

      <div>
        <label class="block font-medium text-gray-700">Cel podróży (destination)</label>
        <input v-model="form.destination" type="text" class="w-full p-2 mt-1 border rounded" required>
      </div>

      <div>
        <label class="block font-medium text-gray-700">Opis (opcjonalne)</label>
        <textarea v-model="form.description" class="w-full p-2 mt-1 border rounded"></textarea>
      </div>

      <div>
        <label class="block font-medium text-gray-700">Data rozpoczęcia</label>
        <input v-model="form.start_date" type="date" class="w-full p-2 mt-1 border rounded" required>
      </div>

      <div>
        <label class="block font-medium text-gray-700">Data zakończenia</label>
        <input v-model="form.end_date" type="date" class="w-full p-2 mt-1 border rounded" required>
      </div>

      <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 font-semibold text-white bg-purple-600 rounded hover:bg-purple-700 disabled:opacity-50"
      >
        {{ loading ? 'Wysyłanie...' : 'Utwórz wycieczkę' }}
      </button>
    </form>
    <router-link
        to="/trips"
        class="inline-block mt-4 text-purple-600 hover:underline">
      ← Powrót do listy
    </router-link>
  </div>
</template>