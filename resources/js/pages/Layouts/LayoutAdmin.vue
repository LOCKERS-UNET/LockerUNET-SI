<script setup lang="ts">
    import { Link } from '@inertiajs/vue3';
    import { ref, onMounted, onUnmounted } from 'vue';

    const isMenuOpen = ref(false);
    
    // Estado para el menú de perfil (dropdown)
    const isProfileOpen = ref(false);

    const openMenu=()=>{
        isMenuOpen.value = !isMenuOpen.value
    }

    const toggleProfileMenu = () => {
        isProfileOpen.value = !isProfileOpen.value;
    }

    // Cerrar el dropdown si se hace clic fuera del área del perfil
    const closeProfileOnClickOutside = (e: MouseEvent) => {
        const target = e.target as HTMLElement;
        if (!target.closest('.profile-dropdown-container')) {
            isProfileOpen.value = false;
        }
    };

    onMounted(() => {
        window.addEventListener('click', closeProfileOnClickOutside);
    });

    onUnmounted(() => {
        window.removeEventListener('click', closeProfileOnClickOutside);
    });

</script>

<template>

    <div>

        <header class="bg-[#1C2F5E] text-white">
            <nav class=" flex items-center justify-between p-4 max-w-screen-lg mx-auto ">
                
                <div class="flex flex-row gap-2 sm:gap-5">
                    <button @click="openMenu" class=" block lg:hidden cursor-pointer mx-5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75ZM3 12a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                
                    <Link href="/inicio-admin">

                        <img src="/img/Logo_Minimal.png" 
                            alt="Logo Minimalista Lockers"
                            class="w-auto h-12 sm:h-15 object-contain"
                        >

                    </Link>
                </div>

                

                <div class=" hidden lg:flex lg:flex-row md:text-xs md:gap-5 lg:gap-10  lg:text-base">
                    <Link 
                        href="/admin/assignments"
                        class=""
                    >Asignaciones</Link>
                    <Link href="/admin/lockers">Gestion Lockers</Link>
                    <Link href="/admin/users">Usuarios</Link>
                    <Link href="/admin/incidents">Incidencias</Link>
                    <Link href="/estadisticas-lockers">Estadisticas</Link>
                    <Link href="/fee-rates">Aranceles</Link>
                    
                </div>

                
                <div class="flex flex-row gap-5 items-center">
                    <p class=" text-white font-bold text-xs md:text-base hidden lg:block">
                        ¡Hola, <span class=" text-[#2E7AC0]">{{ $page.props.auth.user.name }}</span>!
                    </p>

                    
                    <div class="relative profile-dropdown-container">
                        <button @click="toggleProfileMenu" 
                                class="flex items-center cursor-pointer focus:outline-none"
                                aria-haspopup="true"
                                :aria-expanded="isProfileOpen"
                                aria-label="Abrir opciones de perfil">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="sm:size-8 size-5" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div v-if="isProfileOpen" 
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-2xl py-2 z-50 ring-1 ring-black ring-opacity-10">
                            <Link href="/inicio-admin" class="block px-4 py-3 text-sm text-gray-800 hover:bg-gray-100 border-b border-gray-50 transition">
                                <span class="font-medium">Inicio</span>
                            </Link>
                            <Link href="/profile" class="block px-4 py-3 text-sm text-gray-800 hover:bg-gray-100 border-b border-gray-50 transition">
                                <span class="font-medium">Mi Perfil</span>
                            </Link>
                            <Link href="/logout" method="post" as="button" class="w-full text-left block px-4 py-3 text-sm text-red-600 hover:bg-red-50 font-bold transition">
                                Cerrar Sesión
                            </Link>
                        </div>
                    </div>

                </div>

            </nav>

            <div v-if="isMenuOpen" 
                @click="isMenuOpen = false" 
                class="fixed inset-0 bg-black/50 z-40 lg:hidden">
            </div>

        <!-- MENÚ LATERAL (SIDEBAR) -->
            <aside :class="[
                'fixed top-0 left-0 h-full w-64 md:w-96 bg-white z-50 shadow-2xl transform transition-transform duration-300 ease-in-out lg:hidden flex flex-col justify-between p-6',
                isMenuOpen ? 'translate-x-0' : '-translate-x-full'
                ]">
                <div class="">
                    <div class="flex justify-between items-center mb-8">
                        
                        <div class=" flex flex-col">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" class="size-15 ">
                                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                            </svg>

                            <p class=" font-black text-black">{{$page.props.auth.user.name + " " + $page.props.auth.user.lastname}}</p>
                            <p class="text-black text-sm">{{ $page.props.auth.user.email }}</p>

                        </div>

                        
                        <button @click="isMenuOpen = false" class="text-gray-400 text-3xl">&times;</button>
                    </div>

                    <nav class="flex flex-col gap-5 text-gray-700 font-medium">
                        <Link href="/admin/assignments" @click="isMenuOpen = false" class=" flex flex-row items-center gap-2">

                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8">
                                <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375ZM6 12a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V12Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM6 15a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V15Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM6 18a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V18Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                            </svg>

                            <span class="text-black ">Asignaciones</span>
                        </Link>

                        <Link href="/admin/lockers" @click="isMenuOpen = false" class=" flex flex-row items-center gap-2">

                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8">
                                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                            </svg>

                            <span class="text-black">Gestión Lockers</span>

                        </Link>
                        <Link href="/admin/users" @click="isMenuOpen = false" class=" flex flex-row items-center gap-2"> 
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" class="size-8 ">
                                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                            </svg>

                            <span class="text-black">Usuarios</span>
                        </Link>
                        <Link href="/admin/incidents" @click="isMenuOpen = false" class=" flex flex-row items-center gap-2"> 
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8">
                                <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                            </svg>


                            <span class="text-black">Incidencias</span>
                        </Link>
                        
                        <Link href="/estadisticas-lockers" @click="isMenuOpen = false" class=" flex flex-row items-center gap-2"> 
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="black" class="size-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                            </svg>

                            <span class="text-black">Estadística Lockers</span>
                        </Link>

                        <Link href="/fee-rates" @click="isMenuOpen = false" class=" flex flex-row items-center gap-2"> 

                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path d="M10.464 8.746c.227-.18.497-.311.786-.394v2.795a2.252 2.252 0 0 1-.786-.393c-.394-.313-.546-.681-.546-1.004 0-.323.152-.691.546-1.004ZM12.75 15.662v-2.824c.347.085.664.228.921.421.427.32.579.686.579.991 0 .305-.152.671-.579.991a2.534 2.534 0 0 1-.921.42Z" />
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v.816a3.836 3.836 0 0 0-1.72.756c-.712.566-1.112 1.35-1.112 2.178 0 .829.4 1.612 1.113 2.178.502.4 1.102.647 1.719.756v2.978a2.536 2.536 0 0 1-.921-.421l-.879-.66a.75.75 0 0 0-.9 1.2l.879.66c.533.4 1.169.645 1.821.75V18a.75.75 0 0 0 1.5 0v-.81a4.124 4.124 0 0 0 1.821-.749c.745-.559 1.179-1.344 1.179-2.191 0-.847-.434-1.632-1.179-2.191a4.122 4.122 0 0 0-1.821-.75V8.354c.29.082.559.213.786.393l.415.33a.75.75 0 0 0 .933-1.175l-.415-.33a3.836 3.836 0 0 0-1.719-.755V6Z" clip-rule="evenodd" />
                            </svg>


                            <span class="text-black">Aranceles</span>
                        </Link>
                        
                    </nav>
                    
                </div>
                <Link href="/logout" method="post" as="button" class=" flex flex-row items-center gap-2 cursor-pointer"> 
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" class="size-8">
                        <path fill-rule="evenodd" d="M16.5 3.75a1.5 1.5 0 0 1 1.5 1.5v13.5a1.5 1.5 0 0 1-1.5 1.5h-6a1.5 1.5 0 0 1-1.5-1.5V15a.75.75 0 0 0-1.5 0v3.75a3 3 0 0 0 3 3h6a3 3 0 0 0 3-3V5.25a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3V9A.75.75 0 1 0 9 9V5.25a1.5 1.5 0 0 1 1.5-1.5h6ZM5.78 8.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 0 0 0 1.06l3 3a.75.75 0 0 0 1.06-1.06l-1.72-1.72H15a.75.75 0 0 0 0-1.5H4.06l1.72-1.72a.75.75 0 0 0 0-1.06Z" clip-rule="evenodd" />
                    </svg>

                    <span class="text-black">Cerrar Sesión</span>
                </Link>
            </aside>

        </header>

        <main>
            <slot></slot>
        </main>

    </div>

</template>

