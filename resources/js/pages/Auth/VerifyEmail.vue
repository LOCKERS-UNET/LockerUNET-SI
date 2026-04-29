<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

// Tipar correctamente los props
interface Props {
    errors: Record<string, string>;  // 👈🏼 Cambiar 'Object' por Record<string, string>
    status?: string;
}

defineProps<Props>();

const form = reactive({
    code: '',
});

const submit = () => {
    router.post('/verify-email', form, {
        preserveScroll: true,
    });
};

const resendCode = () => {
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
            <img src="/img/Logo_UNET.png" alt="Logo unet" class="h-16 md:h-24 w-auto object-contain">
            <img src="/img/Logo_Lockers_UNET.png" alt="logo lockers unet" class="h-12 md:h-16 w-auto object-contain">
        </header>

        <main class="flex-grow flex items-center justify-center p-4">
            <div class="flex flex-col lg:flex-row w-full max-w-6xl items-center justify-center gap-10 md:gap-24">

                <!-- Columna izquierda: Formulario -->
                <div class="w-full max-w-md bg-white p-2 flex flex-col">

                    <!-- Logo móvil -->
                    <div class="flex justify-center mb-6 lg:hidden">
                        <img src="/img/Logo_Lockers_UNET.png" alt="logo" class="h-auto max-h-[80px] md:max-h-[120px] w-auto object-contain">
                    </div>

                    <!-- Volver -->
                    <Link href="/" class="flex items-center gap-1 text-[#404040] text-sm font-medium hover:text-[#213779] transition mb-6 w-fit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver al inicio
                    </Link>

                    <!-- Título -->
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Verifica tu correo</h2>
                    <p class="text-[#404040] text-sm mb-8 leading-relaxed">
                        Hemos enviado un código de verificación a <strong>{{ user?.email }}</strong>. 
                        Introdúcelo a continuación para activar tu cuenta.
                    </p>

                    <!-- Mensaje de éxito -->
                    <div v-if="status" class="bg-green-100 border border-green-400 text-green-700 font-semibold p-3 text-center rounded-2xl mb-4 text-sm">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" method="POST" class="space-y-6">
                        <div class="space-y-2">
                            <label for="code" class="block text-[#404040] font-semibold ml-1">Código de verificación</label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                placeholder="Ingresa el código de 6 dígitos"
                                maxlength="6"
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
                            <button type="submit" class="w-full py-3 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95">
                                Verificar cuenta
                            </button>
                        </div>
                    </form>

                    <!-- Reenviar código -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            ¿No recibiste el código?
                            <button @click="resendCode" class="text-[#213779] font-bold hover:underline">
                                Reenviar código
                            </button>
                        </p>
                    </div>
                </div>

                <!-- Columna derecha: Imagen -->
                <div class="hidden lg:flex flex-shrink-0 items-center justify-center w-[400px] h-[400px]">
                    <img src="/img/VerificationCode.png" alt="Verificar correo" class="w-full h-full object-contain">
                </div>
            </div>
        </main>
    </section>
</template>