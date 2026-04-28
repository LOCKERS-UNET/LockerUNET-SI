<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// El controlador PaymentController@myPayments
// devuelve los pagos del usuario logueado
// ─────────────────────────────────────────────
defineProps<{
    pagos: Array<{
        payment_id: number;
        amount: number;
        due_date: string;
        payment_status: string; // pending | paid | overdue
        semester: string | null;
        created_at?: string;
        assignment: {
            locker: { 
                locker_code: string; 
                locker_type: string;
                locker_id?: number;
            }
        }
    }>;
    tarifas?: Array<{
        rate_id: number;
        locker_type: string;
        monthly_amount: number;
        effective_from: string;
    }>;
    activeSemester?: {
        id: number;
        name: string;
        start_month: number;
        start_year: number;
        end_month: number;
        end_year: number;
        is_active: boolean;
    } | null;
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

// Formatear fecha de forma legible
const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <div class="flex flex-col items-center justify-center py-5">
        <section class="w-full max-w-3xl flex flex-col gap-5 items-center p-5">

            <h1 class="text-4xl text-center font-bold mb-2">Pago Arancel</h1>
            
            <!-- Semestre activo -->
            <p v-if="activeSemester" class="text-sm text-gray-500 font-bold mb-4">
                Semestre: {{ activeSemester.name }}
            </p>

            <!-- Lista de pagos -->
            <template v-if="pagos && pagos.length > 0">
                <div 
                    v-for="pago in pagos" 
                    :key="pago.payment_id"
                    class="w-full max-w-2xl flex flex-col gap-3 shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white mb-4 border-l-4"
                    :class="{
                        'border-orange-400': pago.payment_status === 'pending',
                        'border-green-500': pago.payment_status === 'paid',
                        'border-red-500': pago.payment_status === 'overdue'
                    }"
                >
                    <!-- Badge de estado -->
                    <div class="flex items-center justify-between">
                        <span 
                            class="text-xs font-bold px-3 py-1 rounded-full"
                            :class="{
                                'bg-orange-100 text-orange-700': pago.payment_status === 'pending',
                                'bg-green-100 text-green-700': pago.payment_status === 'paid',
                                'bg-red-100 text-red-700': pago.payment_status === 'overdue'
                            }"
                        >
                            {{ estadoLabel[pago.payment_status] ?? pago.payment_status }}
                        </span>
                        
                        <span v-if="pago.semester" class="text-xs text-gray-400">
                            {{ pago.semester }}
                        </span>
                    </div>

                    <!-- Detalles del pago -->
                    <div class="flex flex-col gap-2 w-full">
                        <p class="font-bold flex flex-row gap-2 text-sm">
                            <span class="text-gray-600">Locker:</span>
                            <span class="font-normal">{{ pago.assignment.locker.locker_code }}</span>
                        </p>
                        
                        <p class="font-bold flex flex-row gap-2 text-sm">
                            <span class="text-gray-600">Tipo:</span>
                            <span class="font-normal">{{ tipoLabel[pago.assignment.locker.locker_type] ?? pago.assignment.locker.locker_type }}</span>
                        </p>
                        
                        <p class="font-bold flex flex-row gap-2 text-sm">
                            <span class="text-gray-600">Monto:</span>
                            <span class="font-normal text-lg font-bold text-[#213779]">
                                Bs. {{ Number(pago.amount).toFixed(2) }}
                            </span>
                        </p>
                        
                        <p class="font-bold flex flex-row gap-2 text-sm">
                            <span class="text-gray-600">Fecha límite:</span>
                            <span class="font-normal" :class="pago.payment_status === 'overdue' ? 'text-red-500 font-bold' : ''">
                                {{ formatDate(pago.due_date) }}
                            </span>
                        </p>
                    </div>

                    <!-- Mensaje según estado -->
                    <div v-if="pago.payment_status === 'pending'" class="mt-2 p-3 bg-orange-50 rounded-lg border border-orange-200">
                        <p class="text-xs text-orange-700 font-bold">
                            ⚠️ Este pago está pendiente. Dirígete al Decanato para cancelar.
                        </p>
                    </div>
                    
                    <div v-else-if="pago.payment_status === 'overdue'" class="mt-2 p-3 bg-red-50 rounded-lg border border-red-200">
                        <p class="text-xs text-red-700 font-bold">
                            ❌ Pago vencido. Regulariza tu situación lo antes posible.
                        </p>
                    </div>
                    
                    <div v-else class="mt-2 p-3 bg-green-50 rounded-lg border border-green-200">
                        <p class="text-xs text-green-700 font-bold">
                            ✓ Pago confirmado. ¡Gracias!
                        </p>
                    </div>
                </div>
            </template>

            <!-- Si no hay pagos -->
            <template v-else>
                <div class="text-center py-12 opacity-60">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-16 h-16 mx-auto mb-4 text-gray-300">
                        <path fill-rule="evenodd" d="M2.25 2.25a.75.75 0 0 0 0 1.5H3v10.5a3 3 0 0 0 3 3h1.21l-1.172 3.513a.75.75 0 0 0 1.424.474l.329-.987h8.418l.33.987a.75.75 0 0 0 1.422-.474l-1.17-3.513H18a3 3 0 0 0 3-3V3.75h.75a.75.75 0 0 0 0-1.5H2.25Zm6.04 16.5.5-1.5h6.42l.5 1.5H8.29Zm7.46-12a.75.75 0 0 0-1.5 0v6a.75.75 0 0 0 1.5 0v-6Zm-3 2.25a.75.75 0 0 0-1.5 0v3.75a.75.75 0 0 0 1.5 0V9Zm-3 2.25a.75.75 0 0 0-1.5 0v1.5a.75.75 0 0 0 1.5 0v-1.5Z" clip-rule="evenodd" />
                    </svg>
                    <p class="font-bold text-gray-500 text-lg">No tienes pagos registrados</p>
                    <p class="text-sm text-gray-400 mt-2">
                        {{ activeSemester 
                            ? `Los pagos del semestre ${activeSemester.name} se generan automáticamente`
                            : 'Los pagos se generan al inicio de cada semestre'
                        }}
                    </p>
                </div>
            </template>

            <!-- Dirección de pago -->
            <div class="w-full flex flex-col gap-7 mt-8">
                <h2 class="text-xl sm:text-3xl font-bold text-[#4169C4]">Dirección de Pago</h2>
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
                    class="self-center bg-[#22397A] py-2 px-6 text-white text-lg font-bold rounded-lg hover:bg-[#1a2b5f] transition shadow-md"
                >
                    Entendido
                </Link>
            </div>

        </section>
    </div>
</template>