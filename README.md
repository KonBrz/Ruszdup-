<p style="text-align:center;"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" style="width:400px;" alt="Laravel Logo"></a></p>

<p style="text-align:center;">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.
# Rusz Dupe - Aplikacja do planowania podróży

Projekt grupowy. Aplikacja do zarządzania zadaniami i wyjazdami. Składa się z backendu w Laravelu i frontendu w Vue.js.

## Wymagania

Upewnij się, że na Twoim komputerze zainstalowane są następujące narzędzia:
- PHP (w wersji zgodnej z projektem Laravel, np. 8.2+)
- Composer
- Serwer bazy danych (np. MySQL, PostgreSQL, MariaDB)
- Git
- Vue
- Tailwind CSS

## Instalacja (Backend)

Postępuj zgodnie z poniższymi krokami, aby uruchomić projekt lokalnie.

### 1. Klonowanie repozytorium i instalacja zależności

Otwórz terminal i sklonuj repozytorium do wybranego folderu.

```bash
git clone <adres-url-twojego-repozytorium>
cd ruszdupe/backend
```

### 2. Instalacja
Sprawdzić czy znajdujesz się w odpowiednim katalogu:
```
cd backend
```
W terminalu po sklonowaniu wpisz:

```
docker-compose build
```

### 3. Uruchomienie serwera
Uruchamianie za pomocą docker-compose-a w terminalu

```
docker-compose up
```

Aplikacja będzie dostępna domyślnie pod adresem `http://127.0.0.1:8000`.

### 4. Frontend

# 🚀 Vue 3 + TypeScript + Vite

Ten projekt został stworzony przy użyciu **Vue 3**, **TypeScript** oraz **Vite**.  
Poniżej znajdziesz instrukcję, jak uruchomić i rozwijać aplikację lokalnie.

Frontend dostępny pod:
```
http://localhost:5173
```

## E2E / Playwright (Docker)

Po zmianach w konfiguracji backendu:
```
docker-compose exec backend php artisan optimize:clear
docker-compose restart backend
```

Opcjonalnie sprawdź cookies:
```
curl.exe -i http://localhost:8000/sanctum/csrf-cookie
curl.exe -i -X POST http://localhost:8000/login -d "email=YOUR_EMAIL&password=YOUR_PASS"
```

Testy E2E w frontendzie:
```
npm run test:e2e
```

## Final check (przed oddaniem)

Jedna komenda:
```
.\final-check.ps1
```

Sukces oznacza: `ALL TESTS PASSED`.

Logi i artefakty:
- `docker compose logs backend`
- `docker compose logs frontend`
- `backend/storage/logs/laravel.log`
- `frontend/test-results/**/error-context.md`

## 🧩 Dodatkowe informacje

- Projekt korzysta z **Vue 3 `<script setup>`** – dokumentacja:  
  [https://v3.vuejs.org/api/sfc-script-setup.html](https://v3.vuejs.org/api/sfc-script-setup.html)

- Więcej o konfiguracji TypeScript w Vue:  
  [https://vuejs.org/guide/typescript/overview.html](https://vuejs.org/guide/typescript/overview.html)

---

## 💡 Wskazówki IDE

Dla najlepszej integracji i autouzupełniania kodu zalecane jest używanie **Visual Studio Code** z wtyczkami:
- **Volar** (zamiast Vetur)
- **TypeScript Vue Plugin**

---

## 📁 Struktura projektu

```
├── src/
│   ├── components/   # Komponenty Vue
│   ├── assets/       # Pliki statyczne (obrazy, style)
│   ├── App.vue       # Główny komponent aplikacji
│   └── main.ts       # Punkt wejścia aplikacji
├── index.html        # Plik główny HTML
├── tsconfig.json     # Konfiguracja TypeScript
├── vite.config.ts    # Konfiguracja Vite
└── package.json      # Skrypty i zależności
```