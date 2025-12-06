<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "@/api/axios";

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

onMounted(() => fetchTrip());
</script>

<template>
  <div class="container mx-auto p-8">
    <div v-if="loading" class="text-center">Ładowanie...</div>
    <div v-if="error" class="text-center text-red-500">{{ error }}</div>

    <div v-if="trip">
      <h1 class="text-3xl font-bold text-purple-600 mb-6">Edytuj wycieczkę</h1>

      <form @submit.prevent="saveTrip" class="space-y-4">
        <div>
          <label class="block mb-1 font-semibold">Tytuł:</label>
          <input
              v-model="form.title"
              type="text"
              class="border p-2 rounded w-full"
          />
        </div>

        <div>
          <label class="block mb-1 font-semibold">Opis:</label>
          <textarea
              v-model="form.description"
              class="border p-2 rounded w-full"
          ></textarea>
        </div>

        <div>
          <label class="block mb-1 font-semibold">Cel podróży:</label>
          <input
              v-model="form.destination"
              type="text"
              class="border p-2 rounded w-full"
          />
        </div>

        <div>
          <label class="block mb-1 font-semibold">Data rozpoczęcia:</label>
          <input
              v-model="form.start_date"
              type="date"
              class="border p-2 rounded w-full"
          />
        </div>

        <div>
          <label class="block mb-1 font-semibold">Data zakończenia:</label>
          <input
              v-model="form.end_date"
              type="date"
              class="border p-2 rounded w-full"
          />
        </div>

        <button
            type="submit"
            :disabled="saving"
            class="bg-purple-600 text-white px-4 py-2 rounded"
        >
          {{ saving ? "Zapisywanie..." : "Zapisz zmiany" }}
        </button>
      </form>

      <router-link
          :to="`/trips/${trip.id}`"
          class="inline-block mt-4 text-purple-600 hover:underline"
      >
        ← Powrót do szczegółów wycieczki
      </router-link>
    </div>
  </div>
</template>