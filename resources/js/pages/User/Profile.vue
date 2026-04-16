<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

// Accedemos a los datos del usuario autenticado
const user = usePage().props.auth.user;
</script>

<template>
    <div class="flex flex-col items-center justify-center min-h-screen py-8"> 
        
        <div class="w-full max-w-3xl flex flex-col gap-8 px-4">

            <div class="flex flex-col gap-4">
                <h1 class="text-3xl font-bold text-center">Perfil de Usuario</h1> 

                <section class="flex flex-col gap-3">
                    <div class="flex flex-col w-full sm:flex-row items-center gap-10 md:gap-16 shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-8 rounded-xl bg-white justify-center">
                        
                        <div class="shrink-0">
                            <img v-if="user.profile_photo" 
                                 :src="`/storage/${user.profile_photo}`" 
                                 class="size-40 rounded-full object-cover border-4 border-[#22397A] shadow-md"
                                 alt="Foto de perfil">
                            
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-40 text-gray-300">
                                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <div class="flex flex-col gap-2 overflow-hidden text-center sm:text-left">
                            <p class="font-bold text-sm sm:text-base">
                                Nombre Usuario: 
                                <span class="font-normal block sm:inline">{{ user.name }} {{ user.lastname }}</span>
                            </p>
                            <p class="font-bold text-sm sm:text-base">
                                Código Carnet: 
                                <span class="font-normal block sm:inline">{{ user.card_code }}</span>
                            </p>
                            <p class="font-bold text-sm sm:text-base">
                                Carrera: 
                                <span class="font-normal block sm:inline">{{ user.career }}</span>
                            </p>
                            <p class="font-bold text-sm sm:text-base">
                                Correo: 
                                <span class="font-normal block sm:inline break-all text-[#22397A]">{{ user.email }}</span>
                            </p>
                        </div>
                    </div>

                    <Link href="/edit-profile" class="self-end bg-[#22397A] text-white px-6 py-2 rounded-md hover:bg-blue-800 transition duration-200 shadow-md text-sm font-bold">
                        Editar Perfil
                    </Link> 
                </section>
            </div>

            <div class="flex flex-col gap-4">
                <h2 class="text-[#4169C4] text-2xl font-bold">Información Locker</h2> 

                <section class="flex flex-col gap-3">
                    <div class="grid grid-cols-2 gap-6 shadow-[0px_4px_23px_0px_rgba(0,_0,_0,_0.1)] p-8 rounded-xl bg-white">
                        <div class="flex flex-col gap-2 items-center border-r border-gray-100">
                            <p class="font-bold text-sm sm:text-base text-gray-500 italic">ID Locker</p>
                            <p class="text-xl font-bold text-[#22397A]">142</p>
                            <p class="font-bold text-xs sm:text-sm">Código: <span class="font-normal">B-142</span></p>
                        </div>

                        <div class="flex flex-col gap-2 items-center">
                            <p class="font-bold text-sm sm:text-base text-gray-500 italic">Ubicación</p>
                            <p class="text-xl font-bold text-[#22397A]">Edificio B</p>
                            <p class="font-bold text-xs sm:text-sm">Tamaño: <span class="font-normal">Mediano</span></p>
                        </div>
                    </div>

                    <Link href="/logout" method="post" as="button" class="self-end bg-[#DC2626] text-white px-6 py-2 rounded-md hover:bg-red-700 transition duration-200 shadow-md text-sm font-bold">
                        Cerrar Sesión
                    </Link>
                </section>
            </div>

        </div>
    </div>
</template>