<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ModalComponent from '../Components/ModalComponent.vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';   
    
interface User {
    name: string;
    lastname: string;
    email: string;
    card_code: string;
    career: string;
    profile_photo: string | null;
}

interface Fine {
    fine_id: number;
    amount: number;
    reason: string;
    created_at: string;
    admin?: {
        name: string;
    };
    assignment?: {
        locker?: {
            locker_code: string;
        }
    };
}

const confirmingDeletion = ref(false);

const openModal = () => {
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
};

const props = defineProps<{
    user: User;
    multa: Fine[]; // 👈🏼 AHORA ES ARRAY de multas
}>();

defineOptions({ layout: LayoutAdmin })

// 👇🏼 Generar iniciales del usuario
const iniciales = computed(() => {
    const nombre = props.user.name?.charAt(0).toUpperCase() || '';
    const apellido = props.user.lastname?.charAt(0).toUpperCase() || '';
    return nombre + apellido;
});

// 👇🏼 Colores suaves para el fondo
const colorFondo = computed(() => {
    const colores = [
        'bg-blue-400',
        'bg-green-400',
        'bg-purple-400',
        'bg-pink-400',
        'bg-indigo-400',
        'bg-yellow-400',
        'bg-teal-400',
        'bg-cyan-400',
    ];
    
    const index = (props.user.card_code?.charCodeAt(0) || 0) % colores.length;
    return colores[index];
});

const eliminarMulta = (id: number) => {
    if (confirm('¿Estás seguro de eliminar esta multa? Esta acción no se puede deshacer.')) {
        router.delete(`/admin/fines/${id}`, {
            preserveScroll: true,
            onSuccess: () => {
                openModal();
            },
            onError: (errors) => {
                console.error('Error al eliminar:', errors);
                alert('No se pudo eliminar la multa. Intenta de nuevo.');
            }
        });
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'Fecha no disponible';
    return new Date(dateString).toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div class="flex flex-col items-center justify-center min-h-screen py-8"> 
        
        <div class="w-full max-w-4xl flex flex-col gap-8 px-4">

            <!-- INFORMACIÓN DEL USUARIO -->
            <div class="flex flex-col gap-4">
                <h1 class="text-3xl font-bold text-center">
                    Usuario <span class="text-[#2E7AC0]">{{ user.name }}</span>
                </h1> 

                <section class="flex flex-col gap-3">
                    <div class="flex flex-col w-full sm:flex-row items-center gap-10 md:gap-16 shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-8 rounded-xl bg-white justify-center">
                        
                        <!-- 👇🏼 FOTO DE PERFIL CON INICIALES -->
                        <div class="shrink-0 relative group">
                            <img 
                                v-if="user.profile_photo"
                                :src="user.profile_photo"
                                class="size-40 rounded-full object-cover border-4 border-gray-100 shadow-md group-hover:shadow-lg transition cursor-pointer"
                                alt="Foto de perfil"
                            >
                            
                            <div 
                                v-else
                                :class="[colorFondo, 'size-40 rounded-full flex items-center justify-center border-4 border-gray-100 shadow-md group-hover:shadow-lg transition']"
                            >
                                <span class="text-white text-4xl font-bold uppercase tracking-wider">
                                    {{ iniciales }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 overflow-hidden text-center sm:text-left">
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Nombre Usuario: 
                                <span class="font-normal block sm:inline">{{ user.name }} {{ user.lastname }}</span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Código Carnet: 
                                <span class="font-normal block sm:inline">{{ user.card_code }}</span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Carrera: 
                                <span class="font-normal block sm:inline">{{ user.career }}</span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Correo: 
                                <span class="font-normal block sm:inline break-all text-blue-600">{{ user.email }}</span>
                            </p>
                        </div>
                    </div>

                    <Link 
                        :href="`/admin/multas/${user.card_code}`" 
                        as="button" 
                        class="self-end flex flex-row px-6 py-2 items-center justify-center gap-2 bg-[#DC2626] rounded-md hover:bg-red-700 transition duration-200 shadow-md active:scale-95"
                    >
                        <p class="text-white font-bold text-sm">Añadir Multa</p>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-6">
                            <path fill-rule="evenodd" d="M11.484 2.17a.75.75 0 0 1 1.032 0 11.209 11.209 0 0 0 7.877 3.08.75.75 0 0 1 .722.515 12.74 12.74 0 0 1 .635 3.985c0 5.942-4.064 10.933-9.563 12.348a.749.749 0 0 1-.374 0C6.314 20.683 2.25 15.692 2.25 9.75c0-1.39.223-2.73.635-3.985a.75.75 0 0 1 .722-.516l.143.001c2.996 0 5.718-1.17 7.734-3.08ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75ZM12 15a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75v-.008a.75.75 0 0 0-.75-.75H12Z" clip-rule="evenodd" />
                        </svg>
                    </Link> 
                </section>
            </div>

            <!-- 👇 MULTAS DEL USUARIO - LISTA COMPLETA -->
            <div class="flex flex-col gap-4 w-full">
                <h2 class="text-[#4169C4] text-2xl font-bold">Multas del Usuario</h2> 

                <template v-if="multa && multa.length > 0">
                    <div 
                        v-for="fine in multa" 
                        :key="fine.fine_id" 
                        class="flex flex-row justify-between items-center shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white border-l-4 border-red-500"
                    >
                        <div class="flex flex-col gap-3 flex-1">
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Admin:
                                <span class="font-normal block text-xs sm:text-base sm:inline">
                                    {{ fine.admin?.name || 'N/A' }}
                                </span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Motivo:
                                <span class="font-normal block text-xs sm:text-base sm:inline">
                                    {{ fine.reason }}
                                </span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Monto:
                                <span class="font-normal block text-xs sm:text-base sm:inline text-red-600 font-bold">
                                    {{ fine.amount }} BS.
                                </span>
                            </p>
                            <p 
                                v-if="fine.assignment?.locker" 
                                class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base"
                            >
                                Locker:
                                <span class="font-normal block text-xs sm:text-base sm:inline">
                                    {{ fine.assignment.locker.locker_code }}
                                </span>
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatDate(fine.created_at) }}
                            </p>
                        </div>

                        <!-- 👇🏼 BOTÓN ELIMINAR MULTA -->
                        <button 
                            @click="eliminarMulta(fine.fine_id)"
                            class="ml-4 cursor-pointer hover:scale-110 transition duration-200 p-2 rounded-full hover:bg-red-50"
                            title="Eliminar multa"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                viewBox="0 0 24 24" 
                                fill="#DC2626" 
                                class="size-6 sm:size-8"
                            >
                                <path 
                                    fill-rule="evenodd" 
                                    d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" 
                                    clip-rule="evenodd" 
                                />
                            </svg>
                        </button>
                    </div>
                </template>

                <section 
                    v-else 
                    class="flex flex-row justify-center items-center shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white italic text-gray-500"
                >
                    El usuario no posee multas registradas
                </section>
            </div>
        </div>

        <!-- MODAL DE CONFIRMACIÓN -->
        <ModalComponent 
            :show="confirmingDeletion" 
            text="¡Multa Eliminada Correctamente!"
            url="/inicio-admin""
            title-button="Volver al inicio"
            @close="closeModal"
        >
        </ModalComponent>

    </div>
</template>