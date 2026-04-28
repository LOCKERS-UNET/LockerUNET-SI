<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

// Estado para el menú lateral (hamburguesa)
const isMenuOpen = ref(false);
const isProfileOpen = ref(false);

// 👇🏼 NUEVO: Contador de notificaciones no leídas
const unreadCount = ref(0);

// 👇🏼 NUEVO: Polling para actualizar el contador cada 30 segundos
let pollingInterval: ReturnType<typeof setInterval> | null = null;

const fetchUnreadCount = async () => {
    try {
        const response = await fetch('/notifications/unread-count', {
            headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
            const data = await response.json();
            unreadCount.value = data.count;
        }
    } catch (error) {
        console.error('Error al obtener notificaciones:', error);
    }
};

const toggleMobileMenu = () => { isMenuOpen.value = !isMenuOpen.value; };
const toggleProfileMenu = () => { isProfileOpen.value = !isProfileOpen.value; };

const closeProfileOnClickOutside = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (!target.closest('.profile-dropdown-container')) {
        isProfileOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', closeProfileOnClickOutside);
    
    // 👇🏼 Iniciar polling al montar el componente
    fetchUnreadCount(); // Primera carga inmediata
    pollingInterval = setInterval(fetchUnreadCount, 30000); // Cada 30 segundos
});

onUnmounted(() => {
    window.removeEventListener('click', closeProfileOnClickOutside);
    if (pollingInterval) clearInterval(pollingInterval); // Limpiar polling
});
</script>

<template>
    <div>
        <header class="bg-[#1C2F5E] text-white relative">
            <nav class="flex items-center justify-between p-4 max-w-screen-lg mx-auto">
                
                <div class="flex flex-row items-center gap-2 sm:gap-5">
                    <button @click="toggleMobileMenu" 
                            class="block lg:hidden cursor-pointer mx-2"
                            aria-label="Abrir menú de navegación lateral">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75ZM3 12a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <Link href="/">
                        <img src="/img/Logo_Minimal.png" alt="Logo Lockers UNET" class="w-auto h-12 sm:h-15 object-contain">
                    </Link>
                </div>

                <div class="hidden lg:flex lg:flex-row md:gap-5 lg:gap-10 lg:text-base font-medium">
                    <Link href="/assignments/my" class="hover:text-[#2E7AC0] transition">Mi Locker</Link>
                    <Link href="/" class="hover:text-[#2E7AC0] transition">Menú Inicio</Link>
                    <Link href="/lockers" class="hover:text-[#2E7AC0] transition">Buscar Locker</Link>
                    <Link href="/notifications" class="hover:text-[#2E7AC0] transition">Notificaciones</Link>
                </div>

                <div class="flex flex-row gap-5 items-center">
                    <p class="text-white font-bold text-xl hidden lg:block">
                        ¡Hola, <span class="text-[#2E7AC0]">{{ $page.props.auth.user.name }}</span>!
                    </p>

                    <!-- 👇🏼 CAMPANITA CON INDICADOR ROJO -->
                    <Link href="/notifications" aria-label="Ver notificaciones" class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="sm:size-8 size-5" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.25 9a6.75 6.75 0 0 1 13.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 0 1-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 1 1-7.48 0 24.585 24.585 0 0 1-4.831-1.244.75.75 0 0 1-.298-1.205A8.217 8.217 0 0 0 5.25 9.75V9Zm4.502 8.9a2.25 2.25 0 1 0 4.496 0 25.057 25.057 0 0 1-4.496 0Z" clip-rule="evenodd" />
                        </svg>
                        
                        <!-- 👇🏼 PUNTO ROJO: Solo visible si hay notificaciones no leídas -->
                        <span 
                            v-if="unreadCount > 0" 
                            class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-red-500 rounded-full border-2 border-[#1C2F5E] animate-pulse"
                            :title="`${unreadCount} notificacion(es) nueva(s)`"
                        ></span>
                    </Link>

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
                            <Link href="/" class="block px-4 py-3 text-sm text-gray-800 hover:bg-gray-100 border-b border-gray-50 transition">
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

            <!-- Menú lateral móvil (sin cambios) -->
            <div v-if="isMenuOpen" @click="isMenuOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>
            <aside :class="[
                'fixed top-0 left-0 h-full w-64 md:w-96 bg-white z-50 shadow-2xl transform transition-transform duration-300 ease-in-out lg:hidden flex flex-col justify-between p-6',
                isMenuOpen ? 'translate-x-0' : '-translate-x-full'
            ]">
                <div>
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex flex-col">
                            <p class="font-black text-black">{{$page.props.auth.user.name + " " + $page.props.auth.user.lastname}}</p>
                            <p class="text-black text-sm">{{ $page.props.auth.user.email }}</p>
                        </div>
                        <button @click="isMenuOpen = false" class="text-gray-400 text-3xl" aria-label="Cerrar menú">&times;</button>
                    </div>
                    <nav class="flex flex-col gap-6">
                        <Link href="/assignments/my" @click="isMenuOpen = false" class="text-black font-bold">Mi Locker</Link>
                        <Link href="/lockers" @click="isMenuOpen = false" class="text-black font-bold">Buscar Locker</Link>
                        <Link href="/notifications" @click="isMenuOpen = false" class="text-black font-bold">Notificaciones</Link>
                    </nav>
                </div>
                <Link href="/logout" method="post" as="button" class="text-red-600 font-bold text-left">Cerrar Sesión</Link>
            </aside>
        </header>

        <main>
            <slot></slot>
        </main>
    </div>
</template>