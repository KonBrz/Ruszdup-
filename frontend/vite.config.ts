import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '')
    const backendUrl = env.VITE_BACKEND_URL || 'http://localhost:8000'

    const shouldBypassToIndex = (req: { method?: string; headers?: Record<string, string | string[] | undefined> }) => {
        const accept = req.headers?.accept
        const acceptHeader = Array.isArray(accept) ? accept.join(',') : accept || ''
        return req.method === 'GET' && acceptHeader.includes('text/html')
    }

    const authProxy = () => ({
        target: backendUrl,
        changeOrigin: true,
        bypass: (req: { method?: string; headers?: Record<string, string | string[] | undefined> }) => {
            if (shouldBypassToIndex(req)) return '/index.html'
            return undefined
        },
    })

    return {
        plugins: [vue()],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url)),
            },
        },

        // ✅ Vitest bierze TEN SAM config co Vite
        test: {
            globals: true,
            environment: 'jsdom',
            setupFiles: './tests/setup.ts',
            include: ['tests/unit/**/*.test.ts', 'src/**/__tests__/**/*.test.ts'],
            exclude: ['tests/e2e/**', '**/*.spec.{ts,tsx,js,jsx}', 'node_modules/**', 'dist/**'],
        },

        server: {
            proxy: {
                '/api': { target: backendUrl, changeOrigin: true },
                '/sanctum': { target: backendUrl, changeOrigin: true },

                '/login': authProxy(),
                '/logout': authProxy(),
                '/register': authProxy(),
                '/forgot-password': authProxy(),
                '/reset-password': authProxy(),
                '/email': authProxy(),
            },
        },
    }
})
