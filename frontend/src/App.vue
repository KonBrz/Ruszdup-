<template>
  <div>
    <nav class="fixed top-0 left-0 w-full p-4 text-white bg-gray-900 z-50">
      <div class="container flex justify-between mx-auto">
        <router-link to="/" class="flex items-center gap-2 font-bold">
          <img src="/logo.svg" alt="RuszDupe" class="h-8 w-auto object-contain" />
        </router-link>
        <div class="flex items-center space-x-4">
          <router-link to="/trips" class="hover:text-gray-300">Wycieczki</router-link>
          <router-link to="/tasks" class="hover:text-gray-300">Zadania</router-link>
          <router-link to="/ai-advice" class="hover:text-gray-300">Porady AI</router-link>

          <template v-if="authStore.user">
            <router-link to="/dashboard" class="hover:text-gray-300">Panel</router-link>
            <button @click="handleLogout" data-testid="nav-logout" class="hover:text-gray-300">Wyloguj</button>
          </template>
          <template v-else>
            <router-link to="/login" class="hover:text-gray-300">Login</router-link>
            <router-link to="/register" data-testid="nav-register" class="hover:text-gray-300">Rejestracja</router-link>
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