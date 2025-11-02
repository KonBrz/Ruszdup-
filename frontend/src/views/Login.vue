<template>
  <form @submit.prevent="handleLogin" class="max-w-md p-8 mx-auto mt-10 bg-white rounded-lg shadow-md">
    <h1 class="mb-6 text-2xl font-bold text-center">Zaloguj się</h1>
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
    <button type="submit">Zaloguj</button>
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
  email: '',
  password: '',
});
const errors = reactive({
  email: [],
  password: [],
  general: ''
});

async function handleLogin() {
  Object.keys(errors).forEach(key => errors[key] = Array.isArray(errors[key]) ? [] : '');
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
</script>
