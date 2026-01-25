# Troubleshooting Guide

## Backend Issues

### "Failed opening required 'vendor/autoload.php'"

**Cause**: Backend dependencies are not installed locally.

**Solution**: Run backend tests in Docker:
```bash
npm run test:backend
# OR
docker compose -f docker-compose.test.yml run --rm backend-test
```

The `vendor/` directory is managed by Docker volumes and is NOT required on the host.

### "APP_KEY" errors in tests

**Cause**: Missing or invalid APP_KEY.

**Solution**: The test setup automatically generates a valid key. If issues persist:
```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml run --rm backend-test
```

### SQLite "database is locked"

**Cause**: Concurrent test execution hitting SQLite limits.

**Solution**: Tests use `:memory:` SQLite which avoids this. If using file-based SQLite:
```bash
# Clear test database
rm -f backend/database/testing.sqlite
```

### AI tests failing (GEMINI_FAKE)

**Cause**: Real API calls instead of mocked.

**Solution**: Ensure `GEMINI_FAKE=true` in test environment:
```bash
# Check .env.testing
cat backend/.env.testing | grep GEMINI

# Should show: GEMINI_FAKE=true
```

### Policy not found / 403 on authorized actions

**Cause**: Policy not registered with Gate.

**Solution**: Policies are explicitly registered in `AppServiceProvider::boot()`. Verify:
```bash
# Run tinker to check policy registration
docker compose exec backend php artisan tinker
>>> \Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\Trip::class)
# Should return: App\Policies\TripPolicy instance

>>> \Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\Task::class)
# Should return: App\Policies\TaskPolicy instance
```

**Run authorization tests**:
```bash
php artisan test --group=authz
```

---

## Frontend Unit Test Issues

### "Failed to resolve component: router-link"

**Cause**: Vue Router components not stubbed.

**Solution**: Already fixed in `frontend/tests/setup.ts`. If warning persists, check that setup file is loaded in `vite.config.ts`:
```typescript
test: {
  setupFiles: './tests/setup.ts',
}
```

### "Not implemented: navigation to another Document"

**Cause**: JSDOM doesn't support real navigation.

**Solution**: Fixed in setup.ts with `window.location` mock. If persists, wrap navigation in test:
```typescript
vi.spyOn(window, 'location', 'get').mockReturnValue({
  // ... mock properties
})
```

### MSW "Request handler not found"

**Cause**: Unhandled API request.

**Solution**: Add handler to `frontend/src/mocks/handlers.ts` or use `server.use()` in test:
```typescript
server.use(
  http.get('/api/new-endpoint', () => HttpResponse.json({ data: 'test' }))
)
```

---

## E2E Issues

### Tests timeout waiting for element

**Cause**: Element not rendered or selector changed.

**Solution**:
1. Run with debug: `npx playwright test --debug`
2. Check selector matches: `npx playwright codegen http://localhost:5173`
3. Increase timeout if needed:
```typescript
await expect(page.getByTestId('element')).toBeVisible({ timeout: 15000 })
```

### "net::ERR_CONNECTION_REFUSED"

**Cause**: Dev stack not running.

**Solution**:
```bash
npm run dev
# Wait 30 seconds for services to start
npm run test:e2e
```

### Flaky tests (sometimes pass, sometimes fail)

**Causes & Solutions**:

1. **Shared state between tests**
   - Each test should use unique data (`Date.now()` in email)
   - `clearAuthState()` before each test

2. **Race conditions**
   - Use `waitForResponse()` instead of arbitrary waits
   - Use `expect().toBeVisible()` before clicking

3. **Parallel execution conflicts**
   - Reduce workers: `npx playwright test --workers=1`

### Login fails in E2E

**Cause**: Test user doesn't exist or wrong credentials.

**Solution**: The seeder creates `test@example.com` / `password`. Check:
```bash
docker compose exec backend php artisan db:seed
```

---

## Docker Issues

### "orphan containers" warning

```bash
docker compose down --remove-orphans
docker compose -f docker-compose.test.yml down --remove-orphans
```

### Containers won't start

**Check logs**:
```bash
docker compose logs backend
docker compose logs frontend
docker compose logs db
```

**Common fixes**:
```bash
# Full reset
npm run clean
npm run setup
npm run dev
```

### Port conflicts

**Check what's using ports**:
```bash
# Windows
netstat -ano | findstr :8000
netstat -ano | findstr :5173
netstat -ano | findstr :3306

# Linux/Mac
lsof -i :8000
lsof -i :5173
lsof -i :3306
```

**Kill process or change ports** in `docker-compose.yml`.

### Volume permission issues (Linux)

```bash
sudo chown -R $USER:$USER backend/storage
sudo chmod -R 775 backend/storage
```

---

## CI/CD Issues

### GitHub Actions failing

1. Check workflow logs in Actions tab
2. Download artifacts (test results, screenshots)
3. Common issues:
   - Docker build cache stale → clear cache
   - Timeout → increase timeout or reduce workers
   - Network issues → retry

### "Service 'backend-test' failed to build"

**Cause**: Dockerfile or dependency issue.

**Solution**: Build locally first:
```bash
docker compose -f docker-compose.test.yml build --no-cache
```

---

## Getting Help

1. Check this guide first
2. Search existing GitHub issues
3. Create new issue with:
   - OS and version
   - Docker version (`docker --version`)
   - Node version (`node --version`)
   - Full error message
   - Steps to reproduce
