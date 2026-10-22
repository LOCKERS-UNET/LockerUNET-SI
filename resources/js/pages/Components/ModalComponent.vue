<script setup lang="ts">

    import { Link } from '@inertiajs/vue3';
    import { ref, watch} from 'vue';

    const props = defineProps({
        show: Boolean,
        text: String,
        titleButton:String,
        url: String,
        // Con error se muestra un ícono rojo: antes los errores salían con el check verde de éxito
        error: Boolean
    });

    const emit = defineEmits(['close']);
    const dialog = ref<HTMLDialogElement | null>(null);

    // Vigilamos la prop 'show' para abrir o cerrar el modal nativo
    watch(() => props.show, (value) => {
        if (value) {
            dialog.value?.showModal();
            // Evita el scroll en el body cuando el modal está abierto
            document.body.style.overflow = 'hidden';
        } else {
            dialog.value?.close();
            document.body.style.overflow = '';
        }
    });

    const close = () => {
        emit('close');
    };
    
</script>

<template>
    <dialog
        ref="dialog"
        class="
            fixed inset-0 m-auto 
            
            w-[90%] max-w-md rounded-xl shadow-2xl border-none p-0
            
            backdrop:bg-gray-900/60 backdrop:backdrop-blur-sm
            
            overflow-hidden"
        @close="close"
    >
        <div v-if="show" class="bg-white p-6">
            <div class="flex flex-col sm:flex-row items-center gap-5 sm:gap-10">

                <div
                    v-if="error"
                    class="flex items-center justify-center shrink-0 w-[100px] h-[100px] rounded-full bg-[#fee2e2] text-[#B91C1C] text-6xl font-black"
                    aria-hidden="true"
                >!</div>
                <img 
                    v-else
                    src="/img/Icono_Aceptar.webp" 
                    alt="Icono aceptar"

                    class="object-contain h-auto max-h-[100px]"

                >

                <div class=" flex flex-col gap-2">

                    <p class=" text-md sm:text-xl font-black self-center sm:self-start">{{ text }}</p>
                   
                    <Link 
                        :href="url" 
                        @click="$emit('close')"
                        class="text-center font-bold py-1  bg-[#213779] hover:bg-[#1a2b5f] text-white rounded-xl transition duration-300 shadow-md active:scale-95">
                        {{titleButton}}
                    </Link>
                    
                </div>

            </div>
        </div>
    </dialog>
</template>

<style scoped>
/* Animación de entrada */
    dialog[open] {
        animation: appear 0.2s ease-out;
    }

    dialog::backdrop {
        animation: fadeIn 0.2s ease-out;
    }

    @keyframes appear {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
</style>