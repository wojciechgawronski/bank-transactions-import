# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Bank transaction import app: upload CSV/JSON/XML, records are validated in a queue job, valid ones go to `transactions`, invalid ones to `import_logs`. Monorepo: `backend/` (Laravel 12, PHP 8.4, PostgreSQL 17, database queue) and `frontend/` (Vue 3 + TS + Vite + Pinia + Tailwind). Everything runs via Docker Compose (`compose.yaml`). User-facing docs (in Polish) are in `readme.md`.

## Commands

Dev environment: `docker compose up -d --build` — services `app` (:8000), `queue` (worker), `db` (host port 5433), `adminer` (:8080), `frontend` (:5173, proxies `/api` to `app`). First run also needs `cp backend/.env.example backend/.env`, `php artisan key:generate`, recreating `app`/`queue`, then `php artisan migrate --seed` (see `readme.md`).

Backend (run inside `docker compose exec app ...`, or directly in `backend/` if host PHP is available):

```sh
php artisan test                                   # all tests
php artisan test --filter=ImportProcessorTest      # single class / method
vendor/bin/pint                                    # format (CI runs pint --test)
vendor/bin/phpstan analyse --memory-limit=1G       # Larastan, level 6, app/ only
```

Frontend (`docker compose exec frontend ...` or in `frontend/`):

```sh
npm run test:unit -- --run                         # vitest, single run
npm run test:unit -- --run src/modules/imports/__tests__/importsStore.spec.ts
npm run lint                                       # oxlint + eslint with --fix
npm run format                                     # prettier
npm run type-check                                 # vue-tsc
```

CI (`.github/workflows/backend.yml`, `frontend.yml`) runs path-filtered: backend = Pint, Larastan, tests; frontend = `check:lint`, `check:format`, type-check, tests, `build-only`. Run locally with `act pull_request -W .github/workflows/<file>.yml`.

After changing job/domain code, `docker compose restart queue` — the worker keeps old code in memory.

## Architecture

Import flow:

```
POST /api/imports → ImportController (stores file, creates Import pending) → 202
  → ProcessImport job (tries=3) → ImportProcessor::process()
       → ParserRegistry → Csv/Json/XmlParser (streaming generators of TransactionRecord)
       → RecordValidator (LaravelRecordValidator + Rules\Iban, Rules\Currency)
       → chunks of 500, each in a DB transaction: insert transactions + import_logs, bump counters
```

- Import logic lives in `backend/app/Domain/Import` and is independent of HTTP and the queue; controllers and the job are thin wrappers.
- Parsers are registered via container tags in `app/Providers/ImportServiceProvider.php`. A new format = parser class implementing `Contracts\TransactionParser` + a `FileFormat` enum case + one entry in the provider.
- Parsers yield lazily, so a corrupted file can throw `InvalidImportFile` after some chunks were already saved. `ImportProcessor` then makes it all-or-nothing: `reset()` deletes the import's transactions/logs and writes a single file-level log. `process()` also resets at start so job retries don't see their own rows as duplicates.
- Duplicate `transaction_id` is checked both within the file (`$accepted`) and against the DB per chunk; the validator itself only checks a single record without context.
- Final status comes from `ImportStatus::fromCounts()` (0 successful → `failed`, also for empty files).
- The uploaded file is kept until the job finishes or finally fails, so retries can re-read it.
- Error messages are prefixed with `Record {position}:` so rows without a `transaction_id` can still be located.

Frontend: `src/api/` (single axios instance with `/api` base, typed endpoints, `apiErrorMessage()` for Laravel errors), `src/modules/imports/` (Pinia store, `useImportPolling` refreshes the list every 2.5 s only while some import is unfinished, logs drawer routed at `/imports/:id`), `src/components/ui/` for generic components. TS types in `src/api/imports.ts` mirror the API resources, and `MAX_FILE_SIZE_BYTES` mirrors `StoreImportRequest::MAX_SIZE_KB` — keep them in sync with the backend.

## Conventions

- Money is always an integer amount in grosze, never float.
- Every API list is paginated with explicit ordering.
- Tests always use SQLite `:memory:` and `sync` queue (forced in `phpunit.xml`); sample import files live in `backend/tests/fixtures/` (`valid.*`, `mixed.*`, `corrupted.*`).
- UI copy is Polish; code, comments and API error messages are English.
- Conventional Commits with scope `backend`, `frontend`, `docker`, `ci` (e.g. `feat(backend): add xml parser`). Each change on its own branch (`feat/`, `docs/`, `chore/`…), merged into `main`.
