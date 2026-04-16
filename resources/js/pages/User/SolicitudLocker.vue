<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';
defineOptions({ layout: Layout });

// ─────────────────────────────────────────────
// DATOS REALES DEL BACKEND
// Vienen de BuscarLocker cuando el usuario hace clic en "Solicitar"
// La ruta /solicitud-locker recibe los datos como query params
// ─────────────────────────────────────────────
const props = defineProps<{
    lockerData: {
        locker_id: number;
        codigo: string;
        tamano: string;
        edificio: string;
    }
}>();

const confirmado = ref(false);

// Enviamos la solicitud real al backend (POST /solicitud-locker)
// El controlador LockerRequestController@store lo procesa
const enviarSolicitud = () => {
    if (!confirmado.value) return;
    
    router.post('/solicitud-locker', {
        locker_id: props.lockerData.locker_id
    });
};
</script>

<template>
    <Head title="Solicitud del Locker" />
    
    <main class="flex flex-col items-center min-h-screen py-10 px-6 bg-white">
        
        <section class="w-full max-w-lg flex flex-col items-center h-full">
            
            <h1 class="text-4xl font-extrabold text-black mt-10 mb-8 text-center">
                Solicitud del Locker
            </h1>

            <!-- Datos del locker que viene del backend -->
            <div class="w-full text-center sm:text-left mb-10 px-4">
                <h2 class="text-3xl font-bold text-gray-500 mb-6">Datos del Locker</h2>
                
                <div class="bg-white rounded-3xl p-8 shadow-[0px_10px_40px_rgba(0,0,0,0.06)] border border-gray-100 flex flex-col gap-5">
                    <p class="text-2xl font-bold text-black"><span class="font-extrabold text-black">Código:</span> {{ lockerData.codigo }}</p>
                    <p class="text-2xl font-bold text-black"><span class="font-extrabold text-black">Tamaño:</span> {{ lockerData.tamano }}</p>
                    <p class="text-2xl font-bold text-black"><span class="font-extrabold text-black">Edificio:</span> {{ lockerData.edificio }}</p>
                </div>
            </div>

            <!-- Descarga del formulario -->
            <div class="w-full flex flex-col items-center mb-16">
                <p class="text-2xl font-bold text-gray-600 mb-6 text-center">Descargar Formulario CBE-102</p>
                
                <div class="w-full max-w-sm bg-[#F3F4F6] rounded-2xl p-6 flex items-center justify-between shadow-sm border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="bg-white p-3 rounded-xl shadow-inner" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#EF5350" class="size-8">
                                <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
                                <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-black">Descargar Archivo</span>
                    </div>
                    
                    <a 
                        href="/docs/Reglamento_CBE_UNET.pdf" 
                        download="Formulario_CBE-102_UNET.pdf"
                        class="bg-transparent border border-gray-300 rounded-full p-2.5 hover:bg-white transition-colors"
                        aria-label="Descargar Formulario CBE-102"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6 text-gray-700">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 12l4.5 4.5m0 0l4.5-4.5M12 3v13.5" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Dirección -->
            <div class="w-full text-center px-4 mb-16">
                <h3 class="text-4xl font-extrabold text-[#213779] mb-4">Dirección</h3>
                <hr class="border-gray-200 w-11/12 mx-auto mb-6">
                <p class="text-2xl font-bold text-gray-800">Decanato de Desarrollo Estudiantil.</p>
                <hr class="border-gray-200 w-11/12 mx-auto mt-6">
            </div>

            <!-- Checkbox de confirmación -->
            <div class="flex items-start gap-4 mb-16 w-full px-6">
                <input 
                    type="checkbox" 
                    id="confirm-solicitud" 
                    v-model="confirmado"
                    class="mt-1 size-7 rounded border-gray-300 text-[#213779] focus:ring-[#213779] cursor-pointer"
                >
                <label for="confirm-solicitud" class="text-gray-700 text-lg leading-tight cursor-pointer select-none">
                    He descargado el Formulario CBE-102 y acepto consignar los recaudos necesarios para la solicitud del Casillero <span class="font-bold text-black">{{ lockerData.codigo }}</span>.
                </label>
            </div>

            <!-- Botones -->
            <div class="w-full max-w-sm flex flex-col gap-4">
                <!-- Botón Enviar: solo activo si el checkbox está marcado -->
                <button 
                    @click="enviarSolicitud"
                    :disabled="!confirmado"
                    class="w-full font-bold py-5 rounded-3xl text-2xl transition-all shadow-lg active:scale-[0.98]"
                    :class="confirmado ? 'bg-[#213779] text-white hover:bg-[#1a2b5f]' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                    aria-label="Enviar solicitud de locker"
                >
                    Enviar Solicitud
                </button>

                <button 
                    @click="router.visit('/')"
                    class="w-full font-bold py-3 rounded-3xl text-lg border border-gray-300 text-gray-600 hover:bg-gray-100 transition-all"
                    aria-label="Regresar al menú de inicio"
                >
                    Volver al Menú
                </button>
            </div>

        </section>
        
    </main>
</template>