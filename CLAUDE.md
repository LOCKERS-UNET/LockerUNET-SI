# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Lockers UNET: a web system for managing university lockers at UNET, to be deployed for all students. Students search for and request lockers, pay semester fees, report incidents and return lockers. Admins approve requests, assign and release lockers, set fees, issue fines, review incidents and view statistics. It is built on Laravel 13 (PHP ^8.3) with Inertia v3, Vue 3 + TypeScript, Tailwind v4, Vite and Chart.js (`vue-chartjs`), starting from the Laravel blank Vue starter kit. Code, UI text, routes and comments are mostly in Spanish. The database uses English table and column names.

## Branches

- `Douglas` is the working branch (tracks `origin/Douglas`). Make changes there, verify them, then merge into `main` as work progresses.
- `main` is the known-good state, the one used to evaluate the app. `2ebf90f` (the PR #3 merge, same tree as `495fb80`) is the baseline.
- `pruebas/cambios-inciertos` holds unverified later work: a stash of uncommitted controller, seeder and layout changes, plus an incomplete PostgreSQL, Render and Cloudinary migration. Test it there before merging anything into `main`.

## Commands

```bash
composer dev                   # php artisan serve + queue:listen + vite, run together
php artisan migrate:fresh --seed   # rebuild DB: admin user, buildings/sectors/lockers from CSV, fee rates
php artisan schedule:work      # runs the daily app:check-payments in local dev

composer test                  # config:clear + pint --test + php artisan test
php artisan test --filter=SomeTest
composer lint                  # pint
npm run lint                   # eslint --fix
npm run format                 # prettier on resources/
npm run types:check            # vue-tsc --noEmit
npm run build                  # production assets
```

Custom artisan commands live in `app/Console/Commands`: `app:check-payments`, `payments:generate-semester {semester?}`, `import:buildings|sectors|lockers {file}`.

Local development uses MySQL/MariaDB (database `inertia`, see README). Tests use in-memory SQLite (set in `phpunit.xml`). `composer.json` requires `ext-pdo_pgsql` even though `main` runs on MySQL. Email goes out through Gmail SMTP and is required for registration (verification code) and password reset. Setting `MAIL_MAILER=log` writes the codes to `storage/logs/laravel.log` instead.

The seeded admin is `adminBE@unet.edu.ve` with password `@coordBE123..` (`database/seeders/UsersSeeder.php`). The test users listed in README.md are no longer seeded.

## Architecture

- **Routing**: everything lives in `routes/web.php`. There are three areas:
  - The `auth` group holds student routes.
  - Inside it, `IsAdminMiddleware` (which checks `users.is_admin` and otherwise redirects to `home`) guards admin routes, mostly under `/admin/*`.
  - Public and guest routes cover login, registration, email verification and the password reset.

  Some routes return Inertia pages and others return JSON for AJAX calls (e.g. `/admin/stats/*`, `/admin/users/search`, `/notifications/unread-count`). A few routes are inline closures. Route names are reused in places. For example, `home` is declared twice, and the later `HomeController` route wins. `LoginController` redirects admins to `home-admin`.
- **Domain model**: buildings → sectors → lockers. A student creates a `LockerRequest` (`locker_requests`). An admin approves it, which creates a `LockerAssignment` (`locker_assignments`, with `assignment_status` such as `active`). Assignments then have `Payment`s, generated per `Semester` from `FeeRate`s, and `Fine`s. Users also file `Incident`s and receive in-app `Notification`s.
- **Custom primary keys**: domain tables use custom primary keys (`locker_id`, `request_id`, `assignment_id`, `fine_id`, …). Models set `$primaryKey`, and relationships always name both keys explicitly.
- **Notifications**: these are in-app rows created with `App\Helpers\NotificationHelper::send($userId, $type, $title, $message)` from the controllers and `CheckPayments`. They are not Laravel's notification system.
- **Legacy code**: `Assignment`, `Request` and `Multa` models, plus `MultaController` and `BuscarUsuarioController`, come from the first iteration. They point at tables that no longer exist (`requests`, `assignments`) or duplicate newer classes (`Fine`/`FineController`, `UserController`). Use `LockerRequest`, `LockerAssignment`, `Fine` and `UserController` instead.
- **Auth flows**: both are custom code-based flows, not Laravel's built-in brokers.
  - Registration accepts only `@unet.edu.ve` emails. It creates the user, emails a code (`EmailVerificationCode`), and `EmailVerificationController` sets `email_verified_at`. The main route group uses `['auth', 'verified']`, so unverified users are redirected to `/verify-email`; login sends them a fresh code through `App\Helpers\EmailVerificationHelper::send($user)`. Seeded admins must have `email_verified_at` set or they are locked out.
  - Password reset runs through `ForgotPasswordController` → `VerificationCodeController` → `NewPasswordController`. It stores codes in `password_reset_tokens` (the `create_password_reset_codes_table` migration rebuilds Laravel's default table; model `PasswordResetCode`) and passes state between steps through the session keys `reset_email` and `reset_code_verified`.
- **Frontend**: Inertia pages are in `resources/js/pages/{Auth,User,Admin}/*.vue`. Layouts are in `pages/Layouts/` (`Layout.vue` for students, `LayoutAdmin.vue` for admins) and each page imports its layout directly. Shared props from `HandleInertiaRequests` are `name` and `auth.user`. Pages call the backend with `router`/`useForm` and plain URLs.
- **Wayfinder**: `@laravel/vite-plugin-wayfinder` generates `resources/js/{actions,routes,wayfinder}`. These directories are gitignored and regenerated when Vite runs.
- **Seed data**: `database/seeders/data/*.csv` holds the real building, sector and locker inventory, which `LockerSeeder` loads. `public/docs/Solicitud_de_Locker.pdf` is the request form that students download.

## Conventions

- `order.md` maps every file in the project. The tree at its end is generated by `scripts/actualizar-order.php` (`composer order`, and the `.githooks/pre-commit` hook once `composer hooks` has been run). The tables above it are written by hand: when you add, move or delete a file under `app/`, `resources/js/pages/`, `routes/` or `database/`, update its description in the same change. The script lists any it can't find.

- Work on feature branches and avoid committing directly to `main`.
- Don't use special characters (ñ, accents) in file names.
- PHP formatting is Pint (`pint.json`). Frontend formatting is Prettier with the Tailwind plugin, and ESLint enforces import order and type imports.
- Never commit real `.env` values. The stash once leaked an app key and a Gmail app password into `.env.example`.
