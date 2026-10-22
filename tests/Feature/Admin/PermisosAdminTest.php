<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\IsAdminMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RutaLaravel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermisosAdminTest extends TestCase
{
    use RefreshDatabase;

    /** Todas las rutas protegidas por IsAdminMiddleware, con método y URI. */
    private function rutasDeAdmin(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RutaLaravel $ruta) => in_array(IsAdminMiddleware::class, $ruta->gatherMiddleware(), true))
            ->flatMap(fn (RutaLaravel $ruta) => collect($ruta->methods())
                ->reject(fn ($metodo) => $metodo === 'HEAD')
                ->map(fn ($metodo) => [$metodo, $ruta->uri()]))
            ->values()
            ->all();
    }

    /** Reemplaza {id} y {user} (se busca por card_code) por valores que existen, para que el binding no responda 404 antes. */
    private function url(string $uri, User $usuario): string
    {
        return '/'.ltrim(preg_replace(
            ['/\{user\}/', '/\{[^}]+\}/'],
            [(string) $usuario->card_code, (string) $usuario->id],
            $uri
        ), '/');
    }

    public function test_las_rutas_de_admin_existen(): void
    {
        // Si alguien saca las rutas del grupo, este recorrido quedaría vacío y no probaría nada
        $this->assertGreaterThan(30, count($this->rutasDeAdmin()));
    }

    public function test_un_estudiante_es_redirigido_en_todas_las_rutas_de_admin(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false, 'card_code' => '12345']);

        foreach ($this->rutasDeAdmin() as [$metodo, $uri]) {
            $respuesta = $this->actingAs($estudiante)->call($metodo, $this->url($uri, $estudiante));

            $this->assertTrue(
                $respuesta->isRedirect(route('home')),
                "{$metodo} /{$uri} respondió {$respuesta->getStatusCode()} a un estudiante en vez de redirigir al inicio"
            );
        }
    }

    public function test_un_invitado_es_enviado_al_login_en_todas_las_rutas_de_admin(): void
    {
        $usuario = User::factory()->make(['id' => 1, 'card_code' => '12345']);

        foreach ($this->rutasDeAdmin() as [$metodo, $uri]) {
            $respuesta = $this->call($metodo, $this->url($uri, $usuario));

            $this->assertTrue(
                $respuesta->isRedirect(route('login')),
                "{$metodo} /{$uri} respondió {$respuesta->getStatusCode()} a un invitado en vez de redirigir al login"
            );
        }
    }

    public function test_un_admin_abre_todas_las_paginas_get_sin_errores(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'card_code' => '54321']);
        $errores = [];

        foreach ($this->rutasDeAdmin() as [$metodo, $uri]) {
            if ($metodo !== 'GET') {
                continue;
            }

            $estado = $this->actingAs($admin)->get($this->url($uri, $admin))->getStatusCode();

            if ($estado >= 500) {
                $errores[] = "GET /{$uri} respondió {$estado}";
            }
        }

        $this->assertSame([], $errores);
    }

    public function test_una_url_con_el_metodo_equivocado_muestra_la_pagina_405_en_espanol(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/lockers/999999')
            ->assertStatus(405)
            ->assertSee('Esa dirección no se puede abrir así');
    }
}
