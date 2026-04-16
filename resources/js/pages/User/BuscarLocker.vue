<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// El controlador LockerController@disponibles
// nos pasa los lockers disponibles via Inertia
// ─────────────────────────────────────────────
const props = defineProps<{
    lockers: Array<{
        locker_id: number;
        locker_code: string;
        locker_type: string;  // small | mid | large
        plate_number: string | null;
        status: number;       // 0=disponible, 1=ocupado, 2=mantenimiento
        sector: {
            sector_name: string;
            building: {
                building_name: string;
                building_code: string;
            }
        }
    }>
}>();

// Mapeamos los tipos a texto legible
const tipoLabel: Record<string, string> = {
    small: 'Pequeño',
    mid:   'Mediano',
    large: 'Grande',
};

// Filtros reactivos
const filtroEdificio = ref('');
const filtroTamano   = ref('');

// Lockers filtrados según lo que el usuario seleccione
const lockersFiltrados = computed(() => {
    return props.lockers.filter(locker => {
        const matchEdificio = filtroEdificio.value === '' || 
                              locker.sector.building.building_code === filtroEdificio.value;
        const matchTamano   = filtroTamano.value === '' || 
                              locker.locker_type === filtroTamano.value;
        return matchEdificio && matchTamano;
    });
});

// Lista de edificios únicos para el select
const edificios = [...new Map(props.lockers.map(l => 
    [l.sector.building.building_code, l.sector.building]
)).values()];

// Al hacer clic en "Solicitar" vamos a la página de confirmación
const irASolicitud = (locker: typeof props.lockers[0]) => {
    router.get('/solicitud-locker', {
        locker_id: locker.locker_id,
        codigo:    locker.locker_code,
        tamano:    tipoLabel[locker.locker_type] ?? locker.locker_type,
        edificio:  `${locker.sector.building.building_name} - ${locker.sector.sector_name}`,
    });
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
                        <select v-model="filtroEdificio" class="w-full h-12 px-6 border border-gray-200 rounded-[2rem] font-bold text-sm text-black appearance-none bg-white focus:outline-none focus:border-gray-400">
                            <option value="">Edificio (Todos)</option>
                            <option v-for="ed in edificios" :key="ed.building_code" :value="ed.building_code">
                                {{ ed.building_name }}
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
                        <select v-model="filtroTamano" class="w-full h-12 px-6 border border-gray-200 rounded-[2rem] font-bold text-sm text-black appearance-none bg-white focus:outline-none focus:border-gray-400">
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

                <!-- Lista de Lockers -->
                <div class="w-full max-w-3xl flex flex-col">
                    
                    <template v-if="lockersFiltrados.length > 0">
                        <div v-for="(locker, idx) in lockersFiltrados" :key="locker.locker_id" class="w-full">
                            <div class="h-px bg-gray-300 w-full my-6"></div>

                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="flex flex-col gap-1 w-full sm:w-1/3 text-center sm:text-left">
                                    <p class="text-sm sm:text-base text-black"><span class="font-extrabold">Locker:</span> {{ locker.locker_code }}</p>
                                    <p class="text-sm sm:text-base text-black"><span class="font-extrabold">Tamaño:</span> {{ tipoLabel[locker.locker_type] ?? locker.locker_type }}</p>
                                    <p class="text-sm sm:text-base text-black"><span class="font-extrabold">Sector:</span> {{ locker.sector.sector_name }}</p>
                                </div>

                                <!-- Estado: todos son disponibles porque el controller ya filtra por status=0 -->
                                <div class="w-full sm:w-1/3 flex justify-center">
                                    <span class="font-extrabold text-sm sm:text-base tracking-wide text-[#0D7A5F]">Disponible</span>
                                </div>

                                <div class="w-full sm:w-1/3 flex justify-center sm:justify-end">
                                    <button 
                                        @click="irASolicitud(locker)"
                                        class="bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold py-2 px-8 rounded-xl shadow-md transition duration-300 active:scale-95 text-sm"
                                    >
                                        Solicitar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="h-px bg-gray-300 w-full mt-6"></div>
                    </template>
                    
                    <template v-else>
                        <div class="py-12 flex flex-col items-center justify-center opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 mb-4 text-gray-400">
                                <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                            </svg>
                            <p class="font-bold text-gray-500">No hay lockers disponibles con esos filtros</p>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>
</template>