<script setup lang="ts">
import { Link, router} from '@inertiajs/vue3';
import { reactive } from 'vue';

defineProps({ errors: Object });

const form = reactive({
    email: null,
    password: null
})  

const submit = () => {
    router.post('/login', form, {
        onError: () => {
            form.password = null
        }
    })
}
</script>

<template>
    <section class="min-h-screen flex flex-col bg-white">
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

                <!-- Columna izquierda: Imagen -->
                <div class="flex-shrink-0 flex flex-col items-center">
                    <!-- Imagen centrada en desktop -->
                    <div class="hidden lg:flex justify-center w-full">
                        <img src="/img/Login.png" 
                             alt="imagen prueba" 
                             class="lg:h-auto lg:max-h-[300px] md:max-h-[500px] lg:w-auto lg:object-contain"
                        >
                    </div>

                    <!-- Logo en móvil -->
                    <img src="/img/Logo_Lockers_UNET.png" 
                         alt="imagen prueba" 
                         class="block h-auto max-h-[100px] md:max-h-[200px] w-auto object-contain lg:hidden mx-auto"
                    >

                    <!-- 👇🏼 Recuadro de reporte de errores (solo desktop) -->
                    <div class="hidden lg:block mt-6 max-w-xs">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center shadow-sm">
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Si detecta un error, repórtelo al correo 
                                <a href="mailto:douglas.aliendres@unet.edu.ve" 
                                   class="text-[#213779] font-semibold hover:underline break-all">
                                    douglas.aliendres@unet.edu.ve
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: Formulario -->
                <div class="w-full max-w-md bg-white p-2">
                    <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">
                        Iniciar Sesión
                    </h2>
                    
                    <div v-if="errors?.auth" class="bg-red-600 text-white font-bold p-2 text-center rounded-2xl my-2">
                        {{ errors.auth }}
                    </div>

                    <form @submit.prevent="submit" method="POST" class="space-y-6">
                        <div class="space-y-2">
                            <label for="email" class="block text-[#404040] font-semibold ml-1">
                                Correo
                            </label>
                            <input 
                                type="email" 
                                id="email"
                                placeholder="Ingresa tu correo UNET"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                                :class="{
                                    'border-[#DC2626] bg-[#FEE2E2]': errors?.email, 
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.email
                                }"
                                v-model="form.email"
                            >
                            <small v-if="errors?.email" class="text-red-500">{{ errors.email }}</small>
                        </div>

                        <div class="space-y-2">
                            <label for="password" class="block text-[#404040] font-semibold ml-1">
                                Contraseña
                            </label>
                            <input 
                                type="password" 
                                id="password"
                                placeholder="Ingresa tu contraseña"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                                :class="{
                                    'border-[#DC2626] bg-[#FEE2E2]': errors?.password, 
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.password
                                }"
                                v-model="form.password"
                            >
                            <small v-if="errors?.password" class="text-red-500">{{ errors.password }}</small>
                            <div class="flex justify-end pr-2">
                                <Link href="/forgot-password" class="text-gray-500 text-sm hover:text-blue-600 transition">
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-center">
                            <button 
                                type="submit"
                                class="w-full py-3 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95"
                            >
                                Inicia Sesión
                            </button>
                        </div>

                        <p class="text-center text-gray-600 mt-8">
                            ¿No tienes cuenta? 
                            <Link href="/register" class="text-blue-500 font-semibold hover:underline">
                                Resgístrate
                            </Link>
                        </p>
                    </form>
                </div>

            </div>
        </main>
    </section>
</template>