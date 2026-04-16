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

use App\Http\Middleware\IsAdminMiddleware;
use Illuminate\Support\Facades\Route;

// ============================================================
// RUTAS PARA USUARIOS AUTENTICADOS (estudiantes y admins)
// ============================================================
Route::middleware(['auth'])->group(function () {

    // Página de inicio
    Route::inertia('/', 'Home')->name('home');

    // Perfil
    // Perfil (pasamos la asignación activa del usuario para mostrar info del locker)
    Route::get('/user-profile', function () {
        $asignacion = \App\Models\LockerAssignment::with(['locker.sector.building'])
            ->where('user_id', auth()->id())
            ->where('assignment_status', 'active')
            ->first();
        return inertia('User/Profile', ['asignacion' => $asignacion]);
    })->name('user-profile');
    Route::get('/edit-profile', [PerfilController::class, 'index'])->name('editar-perfil');
    Route::post('/edit-profile', [PerfilController::class, 'store']);

    // --------------------------------------------------------
    // RUTAS DEL ESTUDIANTE
    // --------------------------------------------------------

    // Buscar lockers disponibles
    Route::get('/buscar-locker', [LockerController::class, 'disponibles'])->name('buscar-locker');

    // Hacer una solicitud de locker
    Route::get('/solicitud-locker', function () {
        return inertia('User/SolicitudLocker', ['lockerData' => request()->all()]);
    })->name('solicitud-locker');
    Route::post('/solicitud-locker', [LockerRequestController::class, 'store']);

    // Ver mis solicitudes
    Route::get('/mis-solicitudes', [LockerRequestController::class, 'misSolicitudes'])->name('mis-solicitudes');

    // Ver mi locker activo
    Route::get('/mi-locker', [LockerAssignmentController::class, 'miLocker'])->name('mi-locker');

    // Devolver locker
    Route::get('/devolucion-locker', [LockerAssignmentController::class, 'devolucion'])->name('devolucion-locker');
    Route::post('/devolucion-locker/{id}', [LockerAssignmentController::class, 'release']);

    // Notificaciones
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notificaciones');
    Route::post('/notificaciones/{id}/leer', [NotificationController::class, 'markAsRead']);

    // Pago de arancel
    Route::get('/pago-arancel', [PaymentController::class, 'index'])->name('pago-arancel');

    // Reportar incidencia (pasamos la asignación activa del usuario para precargar el locker)
    Route::get('/reportes-user', function () {
        $asignacion = \App\Models\LockerAssignment::with(['locker.sector'])
            ->where('user_id', auth()->id())
            ->where('assignment_status', 'active')
            ->first();
        return inertia('User/ReporteIncidencia', ['asignacion' => $asignacion]);
    })->name('reportes-user');
    Route::post('/reportes-user', [IncidentController::class, 'store']);

    // Mis multas
    Route::get('/multas/usuario', [FineController::class, 'misMultas']);

    // --------------------------------------------------------
    // RUTAS SOLO PARA EL ADMIN (protegidas con IsAdminMiddleware)
    // --------------------------------------------------------
    Route::middleware([IsAdminMiddleware::class])->group(function () {

        // Dashboard admin
        Route::inertia('/inicio-admin', 'Admin/HomeAdmin')->name('home-admin');

        // Gestión de Lockers (ver, crear, editar, eliminar)
        Route::get('/gestion-lockers-admin', [LockerController::class, 'index'])->name('gestion-admin');
        Route::post('/admin/lockers', [LockerController::class, 'store']);
        Route::put('/admin/lockers/{id}', [LockerController::class, 'update']);
        Route::delete('/admin/lockers/{id}', [LockerController::class, 'destroy']);

        // Modificar un locker específico
        Route::inertia('/modificar-locker', 'Admin/ModificarLocker')->name('modificar-locker');

        // Solicitudes de lockers (aprobar / rechazar)
        Route::get('/asignaciones-admin', [LockerRequestController::class, 'index'])->name('asignaciones-admin');
        Route::post('/admin/solicitudes/{id}/aprobar', [LockerRequestController::class, 'approve']);
        Route::post('/admin/solicitudes/{id}/rechazar', [LockerRequestController::class, 'reject']);

        // Estadísticas
        Route::inertia('/estadisticas-lockers', 'Admin/EstadisticasLockers')->name('estadisticas-admin');

        // Aranceles (tarifas)
        Route::get('/aranceles-admin', [FeeRateController::class, 'index'])->name('aranceles-admin');
        Route::post('/admin/aranceles', [FeeRateController::class, 'store']);
        Route::delete('/admin/aranceles/{id}', [FeeRateController::class, 'destroy']);

        // Incidencias
        Route::get('/incidencias-admin', [IncidentController::class, 'index'])->name('incidencias-admin');
        Route::post('/admin/incidencias/{id}/revisar', [IncidentController::class, 'review']);

        // Buscar usuario por código de carnet
        Route::get('/admin/buscar', [BuscarUsuarioController::class, 'index']);
        Route::get('/admin/buscar/{user:card_code}', [BuscarUsuarioController::class, 'mostrar_usuario']);

        // Usuarios
        Route::inertia('/usuarios-admin', 'Admin/Usuarios')->name('usuarios-admin');

        // Multas del admin (ver y crear para un usuario)
        Route::get('/admin/multas/{user:card_code}', [FineController::class, 'index']);
        Route::post('/admin/multas', [FineController::class, 'store']);
        Route::delete('/admin/multas/{id}', [FineController::class, 'destroy']);

        // Pagos
        Route::post('/admin/pagos', [PaymentController::class, 'store']);
        Route::post('/admin/pagos/{id}/pagar', [PaymentController::class, 'markAsPaid']);
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