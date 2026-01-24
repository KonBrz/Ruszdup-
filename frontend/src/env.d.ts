/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue'
    const component: DefineComponent<{}, {}, any>
    export default component
}

declare module 'granim'

declare module '@/api/axios' {
    const apiClient: any
    export default apiClient
}
