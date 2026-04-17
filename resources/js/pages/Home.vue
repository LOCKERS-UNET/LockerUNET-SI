<script setup lang="ts">

    import { Link } from '@inertiajs/vue3';
    import Layout from './Layouts/Layout.vue';

    defineOptions({layout: Layout})

    

</script>

<template>
    
    
    <div class="flex flex-col items-center justify-center min-h-screen py-5">
        
        <section class=" w-3/4 sm:w-8/12 max-w-3xl flex flex-col gap-8 items-center">

            <p class=" block self-start font-bold text-2xl lg:hidden ">
                ¡Hola, 
                <span class="text-[#3A6BC8]">{{ $page.props.auth.user.name }}</span>!
            </p>

            <h1 class=" text-2xl  text-center font-light self-start lg:self-auto lg:font-bold lg:text-4xl"> Accesos Rápidos</h1>

            <div class="grid grid-cols-2 gap-10  w-full">

                <Link 
                    href="/lockers"
                    class="flex flex-row items-center justify-center gap-5 bg-[#213779] hover:bg-[#1a2b5f] p-2 sm:p-5 rounded-lg"
                >

                    <p class="text-white font-bold text-[10px] sm:text-sm md:text-base">Buscar Locker</p>

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-6 sm:size-8">
                        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                    </svg>

                </Link>

                <Link 
                    href="/assignments/my"
                    class="flex flex-row items-center justify-center gap-5 bg-[#213779] hover:bg-[#1a2b5f] p-2 sm:p-5 rounded-lg"
                >

                    <p class="text-white font-bold text-[10px] sm:text-sm md:text-base">Mi Locker</p>

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-6 sm:size-8">
                        <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                    </svg>

                    
                </Link>

                <Link 
                    href="/requests/my"
                    class="flex flex-row items-center justify-center gap-5 bg-[#213779] hover:bg-[#1a2b5f] p-2 sm:p-5 rounded-lg shrink-0"
                >

                    <p class="text-white font-bold text-[10px] sm:text-sm md:text-base">Mis Solicitudes</p>

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-6 sm:size-8">
                        <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375ZM6 12a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V12Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM6 15a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V15Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM6 18a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V18Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                    
                </Link>

                <Link 
                    href="/reportes-user"
                    class="flex flex-row items-center justify-center gap-5 bg-[#213779] hover:bg-[#1a2b5f] p-2 sm:p-5 rounded-lg"
                >

                    <p 
                        class="text-white font-bold text-[10px]  sm:text-sm md:text-base"
                    >
                        Reporte de Incidencia
                    </p>
                    
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-6 sm:size-8">
                        <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                    </svg>

                </Link>

            </div>

            <h3 class="text-2xl text-center font-light mb-5 self-start lg:self-auto lg:font-bold">Disponibilidad Lockers</h3>

            <div class=" flex flex-col w-full gap-10">

                <div class=" flex flex-row justify-between items-center shadow-2xl p-3">

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#3A6BC8" class="size-8 sm:size-10">
                        <path fill-rule="evenodd" d="M4.5 2.25a.75.75 0 0 0 0 1.5v16.5h-.75a.75.75 0 0 0 0 1.5h16.5a.75.75 0 0 0 0-1.5h-.75V3.75a.75.75 0 0 0 0-1.5h-15ZM9 6a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5H9Zm-.75 3.75A.75.75 0 0 1 9 9h1.5a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM9 12a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5H9Zm3.75-5.25A.75.75 0 0 1 13.5 6H15a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75ZM13.5 9a.75.75 0 0 0 0 1.5H15A.75.75 0 0 0 15 9h-1.5Zm-.75 3.75a.75.75 0 0 1 .75-.75H15a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75ZM9 19.5v-2.25a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-.75.75h-4.5A.75.75 0 0 1 9 19.5Z" clip-rule="evenodd" />
                    </svg>


                    <p class=" font-bold start text-sm sm:text-base">
                        Edificio A
                    </p>

                    <p class="text-[#0D7A5F] font-bold text-sm sm:text-base">
                        20 Disponibles
                    </p>

                </div>

                <div class=" flex flex-row justify-between items-center shadow-2xl p-3">

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#3A6BC8" class="size-8 sm:size-10">
                        <path fill-rule="evenodd" d="M4.5 2.25a.75.75 0 0 0 0 1.5v16.5h-.75a.75.75 0 0 0 0 1.5h16.5a.75.75 0 0 0 0-1.5h-.75V3.75a.75.75 0 0 0 0-1.5h-15ZM9 6a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5H9Zm-.75 3.75A.75.75 0 0 1 9 9h1.5a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM9 12a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5H9Zm3.75-5.25A.75.75 0 0 1 13.5 6H15a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75ZM13.5 9a.75.75 0 0 0 0 1.5H15A.75.75 0 0 0 15 9h-1.5Zm-.75 3.75a.75.75 0 0 1 .75-.75H15a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75ZM9 19.5v-2.25a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-.75.75h-4.5A.75.75 0 0 1 9 19.5Z" clip-rule="evenodd" />
                    </svg>

                    <p class="font-bold text-sm sm:text-base">
                        Edificio B
                    </p>

                    <p  class=" text-[#0D7A5F] font-bold text-sm sm:text-base">
                        10 Disponibles
                    </p>

                </div>

            </div>


        </section>

    </div>

</template>