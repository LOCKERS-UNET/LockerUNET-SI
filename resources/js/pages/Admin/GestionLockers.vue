<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ModalComponent from '../Components/ModalComponent.vue';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

defineOptions({ layout: LayoutAdmin });

// Props del backend
const props = defineProps<{
    lockers: any[];
    buildings: any[];
    sectors: any[];
}>();

// ── Estado ──
const vistaActual = ref<'modificar' | 'agregar' | 'listar'>('listar');
const modalAbierto = ref(false);
const lockerSeleccionado = ref<any>(null);

// Formulario para agregar locker
const form = useForm({
    locker_code: '',
    sector_id: null,
    locker_type: 'small',
    status: 0,
});

// Formulario para editar
const formEditar = useForm({
    locker_code: '',
    sector_id: null,
    locker_type: 'small',
    status: 0,
});

const cambiarVista = (vista: 'modificar' | 'agregar' | 'listar') => {
    vistaActual.value = vista;
    if (vista === 'agregar') {
        form.reset();
    }
};

const abrirEditar = (locker: any) => {
    lockerSeleccionado.value = locker;
    formEditar.locker_code = locker.locker_code;
    formEditar.sector_id = locker.sector_id;
    formEditar.locker_type = locker.locker_type;
    formEditar.status = locker.status;
    vistaActual.value = 'modificar';
};

const confirmarAgregar = () => {
    form.post('/admin/lockers', {
        onSuccess: () => {
            modalAbierto.value = true;
            form.reset();
        }
    });
};

const confirmarEditar = () => {
    formEditar.put(`/admin/lockers/${lockerSeleccionado.value.locker_id}`, {
        onSuccess: () => {
            modalAbierto.value = true;
            vistaActual.value = 'listar';
        }
    });
};

const confirmarEliminar = () => {
    if (confirm('¿Estás seguro de que deseas eliminar este locker?')) {
        formEditar.delete(`/admin/lockers/${lockerSeleccionado.value.locker_id}`, {
            onSuccess: () => {
                modalAbierto.value = true;
                vistaActual.value = 'listar';
            }
        });
    }
};

// Helper para obtener el nombre del sector
const getNombreSector = (sectorId: number) => {
    const sector = props.sectors.find(s => s.sector_id === sectorId);
    return sector ? sector.sector_name || 'N/A' : 'N/A';
};

// Helper para obtener el estado
const getNombreEstado = (status: number) => {
    const estados = { 0: 'Disponible', 1: 'Ocupado', 2: 'Mantenimiento' };
    return estados[status] || 'Desconocido';
};

// Helper para obtener el tipo
const getNombreTipo = (tipo: string) => {
    const tipos = { small: 'Pequeño', medium: 'Mediano', mid: 'Mediano', large: 'Grande' };
    return tipos[tipo] || tipo;
};
</script>

<template>
    <Head title="Gestión de Lockers" />
    <div class="min-h-screen bg-white py-12 px-4 flex justify-center">

        <!-- ─── VISTA: LISTAR LOCKERS ─── -->
        <div v-if="vistaActual === 'listar'" class="w-full max-w-6xl flex flex-col items-center">
            
            <div class="flex items-center gap-4 mb-8 mt-6">
                <h1 class="text-3xl font-extrabold text-black">Gestión de Lockers</h1>
                <button 
                    @click="cambiarVista('agregar')"
                    class="bg-[#10b981] hover:bg-[#059669] text-white rounded-full p-2 shadow-md transition-transform hover:scale-110"
                    title="Agregar Nuevo Locker"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
            </div>

            <!-- Tabla de Lockers -->
            <div v-if="props.lockers.length > 0" class="w-full overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-[#213779] text-white">
                            <th class="px-4 py-3 text-left font-bold">Código</th>
                            <th class="px-4 py-3 text-left font-bold">Sector</th>
                            <th class="px-4 py-3 text-left font-bold">Tipo</th>
                            <th class="px-4 py-3 text-left font-bold">Estado</th>
                            <th class="px-4 py-3 text-center font-bold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="locker in props.lockers" :key="locker.locker_id" class="border-b hover:bg-gray-100">
                            <td class="px-4 py-3 text-black font-bold">{{ locker.locker_code }}</td>
                            <td class="px-4 py-3 text-black">{{ getNombreSector(locker.sector_id) }}</td>
                            <td class="px-4 py-3 text-black">{{ getNombreTipo(locker.locker_type) }}</td>
                            <td class="px-4 py-3">
                                <span :class="[
                                    'px-3 py-1 rounded-full text-white font-bold text-sm',
                                    locker.status === 0 ? 'bg-[#10b981]' :
                                    locker.status === 1 ? 'bg-[#f59e0b]' :
                                    'bg-[#ef4444]'
                                ]">
                                    {{ getNombreEstado(locker.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    @click="abrirEditar(locker)"
                                    class="bg-[#3b82f6] hover:bg-[#1d4ed8] text-white px-3 py-1 rounded text-sm mr-2"
                                >
                                    Editar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="text-center py-12">
                <p class="text-gray-500 text-lg mb-4">No hay lockers disponibles</p>
                <p class="text-gray-400">Crea el primer locker haciendo clic en el botón +</p>
            </div>
        </div>

        <!-- ─── VISTA: MODIFICAR LOCKER ─── -->
        <div v-if="vistaActual === 'modificar'" class="w-full max-w-lg flex flex-col items-center">
            
            <button @click="cambiarVista('listar')" class="absolute top-6 left-4 text-gray-400 hover:text-black transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-7 h-7">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </button>

            <h1 class="text-3xl font-extrabold text-black mb-4 mt-6">Editar Locker</h1>
            <h2 class="text-lg font-extrabold text-[#4472c4] mb-12 text-center">{{ lockerSeleccionado?.locker_code }}</h2>

            <!-- Grid de Formulario -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-7 w-full max-w-2xl pb-10">
                
                <!-- Código -->
                <div class="w-full flex flex-col gap-1">
                    <label class="text-sm font-extrabold text-black ml-2 mt-1">Código</label>
                    <input 
                        v-model="formEditar.locker_code" 
                        type="text" 
                        placeholder="Código Locker" 
                        class="w-full h-14 px-6 rounded-xl border border-gray-100 bg-[#f3f4f6] placeholder:text-gray-400 font-bold text-[15px] text-black focus:bg-white focus:outline-none focus:border-gray-300"
                    />
                </div>

                <!-- Sector -->
                <div class="relative w-full shadow-sm rounded-full h-14 mt-[22px]">
                    <select v-model="formEditar.sector_id" class="w-full h-full px-6 border border-gray-200 rounded-[2rem] font-extrabold text-[15px] text-black appearance-none bg-white focus:outline-none focus:border-gray-400">
                        <option :value="null" disabled>Sector</option>
                        <option v-for="sector in sectors" :key="sector.sector_id" :value="sector.sector_id">
                            {{ sector.sector_name || 'N/A' }}
                        </option>
                    </select>
                    <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#1a5eb8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>

                <!-- Tamaño -->
                <div class="relative w-full shadow-sm rounded-full h-14 mt-[22px]">
                    <select v-model="formEditar.locker_type" class="w-full h-full px-6 border border-gray-200 rounded-[2rem] font-extrabold text-[15px] text-black appearance-none bg-white focus:outline-none focus:border-gray-400">
                        <option value="small">Pequeño</option>
                        <option value="medium">Mediano</option>
                        <option value="mid">Mediano</option>
                        <option value="large">Grande</option>
                    </select>
                    <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#1a5eb8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>

                <!-- Estado -->
                <div class="relative w-full shadow-sm rounded-full h-14 mt-[22px]">
                    <select v-model="formEditar.status" class="w-full h-full px-6 border border-gray-200 rounded-[2rem] font-extrabold text-[15px] text-black appearance-none bg-white focus:outline-none focus:border-gray-400">
                        <option :value="0">Disponible</option>
                        <option :value="1">Ocupado</option>
                        <option :value="2">Mantenimiento</option>
                    </select>
                    <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#1a5eb8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="w-full max-w-[280px] flex flex-col gap-4 mt-8">
                <button 
                    @click="confirmarEditar"
                    :disabled="formEditar.processing"
                    class="w-full py-4 bg-[#213779] hover:bg-[#1a2b5f] text-white font-extrabold rounded-xl shadow-md transition-colors active:scale-95 text-md disabled:opacity-50"
                >
                    {{ formEditar.processing ? 'Guardando...' : 'Guardar cambios' }}
                </button>
                <button 
                    @click="confirmarEliminar"
                    :disabled="formEditar.processing"
                    class="w-full py-4 bg-[#f0384a] hover:bg-[#d42c3d] text-white font-extrabold rounded-xl shadow-md transition-colors active:scale-95 text-md disabled:opacity-50"
                >
                    {{ formEditar.processing ? 'Eliminando...' : 'Eliminar Locker' }}
                </button>
            </div>
        </div>

        <!-- ─── VISTA: AGREGAR LOCKER ─── -->
        <div v-else-if="vistaActual === 'agregar'" class="w-full flex flex-col items-center max-w-2xl relative">
            
            <button @click="cambiarVista('listar')" class="absolute -left-10 sm:left-4 top-6 text-gray-400 hover:text-black transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-7 h-7">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </button>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-black mb-12 mt-6">Agregar Locker</h1>

            <!-- Formulario de Agregar -->
            <div class="w-full max-w-[500px] flex flex-col gap-7 mb-12">
                
                <!-- Codigo -->
                <div class="w-full flex flex-col gap-1.5">
                    <label class="text-sm font-extrabold text-black ml-4">Código</label>
                    <input 
                        v-model="form.locker_code"
                        type="text" 
                        placeholder="Ingrese el código del locker" 
                        class="w-full h-14 px-6 rounded-full border border-gray-200 bg-[#f9fafb] placeholder:text-gray-400 font-bold text-[15px] text-black focus:bg-white focus:outline-none focus:border-gray-400"
                    />
                </div>

                <!-- Sector -->
                <div class="relative w-full shadow-sm rounded-full h-14">
                    <select v-model="form.sector_id" class="w-full h-full px-6 border border-gray-200 bg-[#f9fafb] rounded-[2rem] font-bold text-[15px] text-black appearance-none focus:outline-none focus:border-gray-400 focus:bg-white">
                        <option :value="null" disabled>Sector</option>
                        <option v-for="sector in sectors" :key="sector.sector_id" :value="sector.sector_id">
                            {{ sector.sector_name || 'N/A' }}
                        </option>
                    </select>
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 text-[#213779]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>

                <!-- Tamaño -->
                <div class="relative w-full shadow-sm rounded-full h-14">
                    <select v-model="form.locker_type" class="w-full h-full px-6 border border-gray-200 bg-[#f9fafb] rounded-[2rem] font-bold text-[15px] text-black appearance-none focus:outline-none focus:border-gray-400 focus:bg-white">
                        <option value="small">Pequeño</option>
                        <option value="medium">Mediano</option>
                        <option value="mid">Mediano</option>
                        <option value="large">Grande</option>
                    </select>
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 text-[#213779]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>

                <!-- Estado -->
                <div class="relative w-full shadow-sm rounded-full h-14">
                    <select v-model="form.status" class="w-full h-full px-6 border border-gray-200 bg-[#f9fafb] rounded-[2rem] font-bold text-[15px] text-black appearance-none focus:outline-none focus:border-gray-400 focus:bg-white">
                        <option :value="0">Disponible</option>
                        <option :value="1">Ocupado</option>
                        <option :value="2">Mantenimiento</option>
                    </select>
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 text-[#213779]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Boton Agregar -->
            <button 
                @click="confirmarAgregar"
                :disabled="form.processing"
                class="w-full max-w-[280px] py-4 bg-[#213779] hover:bg-[#1a2b5f] text-white font-extrabold rounded-xl shadow-md transition-colors active:scale-95 text-[15px] disabled:opacity-50"
            >
                {{ form.processing ? 'Agregando...' : 'Agregar Locker' }}
            </button>
        </div>

        <!-- Modal de confirmación -->
        <ModalComponent
            :show="modalAbierto"
            :text="vistaActual === 'agregar' ? '¡Locker agregado correctamente!' : '¡Locker actualizado correctamente!'"
            url="/inicio-admin"
            title-button="Volver al Panel"
            @close="modalAbierto = false; cambiarVista('listar')"
        />
    </div>
</template>