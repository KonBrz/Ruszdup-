<template>
  <div>
    <nav class="p-4 text-white bg-gray-800">
      <div class="container flex justify-between mx-auto">
        <router-link to="/" class="font-bold">RuszDupe logo</router-link>
        <div class="space-x-4">
          <router-link to="/trips" class="hover:text-gray-300"
            >Wycieczki</router-link
          >
          <router-link to="/tasks" class="hover:text-gray-300"
            >Zadania</router-link
          >
          <router-link to="/ai-advice" class="hover:text-gray-300"
            >Porady AI</router-link
          >
          <!-- Warunkowe wyświetlanie linków - do implementacji w przyszłości -->
          <router-link to="/login" class="hover:text-gray-300"
            >Login</router-link
          >
          <router-link to="/register" class="hover:text-gray-300"
            >Rejestracja</router-link
          >
        </div>
      </div>
    </nav>

    <!-- Tutaj będą renderowane Twoje widoki -->
    <main>
      <router-view />
    </main>
  </div>
</template>

<script setup>
// W przyszłości możesz tu dodać logikę, np. do sprawdzania stanu zalogowania użytkownika
</script>

<style scoped>
.logo {
  height: 6em;
  padding: 1.5em;
  will-change: filter;
  transition: filter 300ms;
}
.logo:hover {
  filter: drop-shadow(0 0 2em #646cffaa);
}
.logo.vue:hover {
  filter: drop-shadow(0 0 2em #42b883aa);
}
</style>
