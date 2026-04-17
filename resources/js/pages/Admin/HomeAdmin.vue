<script setup lang="ts">
    import { Link } from '@inertiajs/vue3';
    import { ref, onMounted } from 'vue';
    import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

    defineOptions({ layout: LayoutAdmin })

    // Estado para estadísticas
    const stats = ref({
        total: 0,
        occupied: 0,
        available: 0,
        maintenance: 0
    });

    const loading = ref(true);
    const error = ref('');

    // Obtener estadísticas del backend
    const fetchStats = async () => {
        try {
            const response = await fetch('/admin/stats/summary');
            if (!response.ok) throw new Error('Error fetching stats');
            stats.value = await response.json();
        } catch (err) {
            console.error('Error al obtener estadísticas:', err);
            error.value = 'No se pudieron cargar las estadísticas';
        } finally {
            loading.value = false;
        }
    };

    onMounted(() => {
        fetchStats();
    });
</script>

<template>
    <main class="flex flex-col items-center justify-center min-h-screen py-10 px-4">
        
        <section class="w-full max-w-4xl flex flex-col items-center">
 
            <h1 class="text-3xl lg:text-4xl font-extrabold text-black mb-10 text-center">Panel Admin</h1>
            
            <h2 class="text-xl font-bold self-start mb-6 text-[#1768B4] ml-2 lg:ml-10">Información Locker</h2>

            <div class="grid grid-cols-2 gap-4 lg:gap-8 w-full lg:w-11/12 mb-12">

                <template v-if="loading">
                    <div class="flex items-center justify-center col-span-2 py-8">
                        <p class="text-gray-500">Cargando estadísticas...</p>
                    </div>
                </template>
                <template v-else-if="error">
                    <div class="flex items-center justify-center col-span-2 py-8">
                        <p class="text-red-500">{{ error }}</p>
                    </div>
                </template>
                <template v-else>
                    <div class="flex flex-col items-start justify-center gap-1 bg-[#213779] p-4 lg:p-6 rounded-xl shadow-lg">
                        <p class="text-white font-bold text-xs lg:text-lg">Total Lockers</p>
                        <p class="text-white font-bold text-xl lg:text-2xl">{{ stats.total }}</p>
                    </div>

                    <div class="flex flex-col items-start justify-center gap-1 bg-[#0D7A5F] p-4 lg:p-6 rounded-xl shadow-lg">
                        <p class="text-white font-bold text-xs lg:text-lg">Disponibles</p>
                        <p class="text-white font-bold text-xl lg:text-2xl">{{ stats.available }}</p>
                    </div>

                    <div class="flex flex-col items-start justify-center gap-1 bg-[#1768B4] p-4 lg:p-6 rounded-xl shadow-lg">
                        <p class="text-white font-bold text-xs lg:text-lg">Ocupados</p>
                        <p class="text-white font-bold text-xl lg:text-2xl">{{ stats.occupied }}</p>
                    </div>

                    <div class="flex flex-col items-start justify-center gap-1 bg-[#F97316] p-4 lg:p-6 rounded-xl shadow-lg">
                        <p class="text-white font-bold text-xs lg:text-lg">Mantenimiento</p>
                        <p class="text-white font-bold text-xl lg:text-2xl">{{ stats.maintenance }}</p>
                    </div>
                </template>
            </div>

            <h2 class="text-xl font-bold self-start mb-6 text-black ml-2 lg:ml-10">Accesos Rápidos</h2>

            <div class="grid grid-cols-2 gap-4 lg:gap-6 w-full lg:w-10/12">

                <Link href="/admin/assignments" 
                    class="flex items-center justify-between bg-[#213779] hover:bg-[#1a2b5f] p-3 lg:p-4 rounded-xl shadow-md transition-colors"
                    aria-label="Ir a la sección de Asignaciones de lockers">
                    <span class="text-white font-bold text-[10px] lg:text-base">Asignaciones</span>
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-4 lg:size-6">
                        <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375ZM6 12a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V12Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM6 15a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V15Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75ZM6 18a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V18Zm2.25 0a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                </Link>

                <Link href="/admin/lockers" 
                    class="flex items-center justify-between bg-[#213779] hover:bg-[#1a2b5f] p-3 lg:p-4 rounded-xl shadow-md transition-colors"
                    aria-label="Gestionar inventario de lockers">
                    <span class="text-white font-bold text-[10px] lg:text-base">Gestión Lockers</span>
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-4 lg:size-6">
                        <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                    </svg>
                </Link>

                <Link href="/admin/users" 
                    class="flex items-center justify-between bg-[#213779] hover:bg-[#1a2b5f] p-3 lg:p-4 rounded-xl shadow-md transition-colors"
                    aria-label="Administrar usuarios del sistema">
                    <span class="text-white font-bold text-[10px] lg:text-base">Usuarios</span>
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-4 lg:size-6">
                        <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                    </svg>
                </Link>

                <Link href="/estadisticas-lockers" 
                    class="flex items-center justify-between bg-[#213779] hover:bg-[#1a2b5f] p-3 lg:p-4 rounded-xl shadow-md transition-colors"
                    aria-label="Ver estadísticas de uso y reportes">
                    <span class="text-white font-bold text-[10px] lg:text-base">Estadísticas</span>
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="white" class="size-4 lg:size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </Link>

                <Link href="/fee-rates" 
                    class="flex items-center justify-between bg-[#213779] hover:bg-[#1a2b5f] p-3 lg:p-4 rounded-xl shadow-md transition-colors"
                    aria-label="Configurar precios y aranceles">
                    <span class="text-white font-bold text-[10px] lg:text-base">Aranceles</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-4 sm:size-6 ">

                        <path d="M10.464 8.746c.227-.18.497-.311.786-.394v2.795a2.252 2.252 0 0 1-.786-.393c-.394-.313-.546-.681-.546-1.004 0-.323.152-.691.546-1.004ZM12.75 15.662v-2.824c.347.085.664.228.921.421.427.32.579.686.579.991 0 .305-.152.671-.579.991a2.534 2.534 0 0 1-.921.42Z" />

                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v.816a3.836 3.836 0 0 0-1.72.756c-.712.566-1.112 1.35-1.112 2.178 0 .829.4 1.612 1.113 2.178.502.4 1.102.647 1.719.756v2.978a2.536 2.536 0 0 1-.921-.421l-.879-.66a.75.75 0 0 0-.9 1.2l.879.66c.533.4 1.169.645 1.821.75V18a.75.75 0 0 0 1.5 0v-.81a4.124 4.124 0 0 0 1.821-.749c.745-.559 1.179-1.344 1.179-2.191 0-.847-.434-1.632-1.179-2.191a4.122 4.122 0 0 0-1.821-.75V8.354c.29.082.559.213.786.393l.415.33a.75.75 0 0 0 .933-1.175l-.415-.33a3.836 3.836 0 0 0-1.719-.755V6Z" clip-rule="evenodd" />

                    </svg>


                </Link>

                <Link href="/admin/incidents" 
                    class="flex items-center justify-between bg-[#DC2626] hover:bg-[#b91c1c] p-3 lg:p-4 rounded-xl shadow-md transition-colors"
                    aria-label="Ver y reportar incidencias técnicas">
                    <span class="text-white font-bold text-[10px] lg:text-base">Incidencias</span>
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="size-4 lg:size-6">
                        <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                    </svg>
                </Link>

            </div>
        </section>
    </main>
</template>