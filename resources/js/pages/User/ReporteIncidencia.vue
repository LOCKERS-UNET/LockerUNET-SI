<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// El usuario necesita saber su locker_id para reportar.
// Lo sacamos de la asignación activa.
// ─────────────────────────────────────────────
const props = defineProps<{
    // La asignación activa del usuario (para saber su locker)
    asignacion: {
        locker_id: number;
        locker: { locker_code: string; sector: { sector_name: string } }
    } | null
}>();

// Datos del formulario
const form = reactive({
    descripcion: '',
});

// Control del estado de envío
const enviado = ref(false);

// Enviar reporte al backend (POST /reportes-user)
const submitReport = () => {
    if (!props.asignacion) return;

    router.post('/reportes-user', {
        locker_id:   props.asignacion.locker_id,
        description: form.descripcion,
    }, {
        onSuccess: () => {
            enviado.value = true;
            form.descripcion = '';
        }
    });
};
</script>

<template>
    <Head title="Reporte de Incidencias" />

    <div class="min-h-screen bg-white pb-10">
        <div class="max-w-3xl mx-auto pt-12 px-4 sm:px-6 lg:px-8">
            
            <h1 class="text-3xl font-extrabold text-center text-black mb-10">
                Reporte de Incidencias
            </h1>

            <!-- Si no tiene locker asignado -->
            <div v-if="!asignacion" class="text-center py-8 opacity-60">
                <p class="font-bold text-gray-500 text-lg">Necesitas tener un locker asignado para reportar incidencias.</p>
            </div>

            <!-- Mensaje de éxito después del envío -->
            <div v-else-if="enviado" class="text-center py-16 flex flex-col items-center gap-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0D7A5F" class="w-16 h-16">
                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                </svg>
                <p class="font-bold text-green-700 text-xl">¡Reporte enviado con éxito!</p>
                <button @click="enviado = false" class="bg-[#1a3668] text-white font-bold py-2 px-8 rounded-xl">
                    Enviar otro reporte
                </button>
            </div>

            <!-- Formulario -->
            <form v-else @submit.prevent="submitReport" class="space-y-6 max-w-lg mx-auto">
                
                <!-- Locker del usuario (solo lectura) -->
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-2">Tu Locker</label>
                    <input 
                        :value="asignacion.locker.locker_code + ' - ' + asignacion.locker.sector.sector_name"
                        type="text" 
                        readonly
                        class="w-full bg-gray-100 border-none rounded-lg p-3 text-gray-700 outline-none cursor-not-allowed"
                    />
                </div>

                <!-- Descripción del problema -->
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-2">Descripción del problema</label>
                    <textarea 
                        v-model="form.descripcion"
                        placeholder="Describe detalladamente el problema con tu locker..."
                        rows="5"
                        class="w-full bg-gray-100 border-none rounded-lg p-3 text-gray-700 outline-none focus:ring-2 focus:ring-[#1a3668] resize-none transition-shadow"
                        required
                    ></textarea>
                </div>

                <!-- Botón Enviar -->
                <div class="text-center pt-6">
                    <button 
                        type="submit"
                        class="bg-[#1a3668] hover:bg-[#12264a] text-white font-bold py-3 px-12 rounded-xl transition-colors duration-300 shadow-md"
                    >
                        Enviar Reporte
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>
