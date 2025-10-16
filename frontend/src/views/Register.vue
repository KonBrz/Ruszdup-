<template>
  <form @submit.prevent="handleRegister" class="max-w-md p-8 mx-auto mt-10 bg-white rounded-lg shadow-md">
    <h1 class="mb-6 text-2xl font-bold text-center">Zarejestruj się</h1>
    <div>
      <label for="name">Nazwa użytkownika</label>
      <input type="text" v-model="form.name" id="name" required>
      <p v-if="errors.name" class="text-sm text-red-500">{{ errors.name[0] }}</p>
    </div>
    <div>
      <label for="email">Email</label>
      <input type="email" v-model="form.email" id="email" required>
      <p v-if="errors.email" class="text-sm text-red-500">{{ errors.email[0] }}</p>
    </div>
    <div>
      <label for="password">Hasło</label>
      <input type="password" v-model="form.password" id="password" required>
      <p v-if="errors.password" class="text-sm text-red-500">{{ errors.password[0] }}</p>
    </div>
    <div>
      <label for="password_confirmation">Potwierdź hasło</label>
      <input type="password" v-model="form.password_confirmation" id="password_confirmation" required>
    </div>
    <button type="submit">Zarejestruj</button>
    <p v-if="errors.general" class="mt-4 text-red-500">{{ errors.general }}</p>
  </form>
</template>

<script setup lang="ts">
import { reactive, ref, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});
const errors = reactive({
  name: [],
  email: [],
  password: [],
  general: ''
});

async function handleRegister() {
  Object.keys(errors).forEach(key => errors[key] = Array.isArray(errors[key]) ? [] : '');

  if (form.value.password !== form.value.password_confirmation) {
    errors.password = ['Hasła nie są identyczne.'];
    return;
  }

  try {
    await authStore.register(form.value);
    // Poczekaj na następny cykl renderowania, aby upewnić się, że stan store'a jest zaktualizowany
    await nextTick(() => router.push('/dashboard'));
  } catch (e: any) {
    if (e.response && e.response.status === 422) {
      Object.assign(errors, e.response.data.errors);
    } else {
      errors.general = 'Wystąpił błąd podczas rejestracji.';
    }
    console.error(e);
  }
}
</script>
