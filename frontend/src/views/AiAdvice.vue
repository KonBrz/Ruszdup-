<template>
  <div class="relative min-h-screen">
    <!-- Tło animowane -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Kontener główny -->
    <div class="relative min-h-screen flex items-center justify-center z-10">
      <div class="w-4/5 max-w-2xl bg-gray-900 text-gray-100 p-10 rounded-xl shadow-xl text-center">
        <h1 class="text-4xl font-bold text-violet-300">Porady od AI</h1>
        <p class="mt-4 text-gray-300 text-lg">Sekcja w budowie ✨</p>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">
import {onMounted} from 'vue';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

onMounted(async () => {
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
});
</script>