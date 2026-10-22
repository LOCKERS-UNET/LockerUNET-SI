<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

// Tipar correctamente los props
interface Props {
    errors: Record<string, string>;  // 👈🏼 Cambiar 'Object' por Record<string, string>
    status?: string;
}

defineProps<Props>();

const form = reactive({
    code: '',
});

// Bloquea el botón mientras viaja la petición (un doble clic mandaba dos envíos)
const enviando = ref(false);

const submit = () => {
    if (enviando.value) {
        return;
    }

    // Se marca antes de la petición: con CPU lenta onStart llegaba tarde y un doble toque enviaba dos veces
    enviando.value = true;
    router.post('/verify-email', form, {
        preserveScroll: true,
        onFinish: () => (enviando.value = false),
    });
};

// Al pegar "123 456" o con saltos de línea se dejan solo los dígitos (antes maxlength cortaba el texto pegado)
watch(() => form.code, (valor) => {
    const limpio = (valor ?? '').replace(/\D/g, '').slice(0, 6);

    if (limpio !== valor) {
        form.code = limpio;
    }
});

// El backend permite un reenvío por minuto; el botón espera lo mismo para no mandar dos códigos
const esperaReenvio = ref(0);
let temporizadorReenvio: ReturnType<typeof setInterval> | null = null;

const iniciarEspera = () => {
    esperaReenvio.value = 60;
    temporizadorReenvio = setInterval(() => {
        esperaReenvio.value--;

        if (esperaReenvio.value <= 0 && temporizadorReenvio) {
            clearInterval(temporizadorReenvio);
        }
    }, 1000);
};

const resendCode = () => {
    if (esperaReenvio.value > 0) {
        return;
    }

    iniciarEspera();
    router.post('/verify-email/resend', {}, {
        preserveScroll: true,
    });
};

// Obtener usuario actual
const user = usePage().props.auth.user;
</script>

<template>
    <section class="min-h-screen flex flex-col bg-white">

        <!-- Header -->
        <header class="hidden lg:flex lg:justify-between lg:items-center lg:w-full lg:p-6 lg:flex-shrink-0"> 
            <img src="/img/Logo_UNET.webp" alt="Logo unet" class="h-16 md:h-24 w-auto object-contain">
            <img src="/img/Logo_Lockers_UNET.webp" alt="logo lockers unet" class="h-12 md:h-16 w-auto object-contain">
        </header>

        <main class="flex-grow flex items-center justify-center p-4">
            <div class="flex flex-col lg:flex-row w-full max-w-6xl items-center justify-center gap-10 md:gap-24">

                <!-- Columna izquierda: Formulario -->
                <div class="w-full max-w-md bg-white p-2 flex flex-col">

                    <!-- Logo móvil -->
                    <div class="flex justify-center mb-6 lg:hidden">
                        <img src="/img/Logo_Lockers_UNET.webp" alt="logo" class="h-auto max-h-[80px] md:max-h-[120px] w-auto object-contain">
                    </div>

                    <!-- Volver: sin verificar no se puede entrar al inicio, así que se cierra la sesión -->
                    <Link href="/logout" method="post" as="button" class="flex items-center gap-1 text-[#404040] text-sm font-medium hover:text-[#213779] transition mb-6 w-fit cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Cerrar sesión
                    </Link>

                    <!-- Título -->
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Verifica tu correo</h2>
                    <p class="text-[#404040] text-sm mb-8 leading-relaxed">
                        Hemos enviado un código de verificación a <strong>{{ user?.email }}</strong>. 
                        Introdúcelo a continuación para activar tu cuenta. Si no lo ves, revisa la carpeta de spam.
                    </p>

                    <!-- Mensaje de éxito -->
                    <div v-if="status" class="bg-green-100 border border-green-400 text-green-700 font-semibold p-3 text-center rounded-2xl mb-4 text-sm">
                        {{ status }}
                    </div>

                    <!-- Error de envío del correo -->
                    <div v-if="errors?.email" class="bg-[#FEE2E2] border border-[#DC2626] text-[#DC2626] font-semibold p-3 text-center rounded-2xl mb-4 text-sm">
                        {{ errors.email }}
                    </div>

                    <form @submit.prevent="submit" method="POST" class="space-y-6">
                        <div class="space-y-2">
                            <label for="code" class="block text-[#404040] font-semibold ml-1">Código de verificación</label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                placeholder="Ingresa el código de 6 dígitos"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm tracking-widest text-center font-bold text-lg"
                                :class="{
                                    'border-[#DC2626] bg-[#FEE2E2]': errors?.code,
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.code
                                }"
                                v-model="form.code"
                            >
                            <small v-if="errors?.code" class="text-red-500">{{ errors.code }}</small>
                        </div>

                        <div class="pt-2 flex justify-center">
                            <button type="submit" :disabled="enviando" class="w-full py-3 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95 disabled:opacity-60 disabled:cursor-wait">
                                {{ enviando ? 'Verificando…' : 'Verificar cuenta' }}
                            </button>
                        </div>
                    </form>

                    <!-- Reenviar código -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            ¿No recibiste el código?
                            <button type="button" @click="resendCode" :disabled="esperaReenvio > 0" class="text-[#213779] font-bold hover:underline disabled:text-gray-400 disabled:no-underline disabled:cursor-not-allowed min-h-[44px] px-1">
                                {{ esperaReenvio > 0 ? `Reenviar código (${esperaReenvio} s)` : 'Reenviar código' }}
                            </button>
                        </p>
                    </div>
                </div>

                <!-- Columna derecha: Imagen -->
                <div class="hidden lg:flex flex-shrink-0 items-center justify-center w-[400px] h-[400px]">
                    <img loading="lazy" src="/img/VerificationCode.webp" alt="Verificar correo" class="w-full h-full object-contain">
                </div>
            </div>
        </main>
    </section>
</template>