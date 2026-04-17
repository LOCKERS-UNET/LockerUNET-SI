<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ModalComponent from '../Components/ModalComponent.vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';   
    
interface User {
    name: string;
    lastname: string;
    email: string;
    card_code: string;
    career: string;
    profile_photo: string | null; // Nuevo campo
}

const confirmingDeletion = ref(false);

const openModal = () => {
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
};

defineProps<{
    user: User;
    multa: any;
}>();

defineOptions({ layout: LayoutAdmin })

const eliminarMulta = (id: number) => {
    router.delete(`/admin/multas/${id}`, {
        onSuccess: () => {
            openModal();
        }
    });
}
</script>

<template>
    <div class="flex flex-col items-center justify-center min-h-screen py-8"> 
        
        <div class="w-full max-w-3xl flex flex-col gap-8 px-4">

            <div class="flex flex-col gap-4">
                <h1 class="text-3xl font-bold text-center">
                    Usuario <span class="text-[#2E7AC0]">{{ user.name }}</span>
                </h1> 

                <section class="flex flex-col gap-3">
                    <div class="flex flex-col w-full sm:flex-row items-center gap-10 md:gap-16 shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-8 rounded-xl bg-white justify-center">
                        
                        <div class="shrink-0">
                            <img v-if="user.profile_photo" 
                                 :src="`/storage/${user.profile_photo}`" 
                                 class="size-40 rounded-full object-cover border-4 border-gray-100 shadow-md"
                                 alt="Foto de perfil">
                            
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-40 text-gray-300">
                                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                            </svg>
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
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Locker: 
                                <span class="font-normal block sm:inline">002</span>
                            </p>
                            <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                                Edificio: 
                                <span class="font-normal block sm:inline">A</span>
                            </p>
                        </div>
                    </div>

                    <Link :href="`/admin/multas/${user.card_code}`" as="button" class="self-end flex flex-row px-6 py-2 items-center justify-center gap-2 bg-[#DC2626] rounded-md hover:bg-red-700 transition duration-200 shadow-md active:scale-95">
                        <p class="text-white font-bold text-sm">
                            Añadir Multa
                        </p>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-6">
                            <path fill-rule="evenodd" d="M11.484 2.17a.75.75 0 0 1 1.032 0 11.209 11.209 0 0 0 7.877 3.08.75.75 0 0 1 .722.515 12.74 12.74 0 0 1 .635 3.985c0 5.942-4.064 10.933-9.563 12.348a.749.749 0 0 1-.374 0C6.314 20.683 2.25 15.692 2.25 9.75c0-1.39.223-2.73.635-3.985a.75.75 0 0 1 .722-.516l.143.001c2.996 0 5.718-1.17 7.734-3.08ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75ZM12 15a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75v-.008a.75.75 0 0 0-.75-.75H12Z" clip-rule="evenodd" />
                        </svg>
                    </Link> 
                </section>
            </div>

            <div class="flex flex-col gap-4 w-3/4 self-center">
                <h2 class="text-[#4169C4] text-2xl font-bold">Multas del Usuario</h2> 

                <section v-if="multa" class="flex flex-row justify-between items-center shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white border-l-4 border-red-500">
                    <div class="flex flex-col gap-3">
                        <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                            Admin:
                            <span class="font-normal block text-xs sm:text-base sm:inline"> {{$page.props.auth.user.name}}</span>
                        </p>
                        <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                            Motivo:
                            <span class="font-normal block text-xs sm:text-base sm:inline">{{multa.descripcion}}</span>
                        </p>
                        <p class="font-bold flex flex-row gap-2 text-sm sm:block sm:text-base">
                            Monto:
                            <span class="font-normal block text-xs sm:text-base sm:inline text-red-600"> {{multa.monto}} BS.</span>
                        </p>
                    </div>

                    <button @click="eliminarMulta(multa.id)" class="cursor-pointer hover:scale-110 transition duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#DC2626" class="size-6 sm:size-8 md:size-10">
                            <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </section>

                <section v-else class="flex flex-row justify-center items-center shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-6 rounded-md bg-white italic text-gray-500">
                    El usuario no posee Multas
                </section>
            </div>
        </div>

        <ModalComponent 
            :show="confirmingDeletion" 
            text="¡Multa Eliminada Correctamente!"
            url="/admin/buscar"
            title-button="Volver a Búsqueda"
            @close="closeModal">
        </ModalComponent>

    </div>
</template>