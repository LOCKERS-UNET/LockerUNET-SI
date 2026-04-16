<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// La ruta /user-profile busca la asignación activa
// del usuario logueado y nos la pasa aquí.
// Si "asignacion" es null = no tiene locker asignado.
// ─────────────────────────────────────────────
const props = defineProps<{
    asignacion: {
        assignment_id: number;
        start_date: string;
        locker: {
            locker_id: number;
            locker_code: string;
            locker_type: string; // small | mid | large
            sector: {
                sector_name: string;
                building: { building_name: string }
            }
        }
    } | null
}>();

const tipoLabel: Record<string, string> = {
    small: 'Pequeño',
    mid:   'Mediano',
    large: 'Grande',
};
</script>

<template>
    <div class="flex flex-col items-center justify-center min-h-screen py-8">
        
        <div class="w-full max-w-3xl flex flex-col gap-8 px-4">

            <!-- SECCIÓN PERFIL (datos del auth, sin cambios) -->
            <div class="flex flex-col gap-4">
                <h1 class="text-3xl font-bold text-center">Perfil usuario</h1>

                <section class="flex flex-col gap-3">
                    <div class="flex flex-col w-full sm:flex-row items-center gap-10 md:gap-20 shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white justify-center-safe">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-40 shrink-0">
                            <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                        </svg>

                        <div class="flex flex-col gap-2 overflow-hidden">
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Nombre Usuario:
                                <span class="font-normal block sm:inline">{{ $page.props.auth.user.name }} {{ $page.props.auth.user.lastname }}</span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Código Carnet:
                                <span class="font-normal block sm:inline">{{ $page.props.auth.user.card_code }}</span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Carrera:
                                <span class="font-normal block sm:inline">{{ $page.props.auth.user.career }}</span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Correo:
                                <span class="font-normal block sm:inline break-all">{{ $page.props.auth.user.email }}</span>
                            </p>
                        </div>
                    </div>

                    <Link href="/edit-profile" as="button" class="self-end">
                        <p class="bg-[#22397A] text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                            Editar Perfil
                        </p>
                    </Link>
                </section>
            </div>

            <!-- SECCIÓN LOCKER (datos reales del backend) -->
            <div class="flex flex-col gap-4">
                <h2 class="text-[#4169C4] text-2xl font-bold">Información Locker</h2>

                <section class="flex flex-col gap-3">

                    <!-- Si el usuario TIENE locker asignado -->
                    <div v-if="asignacion" class="grid grid-cols-2 gap-6 shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white">
                        <div class="flex flex-col gap-2 items-center">
                            <p class="font-bold text-sm sm:text-base">
                                ID Locker:
                                <span class="font-normal">{{ asignacion.locker.locker_id }}</span>
                            </p>
                            <p class="font-bold text-sm sm:text-base">
                                Código:
                                <span class="font-normal break-all">{{ asignacion.locker.locker_code }}</span>
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 items-center">
                            <p class="font-bold text-sm sm:text-base">
                                Tamaño:
                                <span class="font-normal">{{ tipoLabel[asignacion.locker.locker_type] ?? asignacion.locker.locker_type }}</span>
                            </p>
                            <p class="font-bold text-sm sm:text-base">
                                Edificio:
                                <span class="font-normal">{{ asignacion.locker.sector.building.building_name }} - {{ asignacion.locker.sector.sector_name }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Si el usuario NO tiene locker asignado -->
                    <div v-else class="shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white text-center">
                        <p class="text-gray-400 font-bold text-sm">Sin locker asignado actualmente.</p>
                        <Link href="/buscar-locker" class="inline-block mt-3 text-[#22397A] font-bold text-sm underline hover:text-blue-700 transition-colors">
                            Buscar un locker disponible →
                        </Link>
                    </div>

                    <Link href="/logout" method="post" as="button" class="self-end">
                        <p class="bg-[#DC2626] text-white px-6 py-2 rounded-md hover:bg-red-700 transition-colors text-sm font-medium">
                            Cerrar Sesión
                        </p>
                    </Link>
                </section>
            </div>

        </div>
    </div>
</template>