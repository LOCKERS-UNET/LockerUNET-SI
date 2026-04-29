<script setup lang="ts">
    import { Link, router } from '@inertiajs/vue3';
    import { reactive } from 'vue';

    defineProps({ errors: Object, status: String });

    const form = reactive({
        email: null,
    });

    const submit = () => {
        router.post('/forgot-password', form, {
            preserveScroll: true,
        });
    };
</script>

<template>
    <section class="min-h-screen flex flex-col bg-white">

        <!-- Header: logos (solo visible en escritorio) -->
<header class="hidden lg:flex lg:justify-between lg:items-center lg:w-full lg:p-6 md:p-10 lg:flex-shrink-0"> 
    
    <img src="/img/Logo_UNET.png" 
         alt="Logo unet" 
         class="h-16 md:h-24 w-auto object-contain"
    >

    <img src="/img/Logo_Lockers_UNET.png" 
         alt="logo lockers unet" 
         class="h-12 md:h-16 w-auto object-contain"
    >

</header>

        <main class="flex-grow flex items-center justify-center p-4">

            <div class="flex flex-col lg:flex-row w-full max-w-6xl items-center justify-center gap-10 md:gap-24">

                <!-- ===== COLUMNA IZQUIERDA: Formulario ===== -->
                <div class="w-full max-w-md bg-white p-2 flex flex-col">

                    <!-- Logo: solo visible en móvil y tablet -->
                    <div class="flex justify-center mb-6 lg:hidden">
                        <img src="/img/Logo_Lockers_UNET.png"
                            alt="logo lockers unet"
                            class="h-auto max-h-[80px] md:max-h-[120px] w-auto object-contain"
                        >
                    </div>

                    <!-- Volver al login -->
                    <Link
                        href="/login"
                        class="flex items-center gap-1 text-[#404040] text-sm font-medium hover:text-[#213779] transition mb-6 w-fit"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a la página de inicio de sesión
                    </Link>

                    <!-- Título y descripción -->
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">
                        ¿Olvidaste tu contraseña?
                    </h2>
                    <p class="text-[#404040] text-sm mb-8 leading-relaxed">
                        No te preocupes, nos pasa a todos. Introduce tu correo electrónico
                        a continuación para recuperar tu contraseña.
                    </p>

                    <!-- Mensaje de éxito -->
                    <div
                        v-if="status"
                        class="bg-green-100 border border-green-400 text-green-700 font-semibold p-3 text-center rounded-2xl mb-4 text-sm"
                    >
                        {{ status }}
                    </div>

                    <!-- Mensaje de error general -->
                    <div
                        v-if="errors?.general"
                        class="bg-red-600 text-white font-bold p-2 text-center rounded-2xl mb-4"
                    >
                        {{ errors.general }}
                    </div>

                    <form @submit.prevent="submit" method="POST" class="space-y-6">

                        <div class="space-y-2">
                            <label for="email" class="block text-[#404040] font-semibold ml-1">
                                Correo
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Ingresa tu correo UNET"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                    placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                                :class="{
                                    'border-[#DC2626] bg-[#FEE2E2]': errors?.email,
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.email
                                }"
                                v-model="form.email"
                            >
                            <small v-if="errors?.email" class="text-red-500">{{ errors.email }}</small>
                        </div>

                        <div class="pt-2 flex justify-center">
                            <button
                                type="submit"
                                class="w-full py-3 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95"
                            >
                                Verificar Correo
                            </button>
                        </div>

                    </form>
                </div>

                <!-- ===== COLUMNA DERECHA: Imagen decorativa (solo escritorio) ===== -->
                <div class="hidden lg:flex flex-shrink-0 items-center justify-center w-[400px] h-[400px]">
                    <img src="/img/ForgotPassword.png" alt="Recuperar contraseña" class="w-full h-full object-contain">
                </div>

            </div>
        </main>
    </section>
</template>