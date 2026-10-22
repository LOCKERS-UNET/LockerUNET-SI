<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ModalComponent from '../Components/ModalComponent.vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps<{
    requests?: any[];
}>();

// ── Filtro ──
const filtros: Record<string, string> = {
    Pendientes: 'pending',
    Aprobadas: 'approved',
    Rechazadas: 'rejected',
    Canceladas: 'cancelled',
};
const filtroActual = ref('Pendientes');

// Los props se recargan tras cada aprobación/rechazo (redirect back), así que se filtra sobre ellos directamente
const solicitudesFiltradas = computed(() => {
    return (props.requests || []).filter((sol) => sol.request_status === filtros[filtroActual.value]);
});

// ── Modal State ──
const modalAbierto = ref(false);
const modalMensaje = ref('');
const modalError = ref(false);
const procesando = ref<number | null>(null);

const procesar = (sol: any, accion: 'approve' | 'reject') => {
    procesando.value = sol.request_id;

    router.put(`/admin/requests/${sol.request_id}/${accion}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            modalError.value = false;
            modalMensaje.value = accion === 'approve'
                ? '¡Solicitud aprobada! Se creó la asignación y el pago del semestre.'
                : 'Solicitud rechazada. El estudiante fue notificado.';
            modalAbierto.value = true;
        },
        onError: (errors) => {
            modalError.value = true;
            modalMensaje.value = Object.values(errors)[0] || 'No se pudo procesar la solicitud. Intenta de nuevo.';
            modalAbierto.value = true;
        },
        onFinish: () => {
            procesando.value = null;
        },
    });
};
</script>

<template>
    <Head title="Solicitudes" />
    <div class="min-h-screen bg-white py-12 px-4 flex justify-center">

        <div class="w-full max-w-4xl flex flex-col items-center">

            <h1 class="text-4xl sm:text-5xl font-black text-black mb-10">Solicitudes</h1>

            <!-- Filtro -->
            <div class="flex items-center gap-4 mb-12">
                <span class="font-extrabold text-black text-lg">Filtrar Por:</span>
                <div class="relative w-[180px]">
                    <select v-model="filtroActual" class="w-full h-10 px-4 pr-10 border border-gray-200 shadow-sm rounded-xl font-extrabold text-sm text-black appearance-none bg-white focus:outline-none focus:border-gray-400 cursor-pointer">
                        <option v-for="(_, nombre) in filtros" :key="nombre" :value="nombre">{{ nombre }}</option>
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
                <h2 class="text-xl sm:text-2xl font-black text-black">
                    Solicitudes <span class="text-[#4472c4]">{{ filtroActual }}</span>:
                </h2>
            </div>

            <!-- Lista de Solicitudes -->
            <div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pb-20">

                <template v-if="solicitudesFiltradas.length > 0">
                    <div v-for="sol in solicitudesFiltradas" :key="sol.request_id" class="bg-[#e4ebf7] rounded-sm p-3 flex flex-col shadow-sm border border-gray-100 hover:shadow-md transition">

                        <!-- Encabezado Tarjeta -->
                        <div class="w-full mb-2">
                            <h3 class="text-sm font-black text-black">{{ sol.user?.name }} {{ sol.user?.lastname }}</h3>
                            <div class="h-px bg-gray-300 w-full mt-1"></div>
                        </div>

                        <!-- Detalles -->
                        <div class="flex flex-col gap-0 mb-3 flex-1">
                            <p class="text-[11px] text-gray-800"><span class="font-bold text-black">Carnet:</span> {{ sol.user?.card_code || 'N/A' }}</p>
                            <p class="text-[11px] text-gray-800"><span class="font-bold text-black">Locker:</span> {{ sol.locker?.locker_code || 'N/A' }}</p>
                            <p class="text-[11px] text-gray-800"><span class="font-bold text-black">Ubicación:</span> {{ sol.locker?.sector?.building?.building_code || 'N/A' }} - {{ sol.locker?.sector?.sector_name || 'N/A' }}</p>
                            <p class="text-[11px] text-gray-800"><span class="font-bold text-black">Fecha:</span> {{ new Date(sol.requested_at).toLocaleDateString('es-ES') }}</p>
                        </div>

                        <!-- Acciones -->
                        <div v-if="sol.request_status === 'pending'" class="w-full flex gap-2 mt-auto">
                            <button
                                :disabled="procesando === sol.request_id"
                                @click="procesar(sol, 'approve')"
                                class="flex-1 bg-[#213779] hover:bg-[#1a2b5f] text-white font-extrabold py-1.5 px-2 rounded-md shadow-sm transition active:scale-95 text-[10px] disabled:opacity-50"
                            >
                                Aprobar
                            </button>
                            <button
                                :disabled="procesando === sol.request_id"
                                @click="procesar(sol, 'reject')"
                                class="flex-1 bg-white hover:bg-red-50 text-red-600 border border-red-200 font-extrabold py-1.5 px-2 rounded-md shadow-sm transition active:scale-95 text-[10px] disabled:opacity-50"
                            >
                                Rechazar
                            </button>
                        </div>
                        <div v-else class="w-full flex justify-center mt-auto py-1">
                            <span v-if="sol.request_status === 'approved'" class="text-[#0D7A5F] font-bold italic text-[10px]">Aprobada</span>
                            <span v-else-if="sol.request_status === 'cancelled'" class="text-gray-500 font-bold italic text-[10px]">Cancelada por el estudiante</span>
                            <span v-else class="text-red-500 font-bold italic text-[10px]">Rechazada</span>
                        </div>

                    </div>
                </template>
                <template v-else>
                    <div class="col-span-full flex justify-center py-10 opacity-50">
                        <p class="font-bold text-gray-500">No hay solicitudes {{ filtroActual.toLowerCase() }}.</p>
                    </div>
                </template>

            </div>

        </div>

        <!-- Modal -->
        <ModalComponent
            :show="modalAbierto"
            :text="modalMensaje"
            :error="modalError"
            title-button="Aceptar"
            url=""
            @close="modalAbierto = false"
        />

    </div>
</template>
