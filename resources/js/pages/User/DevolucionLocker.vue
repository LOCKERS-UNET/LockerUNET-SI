<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// El controlador LockerAssignmentController@miLocker
// le da al usuario el ID de su asignación activa.
// Lo pasamos aquí para poder hacer el POST de devolución.
// ─────────────────────────────────────────────
const props = defineProps<{
    asignacion: {
        assignment_id: number;
        locker: { locker_code: string }
    } | null
}>();

const confirmado = ref(false);

// Enviamos la devolución al backend (POST /devolucion-locker/{id})
// El controlador LockerAssignmentController@release lo procesa
const enviarDevolucion = () => {
    if (!confirmado.value || !props.asignacion) return;
    
    router.post(`/devolucion-locker/${props.asignacion.assignment_id}`);
};
</script>

<template>
    <Head title="Devolución Locker" />
    
    <main class="flex flex-col items-center min-h-screen py-10 px-6 bg-white">
        
        <section class="w-full max-w-md flex flex-col items-center">
            
            <h1 class="text-4xl font-extrabold text-black mt-10 mb-12 text-center">
                Devolución Locker
            </h1>

            <!-- Si no tiene asignación activa -->
            <div v-if="!asignacion" class="text-center py-8 opacity-60">
                <p class="font-bold text-gray-500 text-lg">No tienes un locker asignado para devolver.</p>
            </div>

            <!-- Si sí tiene asignación activa -->
            <template v-else>
                <p class="text-gray-600 font-bold mb-6">Vas a devolver el locker: <span class="text-black text-xl">{{ asignacion.locker.locker_code }}</span></p>

                <div class="w-full bg-[#F3F4F6] rounded-2xl p-8 flex flex-col items-center gap-4 shadow-sm border border-gray-100 mb-10">
                    
                    <div class="bg-white p-4 rounded-full shadow-sm mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#EF5350" class="size-10">
                            <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
                            <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                        </svg>
                    </div>

                    <div class="text-center">
                        <h2 class="text-xl font-bold text-black mb-1">Reglamento de Servicio</h2>
                        <p class="text-gray-500 text-sm mb-6">Es obligatorio leer el reglamento antes de devolver su locker.</p>
                        
                        <a 
                            href="/docs/servicio_lockersUNET.pdf" 
                            download="Reglamento_Lockers_UNET.pdf"
                            class="inline-flex items-center gap-2 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md active:scale-95"
                        >
                            <span>Descargar PDF</span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 12l4.5 4.5m0 0l4.5-4.5M12 3v13.5" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Checkbox de confirmación -->
                <div class="flex items-start gap-3 mb-12 w-full px-2">
                    <input 
                        type="checkbox" 
                        id="confirm-baja" 
                        v-model="confirmado"
                        class="mt-1 size-6 rounded border-gray-300 text-[#EF5350] focus:ring-[#EF5350] cursor-pointer"
                    >
                    <label for="confirm-baja" class="text-gray-700 text-sm leading-tight cursor-pointer select-none">
                        He leído el reglamento y confirmo que deseo devolver el locker <span class="font-bold text-black">{{ asignacion.locker.locker_code }}</span>.
                    </label>
                </div>

                <!-- Botón de envío -->
                <div class="w-full">
                    <button 
                        @click="enviarDevolucion"
                        :disabled="!confirmado"
                        class="w-full font-bold py-5 rounded-2xl text-2xl transition-all shadow-lg active:scale-[0.98]"
                        :class="confirmado ? 'bg-[#EF5350] text-white hover:bg-[#d32f2f]' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                    >
                        Enviar
                    </button>
                </div>
            </template>

        </section>
        
    </main>
</template>