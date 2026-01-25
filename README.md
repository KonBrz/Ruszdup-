# Rusz Dupę

Travel planning application with Laravel backend and Vue 3 frontend.

## Requirements

- **Docker Desktop** (Windows/Mac/Linux)
- **Node.js 18+** (for frontend unit tests and Playwright)
- **Git**

> **Note**: PHP and Composer are NOT required locally - backend runs entirely in Docker.

## Quick Start (3 commands)

```bash
# 1. Clone and enter directory
git clone <repo-url> && cd ruszdup

# 2. Setup (build images + install dependencies)
npm run setup
# OR on Linux/Mac: make setup
# OR on Windows: .\scripts\setup.ps1

# 3. Run all tests
npm run test:all
# OR on Linux/Mac: make test
# OR on Windows: .\scripts\test-all.ps1
```

## Available Commands

### npm scripts (cross-platform)

| Command | Description |
|---------|-------------|
| `npm run setup` | Build Docker images and install all dependencies |
| `npm run test:all` | Run all tests (backend + unit + E2E) |
| `npm run test:backend` | Run backend tests (PHPUnit in Docker) |
| `npm run test:unit` | Run frontend unit tests (Vitest) |
| `npm run test:e2e` | Run E2E tests (Playwright) |
| `npm run dev` | Start development stack |
| `npm run clean` | Remove all containers and volumes |

### Make targets (Linux/Mac)

```bash
make setup        # Build and install dependencies
make test         # Run all tests
make test-backend # Backend tests only
make test-unit    # Frontend unit tests only
make test-e2e     # E2E tests only
make dev          # Start dev stack
make clean        # Cleanup
```

### PowerShell scripts (Windows)

```powershell
.\scripts\setup.ps1     # Build and install
.\scripts\test-all.ps1  # Run all tests
.\scripts\clean.ps1     # Cleanup
```

## Test Matrix

| Suite | Framework | Location | Command |
|-------|-----------|----------|---------|
| Backend API | PHPUnit | `backend/tests/` | `npm run test:backend` |
| Frontend Unit | Vitest + MSW | `frontend/tests/` | `npm run test:unit` |
| E2E | Playwright | `e2e/` | `npm run test:e2e` |

### Backend Tests (78 tests)

Runs in Docker with SQLite in-memory database. No MySQL required.

```bash
# All tests
docker compose -f docker-compose.test.yml run --rm backend-test

# Specific test file
docker compose -f docker-compose.test.yml run --rm backend-test php artisan test --filter TripApiTest
```

### Frontend Unit Tests (25 tests)

Uses Vitest with MSW for API mocking - no backend required.

```bash
cd frontend && npm run test:unit
```

### E2E Tests (11 tests)

Requires running dev stack. Tests are isolated with unique users per test.

```bash
# Start stack first
npm run dev

# Run E2E
npm run test:e2e

# With UI
npm run test:e2e:ui
```

## Development

```bash
# Start full stack
npm run dev
# OR: docker compose up -d

# Frontend: http://localhost:5173
# Backend API: http://localhost:8000
# MySQL: localhost:3306

# View logs
docker compose logs -f

# Stop
docker compose down
```

## Project Structure

```
ruszdup/
├── backend/           # Laravel API
│   ├── app/
│   ├── tests/         # PHPUnit tests
│   └── Dockerfile
├── frontend/          # Vue 3 + Vite
│   ├── src/
│   ├── tests/         # Vitest unit tests
│   └── Dockerfile
├── e2e/               # Playwright E2E tests
│   ├── helpers.ts     # Shared test utilities
│   └── *.spec.ts
├── docker-compose.yml          # Development stack
├── docker-compose.test.yml     # Test-only stack (SQLite)
├── playwright.config.ts
├── Makefile                    # Linux/Mac commands
└── scripts/                    # Windows PowerShell scripts
```

## Troubleshooting

### "php artisan test" fails on Windows

Backend tests must run in Docker:
```bash
npm run test:backend
# OR: docker compose -f docker-compose.test.yml run --rm backend-test
```

### "orphan containers" warning

```bash
docker compose down --remove-orphans
```

### E2E tests are flaky

E2E tests are stabilized with:
- Session isolation (`clearAuthState()` before each test)
- Unique users per test (`Date.now()` in email)
- Explicit waits (`waitForResponse()`, no sleeps)
- Stable selectors (`data-testid`)

If issues persist:
```bash
# Run with retries
npx playwright test --retries=2

# Run single test
npx playwright test e2e/auth.spec.ts

# Debug mode
npx playwright test --debug
```

### Port already in use

```bash
npm run clean
# OR: docker compose down -v --remove-orphans
```

### Frontend not connecting to backend

Check backend is running:
```bash
curl http://localhost:8000/api/trips
```

If 502/503, restart:
```bash
docker compose restart backend
```

## CI/CD

GitHub Actions workflow in `.github/workflows/ci.yml` runs:
1. Backend tests (Docker + SQLite)
2. Frontend unit tests (Vitest)
3. E2E tests (Playwright)

## License

MIT
