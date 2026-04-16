<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// El controlador LockerRequestController@misSolicitudes
// devuelve las solicitudes del usuario logueado
// ─────────────────────────────────────────────
const props = defineProps<{
    solicitudes: Array<{
        request_id: number;
        request_status: string; // pending | approved | rejected
        requested_at: string;
        locker: {
            locker_code: string;
            locker_type: string;
            sector: {
                sector_name: string;
                building: { building_name: string }
            }
        }
    }>
}>();

// Mapeamos los estados a texto en español y colores
const estadoLabel: Record<string, string> = {
    pending:  'Pendiente',
    approved: 'Aprobada',
    rejected: 'Rechazada',
};

const estadoColor: Record<string, string> = {
    pending:  'text-orange-500',
    approved: 'text-green-600',
    rejected: 'text-red-500',
};

const tipoLabel: Record<string, string> = {
    small: 'Pequeño',
    mid:   'Mediano',
    large: 'Grande',
};
</script>

<template>
    <Head title="Mis Solicitudes" />
    <div class="flex flex-col items-center min-h-screen py-10 px-4 bg-white">
        
        <section class="w-full max-w-lg flex flex-col items-center mt-6">
            
            <h1 class="text-3xl font-extrabold text-black mb-8">Mis Solicitudes</h1>

            <!-- Lista de solicitudes reales -->
            <template v-if="solicitudes.length > 0">
                <div 
                    v-for="solicitud in solicitudes" 
                    :key="solicitud.request_id"
                    class="bg-[#e5e7eb] px-10 py-8 w-full max-w-[380px] mb-6 flex flex-col gap-3 rounded-sm"
                >
                    <h2 class="text-2xl font-extrabold text-black">Locker {{ solicitud.locker.locker_code }}</h2>
                    <p class="text-black text-md">
                        <span class="font-bold">Tamaño:</span> {{ tipoLabel[solicitud.locker.locker_type] ?? solicitud.locker.locker_type }}
                    </p>
                    <p class="text-black text-md">
                        <span class="font-bold">Sector:</span> {{ solicitud.locker.sector.building.building_name }} - {{ solicitud.locker.sector.sector_name }}
                    </p>
                    <p class="text-black text-md">
                        <span class="font-bold">Fecha:</span> {{ solicitud.requested_at }}
                    </p>
                    <p class="text-md font-bold" :class="estadoColor[solicitud.request_status] ?? 'text-gray-500'">
                        Estado: {{ estadoLabel[solicitud.request_status] ?? solicitud.request_status }}
                    </p>
                </div>
            </template>

            <!-- Si no hay solicitudes -->
            <template v-else>
                <div class="text-center py-16 opacity-60">
                    <p class="font-bold text-gray-500 text-lg">No has hecho ninguna solicitud todavía.</p>
                    <Link href="/buscar-locker" class="mt-6 inline-block bg-[#213779] text-white font-bold py-3 px-8 rounded-xl">
                        Buscar un Locker
                    </Link>
                </div>
            </template>

            <!-- Botón volver -->
            <div class="mt-8">
                <Link href="/" class="bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold py-4 px-12 rounded-xl shadow-md transition-colors text-sm">
                    Volver al Menú
                </Link>
            </div>

        </section>
        
    </div>
</template>