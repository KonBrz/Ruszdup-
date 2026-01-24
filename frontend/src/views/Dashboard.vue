<template>
  <div class="relative min-h-screen">
    <!-- Tło -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <div class="relative min-h-screen flex items-start justify-center z-10 pt-16">
      <div class="w-4/5 bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg">

        <!-- Jeśli zalogowany -->
        <div class="bg-gradient-to-r from-violet-950 via-violet-900 to-gray-900 p-8 rounded-xl shadow-lg">

          <h1 class="text-4xl font-bold text-violet-200 text-center" data-testid="dashboard-loaded">
            Witaj, {{ authStore.user?.name ?? '' }}!
          </h1>

          <p class="mt-4 text-lg text-center text-gray-300">
            Miło Cię widzieć w swoim panelu użytkownika 👋
          </p>

          <div class="mt-6 bg-gray-800 p-6 rounded-xl shadow-md space-y-2">
            <p class="text-gray-200">
              <strong>Email:</strong> {{ authStore.user?.email ?? '' }}
            </p>
          </div>

          <router-link
              to="/trips"
              data-testid="dashboard-trips-link"
              class="inline-block mt-6 px-4 py-2 bg-violet-800 hover:bg-violet-950 text-white rounded-lg shadow transition"
          >
            Przejdź do wycieczek →
          </router-link>

        </div>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import {onBeforeUnmount, onMounted} from "vue";
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const authStore = useAuthStore();

let granimInstance: { destroy?: () => void; pause?: () => void } | null = null;

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
