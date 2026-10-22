<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// Muestra los mensajes flash del backend (->with('success') / ->with('status'))
// que comparte HandleInertiaRequests. Se oculta solo a los 6 segundos.
const page = usePage<{ flash: { success: string | null; status: string | null } }>();
const mensaje = ref<string | null>(null);
let temporizador: ReturnType<typeof setTimeout> | null = null;

watch(
    () => page.props.flash,
    (flash) => {
        const texto = flash?.success || flash?.status;
        if (!texto) return;

        mensaje.value = texto;
        if (temporizador) clearTimeout(temporizador);
        temporizador = setTimeout(() => (mensaje.value = null), 6000);
    },
    { immediate: true },
);
</script>

<template>
    <div
        v-if="mensaje"
        role="status"
        aria-live="polite"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] w-[calc(100%-2rem)] max-w-md bg-[#dcfce7] border border-[#0D7A5F] text-[#0D7A5F] font-bold text-sm rounded-xl shadow-lg px-4 py-3 flex items-start justify-between gap-3"
    >
        <span>{{ mensaje }}</span>
        <button type="button" class="shrink-0 min-w-[44px] min-h-[44px] -my-3 -mr-2 text-lg leading-none" aria-label="Cerrar aviso" @click="mensaje = null">&times;</button>
    </div>
</template>
