<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Página pública (con o sin sesión). Describe lo que la app guarda de verdad; el texto debe revisarlo
// el Decanato de Desarrollo Estudiantil antes de darlo por oficial.
const page = usePage<{ auth: { user: { is_admin: boolean | number } | null } }>();
const volver = computed(() => {
    const user = page.props.auth?.user;

    if (!user) {
        return { href: '/register', texto: 'Volver al registro' };
    }

    return user.is_admin ? { href: '/inicio-admin', texto: 'Volver al inicio' } : { href: '/', texto: 'Volver al inicio' };
});
</script>

<template>
    <Head title="Política de privacidad" />
    <main class="min-h-screen bg-white px-4 py-10 flex justify-center">
        <article class="w-full max-w-3xl text-[#404040] leading-relaxed">
            <Link href="/" class="inline-block mb-6">
                <img src="/img/Logo_Lockers_UNET.webp" alt="Lockers UNET" class="h-16 w-auto object-contain" />
            </Link>

            <h1 class="text-3xl sm:text-4xl font-black text-black mb-2">Política de privacidad</h1>
            <p class="text-sm text-gray-500 mb-8">Lockers UNET · Última actualización: 23 de octubre de 2026</p>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">¿Quién es responsable de tus datos?</h2>
                <p>
                    Lockers UNET es el sistema del Decanato de Desarrollo Estudiantil de la Universidad Nacional Experimental del Táchira (UNET)
                    para solicitar, asignar, cobrar y devolver los lockers de la universidad. El Decanato es responsable de los datos que se
                    guardan en él.
                </p>
            </section>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">¿Qué datos guardamos?</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li><strong>Datos de tu cuenta:</strong> nombre, apellido, correo institucional (@unet.edu.ve), número de carnet y carrera.</li>
                    <li><strong>Tu contraseña,</strong> siempre cifrada: nadie, ni siquiera los administradores, puede leerla.</li>
                    <li><strong>Tu uso del servicio:</strong> solicitudes de locker, asignaciones, devoluciones, pagos del arancel, multas, reportes de incidencias y las notificaciones que te enviamos.</li>
                    <li><strong>Datos técnicos de la sesión:</strong> la dirección IP y el navegador desde el que entras, para mantener tu sesión abierta y proteger tu cuenta.</li>
                </ul>
            </section>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">¿Para qué los usamos?</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Identificarte y verificar que perteneces a la UNET (te enviamos un código a tu correo institucional).</li>
                    <li>Gestionar tu locker: revisar tu solicitud, asignártelo, registrar tus pagos y multas, y confirmar su devolución.</li>
                    <li>Avisarte de cambios en tu solicitud, tu locker o tus pagos.</li>
                    <li>Atender los problemas que reportes sobre un locker.</li>
                </ul>
                <p class="mt-2">No usamos tus datos para publicidad ni para ningún fin ajeno al servicio de lockers.</p>
            </section>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">¿Quién puede verlos?</h2>
                <p>
                    Solo los administradores del Decanato de Desarrollo Estudiantil. No vendemos ni cedemos tus datos. Para que el sistema
                    funcione intervienen dos proveedores técnicos: el servicio de correo con el que se envían los códigos y avisos, y el
                    servidor donde está alojada la aplicación.
                </p>
            </section>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">Cookies</h2>
                <p>
                    Usamos únicamente las cookies necesarias para mantener tu sesión iniciada y proteger los formularios. No usamos cookies
                    de publicidad ni de analítica.
                </p>
            </section>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">¿Cuánto tiempo los conservamos?</h2>
                <p>
                    Mientras tengas una cuenta en Lockers UNET. El historial de asignaciones, pagos y multas se conserva como registro del
                    servicio del Decanato.
                </p>
            </section>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">Tus derechos</h2>
                <p>
                    Puedes consultar tus datos en <strong>Mi perfil</strong>. Para corregirlos o pedir que se eliminen, dirígete al Decanato de
                    Desarrollo Estudiantil. Estos derechos los reconoce la Constitución de la República Bolivariana de Venezuela (artículo 28,
                    acceso y corrección de los datos sobre tu persona, y artículo 60, protección de tu privacidad).
                </p>
            </section>

            <section class="mb-6">
                <h2 class="text-xl font-bold text-[#213779] mb-2">Seguridad</h2>
                <p>
                    Tu contraseña se guarda cifrada, la conexión con el sitio va cifrada (HTTPS) y, al cerrar sesión, el navegador deja de
                    mostrar las páginas que visitaste.
                </p>
            </section>

            <section class="mb-10">
                <h2 class="text-xl font-bold text-[#213779] mb-2">Cambios y contacto</h2>
                <p>
                    Si esta política cambia, actualizaremos la fecha de arriba. Para cualquier duda sobre tus datos, dirígete al Decanato de
                    Desarrollo Estudiantil de la UNET.
                </p>
            </section>

            <Link :href="volver.href" class="inline-flex items-center min-h-[44px] px-6 rounded-xl bg-[#213779] text-white font-bold hover:bg-[#1a2b5f] transition">
                {{ volver.texto }}
            </Link>
        </article>
    </main>
</template>
