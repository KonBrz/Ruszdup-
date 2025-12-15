<script setup>
import { ref, nextTick } from 'vue';
import axios from '@/api/axios';

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
    messages.value.push({ role: 'assistant', content: 'Przepraszam, nie udało się połączyć z serwerem.' });
  } finally {
    loading.value = false;
    await scrollToBottom();
  }
};
</script>

<template>
  <div class="container p-8 mx-auto h-[calc(100vh-80px)] flex flex-col">
    <h1 class="mb-6 text-3xl font-bold text-purple-600">Porady od AI</h1>
    
    <div ref="messagesContainer" class="flex-1 bg-white rounded-lg shadow-md p-4 overflow-y-auto mb-4 border border-gray-200">
      <div 
        v-for="(msg, index) in messages" 
        :key="index" 
        class="mb-4 flex"
        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
      >
        <div 
          class="p-3 rounded-lg max-w-[80%] whitespace-pre-wrap"
          :class="msg.role === 'user' ? 'bg-purple-600 text-white rounded-br-none' : 'bg-gray-100 text-gray-800 rounded-bl-none'"
        >
          {{ msg.content }}
        </div>
      </div>
      <div v-if="loading" class="text-gray-500 italic ml-2">AI pisze...</div>
    </div>

    <form @submit.prevent="sendMessage" class="flex gap-2">
      <input 
        v-model="userInput" 
        type="text" 
        class="flex-1 border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-purple-600"
        placeholder="Zapytaj o plan wycieczki..." 
      />
      <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition" :disabled="loading">Wyślij</button>
    </form>
  </div>
</template>
