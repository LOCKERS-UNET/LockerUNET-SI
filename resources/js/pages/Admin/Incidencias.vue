<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ModalComponent from '../Components/ModalComponent.vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps<{
    incidents?: any[];
}>();

// ── Variables Reactivas ──
const filtroActual = ref('Pendientes'); // 'Pendientes' | 'Revisadas'
const incidencias = ref<any[]>(props.incidents || []);

// ── Filtro Computado ──
const incidenciasFiltradas = computed(() => {
    return incidencias.value.filter((inc) => {
        if (filtroActual.value === 'Pendientes') {
            return inc.status === 'pending';
        }

        return inc.status === 'reviewed';
    });
});

// ── Modal State ──
const modalAbierto = ref(false);
const modalMensaje = ref('');
const formReview = useForm({});

const marcarComoRevisada = (inc: any) => {
    formReview.put(`/admin/incidents/${inc.incident_id}/review`, {
        preserveScroll: true,
        onSuccess: () => {
            inc.status = 'reviewed';
            modalMensaje.value = '¡Incidencia marcada como revisada!';
            modalAbierto.value = true;
        },
        onError: () => {
            modalMensaje.value = 'No se pudo actualizar la incidencia. Intenta de nuevo.';
            modalAbierto.value = true;
        }
    });
};
</script>

<template>
    <Head title="Incidencias" />
    <div class="min-h-screen bg-white py-12 px-4 flex justify-center">

        <!-- El contenedor se hizo un poquito más ancho (max-w-4xl) para acomodar las tarjetas si se muestran en cuadricula -->
        <div class="w-full max-w-4xl flex flex-col items-center">
            
            <!-- TITULO MAS GRANDE -->
            <h1 class="text-4xl sm:text-5xl font-black text-black mb-10">Incidencias</h1>

            <!-- Filtro -->
            <div class="flex items-center gap-4 mb-12">
                <span class="font-extrabold text-black text-lg">Filtrar Por:</span>
                <div class="relative w-[180px]">
                    <select v-model="filtroActual" class="w-full h-10 px-4 pr-10 border border-gray-200 shadow-sm rounded-xl font-extrabold text-sm text-black appearance-none bg-white focus:outline-none focus:border-gray-400 cursor-pointer">
                        <option value="Pendientes">Pendientes</option>
                        <option value="Revisadas">Revisadas</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#1a5eb8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Subtitulo Dinámico -->
            <div class="w-full flex justify-start mb-6">
                <!-- Tambien el titulo de la sección un poco mas grande -->
                <h2 class="text-xl sm:text-2xl font-black text-black">
                    Incidencias <span class="text-[#4472c4]">{{ filtroActual }}</span>:
                </h2>
            </div>

            <!-- Lista de Incidencias: Cuadrícula ultra compacta (3 columnas en PC) -->
            <div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pb-20">
                
                <template v-if="incidenciasFiltradas.length > 0">
                    <!-- TARJETA MINIATURIZADA -->
                    <div v-for="inc in incidenciasFiltradas" :key="inc.incident_id" class="bg-[#e4ebf7] rounded-sm p-3 flex flex-col shadow-sm border border-gray-100 hover:shadow-md transition">
                        
                        <!-- Encabezado Tarjeta -->
                        <div class="w-full mb-2">
                            <h3 class="text-sm font-black text-black">{{ inc.user?.name }} {{ inc.user?.lastname }}</h3>
                            <div class="h-px bg-gray-300 w-full mt-1"></div>
                        </div>

                        <!-- Detalles ultra compactos -->
                        <div class="flex flex-col gap-0 mb-3 flex-1">
                            <p class="text-[11px] text-gray-800"><span class="font-bold text-black">Locker:</span> {{ inc.locker?.locker_code || 'N/A' }}</p>
                            <p class="text-[11px] text-gray-800"><span class="font-bold text-black">Ubicación:</span> {{ inc.locker?.sector?.building?.building_code || 'N/A' }} - {{ inc.locker?.sector?.sector_name || 'N/A' }}</p>
                            <p class="text-[11px] text-gray-800"><span class="font-bold text-black">Fecha:</span> {{ new Date(inc.created_at).toLocaleDateString('es-ES') }}</p>
                            <p class="text-[11px] text-gray-800 mt-[2px] line-clamp-2 leading-tight" :title="inc.description">
                                <span class="font-bold text-black">Motivo:</span> {{ inc.description }}
                            </p>
                        </div>

                        <!-- Botón Acción Compacto -->
                        <div v-if="inc.status === 'pending'" class="w-full flex justify-center mt-auto">
                            <button @click="marcarComoRevisada(inc)" class="w-full bg-[#213779] hover:bg-[#1a2b5f] text-white font-extrabold py-1.5 px-2 rounded-md shadow-sm transition active:scale-95 text-[10px]">
                                Revisada
                            </button>
                        </div>
                        <div v-else class="w-full flex justify-center mt-auto py-1">
                            <span class="text-[#0D7A5F] font-bold italic text-[10px]">Revisada y resuelta</span>
                        </div>

                    </div>
                </template>
                <template v-else>
                    <div class="col-span-full flex justify-center py-10 opacity-50">
                        <p class="font-bold text-gray-500">No hay incidencias {{ filtroActual.toLowerCase() }}.</p>
                    </div>
                </template>

            </div>

        </div>

        <!-- Modal -->
        <ModalComponent
            :show="modalAbierto"
            :text="modalMensaje"
            title-button="Aceptar"
            url=""
            @close="modalAbierto = false"
        />

    </div>
</template>