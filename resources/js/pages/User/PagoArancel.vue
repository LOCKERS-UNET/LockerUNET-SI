<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// El controlador PaymentController@index
// devuelve los pagos del usuario logueado
// ─────────────────────────────────────────────
defineProps<{
    pagos: Array<{
        payment_id: number;
        amount: number;
        due_date: string;
        payment_status: string; // pending | paid | overdue
        semester: string | null;
        assignment: {
            locker: { locker_code: string; locker_type: string }
        }
    }>;
    tarifas: Array<{
        rate_id: number;
        locker_type: string;
        monthly_amount: number;
        effective_from: string;
    }>;
}>();

const estadoLabel: Record<string, string> = {
    pending: 'Pendiente',
    paid:    'Pagado',
    overdue: 'Vencido',
};

const estadoColor: Record<string, string> = {
    pending: 'text-orange-500',
    paid:    'text-green-600',
    overdue: 'text-red-500',
};

const tipoLabel: Record<string, string> = {
    small: 'Pequeño',
    mid:   'Mediano',
    large: 'Grande',
};
</script>

<template>
    <div class="flex flex-col items-center justify-center py-5">
        <section class="w-full max-w-3xl flex flex-col gap-5 items-center p-5">

            <h1 class="text-4xl text-center font-bold mb-5">Pago Arancel</h1>

            <!-- Lista de pagos reales -->
            <template v-if="pagos.length > 0">
                <div 
                    v-for="pago in pagos" 
                    :key="pago.payment_id"
                    class="w-1/2 flex flex-row justify-center items-center shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white"
                >
                    <div class="flex flex-col gap-3 w-full">
                        <p class="self-center font-bold flex flex-row gap-2 text-xs sm:block sm:text-base">
                            Locker:
                            <span class="font-normal block text-xs sm:text-base sm:inline">{{ pago.assignment.locker.locker_code }}</span>
                        </p>
                        <p class="self-center font-bold flex flex-row gap-2 text-xs sm:block sm:text-base">
                            Tipo:
                            <span class="font-normal block text-xs sm:text-base sm:inline">{{ tipoLabel[pago.assignment.locker.locker_type] ?? pago.assignment.locker.locker_type }}</span>
                        </p>
                        <p class="self-center font-bold flex flex-row gap-2 text-xs sm:block sm:text-base">
                            Monto:
                            <span class="font-normal block text-xs sm:text-base sm:inline">Bs. {{ pago.amount }}</span>
                        </p>
                        <p class="self-center font-bold flex flex-row gap-2 text-xs sm:block sm:text-base">
                            Fecha límite:
                            <span class="font-normal block text-xs sm:text-base sm:inline">{{ pago.due_date }}</span>
                        </p>
                        <p class="self-center font-bold flex flex-row gap-2 text-xs sm:block sm:text-base">
                            Estado:
                            <span :class="estadoColor[pago.payment_status]" class="font-bold block text-xs sm:text-base sm:inline">
                                {{ estadoLabel[pago.payment_status] ?? pago.payment_status }}
                            </span>
                        </p>
                        <p v-if="pago.semester" class="text-gray-500 text-xs">Semestre: {{ pago.semester }}</p>
                    </div>
                </div>
            </template>

            <!-- Si no hay pagos -->
            <template v-else>
                <div class="text-center py-8 opacity-60">
                    <p class="font-bold text-gray-500">No tienes pagos registrados actualmente.</p>
                </div>
            </template>

            <!-- Dirección de pago -->
            <div class="w-full flex flex-col gap-7 mt-6">
                <h2 class="text-xl sm:text-3xl font-bold text-[#4169C4]">Dirección</h2>
                <hr>
                <h3 class="self-center font-bold text-sm sm:text-lg">Decanato de Desarrollo Estudiantil</h3>
                <hr>
                <p class="self-center flex flex-row font-bold items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-base font-normal">8:00am - 2:00pm</span>
                </p>

                <Link 
                    href="/"
                    class="self-center bg-[#22397A] py-1 px-3 text-white text-lg font-bold rounded-lg"
                >
                    Entendido
                </Link>
            </div>

        </section>
    </div>
</template>
