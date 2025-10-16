<template>
  <div>
    <nav class="p-4 text-white bg-gray-800">
      <div class="container flex justify-between mx-auto">
        <router-link to="/" class="font-bold">RuszDupe logo</router-link>
        <div class="flex items-center space-x-4">
          <router-link to="/trips" class="hover:text-gray-300">Wycieczki</router-link>
          <router-link to="/tasks" class="hover:text-gray-300">Zadania</router-link>
          <router-link to="/ai-advice" class="hover:text-gray-300">Porady AI</router-link>

          <template v-if="authStore.user">
            <router-link to="/dashboard" class="hover:text-gray-300">Panel</router-link>
            <button @click="handleLogout" class="hover:text-gray-300">Wyloguj</button>
          </template>
          <template v-else>
            <router-link to="/login" class="hover:text-gray-300">Login</router-link>
            <router-link to="/register" class="hover:text-gray-300">Rejestracja</router-link>
          </template>
        </div>
      </div>
    </nav>

    <!-- Tutaj będą renderowane Twoje widoki -->
    <main>
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>