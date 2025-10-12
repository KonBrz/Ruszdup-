import { createApp } from 'vue'
import App from './App.vue'
import router from './router' // Importuj router
import './style.css' // Importuj style Tailwind

const app = createApp(App)

app.use(router) // Użyj routera w aplikacji

app.mount('#app')
