<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import LayoutAdmin from '../Layouts/LayoutAdmin.vue';
// Importación de Chart.js
import { Line } from 'vue-chartjs';
import { 
    Chart as ChartJS, Title, Tooltip, Legend, LineElement, 
    CategoryScale, LinearScale, PointElement, Filler 
} from 'chart.js';

// Registro de componentes de Chart.js
ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler);

defineOptions({ layout: LayoutAdmin });

interface Stats {
    uso_proyectado: number;
    capacidad_total: number;
    crecimiento: string;
    tasa_ocupacion: string;
    chart_data: number[];
}

interface Filters {
    carrera: string;
    semestre: string;
    mes: string;
}

const props = defineProps<{
    stats: Stats;
    filters: Filters;
}>();

const carreras = ['Informática', 'Industrial', 'Civil', 'Electrónica', 'Mecánica', 'Psicología'];
const semestres = ['2025-2', '2026-1'];
const mesesOptions = ['Enero 2026', 'Febrero 2026', 'Marzo 2026', 'Abril 2026', 'Mayo 2026'];

const filtroCarrera = ref(props.filters?.carrera || '');
const filtroSemestre = ref(props.filters?.semestre || '2026-1');
const filtroMes = ref(props.filters?.mes || 'Abril 2026');

// Configuración de Datos para la Gráfica
const chartData = computed(() => {
    // Si no hay datos, enviamos una estructura vacía segura
    const dataPoints = props.stats?.chart_data || [];
    
    return {
        labels: Array.from({ length: dataPoints.length }, (_, i) => i + 1),
        datasets: [{
            label: 'Ocupación',
            data: dataPoints,
            borderColor: '#1768B4',
            backgroundColor: 'rgba(23, 104, 180, 0.1)',
            borderWidth: 4,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 6
        }]
    };
});

// Opciones de la Gráfica (Ejes y límites)
const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#213779',
            titleFont: { size: 14, weight: 'bold' },
            padding: 12,
            cornerRadius: 10
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            // Validación de seguridad para capacidad_total
            max: (props.stats?.capacidad_total > 0) ? props.stats.capacidad_total : 10,
            grid: { color: 'rgba(0,0,0,0.03)' },
            ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
        },
        x: {
            grid: { display: false },
            ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
        }
    }
}));

const kpis = computed(() => [
    { label: 'Uso Actual', value: props.stats?.uso_proyectado ?? 0, color: 'bg-[#1768B4]' },
    { label: 'Capacidad Total', value: props.stats?.capacidad_total ?? 0, color: 'bg-[#DC2626]' },
    { label: 'Crecimiento Mensual', value: (parseFloat(props.stats?.crecimiento) >= 0 ? '+' : '') + (props.stats?.crecimiento ?? '0') + '%', color: 'bg-[#0D7A5F]' },
    { label: 'Tasa de Ocupación', value: (props.stats?.tasa_ocupacion ?? '0') + '%', color: 'bg-[#F97316]' }
]);

const aplicarFiltros = () => {
    router.get('/estadisticas-lockers', { 
        carrera: filtroCarrera.value || null, 
        mes: filtroMes.value,
        semestre: filtroSemestre.value // <--- Agrega esto
    }, { 
        preserveState: true,
        replace: true 
    });
};
</script>

<template>
    <Head title="Estadísticas de Lockers" />
    <main class="py-10 px-4 sm:px-10">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl lg:text-4xl font-extrabold text-black mb-10">Estadísticas Lockers</h1>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-10 items-end bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex flex-col gap-2">
                    <label class="font-bold text-xs uppercase text-gray-500">Carrera</label>
                    <select v-model="filtroCarrera" class="border border-gray-200 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#213779] bg-white">
                        <option value="">Todas las carreras</option>
                        <option v-for="c in carreras" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-bold text-xs uppercase text-gray-500">Semestre</label>
                    <select v-model="filtroSemestre" class="border border-gray-200 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#213779] bg-white">
                        <option v-for="s in semestres" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-bold text-xs uppercase text-gray-500">Mes de Análisis</label>
                    <select v-model="filtroMes" class="border border-gray-200 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#213779] bg-white">
                        <option v-for="m in mesesOptions" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <button @click="aplicarFiltros" class="bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold p-3 rounded-xl transition-all shadow-lg active:scale-95">
                    Filtrar Datos
                </button>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <div class="w-full lg:w-3/4 bg-white p-8 rounded-3xl shadow-sm border border-gray-50">
                    <h3 class="text-center font-black text-gray-400 mb-8 uppercase tracking-widest text-sm">
                        Curva de Ocupación Acumulada - {{ filtroMes }}
                    </h3>
                    
                    <div class="h-[300px] w-full relative">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>

                    <div class="flex justify-center gap-8 mt-10">
                        <div class="flex items-center gap-2 text-[10px] font-bold text-gray-500 uppercase">
                            <span class="w-6 h-1.5 bg-[#1768B4] rounded-full"></span> Uso Real Acumulado
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-bold text-gray-500 uppercase">
                            <span class="w-6 h-0.5 border-t border-dashed border-gray-300"></span> Eje de Tiempo (Días)
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/4 flex flex-col gap-4">
                    <div v-for="kpi in kpis" :key="kpi.label" 
                        class="p-6 rounded-3xl shadow-sm transition-all hover:-translate-y-1"
                        :class="kpi.color">
                        <p class="text-white/70 text-[10px] font-black uppercase tracking-widest">{{ kpi.label }}</p>
                        <p class="text-white text-4xl font-black mt-2">{{ kpi.value }}</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>