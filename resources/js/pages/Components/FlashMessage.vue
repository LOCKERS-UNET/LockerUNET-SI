<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// Muestra los mensajes flash del backend (->with('success') / ->with('status'))
// que comparte HandleInertiaRequests. Se oculta solo a los 8 segundos; va abajo para no tapar el menú superior.
// Con conErrores también muestra el primer error de validación (->withErrors): el panel admin lo usa
// porque la mayoría de sus páginas no pintan los errores y el admin no se enteraba de por qué no pasó nada.
const props = defineProps<{ conErrores?: boolean }>();

const page = usePage<{
    flash: { success: string | null; status: string | null };
    errors: Record<string, string>;
}>();
const mensaje = ref<string | null>(null);
const esError = ref(false);
let temporizador: ReturnType<typeof setTimeout> | null = null;

const mostrar = (texto: string, error: boolean) => {
    mensaje.value = texto;
    esError.value = error;

    if (temporizador) {
        clearTimeout(temporizador);
    }

    temporizador = setTimeout(() => (mensaje.value = null), 8000);
};

watch(
    () => page.props.flash,
    (flash) => {
        const texto = flash?.success || flash?.status;

        if (texto) {
            mostrar(texto, false);
        }
    },
    { immediate: true },
);

watch(
    () => page.props.errors,
    (errors) => {
        const texto = props.conErrores ? Object.values(errors ?? {})[0] : null;

        if (texto) {
            mostrar(texto, true);
        }
    },
);
</script>

<template>
    <div
        v-if="mensaje"
        :role="esError ? 'alert' : 'status'"
        :aria-live="esError ? 'assertive' : 'polite'"
        class="fixed bottom-4 left-1/2 z-[60] flex w-[calc(100%-2rem)] max-w-md -translate-x-1/2 items-start justify-between gap-3 rounded-xl border px-4 py-3 text-sm font-bold shadow-lg"
        :class="
            esError
                ? 'border-[#B91C1C] bg-[#fee2e2] text-[#B91C1C]'
                : 'border-[#0D7A5F] bg-[#dcfce7] text-[#0D7A5F]'
        "
    >
        <span>{{ mensaje }}</span>
        <button
            type="button"
            class="-my-3 -mr-2 min-h-[44px] min-w-[44px] shrink-0 text-lg leading-none"
            aria-label="Cerrar aviso"
            @click="mensaje = null"
        >
            &times;
        </button>
    </div>
</template>
