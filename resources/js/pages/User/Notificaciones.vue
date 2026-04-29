<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps<{
    notificaciones: Array<{
        notification_id: number;
        notification_type: string | null;
        title: string;
        message: string;
        is_read: boolean;
        created_at: string;
    }>;
    pagination?: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        from: number;
        to: number;
    };
}>();

// Estado de paginación (inicializado desde props)
const currentPage = ref(props.pagination?.current_page ?? 1);
const totalPages = ref(props.pagination?.last_page ?? 1);
const totalItems = ref(props.pagination?.total ?? 0);

// Buscador
const search = ref('');

// 👇🏼 Calcular páginas visibles dinámicamente
const visiblePages = computed(() => {
    const pages: number[] = [];
    const maxVisible = 5;
    
    let start = Math.max(1, currentPage.value - 2);
    let end = Math.min(totalPages.value, start + maxVisible - 1);
    
    if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
    }
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});

// 👇 Navegación de paginación AUTOMÁTICA
const goToPage = (page: number) => {
    if (page < 1 || page > totalPages.value || page === currentPage.value) return;
    
    // Actualizar estado local inmediatamente
    currentPage.value = page;
    
    // Hacer petición al backend con Inertia
    router.get('/notifications', 
        { 
            page,
            search: search.value || null
        },
        {
            preserveState: true,  // 👈 Mantiene el estado del componente
            preserveScroll: true, // 👈 Mantiene la posición del scroll
            replace: true,        // 👈 No guarda en historial del navegador
        }
    );
};

const prevPage = () => goToPage(currentPage.value - 1);
const nextPage = () => goToPage(currentPage.value + 1);

// 👇🏼 Marcar como leída SIN recargar toda la página
const marcarLeida = (id: number) => {
    router.patch(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Actualizar el contador de no-leídas si es necesario
            // (esto se maneja automáticamente con Inertia)
        }
    });
};

const marcarTodasLeidas = () => {
    router.patch('/notifications/read-all', {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// 👇 Actualizar currentPage cuando cambien las props (por si Inertia recarga)
watch(() => props.pagination?.current_page, (newPage) => {
    if (newPage) {
        currentPage.value = newPage;
    }
});
</script>

<template>
    <Head title="Notificaciones" />
    <div class="flex flex-col items-center min-h-screen py-12 px-4 bg-white">

        <section class="w-full max-w-2xl flex flex-col items-center gap-6">

            <h1 class="text-3xl font-extrabold text-black">Notificaciones</h1>

            <!-- Buscador + Botón "Marcar todas" -->
            <div class="w-full max-w-md flex flex-col sm:flex-row gap-3 mt-2 mb-6">
                <div class="relative flex-1">
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
                
                <button 
                    v-if="notificaciones.some(n => !n.is_read)"
                    @click="marcarTodasLeidas"
                    class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-[2rem] transition shadow-sm"
                >
                    Marcar todas
                </button>
            </div>

            <div class="w-full max-w-[500px] text-left">
                <h2 class="text-md sm:text-lg font-extrabold text-black mb-4">
                    Visualización notificaciones 
                    <span class="text-gray-500 font-normal text-sm">
                        ({{ pagination?.total ?? totalItems }} total)
                    </span>
                </h2>

                <div class="flex flex-col gap-4">
                    <template v-if="notificaciones.length > 0">
                        <div 
                            v-for="n in notificaciones" 
                            :key="n.notification_id" 
                            class="p-6 w-full flex flex-col gap-2 rounded-md transition-all duration-300 relative"
                            :class="n.is_read ? 'bg-[#e5e7eb]' : 'bg-[#dbeafe] border border-blue-200'"
                        >
                            <!-- Badge de no leída -->
                            <span 
                                v-if="!n.is_read" 
                                class="absolute top-4 right-4 w-3 h-3 bg-blue-500 rounded-full animate-pulse"
                                title="Nueva"
                            ></span>
                            
                            <p class="font-extrabold text-black pr-6">{{ n.title }}</p>
                            <p class="text-black text-sm">{{ n.message }}</p>
                            <p class="text-gray-500 text-xs">{{ formatDate(n.created_at) }}</p>

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

                <!-- 👇🏼 PAGINACIÓN AUTOMÁTICA -->
                <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-8">
                    
                    <!-- Flecha anterior -->
                    <button 
                        @click="prevPage" 
                        :disabled="currentPage === 1"
                        class="p-2 rounded-full hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
                        aria-label="Página anterior"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    <!-- Números de página dinámicos -->
                    <div class="flex gap-1">
                        <button 
                            v-for="page in visiblePages" 
                            :key="page"
                            @click="goToPage(page)"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm font-bold transition',
                                page === currentPage 
                                    ? 'bg-[#213779] text-white' 
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </div>

                    <!-- Flecha siguiente -->
                    <button 
                        @click="nextPage" 
                        :disabled="currentPage === totalPages"
                        class="p-2 rounded-full hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
                        aria-label="Página siguiente"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

                <!-- Info de paginación dinámica -->
                <p v-if="totalPages > 1" class="text-center text-xs text-gray-500 mt-2">
                    Mostrando {{ pagination?.from ?? 1 }} - {{ pagination?.to ?? notificaciones.length }} de {{ pagination?.total ?? totalItems }} notificaciones
                    <br>
                    Página {{ currentPage }} de {{ totalPages }}
                </p>
            </div>

        </section>
    </div>
</template>