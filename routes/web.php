<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\VerificationCodeController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\PerfilController;

// Nuevos controladores del backend
use App\Http\Controllers\LockerController;
use App\Http\Controllers\LockerRequestController;
use App\Http\Controllers\LockerAssignmentController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\FeeRateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BuscarUsuarioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;

use App\Http\Middleware\IsAdminMiddleware;
use Illuminate\Support\Facades\Route;

// ============================================================
// RUTAS PARA USUARIOS AUTENTICADOS (estudiantes y admins)
// ============================================================
Route::middleware(['auth'])->group(function () {

    // Página de inicio
    Route::inertia('/', 'Home')->name('home');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'show'])->name('user-profile');
    Route::put('/profile', [ProfileController::class, 'update']);

    // --------------------------------------------------------
    // RUTAS DEL ESTUDIANTE
    // --------------------------------------------------------

    // Buscar lockers disponibles
    Route::get('/lockers', [LockerController::class, 'index'])->name('buscar-locker');

    // Hacer una solicitud de locker
    Route::match(['get', 'head'], '/solicitud-locker', function () {
        return inertia('User/SolicitudLocker', ['lockerData' => request()->all()]);
    })->name('solicitud-locker');
    Route::match(['post'], '/solicitud-locker', [LockerRequestController::class, 'store'])->name('solicitud-locker.store');
    Route::post('/requests', [LockerRequestController::class, 'store']);

    // Ver mis solicitudes
    Route::get('/requests/my', [LockerRequestController::class, 'myRequests'])->name('mis-solicitudes');

    // Ver mi locker activo (Asignación)
    Route::get('/assignments/my', [LockerAssignmentController::class, 'myAssignment'])->name('mi-locker');

    // Notificaciones (Bandeja, lectura y contador)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notificaciones');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Pago de arancel e historial
    Route::get('/payments/my', [PaymentController::class, 'myPayments'])->name('pago-arancel');
    Route::get('/payments/my/pending', [PaymentController::class, 'myPending']);

    // Reportar incidencia
    Route::get('/reportes-user', function () {
        $asignacion = \App\Models\LockerAssignment::with(['locker.sector'])
            ->where('user_id', auth()->id())
            ->where('assignment_status', 'active')
            ->first();
        return inertia('User/ReporteIncidencia', ['asignacion' => $asignacion]);
    })->name('reportes-user');
    Route::post('/incidents', [IncidentController::class, 'store']);

    // Mis multas
    Route::get('/fines/my', [FineController::class, 'myFines']);

    // Devolución Locker
    Route::get('/devolucion-locker', function () {
        $asignacion = \App\Models\LockerAssignment::with(['locker'])
            ->where('user_id', auth()->id())
            ->where('assignment_status', 'active')
            ->first();
        return inertia('User/DevolucionLocker', ['asignacion' => $asignacion]);
    })->name('devolucion-locker');
    Route::post('/devolucion-locker/{id}', [LockerAssignmentController::class, 'release']);
    Route::put('/admin/assignments/{id}/release', [LockerAssignmentController::class, 'release'])
    ->name('admin.assignments.release');
    // Aranceles (disponibles para lectura)
    Route::get('/fee-rates', [FeeRateController::class, 'index'])->name('aranceles-admin');

    // --------------------------------------------------------
    // RUTAS SOLO PARA EL ADMIN (protegidas con IsAdminMiddleware)
    // --------------------------------------------------------
    Route::middleware([IsAdminMiddleware::class])->group(function () {

        // Dashboard admin
        Route::inertia('/inicio-admin', 'Admin/HomeAdmin')->name('home-admin');

        // Gestión de Lockers (ver, crear, editar, eliminar)
        Route::get('/admin/lockers', [LockerController::class, 'adminIndex'])->name('gestion-admin');
        Route::get('/admin/lockers/available', [LockerController::class, 'availableJson']);
        Route::get('/admin/lockers/create', [LockerController::class, 'create'])->name('crear-locker-admin');
        Route::post('/admin/lockers', [LockerController::class, 'store']);
        Route::get('/admin/lockers/{id}/edit', [LockerController::class, 'edit'])->name('modificar-locker-admin');
        Route::put('/admin/lockers/{id}', [LockerController::class, 'update']);
        Route::delete('/admin/lockers/{id}', [LockerController::class, 'destroy']);

        // Solicitudes de lockers (Ver, Aprobar, Rechazar)
        Route::get('/admin/requests', [LockerRequestController::class, 'index'])->name('peticiones-admin');
        Route::put('/admin/requests/{id}/approve', [LockerRequestController::class, 'approve']);
        Route::put('/admin/requests/{id}/reject', [LockerRequestController::class, 'reject']);

        // Asignaciones directas e histórico (Ver, Crear directa, Liberar)
        Route::get('/admin/assignments', [LockerAssignmentController::class, 'index'])->name('asignaciones-admin');
        Route::get('/admin/assignments/list', [LockerAssignmentController::class, 'listJson']);
        Route::post('/admin/assignments', [LockerAssignmentController::class, 'store']);
        Route::put('/admin/assignments/{id}/release', [LockerAssignmentController::class, 'release']);

        // Estadísticas (Vista)
Route::get('/estadisticas-lockers', [StatsController::class, 'index'])->name('estadisticas-admin');
        // Estadísticas (Endpoints JSON AJAX para Gráficos)
Route::get('/admin/stats/summary', [StatsController::class, 'summary'])->name('admin.stats.summary');        Route::get('/admin/stats/by-semester', [StatsController::class, 'bySemester']);
        Route::get('/admin/stats/monthly', [StatsController::class, 'monthly']);
        // Aranceles (tarifas) - Registro histórico
        Route::post('/admin/fee-rates', [FeeRateController::class, 'store']);
        Route::get('/admin/stats', [StatsController::class, 'index']);
        // Incidencias
        Route::get('/admin/incidents', [IncidentController::class, 'index'])->name('incidencias-admin');
        Route::put('/admin/incidents/{id}/review', [IncidentController::class, 'review']);

        // Usuarios (Listado, Búsqueda y Ver Detalle)
        Route::get('/admin/users/search', [UserController::class, 'searchJson']);
        Route::get('/admin/users', [UserController::class, 'index'])->name('usuarios-admin');
        Route::get('/admin/users/{id}', [UserController::class, 'show'])->name('admin-ver-usuario');

        // Multas del admin (ver y crear para un usuario específico)
        Route::get('/admin/users/{userId}/fines', [FineController::class, 'userFines']);
        Route::get('/admin/multas/{user:card_code}', [FineController::class, 'create'])->name('crear-multa-admin');
        Route::post('/admin/fines', [FineController::class, 'store']);
        Route::delete('/admin/fines/{id}', [FineController::class, 'destroy']);

        // Pagos
        Route::get('/admin/payments', [PaymentController::class, 'index']);
        Route::patch('/admin/payments/{id}/paid', [PaymentController::class, 'markPaid']);
    });
});

// ============================================================
// RUTAS DE AUTENTICACIÓN (Login, Registro, Recuperar contraseña)
// ============================================================
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'logout']);

Route::get('/register', [RegisterController::class, 'index']);
Route::post('/register', [RegisterController::class, 'store']);

Route::middleware('guest')->group(function () {
    // Paso 1: Ingresar correo
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->name('password.email');

    // Paso 2: Verificar código
    Route::get('/verify-code', [VerificationCodeController::class, 'show'])->name('password.verify');
    Route::post('/verify-code', [VerificationCodeController::class, 'verify'])->name('password.verify.post');

    // Paso 3: Nueva contraseña
    Route::get('/new-password', [NewPasswordController::class, 'show'])->name('password.new');
    Route::post('/new-password', [NewPasswordController::class, 'update'])->name('password.update');
});