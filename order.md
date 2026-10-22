# Orden del proyecto

Mapa de cómo están distribuidos los archivos de Lockers UNET y qué hace cada uno.

- **El árbol del final se genera solo.** `composer order` lo regenera y el hook `pre-commit` lo actualiza en cada commit.
- **Las descripciones se escriben a mano.** Si agregas, mueves o borras un archivo de `app/`, `resources/js/pages/`, `routes/` o `database/`, descríbelo aquí. El script avisa de los que falten.

## Raíz

| Archivo | Para qué sirve |
| --- | --- |
| `artisan` | CLI de Laravel. |
| `composer.json` / `composer.lock` | Dependencias PHP y scripts (`composer dev`, `composer test`, `composer lint`, `composer order`). |
| `package.json` / `package-lock.json` | Dependencias JS y scripts (`npm run build`, `lint`, `format`, `types:check`). |
| `vite.config.ts` | Vite con los plugins de Laravel, Vue, Tailwind y Wayfinder. |
| `tsconfig.json` | Configuración de TypeScript para `vue-tsc`. |
| `eslint.config.js`, `.prettierrc`, `.prettierignore` | Lint y formato del frontend. |
| `pint.json` | Formato del código PHP (Pint). |
| `phpunit.xml` | Pruebas, con SQLite en memoria. |
| `.env.example` | Plantilla de variables de entorno. Sin valores reales. |
| `.editorconfig`, `.gitattributes`, `.gitignore`, `.npmrc` | Configuración del editor, git y npm. |
| `escanear_lockers.php` | Script suelto para revisar si `Lockers.csv` apunta a sectores inexistentes. Tiene una ruta de Windows fija y no forma parte de la app. |
| `README.md` | Instrucciones de instalación. |
| `CLAUDE.md` | Guía del proyecto para Claude Code. |
| `order.md` | Este archivo. |

## `app/`: backend

### `app/Console/Commands/`: comandos artisan

| Archivo | Comando | Qué hace |
| --- | --- | --- |
| `CheckPayments.php` | `app:check-payments` | Revisa los pagos vencidos y notifica. Corre a diario (`routes/console.php`). |
| `GenerateSemesterPayments.php` | `payments:generate-semester {semester?}` | Genera los pagos del semestre a partir de las tarifas. |
| `ImportBuildings.php` | `import:buildings {file}` | Importa edificios desde un CSV. |
| `ImportSectors.php` | `import:sectors {file}` | Importa sectores desde un CSV. |
| `ImportLockers.php` | `import:lockers {file}` | Importa lockers desde un CSV. |

### `app/Http/Controllers/`

**Autenticación (`Auth/`).** Todos los flujos son propios y funcionan con códigos enviados por correo.

| Archivo | Qué hace |
| --- | --- |
| `LoginController.php` | Login y logout (el logout invalida la sesión, renueva el token y limpia el historial). Si el correo no está verificado, envía un código nuevo y manda a `/verify-email`. Manda a los admins a `home-admin`. |
| `RegisterController.php` | Registro, solo con correos `@unet.edu.ve`. Envía el código de verificación; si el correo no sale, borra el usuario. |
| `EmailVerificationController.php` | Muestra `/verify-email`, verifica el código y lo reenvía. Marca `email_verified_at`. |
| `ForgotPasswordController.php` | Paso 1 de la recuperación: envía el código. Responde igual exista o no el correo (sesión `reset_iniciado`). |
| `VerificationCodeController.php` | Paso 2: valida el código (sesión `reset_code_verified`), con límite de 5 intentos. |
| `NewPasswordController.php` | Paso 3: guarda la contraseña nueva. |

**Dominio.**

| Archivo | Qué hace | Páginas |
| --- | --- | --- |
| `HomeController.php` | Inicio del estudiante. | `Home` |
| `LockerController.php` | Busca lockers disponibles para el estudiante (filtrados por edificio y tamaño, 15 por página) y los gestiona: CRUD y JSON de disponibles. Los lockers se crean desde un modal de `GestionLockers`. | `User/BuscarLocker`, `Admin/GestionLockers`, `Admin/ModificarLocker` |
| `LockerRequestController.php` | Muestra el formulario de solicitud con los datos del locker (`form`), crea solicitudes, muestra "mis solicitudes", deja al estudiante cancelar una pendiente (`cancelled`) y permite aprobarlas o rechazarlas. Aprobar crea la asignación y el primer pago, y necesita un arancel para ese tipo de locker. | `User/MisSolicitudes`, `Admin/GestionSolicitudes` |
| `LockerAssignmentController.php` | Muestra "mi locker", crea asignaciones directas y lista asignaciones en JSON. Devolución en dos pasos: el estudiante la solicita (`requestReturn`, queda `return_requested`) y el admin la confirma (`release`). Pagos pendientes o multas la bloquean. | `User/MiLocker`, `Admin/Asignaciones` |
| `PaymentController.php` | Muestra los pagos del estudiante y permite al admin marcarlos como pagados (desde `Asignaciones`). | `User/PagoArancel` |
| `FeeRateController.php` | Tarifas (aranceles) y generación de pagos del semestre. | `Admin/Aranceles` |
| `FineController.php` | Multas: las del estudiante, y crearlas o borrarlas desde el admin. El admin ve las multas de un usuario en `VistaUsuario`. | `User/MultasUser`, `Admin/CrearMulta` |
| `IncidentController.php` | Recibe los reportes de incidencias y los marca como revisados. | `Admin/Incidencias` |
| `NotificationController.php` | Notificaciones en la app: listado, marcar leídas y contador. | `User/Notificaciones` |
| `StatsController.php` | Estadísticas: la vista y los endpoints JSON para las gráficas. También gestiona los semestres. | `Admin/EstadisticasLockers` |
| `UserController.php` | Listado, búsqueda y detalle de usuarios (admin). | `Admin/Usuarios`, `Admin/VistaUsuario` |
| `ProfileController.php` | Ver y actualizar el perfil (`/profile`). | `User/Profile` |
| `PerfilController.php` | Editar el perfil (`/edit-profile`): nombre, apellido, carrera (de `User::CARRERAS`) y foto, y cambiar la contraseña (`password`). El correo y el carnet no se editan. | `User/EditProfile` |
| `Controller.php` | Clase base. | |
| `MultaController.php` | **Legado.** Reemplazado por `FineController`. | |
| `BuscarUsuarioController.php` | **Legado.** Reemplazado por `UserController`. | |

Cada página que se renderiza desde PHP debe existir en `resources/js/pages/`. Lo revisa `tests/Unit/PaginasInertiaExistenTest.php`.

### `app/Http/Middleware/`

| Archivo | Qué hace |
| --- | --- |
| `HandleInertiaRequests.php` | Props compartidas con todas las páginas: `name`, `auth.user` y `flash` (`success`, `status`). |
| `IsAdminMiddleware.php` | Protege las rutas de admin con `users.is_admin` y, si no es admin, redirige a `home`. |
| `SinCacheConSesion.php` | Con sesión iniciada envía `Cache-Control: no-store, private` (en el grupo `web`, `bootstrap/app.php`). |
| `CifrarHistorial.php` | Cifra el historial de Inertia en el grupo autenticado si la conexión es HTTPS o localhost; el logout lo limpia. |

### `app/Models/`

| Archivo | Tabla | Notas |
| --- | --- | --- |
| `User.php` | `users` | Campo `is_admin`, perfil y foto. `CARRERAS` es la lista de carreras del registro y el perfil. |
| `Building.php` | `buildings` | Edificio → sectores; `lockers()` llega a sus lockers a través de los sectores. |
| `Sector.php` | `sectors` | Sector → lockers. |
| `Locker.php` | `lockers` | PK `locker_id`. |
| `LockerRequest.php` | `locker_requests` | Solicitud del estudiante. PK `request_id`. |
| `LockerAssignment.php` | `locker_assignments` | Asignación aprobada. `assignment_status`: `active`, `return_requested` o `released`; `VIGENTES` agrupa las dos primeras. PK `assignment_id`. |
| `Payment.php` | `payments` | Pago de una asignación en un semestre. |
| `FeeRate.php` | `fee_rates` | Tarifas, con historial. |
| `Semester.php` | `semesters` | Semestres académicos. |
| `Fine.php` | `fines` | Multas. PK `fine_id`. |
| `Incident.php` | `incidents` | Reportes de incidencias. |
| `Notification.php` | `notifications` | Notificaciones de la app (no usa el sistema de Laravel). |
| `EmailVerificationCode.php` | `email_verification_codes` | Códigos de verificación del registro. |
| `PasswordResetCode.php` | `password_reset_tokens` | Códigos para recuperar la contraseña. Reemplaza la tabla por defecto de Laravel. |
| `Assignment.php` | `assignments` | **Legado.** La tabla ya no existe; usa `LockerAssignment`. |
| `Request.php` | `requests` | **Legado.** La tabla ya no existe; usa `LockerRequest`. |
| `Multa.php` | `fines` | **Legado.** Usa `Fine`. |

### Otros

| Archivo | Qué hace |
| --- | --- |
| `app/Helpers/NotificationHelper.php` | `NotificationHelper::send($userId, $type, $title, $message)` crea una notificación en la app. |
| `app/Helpers/EmailVerificationHelper.php` | `EmailVerificationHelper::send($user)` genera un código de 6 dígitos (15 min), invalida los anteriores y lo envía. Lo usan el registro, el login y "Reenviar código". |
| `app/Mail/EmailVerificationCodeMail.php` | Correo con el código de registro. |
| `app/Mail/ResetCodeMail.php` | Correo con el código para recuperar la contraseña. |
| `app/Providers/AppServiceProvider.php` | Provider de la app. |

## `routes/`

| Archivo | Qué hace |
| --- | --- |
| `web.php` | Todas las rutas, en tres zonas. **Estudiante:** grupo `auth` + `verified` (sin correo verificado redirige a `/verify-email`). **Admin:** `IsAdminMiddleware`, casi todas bajo `/admin/*`. **Públicas e invitado:** login, registro, verificación y recuperación. Algunas devuelven páginas Inertia y otras devuelven JSON. |
| `console.php` | Programa `app:check-payments` a diario. |

Notas sobre `web.php`:

- `home` está declarada dos veces. Gana la segunda, la de `HomeController`.
- `/fee-rates` (`aranceles-admin`) está dentro del grupo admin, aunque su URL no empiece con `/admin`.
- `POST /devolucion-locker/{id}` (estudiante, solo su asignación) solicita la devolución; `PUT /admin/assignments/{id}/release` (admin) la confirma o libera directamente.
- Las rutas de estadísticas, semestres y generación de pagos están dentro del grupo admin, aunque su indentación no lo parezca.
- Algunas páginas se renderizan desde closures en la misma ruta: `User/ReporteIncidencia` y `User/DevolucionLocker`. `User/SolicitudLocker` la arma `LockerRequestController@form` desde el `locker_id`.

## `database/`

### `migrations/`

| Archivo | Crea o modifica |
| --- | --- |
| `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table.php` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table.php` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_04_01_193202_add_profile_info_to_users_table.php` | Datos de perfil en `users` |
| `2026_04_08_0000000_create_password_reset_codes_table.php` | Rehace `password_reset_tokens` con `user_id`, `token` (código), `expires_at` y `used`. A pesar del nombre del archivo, no crea `password_reset_codes`. |
| `2026_04_08_193537_create_buildings_table.php` | `buildings` |
| `2026_04_08_193538_create_sectors_table.php` | `sectors` |
| `2026_04_08_193539_create_lockers_table.php` | `lockers` |
| `2026_04_08_193540_create_requests_table.php` | `locker_requests` |
| `2026_04_08_193542_create_assignments_table.php` | `locker_assignments` |
| `2026_04_08_193543_create_fee_rates_table.php` | `fee_rates` |
| `2026_04_08_193544_create_payments_table.php` | `payments` |
| `2026_04_08_193545_create_fines_table.php` | `fines` |
| `2026_04_08_193546_create_incidents_table.php` | `incidents` |
| `2026_04_08_193547_create_notifications_table.php` | `notifications` |
| `2026_04_09_094348_add_profile_photo_to_users_table.php` | Foto de perfil en `users` |
| `2026_04_24_045310_create_semesters_table.php` | `semesters` |
| `2026_04_28_152837_add_email_verified_at_to_users_table.php` | `email_verified_at` en `users` |
| `2026_04_28_153201_create_email_verification_codes_table.php` | `email_verification_codes` |
| `2026_10_21_000000_mark_existing_admins_as_verified.php` | Marca como verificados a los admins existentes, que el seeder creaba sin `email_verified_at`. |

### `seeders/`

| Archivo | Qué siembra |
| --- | --- |
| `DatabaseSeeder.php` | Llama a los demás seeders. |
| `UsersSeeder.php` | El admin `adminBE@unet.edu.ve`, ya verificado. |
| `LockerSeeder.php` | Edificios, sectores y lockers desde `data/*.csv`. |
| `FeeRateSeeder.php` | Tarifas iniciales. |
| `data/Buildings.csv`, `data/sectors.csv`, `data/Lockers.csv` | El inventario real de la UNET. |

`factories/UserFactory.php` se usa en las pruebas.

## `resources/`: frontend

| Ruta | Qué hace |
| --- | --- |
| `views/app.blade.php` | Shell HTML de Inertia. Carga `resources/js/app.ts` con Vite. |
| `views/emails/email-verification.blade.php` | Plantilla del correo de verificación. |
| `views/emails/reset-code.blade.php` | Plantilla del correo de recuperación. |
| `css/app.css` | Tailwind v4 y estilos globales. |
| `js/app.ts` | Arranque de Inertia y Vue. |
| `js/lib/utils.ts` | Utilidades compartidas. |
| `js/types/` | Tipos: `auth.ts`, `index.ts`, `global.d.ts`, `vue-shims.d.ts`. |
| `js/actions/`, `js/routes/`, `js/wayfinder/` | Los genera Wayfinder al correr Vite. Están en el gitignore y no aparecen en el árbol. |

### `js/pages/`

**`Layouts/`.** Cada página importa el suyo.

- `Layout.vue`: layout del estudiante.
- `LayoutAdmin.vue`: layout del admin.

**`Components/`**

- `ModalComponent.vue`: modal reutilizable.
- `FlashMessage.vue`: aviso flotante con los mensajes `success`/`status` del backend; lo usa `Layout.vue`.

**`Auth/`**

| Página | Ruta |
| --- | --- |
| `Login.vue` | `/login` |
| `Register.vue` | `/register` |
| `VerifyEmail.vue` | `/verify-email` |
| `ForgotPassword.vue` | `/forgot-password` |
| `VerificationCode.vue` | `/verify-code` |
| `NewPassword.vue` | `/new-password` |

**`Home.vue`**: inicio del estudiante (`/`).

**`User/`**: páginas del estudiante.

| Página | Ruta | Qué hace |
| --- | --- | --- |
| `BuscarLocker.vue` | `/lockers` | Buscar lockers disponibles, con filtros y paginación del servidor. |
| `SolicitudLocker.vue` | `/solicitud-locker` | Pedir un locker y descargar el PDF de la solicitud. |
| `MisSolicitudes.vue` | `/requests/my` | Estado de las solicitudes; cancelar una pendiente. |
| `MiLocker.vue` | `/assignments/my` | El locker asignado. |
| `PagoArancel.vue` | `/payments/my` | Pagos del semestre. |
| `MultasUser.vue` | `/fines/my` | Multas del estudiante. |
| `ReporteIncidencia.vue` | `/reportes-user` | Reportar una incidencia. |
| `DevolucionLocker.vue` | `/devolucion-locker` | Solicitar la devolución del locker; muestra "Devolución en proceso" hasta que el admin la confirme. |
| `Notificaciones.vue` | `/notifications` | Notificaciones de la app. |
| `Profile.vue` | `/profile` | Ver el perfil. |
| `EditProfile.vue` | `/edit-profile` | Editar el perfil y cambiar la contraseña. |

**`Admin/`**: páginas del administrador.

| Página | Ruta | Qué hace |
| --- | --- | --- |
| `HomeAdmin.vue` | `/inicio-admin` | Panel principal. |
| `GestionLockers.vue` | `/admin/lockers` | Listar y buscar lockers. |
| `ModificarLocker.vue` | `/admin/lockers/{id}/edit` | Editar un locker. |
| `Asignaciones.vue` | `/admin/assignments` | Asignar y liberar lockers, confirmar devoluciones pendientes y marcar pagos como pagados. |
| `GestionSolicitudes.vue` | `/admin/requests` | Aprobar o rechazar las solicitudes de los estudiantes. |
| `Aranceles.vue` | `/fee-rates` | Tarifas y generación de pagos. |
| `EstadisticasLockers.vue` | `/estadisticas-lockers` | Gráficas con Chart.js. |
| `Incidencias.vue` | `/admin/incidents` | Revisar incidencias. |
| `Usuarios.vue` | `/admin/users` | Listado de usuarios. |
| `VistaUsuario.vue` | `/admin/users/{id}` | Detalle de un usuario. |
| `CrearMulta.vue` | `/admin/multas/{card_code}` | Crear una multa. |

## `public/`

| Ruta | Qué hace |
| --- | --- |
| `index.php`, `.htaccess`, `robots.txt` | Punto de entrada web. |
| `favicon.*`, `apple-touch-icon.png` | Íconos. |
| `img/` | Logos e ilustraciones de las pantallas de auth. |
| `docs/Solicitud_de_Locker.pdf` | El formulario que descargan los estudiantes. |
| `build/` | Assets compilados por Vite. Están en el gitignore. |

## Otros directorios

| Ruta | Qué hace |
| --- | --- |
| `bootstrap/` | `app.php` (rutas, middleware y excepciones) y `providers.php`. |
| `config/` | Configuración de Laravel: `app`, `auth`, `database`, `mail`, `inertia`, etc. `app.locale` está fijo en `es`. |
| `lang/es/validation.php` | Mensajes de validación en español y nombres de los campos (`card_code` → "carnet", etc.). |
| `storage/` | Logs, caché y archivos subidos. En git solo están sus `.gitignore`. |
| `tests/` | `TestCase.php`, los `ExampleTest.php` y:<br>• `Feature/RutasProtegidasTest.php`: aranceles y la liberación de lockers solo los usan el admin o el dueño, y no se devuelve un locker con pagos pendientes o vencidos.<br>• `Feature/RecuperarContrasenaTest.php`: recuperar contraseña sin revelar cuentas y con límite de intentos.<br>• `Feature/GestionSolicitudesTest.php`: aprobar o rechazar solicitudes.<br>• `Feature/UsabilidadTest.php`: arreglos de la prueba de usabilidad (`qa-reports/`).<br>• `Feature/DevolucionLockerTest.php`: devolución en dos pasos y multas que la bloquean.<br>• `Feature/VerificacionCorreoTest.php`: sin correo verificado no se entra, el login reenvía el código, el código verifica.<br>• `Unit/PaginasInertiaExistenTest.php`: cada página renderizada existe. |
| `.github/workflows/` | CI: `lint.yml` y `tests.yml`. |
| `scripts/actualizar-order.php` | Regenera el árbol de este archivo. |
| `qa-reports/` | Reportes de las pruebas de usabilidad con subagentes (`RESUMEN.md`, uno por persona y por ronda). Las capturas están en el gitignore. |
| `.githooks/pre-commit` | Corre el script anterior antes de cada commit. |

## Árbol de archivos

<!-- ARBOL:INICIO (generado por scripts/actualizar-order.php, no editar a mano) -->
```text
LockerUNET-SI/
├── .githooks/
│   └── pre-commit
├── .github/
│   └── workflows/  (2 archivos)
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CheckPayments.php
│   │       ├── GenerateSemesterPayments.php
│   │       ├── ImportBuildings.php
│   │       ├── ImportLockers.php
│   │       └── ImportSectors.php
│   ├── Helpers/
│   │   ├── EmailVerificationHelper.php
│   │   └── NotificationHelper.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── EmailVerificationController.php
│   │   │   │   ├── ForgotPasswordController.php
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── RegisterController.php
│   │   │   │   └── VerificationCodeController.php
│   │   │   ├── BuscarUsuarioController.php
│   │   │   ├── Controller.php
│   │   │   ├── FeeRateController.php
│   │   │   ├── FineController.php
│   │   │   ├── HomeController.php
│   │   │   ├── IncidentController.php
│   │   │   ├── LockerAssignmentController.php
│   │   │   ├── LockerController.php
│   │   │   ├── LockerRequestController.php
│   │   │   ├── MultaController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── PerfilController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── StatsController.php
│   │   │   └── UserController.php
│   │   └── Middleware/
│   │       ├── CifrarHistorial.php
│   │       ├── HandleInertiaRequests.php
│   │       ├── IsAdminMiddleware.php
│   │       └── SinCacheConSesion.php
│   ├── Mail/
│   │   ├── EmailVerificationCodeMail.php
│   │   └── ResetCodeMail.php
│   ├── Models/
│   │   ├── Assignment.php
│   │   ├── Building.php
│   │   ├── EmailVerificationCode.php
│   │   ├── FeeRate.php
│   │   ├── Fine.php
│   │   ├── Incident.php
│   │   ├── Locker.php
│   │   ├── LockerAssignment.php
│   │   ├── LockerRequest.php
│   │   ├── Multa.php
│   │   ├── Notification.php
│   │   ├── PasswordResetCode.php
│   │   ├── Payment.php
│   │   ├── Request.php
│   │   ├── Sector.php
│   │   ├── Semester.php
│   │   └── User.php
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   ├── cache/  (1 archivo)
│   ├── app.php
│   └── providers.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── inertia.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_04_01_193202_add_profile_info_to_users_table.php
│   │   ├── 2026_04_08_0000000_create_password_reset_codes_table.php
│   │   ├── 2026_04_08_193537_create_buildings_table.php
│   │   ├── 2026_04_08_193538_create_sectors_table.php
│   │   ├── 2026_04_08_193539_create_lockers_table.php
│   │   ├── 2026_04_08_193540_create_requests_table.php
│   │   ├── 2026_04_08_193542_create_assignments_table.php
│   │   ├── 2026_04_08_193543_create_fee_rates_table.php
│   │   ├── 2026_04_08_193544_create_payments_table.php
│   │   ├── 2026_04_08_193545_create_fines_table.php
│   │   ├── 2026_04_08_193546_create_incidents_table.php
│   │   ├── 2026_04_08_193547_create_notifications_table.php
│   │   ├── 2026_04_09_094348_add_profile_photo_to_users_table.php
│   │   ├── 2026_04_24_045310_create_semesters_table.php
│   │   ├── 2026_04_28_152837_add_email_verified_at_to_users_table.php
│   │   ├── 2026_04_28_153201_create_email_verification_codes_table.php
│   │   └── 2026_10_21_000000_mark_existing_admins_as_verified.php
│   ├── seeders/
│   │   ├── data/  (3 archivos)
│   │   ├── DatabaseSeeder.php
│   │   ├── FeeRateSeeder.php
│   │   ├── LockerSeeder.php
│   │   └── UsersSeeder.php
│   └── .gitignore
├── lang/
│   └── es/
│       └── validation.php
├── public/
│   ├── docs/
│   │   └── Solicitud_de_Locker.pdf
│   ├── img/  (17 archivos)
│   ├── .htaccess
│   ├── apple-touch-icon.png
│   ├── favicon.ico
│   ├── favicon.svg
│   ├── index.php
│   └── robots.txt
├── qa-reports/  (10 archivos)
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── lib/
│   │   │   └── utils.ts
│   │   ├── pages/
│   │   │   ├── Admin/
│   │   │   │   ├── Aranceles.vue
│   │   │   │   ├── Asignaciones.vue
│   │   │   │   ├── CrearMulta.vue
│   │   │   │   ├── EstadisticasLockers.vue
│   │   │   │   ├── GestionLockers.vue
│   │   │   │   ├── GestionSolicitudes.vue
│   │   │   │   ├── HomeAdmin.vue
│   │   │   │   ├── Incidencias.vue
│   │   │   │   ├── ModificarLocker.vue
│   │   │   │   ├── Usuarios.vue
│   │   │   │   └── VistaUsuario.vue
│   │   │   ├── Auth/
│   │   │   │   ├── ForgotPassword.vue
│   │   │   │   ├── Login.vue
│   │   │   │   ├── NewPassword.vue
│   │   │   │   ├── Register.vue
│   │   │   │   ├── VerificationCode.vue
│   │   │   │   └── VerifyEmail.vue
│   │   │   ├── Components/
│   │   │   │   ├── FlashMessage.vue
│   │   │   │   └── ModalComponent.vue
│   │   │   ├── Layouts/
│   │   │   │   ├── Layout.vue
│   │   │   │   └── LayoutAdmin.vue
│   │   │   ├── User/
│   │   │   │   ├── BuscarLocker.vue
│   │   │   │   ├── DevolucionLocker.vue
│   │   │   │   ├── EditProfile.vue
│   │   │   │   ├── MiLocker.vue
│   │   │   │   ├── MisSolicitudes.vue
│   │   │   │   ├── MultasUser.vue
│   │   │   │   ├── Notificaciones.vue
│   │   │   │   ├── PagoArancel.vue
│   │   │   │   ├── Profile.vue
│   │   │   │   ├── ReporteIncidencia.vue
│   │   │   │   └── SolicitudLocker.vue
│   │   │   └── Home.vue
│   │   ├── types/
│   │   │   ├── auth.ts
│   │   │   ├── global.d.ts
│   │   │   ├── index.ts
│   │   │   └── vue-shims.d.ts
│   │   └── app.ts
│   └── views/
│       ├── emails/
│       │   ├── email-verification.blade.php
│       │   └── reset-code.blade.php
│       └── app.blade.php
├── routes/
│   ├── console.php
│   └── web.php
├── scripts/
│   └── actualizar-order.php
├── storage/  (10 archivos)
├── tests/
│   ├── Feature/
│   │   ├── DevolucionLockerTest.php
│   │   ├── ExampleTest.php
│   │   ├── GestionSolicitudesTest.php
│   │   ├── RecuperarContrasenaTest.php
│   │   ├── RutasProtegidasTest.php
│   │   ├── UsabilidadTest.php
│   │   └── VerificacionCorreoTest.php
│   ├── Unit/
│   │   ├── ExampleTest.php
│   │   └── PaginasInertiaExistenTest.php
│   └── TestCase.php
├── .editorconfig
├── .env.example
├── .gitattributes
├── .gitignore
├── .npmrc
├── .prettierignore
├── .prettierrc
├── artisan
├── CLAUDE.md
├── composer.json
├── composer.lock
├── escanear_lockers.php
├── eslint.config.js
├── order.md
├── package-lock.json
├── package.json
├── phpunit.xml
├── pint.json
├── prompt-5-subagentes.md
├── README.md
├── tsconfig.json
└── vite.config.ts
```
<!-- ARBOL:FIN -->
