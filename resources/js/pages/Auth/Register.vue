<script setup lang="ts">
    import { Link, router } from '@inertiajs/vue3';
    import { reactive } from 'vue';

    defineProps({ errors: Object });

    const form = reactive({

        name: null,
        lastname:null,
        email: null,
        card_code:null,
        career:null,
        password:null,
        password_confirmation: null

    })

    const submit = () =>{

        router.post('/register',form,{
            preserveScroll:true,
            onError:() => {
                form.password=null;
                form.password_confirmation=null;
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

                <div class="flex-shrink-0">
                    <img src="/img/Login.png" 
                         alt="imagen prueba" 
                         class="hidden lg:block lg:h-auto lg:max-h-[300px] md:max-h-[500px] lg:w-auto lg:object-contain"
                    >

                    <img src="/img/Logo_Lockers_UNET.png" 
                         alt="imagen prueba" 
                         class="block h-auto max-h-[100px] md:max-h-[200px] w-auto object-contain lg:hidden"
                    >
                </div>

                <div class="w-full max-w-md bg-white p-2">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8 flex lg:start">
                        Resgistro
                    </h2>

                    <form @submit.prevent="submit" method="POST" class="space-y-6">
                        
                        <div class="space-y-2 min-h-16">
                            <label for="name" class="block text-[#404040] font-semibold ml-1">
                                Nombre
                            </label>
                            <input 
                                type="text"
                                name="name" 
                                id="name"
                                placeholder="Ingresa tu Nombre"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-2  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-xs md:placeholder:text-sm"
                                :class="{
                                    'border-[#DC2626]  bg-[#FEE2E2]': errors?.name, 
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.name
                                }"
                                v-model="form.name"
                            >
                            <small v-if="errors?.name" class="text-red-500">{{ errors.name }}</small>
                        </div>

                        <div class="space-y-2 min-h-16">
                            <label for="lastname" class="block text-[#404040] font-semibold ml-1">
                                Apellido
                            </label>
                            <input 
                                type="text"
                                name="lastname" 
                                id="lastname"
                                placeholder="Ingresa tu Apellido"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-2  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-xs md:placeholder:text-sm"
                                :class="{
                                    'border-[#DC2626]  bg-[#FEE2E2]': errors?.lastname, 
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.lastname
                                }"
                                v-model="form.lastname"
                            >
                            <small v-if="errors?.lastname" class="text-red-500">{{ errors.lastname }}</small>
                        </div>

                        <div class="space-y-2 min-h-16">
                            <label for="email" class="block text-[#404040] font-semibold ml-1">
                                Correo
                            </label>
                            <input 
                                type="email" 
                                id="email"
                                name="email"
                                placeholder="Ingresa tu correo UNET"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-2  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-xs md:placeholder:text-sm"
                                :class="{
                                    'border-[#DC2626]  bg-[#FEE2E2]': errors?.email, 
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.email
                                }"
                                v-model="form.email"
                            >
                            <small v-if="errors?.email" class="text-red-500">{{ errors.email }}</small>
                        </div>

                        <div class="space-y-2 flex flex-row gap-5 min-h-16">

                            <section>
                                <label for="card_code" class="block text-[#404040] font-semibold ml-1">
                                    Código Carnet
                                </label>
                                <input 
                                    type="number" 
                                    id="card_code"
                                    name="card_code"
                                    placeholder="Código 5 dígitos Carnet"
                                    class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-2  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                    placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-[10px] md:placeholder:text-sm"
                                    :class="{
                                        'border-[#DC2626]  bg-[#FEE2E2]': errors?.card_code, 
                                        'border-[#A3A3A3] focus:border-[#22397A]': !errors?.card_code
                                    }"
                                    v-model="form.card_code"
                                >
                                <small v-if="errors?.card_code" class="text-red-500">{{ errors.card_code }}</small>
                            </section>

                            <section>
                                <label for="career" class="block text-[#404040] font-semibold ml-1">
                                    Carrera
                                </label>
                                <input 
                                    type="text" 
                                    id="career"
                                    name="career"
                                    placeholder="Ingresa tu carrera"
                                    class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-2  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                    placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-[10px] md:placeholder:text-sm"
                                    :class="{
                                        'border-[#DC2626]  bg-[#FEE2E2]': errors?.career, 
                                        'border-[#A3A3A3] focus:border-[#22397A]': !errors?.career
                                    }"
                                    v-model="form.career"
                                >
                                <small v-if="errors?.career" class="text-red-500">{{ errors.career }}</small>
                            </section>

                        </div>

                        <div class="space-y-2 min-h-16">
                            <label for="password" class="block text-[#404040] font-semibold ml-1">
                                Contraseña
                            </label>
                            <input 
                                type="password" 
                                id="password"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-2  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-xs md:placeholder:text-sm"
                                :class="{
                                    'border-[#DC2626]  bg-[#FEE2E2]': errors?.password, 
                                    'border-[#A3A3A3] focus:border-[#22397A]': !errors?.password
                                }"
                                v-model="form.password"
                            >
                            <small v-if="errors?.password" class="text-red-500">{{ errors.password }}</small>
                        </div>

                        <div class="space-y-2 min-h-16">
                            <label for="password_confirmation" class="block text-[#404040] font-semibold ml-1">
                               Confirmar Contraseña
                            </label>
                            <input 
                                type="password" 
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Confirma tu contraseña"
                                class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-2 border-[#A3A3A3]
                                 focus:border-[#22397A] focus:bg-white focus:outline-none text-[#404040] transition duration-200
                                 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-xs md:placeholder:text-sm"
                                v-model="form.password_confirmation"
                            >
                            
                        </div>

                        <p class=" flex justify-end text-center text-gray-600 mt-8">
                            ¿Ya tienes cuenta? 
                            <Link href="/login" class="text-blue-500 font-semibold hover:underline">
                                Inicia Sesión
                            </Link>
                        </p>

                        <div class="pt-4 flex justify-center">
                            <button 
                                type="submit"
                                class="w-full py-3 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95"
                            >
                                Crear Cuenta
                            </button>
                        </div>

                        
                    </form>
                </div>

            </div>
        </main>
    </section>
</template>