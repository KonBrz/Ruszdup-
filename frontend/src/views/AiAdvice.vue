<script setup>
import { ref, nextTick, onMounted } from 'vue';
import axios from '@/api/axios';
import Granim from 'granim';
import forestImg from '@/assets/forest2.jpg';

const messages = ref([
  { role: 'assistant', content: 'Cześć! Jestem Twoim inteligentnym doradcą podróży. W czym mogę Ci pomóc przy planowaniu wyjazdu?' }
]);
const userInput = ref('');
const loading = ref(false);
const messagesContainer = ref(null);

const scrollToBottom = async () => {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const sendMessage = async () => {
  if (!userInput.value.trim() || loading.value) return;

  const text = userInput.value;
  userInput.value = '';
  messages.value.push({ role: 'user', content: text });
  await scrollToBottom();

  loading.value = true;

  try {
    // Wysłanie zapytania do backendu (wymagany endpoint /api/ai-chat)
    const response = await axios.post('/api/ai-chat', { prompt: text });
    
    // Zakładamy, że backend zwraca pole 'response' lub 'reply'
    const reply = response.data.response || response.data.reply || 'Otrzymano pustą odpowiedź.';
    messages.value.push({ role: 'assistant', content: reply });
  } catch (e) {
    console.error(e);
    // Pobierz komunikat z backendu (pole 'response' z kontrolera lub 'message' z Laravela)
    const errorMsg = e.response?.data?.response || e.response?.data?.message || 'Przepraszam, nie udało się połączyć z serwerem.';
    messages.value.push({ role: 'assistant', content: errorMsg });
  } finally {
    loading.value = false;
    await scrollToBottom();
  }
};

onMounted(() => {
  try {
    new Granim({
      element: '#granim-canvas',
      name: 'granim-ai-advice',
      direction: 'top-bottom',
      isPausedWhenNotInView: true,
      image: {
        source: forestImg,
        blendingMode: 'hard-light'
      },
      states: {
        'default-state': {
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
  } catch (err) {
    // jeśli Granim nie jest zainstalowany lub wystąpi błąd, nie przerywamy działania czatu
    // błąd logujemy do konsoli
    // eslint-disable-next-line no-console
    console.warn('Granim initialization failed:', err);
  }
});
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Tło animowane -->
    <canvas id="granim-canvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <!-- Kontener główny w motywie CreateTask -->
    <div class="relative min-h-screen flex items-start justify-center z-10 pt-24">
      <div class="w-4/5 max-w-2xl bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg">

        <h1 class="text-3xl font-bold text-violet-300 mb-6 text-center">Porady od AI</h1>

        <div ref="messagesContainer" class="space-y-2 bg-gray-800 p-4 rounded-lg border border-gray-700 h-64 overflow-auto mb-4">
          <div v-for="(m, idx) in messages" :key="idx" class="mb-2">
            <div v-if="m.role === 'assistant'" class="text-left text-gray-200">{{ m.content }}</div>
            <div v-else class="text-right text-violet-300">{{ m.content }}</div>
          </div>
          <div v-if="loading" class="text-gray-400 italic">AI pisze...</div>
        </div>

        <form @submit.prevent="sendMessage" class="flex gap-3">
          <input v-model="userInput" @keyup.enter="sendMessage" class="flex-1 p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-violet-600" placeholder="Napisz wiadomość..." />
          <button type="submit" :disabled="loading" class="px-4 py-3 bg-violet-800 hover:bg-violet-950 transition text-white rounded-lg">
            {{ loading ? 'Wysyłanie...' : 'Wyślij' }}
          </button>
        </form>

        <div class="mt-4 text-center">
          <small class="text-gray-500">Wiadomości są wysyłane do serwera AI</small>
        </div>

      </div>
    </div>
  </div>
</template>