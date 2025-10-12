<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
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

### 2. Instalacja zależności

Użyj Composera, aby zainstalować wszystkie wymagane pakiety PHP.

```bash
composer install
```

### 3. Konfiguracja środowiska

Skopiuj plik `.env.example` do nowego pliku o nazwie `.env`. Będzie on zawierał konfigurację specyficzną dla Twojego środowiska.

```bash
cp .env.example .env
```

Następnie wygeneruj unikalny klucz aplikacji.

```bash
php artisan key:generate
```

### 4. Konfiguracja bazy danych

Otwórz plik `.env` w edytorze tekstu i uzupełnij dane dostępowe do Twojej bazy danych. Upewnij się, że stworzyłeś wcześniej pustą bazę danych o podanej nazwie.

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ruszdupe
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Migracje i zasilanie danymi

Uruchom migracje, aby stworzyć strukturę bazy danych.

```bash
php artisan migrate
```

Opcjonalnie, możesz wypełnić bazę danych przykładowymi danymi za pomocą seederów.

```bash
php artisan db:seed
```

### 6. Uruchomienie serwera

Na koniec uruchom lokalny serwer deweloperski Laravela.

```bash
php artisan serve
```

Aplikacja będzie dostępna domyślnie pod adresem `http://127.0.0.1:8000`.

### 7. Frontend

# 🚀 Vue 3 + TypeScript + Vite

Ten projekt został stworzony przy użyciu **Vue 3**, **TypeScript** oraz **Vite**.  
Poniżej znajdziesz instrukcję, jak uruchomić i rozwijać aplikację lokalnie.

---

## 📦 Wymagania

Upewnij się, że masz zainstalowane:

- [Node.js](https://nodejs.org/) w wersji **16+**  
- [npm](https://www.npmjs.com/) lub [yarn](https://yarnpkg.com/) (menedżer pakietów)

---

## 🛠️ Instalacja zależności

Po sklonowaniu projektu uruchom w katalogu projektu:

```bash
npm install
# lub
yarn install
```

---

## ▶️ Uruchomienie projektu

Aby uruchomić projekt w trybie deweloperskim:

```bash
npm run dev
# lub
yarn dev
```

Po chwili aplikacja będzie dostępna pod adresem:
```
http://localhost:5173
```

---

## 🏗️ Budowanie projektu produkcyjnego

Aby zbudować gotowy do wdrożenia pakiet:

```bash
npm run build
# lub
yarn build
```

Wynik zostanie zapisany w folderze `dist/`.

---

## 🔍 Testowanie buildu lokalnie

Aby przetestować zbudowaną aplikację lokalnie:

```bash
npm run preview
# lub
yarn preview
```

---

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

---

## 🧰 Przydatne skrypty

| Komenda              | Opis                              |
|----------------------|------------------------------------|
| `npm run dev`        | Uruchamia środowisko developerskie |
| `npm run build`      | Buduje aplikację produkcyjną       |
| `npm run preview`    | Uruchamia lokalny podgląd buildu   |

---