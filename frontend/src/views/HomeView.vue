<script setup>
import { onMounted } from 'vue';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg'; // ścieżka do obrazu

onMounted(() => {
  const granimInstance = new Granim({
    element: '#granim-canvas',
    name: 'granim',
    direction: 'top-bottom',
    isPausedWhenNotInView: true,
    image : {
      source: forestImg,
      blendingMode: 'hard-light', // blendowanie z gradientem
    },
    states : {
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

<template>
  <div class="min-h-screen relative overflow-hidden bg-gray-900 flex items-start justify-center pt-28">

    <!-- animowane tło (tu będzie Granim) -->
    <div class="absolute inset-0">
      <canvas id="granim-canvas" class="w-full h-full"></canvas>
    </div>

    <!-- treść -->
    <div class="relative text-center">
      <h1 class="text-5xl font-bold text-violet-200 drop-shadow-lg">
        Witaj w RuszDupe!
      </h1>
      <p class="mt-3 text-xl text-gray-300">
        Zacznij planować swoje wycieczki już dziś.
      </p>
    </div>

  </div>
</template>
