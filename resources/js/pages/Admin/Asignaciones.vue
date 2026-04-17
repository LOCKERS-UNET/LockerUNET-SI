<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import ModalComponent from '../Components/ModalComponent.vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

defineOptions({ layout: LayoutAdmin });

// Props del backend
const props = defineProps<{
    asignaciones?: any[];
}>();

// ── Estado ──
const tabActual = ref('asignar_liberar'); // 'historial' | 'asignar_liberar'
const searchQuery = ref('');
const usuarioSeleccionado = ref<any>(null);
const lockeresDisponibles = ref<any[]>([]);
const asignacionesList = ref<any[]>([]);
const modalAbierto = ref(false);
const modalMensaje = ref('');
const loading = ref(true);

// Formularios
const formAsignar = useForm({
    user_id: null as any,
    locker_id: null as any
});

const formLiberar = useForm({
    assignment_id: null as any
});

// Buscar usuario
const buscarUsuario = async () => {
    if (!searchQuery.value) {
        usuarioSeleccionado.value = null;

        return;
    }

    try {

        const response = await fetch(`/admin/users/search?search=${encodeURIComponent(searchQuery.value)}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error('Error');
        }

        const users = await response.json();

        usuarioSeleccionado.value = Array.isArray(users) && users.length > 0 ? users[0] : null;
    } catch (err) {
        console.error('Error al buscar usuario:', err);
        usuarioSeleccionado.value = null;
    }
};

// Obtener lockers disponibles
const fetchLockeresDisponibles = async () => {
    try {
        const response = await fetch('/admin/lockers/available', {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error('Error');
        }

        const data = await response.json();

        lockeresDisponibles.value = data.lockers || data;
    } catch (err) {
        console.error('Error al obtener lockers:', err);
    }
};

// Obtener historial de asignaciones
const fetchAsignaciones = async () => {
    try {
        const response = await fetch('/admin/assignments/list', {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error('Error');
        }

        const data = await response.json();

        asignacionesList.value = data.asignaciones || data;
    } catch (err) {
        console.error('Error al obtener asignaciones:', err);
    } finally {
        loading.value = false;
    }
};

const getLockerDisplay = (locker: any) => {
    if (!locker) {
        return 'N/A';
    }

    const sector = locker.sector || {};
    const building = sector.building || {};

    return `${locker.locker_code} - ${building.building_code || 'N/A'} - ${sector.sector_name || 'N/A'}`;
};

const confirmarAsignacion = () => {
    if (!usuarioSeleccionado.value || !formAsignar.locker_id) {
        return;
    }

    formAsignar.user_id = usuarioSeleccionado.value.id;
    formAsignar.post('/admin/assignments', {
        onSuccess: () => {
            modalMensaje.value = '¡Locker asignado con éxito!';
            modalAbierto.value = true;
            fetchAsignaciones();
            searchQuery.value = '';
            usuarioSeleccionado.value = null;
            formAsignar.reset();
        }
    });
};

const confirmarLiberacion = (assignment: any) => {
    if (confirm('¿Deseas liberar el locker?')) {
        formLiberar.put(`/admin/assignments/${assignment.assignment_id}/release`, {
            onSuccess: () => {
                modalMensaje.value = '¡Locker liberado con éxito!';
                modalAbierto.value = true;
                fetchAsignaciones();
            }
        });
    }
};

onMounted(() => {
    if (props.asignaciones) {
        asignacionesList.value = props.asignaciones;
        loading.value = false;
    } else {
        fetchAsignaciones();
    }

    fetchLockeresDisponibles();
});

</script>

<template>
    <Head title="Asignaciones" />
    <div class="min-h-screen bg-white py-12 px-4 flex justify-center">

        <div class="w-full max-w-2xl flex flex-col items-center">
            
            <h1 class="text-3xl font-extrabold text-black mb-6">Asignaciones</h1>

            <!-- Tabs -->
            <div class="flex w-full max-w-lg mb-10 border-b border-gray-300">
                <button 
                    @click="tabActual = 'historial'"
                    class="w-1/2 py-3 text-center transition relative"
                    :class="tabActual === 'historial' ? 'text-black font-extrabold' : 'text-gray-400 font-extrabold'"
                >
                    Historial
                    <div v-if="tabActual === 'historial'" class="absolute bottom-[-1px] left-0 w-full h-[3px] bg-black"></div>
                </button>
                <button 
                    @click="tabActual = 'asignar_liberar'"
                    class="w-1/2 py-3 text-center transition relative"
                    :class="tabActual === 'asignar_liberar' ? 'text-black font-extrabold' : 'text-gray-400 font-extrabold'"
                >
                    Asignar/ Liberar
                    <div v-if="tabActual === 'asignar_liberar'" class="absolute bottom-[-1px] left-0 w-full h-[2px] bg-black"></div>
                </button>
            </div>

            <!-- Contenido Tab Principal -->
            <div v-if="tabActual === 'asignar_liberar'" class="w-full flex flex-col items-center">
                
                <!-- Buscador -->
                <div class="w-full max-w-sm relative mb-10">
                    <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6 text-black">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        v-model="searchQuery"
                        @keyup.enter="buscarUsuario"
                        class="w-full py-4 pl-16 pr-6 border border-gray-200 rounded-full font-bold text-black text-sm shadow-[0_2px_12px_rgba(0,0,0,0.06)] focus:outline-none focus:border-gray-400 focus:ring-0 text-center"
                        placeholder="Buscar usuario..."
                    />
                    <p class="text-[10px] text-gray-700 mt-2 text-center">Presiona Enter para buscar</p>
                </div>

                <!-- Usuario no seleccionado -->
                <div v-if="!usuarioSeleccionado" class="text-center py-12">
                    <p class="text-gray-500 text-lg">Ingresa el nombre o carnet del usuario</p>
                </div>

                <!-- Tarjeta Usuario Principal -->
                <div v-else class="w-full max-w-[500px] border-[2px] border-[#213779] shadow-[0_4px_10px_rgba(33,55,121,0.2)] rounded-[4px] p-5 mb-8 flex justify-between items-center bg-white pr-4">
                    <div class="flex flex-col">
                        <p class="font-extrabold text-black text-lg sm:text-xl">{{ usuarioSeleccionado.name }} {{ usuarioSeleccionado.lastname }}</p>
                        <p class="text-xs sm:text-[13px] text-gray-600 font-semibold mt-1">Email: {{ usuarioSeleccionado.email }} - Carrera: {{ usuarioSeleccionado.career || 'N/A' }}</p>
                    </div>

                    <!-- Estado Pill (Dinámico) -->
                    <div v-if="usuarioSeleccionado.locker_assignment" class="bg-[#e4ebf7] px-4 py-[8px] rounded-full flex items-center gap-2 border border-[#d0dcf0]">
                        <div class="w-[6px] h-[6px] rounded-full bg-[#213779]"></div>
                        <span class="text-[#213779] font-black text-xs sm:text-[13px]">Con Locker</span>
                    </div>
                    <div v-else class="bg-[#dcfce7] px-4 py-[8px] rounded-full flex items-center gap-2 border border-[#bbf7d0]">
                        <div class="w-[6px] h-[6px] rounded-full bg-[#0D7A5F]"></div>
                        <span class="text-[#0D7A5F] font-black text-xs sm:text-[13px]">Sin Locker</span>
                    </div>
                </div>

                <!-- ─── Estado: CON LOCKER (Liberar) ─── -->
                <template v-if="usuarioSeleccionado && usuarioSeleccionado.locker_assignment">
                    <div class="w-full max-w-[500px] border-[2px] border-[#213779] shadow-[0_4px_10px_rgba(33,55,121,0.2)] rounded-[4px] p-6 mb-10 bg-white flex flex-col gap-4">
                        <h3 class="font-black text-[#213779] text-xl mb-2">Locker Actual</h3>
                        
                        <div class="flex items-center justify-between">
                            <span class="font-black text-black text-[15px]">Código</span>
                            <span class="font-bold text-[#8ba2dc] text-[15px]">{{ usuarioSeleccionado.locker_assignment.locker.locker_code }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-black text-[15px]">Ubicación</span>
                            <span class="font-bold text-gray-800 text-[15px]">{{ usuarioSeleccionado.locker_assignment.locker.sector?.building?.building_code || 'N/A' }} - {{ usuarioSeleccionado.locker_assignment.locker.sector?.sector_name || 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-black text-[15px]">Desde</span>
                            <span class="font-bold text-gray-800 text-[15px]">{{ new Date(usuarioSeleccionado.locker_assignment.start_date).toLocaleDateString('es-ES') }}</span>
                        </div>
                    </div>

                    <button 
                        @click="confirmarLiberacion(usuarioSeleccionado.locker_assignment)"
                        :disabled="formLiberar.processing"
                        class="bg-[#DC2626] hover:bg-red-700 disabled:opacity-50 text-white font-extrabold py-3.5 px-16 rounded-full flex items-center gap-3 shadow-md transition-all active:scale-95"
                    >
                        <div class="w-[5px] h-[5px] rounded-full bg-white"></div>
                        {{ formLiberar.processing ? 'Liberando...' : 'Liberar' }}
                    </button>
                </template>

                <!-- ─── Estado: SIN LOCKER (Asignar) ─── -->
                <template v-else-if="usuarioSeleccionado">
                    <div class="w-full max-w-[500px] border-[2px] border-[#213779] shadow-[0_4px_10px_rgba(33,55,121,0.2)] rounded-[4px] p-6 mb-6 flex flex-col gap-4 bg-white">
                        <h3 class="font-black text-[#213779] text-xl mb-2">Sin Locker Asignado</h3>
                        
                        <div class="flex items-center justify-between">
                            <span class="font-black text-black text-[15px]">Estado</span>
                            <span class="font-bold text-[#14b8a6] text-[15px]">Disponible para asignar</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-black text-[15px]">Carrera</span>
                            <span class="font-bold text-gray-800 text-[15px]">{{ usuarioSeleccionado.career || 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Dropdown Seleccionar -->
                    <div class="w-full max-w-[500px] flex flex-col items-start mt-2 mb-10 pl-1">
                        <label class="font-extrabold text-[#1a5eb8] text-[15px] mb-2">Seleccionar Locker</label>
                        <div class="relative w-full max-w-[300px]">
                            <select v-model="formAsignar.locker_id" class="w-full py-3.5 pl-6 pr-10 border border-gray-200 text-black font-extrabold text-sm rounded-xl appearance-none bg-white focus:outline-none focus:border-gray-300 shadow-sm">
                                <option :value="null">Selecciona un locker...</option>
                                <option v-for="locker in lockeresDisponibles" :key="locker.locker_id" :value="locker.locker_id">
                                    {{ getLockerDisplay(locker) }}
                                </option>
                            </select>
                            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.0" stroke="currentColor" class="w-5 h-5 text-gray-900">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <button 
                        @click="confirmarAsignacion"
                        :disabled="formAsignar.processing || !formAsignar.locker_id"
                        class="bg-[#0f8b6e] hover:bg-[#0c6b54] disabled:opacity-50 text-white font-extrabold py-3.5 px-10 rounded-xl shadow-md transition-all flex justify-center active:scale-95 mb-6"
                    >
                        {{ formAsignar.processing ? 'Asignando...' : 'Confirmar Asignación' }}
                    </button>
                </template>

            </div>
            
            <div v-else class="w-full flex justify-center pb-20">
                <div v-if="loading" class="text-center py-12">
                    <p class="text-gray-500">Cargando historial...</p>
                </div>
                <div v-else-if="asignacionesList.length === 0" class="text-center py-12">
                    <p class="text-gray-500">No hay asignaciones</p>
                </div>
                <div v-else class="w-full flex flex-col gap-4">
                    <div v-for="item in asignacionesList" :key="item.assignment_id" class="border border-gray-200 bg-[#f9fafb] p-5 flex justify-between items-center rounded-sm hover:shadow-sm transition">
                        <!-- Estudiante -->
                        <div class="flex flex-col">
                            <span class="font-extrabold text-black">{{ item.user?.name }} {{ item.user?.lastname }}</span>
                            <span class="text-xs text-gray-500 font-bold">Email: {{ item.user?.email }}</span>
                        </div>
                        
                        <!-- Locker / Fecha -->
                        <div class="flex flex-col items-center">
                            <span class="font-black text-[#213779] text-sm">{{ item.locker?.locker_code }}</span>
                            <span class="text-[11px] text-gray-500 font-bold mt-1">{{ new Date(item.start_date).toLocaleDateString('es-ES') }}</span>
                        </div>
                        
                        <!-- Acción (Pill) -->
                        <div class="w-[80px] flex justify-end">
                            <span :class="['font-extrabold text-[11px] px-3 py-1 rounded-full text-center', 
                                item.assignment_status === 'active' ? 'bg-[#dcfce7] text-[#0D7A5F]' : 'bg-[#fee2e2] text-red-600']">
                                {{ item.assignment_status === 'active' ? 'Activo' : 'Completado' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal de Éxito -->
        <ModalComponent
            :show="modalAbierto"
            :text="modalMensaje"
            url="/inicio-admin"
            title-button="Aceptar"
            @close="modalAbierto = false"
        />

    </div>
</template>