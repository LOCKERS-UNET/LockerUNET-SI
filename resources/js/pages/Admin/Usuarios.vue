<script setup lang="ts">

    import { router, Link } from '@inertiajs/vue3';
    import debounce from 'lodash/debounce';
    import { watch, ref, computed } from 'vue';
    import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

    const props = defineProps<{
        users: any[],
        filters: any
    }>();

    const search = ref(props.filters?.search ?? '');

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

    // Helper para obtener información del locker del usuario
    const getLockerInfo = (user: any) => {
        if (user.locker_assignment && user.locker_assignment.locker) {
            const locker = user.locker_assignment.locker;
            return {
                code: locker.locker_code,
                building: locker.sector?.building?.building_code || 'N/A',
                sector: locker.sector?.sector_name || 'N/A'
            };
        }
        return null;
    };
</script>


<template>

    <div class="flex flex-col items-center justify-center gap-8 py-5">

        <h1 class="text-4xl font-bold">
            Usuarios
        </h1>

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
                class="block w-full p-3 ps-9 rounded-lg border-2 border-[#A3A3A3] outline-none focus:border-[#22397A]
                 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm" 
                placeholder="Nombre Usuario..."
            />
        </div>

        <p class="text-[#4169C4] font-bold">Visualización de Usuarios ({{ users.length }})</p>

        <div v-if="users.length === 0" class="text-center py-8">
            <p class="text-gray-500 text-lg">No hay usuarios disponibles</p>
        </div>

        <div v-for="user in users" :key="user.id" class="w-3/4 lg:w-1/2 flex flex-col gap-3">

            <div class="flex flex-col rounded-lg bg-[#D6E4F7] px-5 py-2 gap-3">
                <div>
                    <p class="mb-2 font-bold text-sm sm:text-lg lg:text-xl">
                        {{ user.name }} {{ user.lastname }}
                    </p>
                    <hr>
                </div>

                <div class="flex flex-col gap-1">
                    <p class="font-bold text-xs sm:text-sm lg:text-base">
                        Email:
                        <span class="font-normal">{{ user.email }}</span>
                    </p>

                    <p v-if="getLockerInfo(user)" class="font-bold text-xs sm:text-sm lg:text-base">
                        Locker:
                        <span class="font-normal">
                            {{ getLockerInfo(user).code }}
                        </span>
                    </p>
                    <p v-else class="font-bold text-xs sm:text-sm lg:text-base text-red-600">
                        Sin locker asignado
                    </p>

                    <p v-if="getLockerInfo(user)" class="font-bold text-xs sm:text-sm lg:text-base">
                        Edificio / Sector:
                        <span class="font-normal">
                            {{ getLockerInfo(user).building }} - {{ getLockerInfo(user).sector }}
                        </span>
                    </p>

                    <div class="flex flex-row justify-between items-center">
                        <p class="font-bold text-xs sm:text-sm lg:text-base">
                            Rol:
                            <span class="font-normal">
                                {{ user.is_admin === 1 ? 'Admin' : 'Usuario' }}
                            </span>
                        </p>

                        <Link 
                            :href="`/admin/users/${user.id}`"
                            class="px-1 bg-[#1C2F5E] text-white text-center font-bold rounded-lg text-xs w-[50px] md:w-[100px] md:px-2 sm:w-[80px] sm:text-base hover:bg-[#0f1a3a]">
                            Ver
                        </Link>
                    </div>
                </div>
            </div>
        </div>

    </div>

</template>