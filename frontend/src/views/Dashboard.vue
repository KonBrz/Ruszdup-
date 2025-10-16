<template>
  <div class="container p-8 mx-auto">
    <div v-if="authStore.user" class="p-8 text-center bg-white rounded-lg shadow-md">
      <h1 class="text-4xl font-bold">Witaj, {{ authStore.user.name }}!</h1>
      <p class="mt-4 text-lg">Jesteś w swoim panelu.</p>
      <p class="mt-2 text-gray-600">Twój email: {{ authStore.user.email }}</p>
    </div>
    <div v-else class="text-center">
      <p>Ładowanie danych użytkownika...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
</script>
