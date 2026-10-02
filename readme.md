# Gdynia Project

Wewnętrzne narzędzie do importu transakcji bankowych z plików **CSV, JSON i XML**,
z historią importów i podglądem błędów walidacji.

Monorepo: dwie niezależne aplikacje w jednym repozytorium.

| Katalog     | Aplikacja                                         |
| ----------- | ------------------------------------------------- |
| `backend/`  | Laravel 12 (PHP 8.4), REST API + worker kolejki   |
| `frontend/` | Vue 3 + TypeScript + Vite (SPA)                   |

Development i produkcja działają w Dockerze.

---

## Założenia

- **Bez kont i logowania.** To narzędzie wewnętrzne, dostęp chroni sieć lub reverse proxy, nie aplikacja.
- **Kwoty w groszach.** `amount` to liczba całkowita w jednostkach podrzędnych waluty (`150000` = 1 500,00 PLN).
  W bazie siedzi `unsignedBigInteger`, nigdy `float`. Formatowanie do postaci „1 500,00” odbywa się dopiero we froncie.
- **Waluta z listy ISO 4217.** Sprawdza ją `symfony/intl` (`Currencies::exists()`).
- **Import częściowy.** Poprawne rekordy trafiają do `transactions`, błędne do `import_logs`.
  Status importu to `success`, `partial` albo `failed`.
- **Asynchronicznie.** Upload odpowiada od razu (`202`), a plik przetwarza worker kolejki.

## Stos

| Warstwa      | Wybór                                                                 |
| ------------ | --------------------------------------------------------------------- |
| Backend      | Laravel 12, PHP 8.4                                                   |
| Baza         | PostgreSQL 17 (Docker), SQLite `:memory:` w testach                   |
| Kolejka      | driver `database`, osobny kontener `queue`                            |
| Parsowanie   | `league/csv`, wbudowany `XMLReader`, `json_decode`                    |
| Walidacja    | własne reguły `Iban` (mod-97) i `Currency` (`symfony/intl`)           |
| Jakość PHP   | Pint, Larastan (PHPStan), PHPUnit                                     |
| Frontend     | Vue 3.5, TypeScript, Vite, vue-router, Pinia                          |
| UI           | Tailwind 4, `reka-ui`, `lucide-vue-next`, `vue-sonner`, `@vueuse/core` |
| HTTP         | `axios` w jednym module `src/api/`                                    |
| Testy frontu | Vitest + Vue Test Utils                                               |

## Architektura backendu

```
POST /api/v1/imports
  └─ ImportController ── zapis pliku + rekord imports (pending) ── 202
       └─ dispatch ProcessImport (kolejka)
            └─ ImportProcessor
                 ├─ ParserRegistry ─► TransactionParser (Csv / Json / Xml)   ← strategia
                 ├─ RecordValidator (Iban, Currency, amount > 0)
                 └─ paczki po 500 w DB::transaction:
                      transactions + import_logs + liczniki w imports
```

| Wzorzec              | Gdzie                                                            |
| -------------------- | ---------------------------------------------------------------- |
| Strategia            | `TransactionParser` + `CsvParser`, `JsonParser`, `XmlParser`     |
| Rejestr + DI         | `ParserRegistry` dostaje otagowane parsery (`giveTagged`)        |
| Abstrakcja walidacji | `RecordValidator` (interfejs) + `LaravelRecordValidator`         |
| DTO                  | `TransactionRecord`, `ImportResult` (readonly)                   |
| Enum z logiką        | `ImportStatus::fromCounts()`, `FileFormat::fromExtension()`      |
| Serwis aplikacyjny   | `ImportProcessor`, niezależny od HTTP i kolejki                  |
| Job                  | `ProcessImport`, cienka powłoka nad procesorem                   |

Nowy format pliku to jedna nowa klasa parsera i jedna linia w `ImportServiceProvider`.

## Model danych

| Tabela        | Kolumny                                                                                                   |
| ------------- | --------------------------------------------------------------------------------------------------------- |
| `imports`     | `id`, `file_name`, `total_records`, `successful_records`, `failed_records`, `status`, timestamps          |
| `transactions`| `id`, `import_id`¹, `transaction_id` (unique), `account_number`, `transaction_date`, `amount` (grosze), `currency`, timestamps |
| `import_logs` | `id`, `import_id`, `transaction_id` (nullable), `error_message`, timestamps                               |

¹ Poza briefem. Mówi, z którego pliku pochodzi transakcja.

Status importu:

| Warunek                                       | Status    |
| --------------------------------------------- | --------- |
| `failed_records = 0` i `total_records > 0`    | `success` |
| `successful_records = 0` (też pusty plik)     | `failed`  |
| pozostałe                                     | `partial` |

Przed i w trakcie przetwarzania import ma status `pending`, a potem `processing`.

## Walidacja rekordu

| Pole               | Reguła                                                         |
| ------------------ | -------------------------------------------------------------- |
| `transaction_id`   | wymagane, unikalne w pliku i w bazie                           |
| `account_number`   | wymagane, poprawny IBAN (format + suma kontrolna mod-97)       |
| `transaction_date` | wymagane, data `Y-m-d`                                         |
| `amount`           | wymagane, liczba całkowita > 0 (grosze)                        |
| `currency`         | wymagane, kod ISO 4217                                         |

Błąd rekordu nie przerywa importu, tylko trafia do `import_logs`.

## API

| Metoda | Ścieżka                            | Opis                                  |
| ------ | ---------------------------------- | ------------------------------------- |
| POST   | `/api/v1/imports`                  | upload pliku (`multipart`, pole `file`) → `202` |
| GET    | `/api/v1/imports`                  | lista importów, paginowana            |
| GET    | `/api/v1/imports/{import}`         | jeden import (polling statusu)        |
| GET    | `/api/v1/imports/{import}/logs`    | błędy importu, paginowane             |

## Struktura repozytorium

```
gdynia-project/
├── .github/workflows/      backend.yml, frontend.yml (filtr paths)
├── compose.yaml            development
├── compose.prod.yaml       produkcja
├── .editorconfig  .gitignore  .nvmrc  README.md
├── backend/
│   ├── app/
│   │   ├── Domain/Import/  Contracts, Parsers, Data, Enums, ParserRegistry, ImportProcessor
│   │   ├── Http/           Controllers, Requests, Resources
│   │   ├── Jobs/           ProcessImport
│   │   ├── Models/         Import, Transaction, ImportLog
│   │   ├── Providers/      ImportServiceProvider
│   │   └── Rules/          Iban, Currency
│   ├── tests/              Unit, Feature, fixtures/
│   └── Dockerfile
└── frontend/
    ├── src/                api/, modules/imports/, layouts/, components/ui/, router/
    └── Dockerfile
```

---

## Uruchomienie (Docker)

Wymagane: Docker z Compose v2.

```sh
cp backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

| Usługa     | Rola                                   | Adres                   |
| ---------- | -------------------------------------- | ----------------------- |
| `app`      | Laravel API                            | http://localhost:8000   |
| `queue`    | `php artisan queue:work`               | –                       |
| `db`       | PostgreSQL                             | localhost:5432          |
| `frontend` | Vite dev server, proxy `/api` → `app`  | http://localhost:5173   |

`app` i `queue` współdzielą wolumen `storage/app/private`, żeby worker widział wgrany plik.

Codzienne komendy:

```sh
docker compose exec app php artisan test           # testy backendu
docker compose exec app vendor/bin/pint            # styl
docker compose exec app vendor/bin/phpstan analyse # Larastan
docker compose logs -f queue                       # podgląd workera
docker compose exec frontend npm run lint
docker compose exec frontend npm run type-check
docker compose exec frontend npm run test:unit
```

**Produkcja** (`compose.prod.yaml`): obraz backendu bez dev-zależności (`composer install --no-dev --optimize-autoloader`,
`config:cache`, `route:cache`) oraz obraz frontu budowany wieloetapowo (`npm ci && npm run build` → statyczne pliki).
Serwer frontu podaje `dist/` i przekazuje `/api` do backendu, więc wszystko działa z jednej domeny, bez CORS.

---


## Konwencje

- Commity: [Conventional Commits](https://www.conventionalcommits.org/) ze scope `backend`, `frontend`, `docker`, `ci`,
  np. `feat(backend): add xml parser`.
- Gałąź główna: `main`. CI uruchamia się na push i PR do `main`, osobno dla `backend/**` i `frontend/**`.
- Kontrolery tylko delegują, a logika żyje w `app/Domain/Import`.
- Pieniądze zawsze jako liczba całkowita groszy.
- Każda lista w API jest paginowana i ma jawne `orderBy`.