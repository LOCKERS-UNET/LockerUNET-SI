<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

type Locker = {
    locker_id: number;
    locker_code: string;
    locker_type: string;
    sector: {
        sector_name: string;
        building: { building_name: string; building_code: string };
    };
};

// El backend manda solo lockers disponibles, ya filtrados y paginados (15 por página)
const props = defineProps<{
    lockers: { data: Locker[]; current_page: number; last_page: number; total: number };
    edificios: Array<{ building_code: string; building_name: string; disponibles: number }>;
    filtros: { edificio: string; tamano: string };
    bloqueo: { mensaje: string; enlace: string; texto_enlace: string } | null; // ya tiene un locker o una solicitud pendiente
}>();

// Mapeamos los tipos a texto legible
const tipoLabel: Record<string, string> = {
    small: 'Pequeño',
    mid:   'Mediano',
    large: 'Grande',
};

const filtroEdificio = ref(props.filtros.edificio);
const filtroTamano   = ref(props.filtros.tamano);
const cargando = ref(false);

// Error de la página de solicitud (p. ej. el locker ya no estaba disponible)
const errorLocker = computed(() => usePage().props.errors?.locker as string | undefined);

const buscar = (pagina = 1) => {
    router.get('/lockers', {
        edificio: filtroEdificio.value || undefined,
        tamano: filtroTamano.value || undefined,
        page: pagina > 1 ? pagina : undefined,
    }, {
        preserveState: true,
        replace: true,
        onStart: () => (cargando.value = true),
        onFinish: () => {
            cargando.value = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};

// Al hacer clic en "Solicitar" vamos a la página de confirmación
const irASolicitud = (locker: Locker) => {
    // Solo el id: la página de solicitud carga el resto desde la base
    router.get('/solicitud-locker', { locker_id: locker.locker_id });
};
</script>

<template>
    <Head title="Buscar Locker" />
    <div class="min-h-screen bg-white py-12 px-4 flex justify-center">

        <div class="w-full max-w-4xl flex flex-col items-center">
            
            <div class="w-full border-[5px] border-[#1da1f2] rounded-md p-6 sm:p-12 flex flex-col items-center">
                
                <h1 class="text-2xl sm:text-3xl font-extrabold text-black mb-8">Búsqueda de Lockers</h1>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6 w-full max-w-2xl mb-12">
                    
                    <!-- Filtro por Edificio -->
                    <div class="relative w-full shadow-sm rounded-full">
                        <select v-model="filtroEdificio" @change="buscar()" aria-label="Filtrar por edificio" class="w-full h-12 px-6 border border-gray-200 rounded-[2rem] font-bold text-base sm:text-sm text-black appearance-none bg-white focus:outline-none focus:border-gray-400">
                            <option value="">Edificio (Todos)</option>
                            <option v-for="ed in edificios" :key="ed.building_code" :value="ed.building_code">
                                {{ ed.building_name }} ({{ ed.disponibles }})
                            </option>
                        </select>
                        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-black">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filtro por Tamaño -->
                    <div class="relative w-full shadow-sm rounded-full">
                        <select v-model="filtroTamano" @change="buscar()" aria-label="Filtrar por tamaño" class="w-full h-12 px-6 border border-gray-200 rounded-[2rem] font-bold text-base sm:text-sm text-black appearance-none bg-white focus:outline-none focus:border-gray-400">
                            <option value="">Tamaño (Todos)</option>
                            <option value="small">Pequeño</option>
                            <option value="mid">Mediano</option>
                            <option value="large">Grande</option>
                        </select>
                        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-black">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                </div>

                <div v-if="bloqueo" class="w-full max-w-2xl mb-6 bg-[#FEF3C7] border border-[#F59E0B] text-[#92400E] font-semibold p-4 text-center rounded-2xl text-sm">
                    {{ bloqueo.mensaje }}
                    <Link :href="bloqueo.enlace" class="block mt-2 underline font-bold min-h-[44px] leading-[44px]">{{ bloqueo.texto_enlace }}</Link>
                </div>

                <div v-if="errorLocker" class="w-full max-w-2xl mb-6 bg-[#FEE2E2] border border-[#DC2626] text-[#DC2626] font-semibold p-3 text-center rounded-2xl text-sm">
                    {{ errorLocker }}
                </div>

                <p class="w-full max-w-3xl text-sm font-bold text-gray-500 mb-2" aria-live="polite">
                    <template v-if="cargando">Buscando lockers…</template>
                    <template v-else>{{ lockers.total }} {{ lockers.total === 1 ? 'locker disponible' : 'lockers disponibles' }}</template>
                </p>

                <!-- 👇🏼 Lista de Lockers DISPONIBLES -->
                <div class="w-full max-w-3xl flex flex-col transition-opacity" :class="{ 'opacity-50 pointer-events-none': cargando }">
                    
                    <template v-if="lockers.data.length > 0">
                        <div v-for="locker in lockers.data" :key="locker.locker_id" class="w-full">
                            <div class="h-px bg-gray-300 w-full my-6"></div>

                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="flex flex-col gap-1 w-full sm:w-1/3 text-center sm:text-left">
                                    <p class="text-sm sm:text-base text-black"><span class="font-extrabold">Locker:</span> {{ locker.locker_code }}</p>
                                    <p class="text-sm sm:text-base text-black"><span class="font-extrabold">Tamaño:</span> {{ tipoLabel[locker.locker_type] ?? locker.locker_type }}</p>
                                    <p class="text-sm sm:text-base text-black"><span class="font-extrabold">Edificio:</span> {{ locker.sector.building.building_name }}</p>
                                    <p class="text-sm sm:text-base text-black"><span class="font-extrabold">Sector:</span> {{ locker.sector.sector_name }}</p>
                                </div>

                                <!-- 👇🏼 ESTADO: Siempre "Disponible" (verde) -->
                                <div class="w-full sm:w-1/3 flex justify-center">
                                    <span class="font-extrabold text-sm sm:text-base tracking-wide text-[#0D7A5F]">
                                        Disponible
                                    </span>
                                </div>

                                <div class="w-full sm:w-1/3 flex justify-center sm:justify-end">
                                    <!-- 👇 BOTÓN SIEMPRE visible (porque todos están disponibles) -->
                                    <button 
                                        @click="irASolicitud(locker)"
                                        :disabled="!!bloqueo"
                                        :title="bloqueo?.mensaje"
                                        class="bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold py-3 px-8 min-h-[44px] rounded-xl shadow-md transition duration-300 active:scale-95 text-sm disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed disabled:shadow-none"
                                    >
                                        Solicitar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="h-px bg-gray-300 w-full mt-6"></div>

                        <!-- Paginación -->
                        <nav v-if="lockers.last_page > 1" class="flex items-center justify-between gap-4 mt-8" aria-label="Paginación">
                            <button type="button" :disabled="lockers.current_page <= 1 || cargando" @click="buscar(lockers.current_page - 1)"
                                class="min-h-[44px] px-5 rounded-xl border border-gray-300 font-bold text-sm text-[#213779] disabled:text-gray-300 disabled:cursor-not-allowed">
                                ← Anterior
                            </button>
                            <span class="text-sm font-bold text-gray-600">Página {{ lockers.current_page }} de {{ lockers.last_page }}</span>
                            <button type="button" :disabled="lockers.current_page >= lockers.last_page || cargando" @click="buscar(lockers.current_page + 1)"
                                class="min-h-[44px] px-5 rounded-xl border border-gray-300 font-bold text-sm text-[#213779] disabled:text-gray-300 disabled:cursor-not-allowed">
                                Siguiente →
                            </button>
                        </nav>
                    </template>
                    
                    <template v-else>
                        <div class="py-12 flex flex-col items-center justify-center opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 mb-4 text-gray-400">
                                <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                            </svg>
                            <p class="font-bold text-gray-500">
                                {{ filtroEdificio || filtroTamano 
                                    ? 'No hay lockers disponibles con esos filtros' 
                                    : 'No hay lockers disponibles actualmente' 
                                }}
                            </p>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>
</template>