<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';  // 👈🏼 Agregar 'router'
import { computed, ref } from 'vue';  // 👈🏼 Agregar 'ref'
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

defineOptions({ layout: LayoutAdmin });

// 👇🏼 Agregar 'semesters' a los props
const props = defineProps<{
    rates?: Record<string, any>;
    semesters?: Array<{
        id: number;
        name: string;
        start_month: number;
        start_year: number;
        end_month: number;
        end_year: number;
        is_active: boolean;
    }>;
}>();

const form = useForm({
    locker_type: 'small',
    monthly_amount: null as number | null,
    reason: ''
});

// 👇🏼 Estado para generación de pagos (fuera de computed)
const selectedSemester = ref('');
const generating = ref(false);

const tipoLabel = (type: string) => {
    if (type === 'small') return 'Pequeño';
    if (type === 'mid') return 'Mediano';
    return 'Grande';
};

const aranceles = computed(() => {
    const rates = props.rates || {};

    return ['small', 'mid', 'large']
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
});

const submitNuevoArancel = () => {
    form.post('/admin/fee-rates', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        }
    });
};

const generarPagosSemestre = () => {
    if (!selectedSemester.value) {
        alert('Selecciona un semestre primero');
        return;
    }

    if (!confirm(`¿Generar pagos para el semestre ${selectedSemester.value}?\n\nSe crearán pagos pendientes para todos los usuarios con lockers activos.`)) {
        return;
    }

    generating.value = true;

    router.post('/admin/payments/generate-semester', {
        semester_name: selectedSemester.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedSemester.value = '';
            generating.value = false;
        },
        onError: () => {
            generating.value = false;
        }
    });
};
</script>

<template>
    <Head title="Aranceles" />
    <div class="min-h-screen bg-white py-12 px-4 flex justify-center">

        <div class="w-full max-w-2xl flex flex-col items-center">
            
            <h1 class="text-3xl sm:text-4xl font-extrabold text-black mb-8 mt-4">Aranceles</h1>
            <h2 class="text-lg sm:text-xl font-black text-[#4472c4] mb-12">Montos mensuales por tamaño</h2>

            <!-- Lista de Aranceles -->
            <div v-if="aranceles.length === 0" class="text-center py-8">
                <p class="text-gray-500">No hay aranceles disponibles</p>
            </div>

            <div v-else class="w-full max-w-[500px] flex flex-col gap-6">
                <div v-for="a in aranceles" :key="a.rate_id" class="w-full border border-gray-200 shadow-[0px_4px_15px_rgba(0,0,0,0.06)] rounded-sm pt-6 pb-4 px-6 flex flex-col sm:flex-row justify-between items-center bg-white gap-4">
                    
                    <div class="flex flex-col text-center sm:text-left">
                        <p class="font-extrabold text-black text-sm mb-1">{{ tipoLabel(a.locker_type) }}</p>
                        <p class="text-[#213779] font-black text-xl sm:text-2xl mb-1">Bs. {{ Number(a.monthly_amount) }}</p>
                        <p class="text-gray-400 text-[11px] font-semibold">Por mes</p>
                    </div>

                    <div class="flex flex-col items-center w-full sm:w-[160px]">
                        <p class="text-gray-400 text-[11px] sm:text-xs font-semibold">Desde {{ new Date(a.created_at).toLocaleDateString('es-ES') }}</p>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 1: Registrar nuevo arancel -->
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

            <!-- SECCIÓN 2: Generar Pagos Semestrales (SEPARADA) -->
            <div class="w-full max-w-2xl flex flex-col items-center mt-12">
                <h2 class="text-2xl sm:text-3xl font-black text-black mb-6">Generar Pagos Semestrales</h2>
                
                <div v-if="props.semesters && props.semesters.length > 0" class="w-full max-w-lg bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-2">
                            <label class="font-extrabold text-black text-sm">Seleccionar Semestre</label>
                            <select v-model="selectedSemester" class="w-full h-12 px-4 border border-gray-200 rounded-xl bg-[#f3f4f6] focus:outline-none focus:border-gray-300">
                                <option value="">Selecciona un semestre...</option>
                                <option v-for="sem in props.semesters" :key="sem.id" :value="sem.name">
                                    {{ sem.name }} ({{ sem.start_month }}/{{ sem.start_year }} - {{ sem.end_month }}/{{ sem.end_year }})
                                </option>
                            </select>
                        </div>

                        <button
                            @click="generarPagosSemestre"
                            :disabled="!selectedSemester || generating"
                            class="w-full py-3 text-white bg-[#0D7A5F] hover:bg-[#0a5f4a] font-extrabold rounded-xl transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ generating ? 'Generando...' : 'Generar Pagos del Semestre' }}
                        </button>

                        <p class="text-xs text-gray-500 text-center">
                            Esto creará un pago pendiente para todos los usuarios con lockers activos
                        </p>
                    </div>
                </div>

                <div v-else class="text-center py-8 bg-yellow-50 rounded-xl border border-yellow-200">
                    <p class="text-gray-600 font-bold">No hay semestres activos registrados</p>
                    <p class="text-sm text-gray-500 mt-2">Primero crea un semestre en Estadísticas</p>
                </div>
            </div>

        </div>
    </div>
</template>