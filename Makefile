# Rusz Dupę - Makefile for Linux/Mac
# Usage: make setup && make test

.PHONY: setup test test-backend test-unit test-e2e dev clean help

# Default target
help:
	@echo "Rusz Dupę - Available commands:"
	@echo ""
	@echo "  make setup       - Build Docker images and install dependencies"
	@echo "  make test        - Run all tests (backend + unit + e2e)"
	@echo "  make test-backend - Run backend tests in Docker"
	@echo "  make test-unit   - Run frontend unit tests (Vitest)"
	@echo "  make test-e2e    - Run E2E tests (Playwright)"
	@echo "  make dev         - Start development stack"
	@echo "  make clean       - Remove all containers and volumes"
	@echo ""

# Setup: build images and install dependencies
setup:
	docker compose build
	docker compose -f docker-compose.test.yml run --rm composer
	cd frontend && npm ci

# Run all tests
test: test-backend test-unit test-e2e
	@echo ""
	@echo "✅ ALL TESTS PASSED"

# Backend tests (PHPUnit in Docker with SQLite :memory:)
test-backend:
	@echo "==> Running backend tests..."
	docker compose -f docker-compose.test.yml run --rm backend-test

# Frontend unit tests (Vitest + MSW)
test-unit:
	@echo "==> Running frontend unit tests..."
	cd frontend && npm run test:unit

# E2E tests (Playwright) - requires running dev stack
test-e2e:
	@echo "==> Running E2E tests..."
	@if ! curl -s http://localhost:5173 > /dev/null 2>&1; then \
		echo "Starting dev stack for E2E..."; \
		docker compose up -d; \
		echo "Waiting for services..."; \
		sleep 15; \
	fi
	npx playwright test

# Start development stack
dev:
	docker compose up -d
	@echo "Frontend: http://localhost:5173"
	@echo "Backend:  http://localhost:8000"

# Stop development stack
dev-down:
	docker compose down

# Clean everything
clean:
	docker compose down -v --remove-orphans
	docker compose -f docker-compose.test.yml down -v --remove-orphans
	@echo "Cleaned up all containers and volumes"
