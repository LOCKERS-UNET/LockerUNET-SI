<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { watch, ref } from 'vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

const props = defineProps<{
    users: any[],
    filters: any
}>();

const search = ref(props.filters?.search ?? '');
const modalUser = ref<any>(null);

defineOptions({ layout: LayoutAdmin })

watch(search, debounce((value) => {
    router.get('/admin/users',
        { search: value },
        {
            replace: true,
            preserveState: true
        }
    );
}, 300))

// Abrir modal con foto de perfil (tipo Instagram/WhatsApp)
const abrirModalFoto = (user: any) => {
    modalUser.value = user;
};

// Cerrar modal
const cerrarModal = () => {
    modalUser.value = null;
};
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-8 py-5">
        <h1 class="text-4xl font-bold">Usuarios</h1>

        <!-- Buscador -->
        <div class="relative w-1/2 sm:w-2/5">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <input 
                type="text" 
                v-model="search"
                id="search" 
                name="search"
                class="block w-full p-3 ps-9 rounded-lg border-2 border-[#A3A3A3] outline-none focus:border-[#22397A] placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm" 
                placeholder="Nombre Usuario..."
            />
        </div>

        <p class="text-[#4169C4] font-bold">Visualización de Usuarios ({{ users.length }})</p>

        <!-- Sin resultados -->
        <div v-if="users.length === 0" class="text-center py-8">
            <p class="text-gray-500 text-lg">No hay usuarios disponibles</p>
        </div>

        <!-- Lista de usuarios -->
        <div v-for="user in users" :key="user.id" class="w-3/4 lg:w-1/2 flex flex-col gap-3">
            <div class="flex flex-col rounded-lg bg-[#D6E4F7] px-5 py-2 gap-3">
                
                <!-- 👇🏼 ENCABEZADO: Foto + Nombre (con click para ver foto grande) -->
                <div class="flex items-center gap-3">
                    <!-- Foto pequeña clicable -->
                    <div 
                        @click="abrirModalFoto(user)"
                        class="cursor-pointer shrink-0 group"
                        title="Click para ver foto"
                    >
                        <img 
                            v-if="user.profile_photo"
                            :src="`/storage/${user.profile_photo}`"
                            class="w-12 h-12 rounded-full object-cover border-2 border-[#22397A] group-hover:border-[#1a2b5f] transition"
                            alt="Foto de perfil"
                            @error="user.profile_photo = null"
                        >
                        <!-- Placeholder si no tiene foto -->
                        <svg 
                            v-else 
                            xmlns="http://www.w3.org/2000/svg" 
                            viewBox="0 0 24 24" 
                            fill="currentColor" 
                            class="w-12 h-12 text-gray-400 group-hover:text-gray-500 transition"
                        >
                            <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                        </svg>
                        
                    </div>
                    

                    <!-- Nombre del usuario -->
                    <p class="font-bold text-sm sm:text-lg lg:text-xl flex-1">
                        {{ user.name }} {{ user.lastname }}
                    </p>
                </div>

                <hr>

                <!-- Información del usuario -->
                <div class="flex flex-col gap-1">
                    <!-- Email -->
                    <p class="font-bold text-xs sm:text-sm lg:text-base">
                        Email:
                        <span class="font-normal break-all">{{ user.email || 'Sin email' }}</span>
                    </p>

                    <!-- Locker asignado -->
                    <p v-if="user.locker_code" class="font-bold text-xs sm:text-sm lg:text-base">
                        Locker:
                        <span class="font-normal">{{ user.locker_code }}</span>
                    </p>
                    <p v-else class="font-bold text-xs sm:text-sm lg:text-base text-red-600">
                        Sin locker asignado
                    </p>

                    <!-- Ubicación del locker -->
                    <p v-if="user.building_name || user.sector_name" class="font-bold text-xs sm:text-sm lg:text-base">
                        Ubicación:
                        <span class="font-normal">
                            {{ user.building_name || 'N/A' }} - {{ user.sector_name || 'N/A' }}
                        </span>
                    </p>

                    <!-- Carrera + Botón Ver -->
                    <div class="flex flex-row justify-between items-center pt-1">
                        <p class="font-bold text-xs sm:text-sm lg:text-base">
                            Carrera:
                            <span class="font-normal">
                                {{ user.career || 'Sin carrera' }}
                            </span>
                        </p>

                        <Link 
                            :href="`/admin/users/${user.id}`"
                            class="px-1 bg-[#1C2F5E] text-white text-center font-bold rounded-lg text-xs w-[50px] md:w-[100px] md:px-2 sm:w-[80px] sm:text-base hover:bg-[#0f1a3a] transition"
                        >
                            Ver
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- 👇🏼 MODAL DE FOTO DE PERFIL (tipo Instagram/WhatsApp) -->
        <div 
            v-if="modalUser"
            @click="cerrarModal"
            class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 backdrop-blur-sm"
        >
            <div class="relative max-w-2xl w-full flex flex-col items-center" @click.stop>
                
                <!-- Botón cerrar (X) -->
                <button 
                    @click="cerrarModal"
                    class="absolute -top-14 right-0 text-white hover:text-gray-300 transition p-2 hover:bg-white/10 rounded-full"
                    title="Cerrar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Foto grande con animación -->
                <div class="relative">
                    <img 
                        v-if="modalUser.profile_photo"
                        :src="`/storage/${modalUser.profile_photo}`"
                        class="w-72 h-72 sm:w-96 sm:h-96 rounded-full object-cover border-4 border-white shadow-2xl animate-fade-in"
                        alt="Foto de perfil"
                    >
                    <!-- Placeholder si no tiene foto -->
                    <svg 
                        v-else
                        xmlns="http://www.w3.org/2000/svg" 
                        viewBox="0 0 24 24" 
                        fill="currentColor" 
                        class="w-72 h-72 sm:w-96 sm:h-96 text-gray-400"
                    >
                        <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- Información del usuario en el modal -->
                <div class="mt-6 text-center text-white px-4">
                    <h2 class="text-2xl font-bold">
                        {{ modalUser.name }} {{ modalUser.lastname }}
                    </h2>
                    <p class="text-gray-300 mt-1 break-all">{{ modalUser.email }}</p>
                    <p class="text-gray-300">{{ modalUser.career || 'Sin carrera registrada' }}</p>
                    <p v-if="modalUser.card_code" class="text-gray-400 text-sm mt-1">
                        Carnet: {{ modalUser.card_code }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animación suave para la foto del modal */
@keyframes fade-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fade-in 0.2s ease-out;
}
</style>