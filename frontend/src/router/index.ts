import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import Trips from '../views/Trip.vue';
import Tasks from '../views/Tasks.vue';
import AiAdvice from '../views/AiAdvice.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    {
      path: '/login',
      name: 'Login',
      component: Login,
    },
    {
      path: '/register',
      name: 'Register',
      component: Register,
    },
    {
      path: '/trips',
      name: 'Trips',
      component: Trips,
    },
    {
      path: '/tasks',
      name: 'Tasks',
      component: Tasks,
    },
    {
      path: '/ai-advice',
      name: 'AiAdvice',
      component: AiAdvice,
    },
  ]
})

export default router