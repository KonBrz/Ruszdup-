<template>
  <div class="relative min-h-screen">
    <!-- Granim -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Centrum -->
    <div class="relative min-h-screen flex items-start justify-center z-10 pt-24">
      <form
          @submit.prevent="handleLogin"
          class="w-4/5 max-w-md bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg"
      >
        <h1 class="text-3xl font-bold text-center mb-6">Zaloguj się</h1>

        <!-- Email -->
        <div class="mb-4">
          <label for="email" class="block mb-1 text-gray-300">Email</label>
          <input
              type="email"
              v-model="form.email"
              id="email"
              data-testid="login-email"
              required
              class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-violet-600"
          >
          <p v-if="errors.email" class="text-sm text-red-500 mt-1">
            {{ errors.email[0] }}
          </p>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label for="password" class="block mb-1 text-gray-300">Hasło</label>
          <input
              type="password"
              v-model="form.password"
              id="password"
              data-testid="login-password"
              required
              class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-violet-600"
          >
          <p v-if="errors.password" class="text-sm text-red-500 mt-1">
            {{ errors.password[0] }}
          </p>
        </div>

        <!-- Submit -->
        <button
            type="submit"
            data-testid="login-submit"
            class="w-full bg-violet-800 hover:bg-violet-950 transition text-white py-2 rounded-lg font-medium shadow-md"
        >
          Zaloguj
        </button>

        <p v-if="errors.general" class="mt-4 text-center text-red-500">
          {{ errors.general }}
        </p>
      </form>
    </div>
  </div>
</template>


<script setup lang="ts">
import {reactive, ref, onBeforeUnmount, onMounted} from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
  email: '',
  password: '',
});
type Errors = {
  email: string[];
  password: string[];
  general: string;
};

const errors = reactive<Errors>({
  email: [],
  password: [],
  general: ''
});

let granimInstance: { destroy?: () => void; pause?: () => void } | null = null;

async function handleLogin() {
  errors.email = [];
  errors.password = [];
  errors.general = '';
  try {
    await authStore.login(form.value);
    if (authStore.user?.is_admin) {
      window.location.href = 'http://localhost:8000/admin'
    } else {
      router.push('/dashboard')
    }

  } catch (e: any) {
    if (e.response && e.response.status === 422) {
      Object.assign(errors, e.response.data.errors);
    } else {
      errors.general = 'Nieprawidłowe dane logowania lub błąd serwera.';
    }
    console.error(e);
  }
}

onMounted(async () => {
  granimInstance = new Granim({
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
});

onBeforeUnmount(() => {
  granimInstance?.destroy?.();
  granimInstance?.pause?.();
  granimInstance = null;
});
</script>
