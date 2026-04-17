<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps<{
    rates?: Record<string, any>;
}>();

const aranceles = ref<any[]>([]);
const loading = ref(true);

const form = useForm({
    locker_type: 'small',
    monthly_amount: null as number | null,
    reason: ''
});

const tipoLabel = (type: string) => {
    if (type === 'small') return 'Pequeño';
    if (type === 'mid') return 'Mediano';
    return 'Grande';
};

const buildAranceles = () => {
    const rates = props.rates || {};

    aranceles.value = ['small', 'mid', 'large']
        .map((type) => {
            const rate = rates[type];
            if (!rate) return null;

            return {
                ...rate,
                locker_type: type,
                monthly_amount: rate.monthly_amount,
            };
        })
        .filter((item) => item !== null);
};

const submitNuevoArancel = () => {
    form.post('/admin/fee-rates', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        }
    });
};

onMounted(() => {
    buildAranceles();
    loading.value = false;
});
</script>

<template>
    <Head title="Aranceles" />
    <div class="min-h-screen bg-white py-12 px-4 flex justify-center">

        <!-- ── VISTA 1: Lista de Aranceles ── -->
        <div class="w-full max-w-2xl flex flex-col items-center">
            
            <h1 class="text-3xl sm:text-4xl font-extrabold text-black mb-8 mt-4">Aranceles</h1>
            <h2 class="text-lg sm:text-xl font-black text-[#4472c4] mb-12">Montos mensuales por tamaño</h2>

            <div v-if="loading" class="text-center py-8">
                <p class="text-gray-500">Cargando aranceles...</p>
            </div>

            <div v-else-if="aranceles.length === 0" class="text-center py-8">
                <p class="text-gray-500">No hay aranceles disponibles</p>
            </div>

            <div v-else class="w-full max-w-[500px] flex flex-col gap-6">
                <!-- Tarjeta Arancel Iterable -->
                <div v-for="a in aranceles" :key="a.rate_id" class="w-full border border-gray-200 shadow-[0px_4px_15px_rgba(0,0,0,0.06)] rounded-sm pt-6 pb-4 px-6 flex flex-col sm:flex-row justify-between items-center bg-white gap-4">
                    
                    <!-- Datos Lado Izquierdo -->
                    <div class="flex flex-col text-center sm:text-left">
                        <p class="font-extrabold text-black text-sm mb-1">{{ tipoLabel(a.locker_type) }}</p>
                        <p class="text-[#213779] font-black text-xl sm:text-2xl mb-1">Bs. {{ a.monthly_amount }}</p>
                        <p class="text-gray-400 text-[11px] font-semibold">Por mes</p>
                    </div>

                    <!-- Fecha -->
                    <div class="flex flex-col items-center w-full sm:w-[160px]">
                        <p class="text-gray-400 text-[11px] sm:text-xs font-semibold">Desde {{ new Date(a.created_at).toLocaleDateString('es-ES') }}</p>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-2xl flex flex-col items-center mt-12">
                <h2 class="text-2xl sm:text-3xl font-black text-black mb-6">Registrar nuevo arancel</h2>
            <div class="w-full max-w-lg bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-2">
                        <label class="font-extrabold text-black text-sm">Tamaño de locker</label>
                        <select v-model="form.locker_type" class="w-full h-12 px-4 border border-gray-200 rounded-xl bg-[#f3f4f6] focus:outline-none focus:border-gray-300">
                            <option value="small">Pequeño</option>
                            <option value="mid">Mediano</option>
                            <option value="large">Grande</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-extrabold text-black text-sm">Monto mensual (Bs.)</label>
                        <input
                            type="number"
                            v-model="form.monthly_amount"
                            placeholder="Ej. 150"
                            class="w-full h-12 px-4 border border-gray-200 rounded-xl bg-[#f3f4f6] focus:outline-none focus:border-gray-300"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-extrabold text-black text-sm">Motivo</label>
                        <textarea
                            v-model="form.reason"
                            rows="4"
                            placeholder="Describe el motivo del ajuste"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-[#f3f4f6] focus:outline-none focus:border-gray-300 resize-none"
                        ></textarea>
                    </div>

                    <button
                        @click="submitNuevoArancel"
                        :disabled="form.processing"
                        class="w-full py-3 text-white bg-[#213779] hover:bg-[#1a2b5f] font-extrabold rounded-xl transition active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando...' : 'Registrar nuevo arancel' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>