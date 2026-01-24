import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'

import App from '@/App.vue'

describe('App', () => {
    it('mounts without crashing', async () => {
        const pinia = createPinia()
        setActivePinia(pinia)

        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/', component: { template: '<div>Home</div>' } },
                { path: '/login', component: { template: '<div>Login</div>' } },
                { path: '/register', component: { template: '<div>Register</div>' } },
                { path: '/dashboard', component: { template: '<div>Dashboard</div>' } },
                { path: '/trips', component: { template: '<div>Trips</div>' } },
                { path: '/tasks', component: { template: '<div>Tasks</div>' } },
                { path: '/ai-advice', component: { template: '<div>AI Advice</div>' } },
            ],
        })

        await router.push('/')
        await router.isReady()

        mount(App, {
            global: {
                plugins: [pinia, router],
            },
        })

        expect(true).toBe(true)
    })
})
