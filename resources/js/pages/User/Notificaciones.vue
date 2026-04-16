<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// El controlador NotificationController@index
// devuelve las notificaciones del usuario logueado
// ─────────────────────────────────────────────
const props = defineProps<{
    notificaciones: Array<{
        notification_id: number;
        notification_type: string | null;
        title: string;
        message: string;
        is_read: boolean;
        created_at: string;
    }>
}>();

// Buscador local (filtra en el frontend sin recargar la página)
const search = ref('');

const notificacionesFiltradas = computed(() => {
    if (!search.value) return props.notificaciones;
    const query = search.value.toLowerCase();
    return props.notificaciones.filter(n => 
        n.title.toLowerCase().includes(query) ||
        n.message.toLowerCase().includes(query)
    );
});

// Marcar una notificación como leída (POST al backend)
const marcarLeida = (id: number) => {
    router.post(`/notificaciones/${id}/leer`, {}, {
        // preserveScroll evita que la página salte hacia arriba al hacer el POST
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Notificaciones" />
    <div class="flex flex-col items-center min-h-screen py-12 px-4 bg-white">

        <section class="w-full max-w-2xl flex flex-col items-center gap-6">

            <h1 class="text-3xl font-extrabold text-black">Notificaciones</h1>

            <!-- Buscador -->
            <div class="w-full max-w-md relative mt-2 mb-6">
                <div class="absolute inset-y-0 left-4 flex items-center justify-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-gray-500">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input 
                    v-model="search"
                    type="text" 
                    placeholder="Buscar notificación" 
                    class="w-full py-3 pl-12 pr-6 border border-gray-300 rounded-[2rem] text-sm text-center outline-none focus:border-gray-400 focus:ring-0 shadow-sm placeholder:text-center placeholder:text-gray-400"
                    aria-label="Buscar notificaciones"
                />
            </div>

            <div class="w-full max-w-[500px] text-left">
                <h2 class="text-md sm:text-lg font-extrabold text-black mb-4">Visualización notificaciones</h2>

                <div class="flex flex-col gap-4">
                    <template v-if="notificacionesFiltradas.length > 0">
                        <div 
                            v-for="n in notificacionesFiltradas" 
                            :key="n.notification_id" 
                            class="p-6 w-full flex flex-col gap-2 rounded-md transition-all duration-300"
                            :class="n.is_read ? 'bg-[#e5e7eb]' : 'bg-[#dbeafe] border border-blue-200'"
                        >
                            <!-- Título y badge de no leída -->
                            <div class="flex items-center justify-between">
                                <p class="font-extrabold text-black">{{ n.title }}</p>
                                <span v-if="!n.is_read" class="text-xs bg-blue-500 text-white font-bold px-2 py-0.5 rounded-full">Nueva</span>
                            </div>
                            
                            <p class="text-black text-sm">{{ n.message }}</p>
                            <p class="text-gray-500 text-xs">{{ n.created_at }}</p>

                            <!-- Botón para marcar como leída -->
                            <button 
                                v-if="!n.is_read"
                                @click="marcarLeida(n.notification_id)"
                                class="self-start text-xs text-blue-600 font-bold mt-1 hover:underline"
                            >
                                Marcar como leída
                            </button>
                        </div>
                    </template>

                    <div v-else class="text-center py-10 opacity-60">
                        <p class="font-bold text-gray-500">
                            {{ search ? `No hay notificaciones que coincidan con "${search}"` : 'No tienes notificaciones aún.' }}
                        </p>
                    </div>
                </div>
            </div>

        </section>
    </div>
</template>