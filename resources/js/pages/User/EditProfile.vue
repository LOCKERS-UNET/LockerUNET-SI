<script setup lang="ts">
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ModalComponent from '../Components/ModalComponent.vue';
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    errors: Object
});

// Obtenemos los datos actuales del usuario desde las props globales
const user = usePage().props.auth.user;

const confirmingDeletion = ref(false);
const photoPreview = ref<string | null>(null);

// Usamos useForm para manejar el envío de datos y archivos de forma sencilla
const form = useForm({
    name: user.name || '',
    lastname: user.lastname || '',
    email: user.email || '',
    card_code: user.card_code || '',
    career: user.career || '',
    profile_photo: null as File | null,
});

const openModal = () => {
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
};

const handlePhotoChange = (e: any) => {
    const file = e.target.files[0];
    if (file) {
        form.profile_photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    // IMPORTANTE: Se usa post con forceFormData para que Laravel reciba el archivo correctamente
    form.post('/edit-profile', {
        forceFormData: true,
        onSuccess: () => {
            openModal();
        },
    });
};
</script>

<template> 
    <div class="flex flex-col items-center justify-center min-h-screen py-5">
        <section class="w-full max-w-3xl flex flex-col items-center p-5">
            <h1 class="text-4xl text-center font-bold mb-8">Editar Perfil</h1>

            <form @submit.prevent="submit" class="flex flex-col gap-5 w-full">
                
                <div class="flex flex-col items-center mb-6">
                    <div class="relative group">
                        <img v-if="photoPreview || user.profile_photo" 
                             :src="photoPreview || `/storage/${user.profile_photo}`" 
                             class="size-32 rounded-full object-cover border-4 border-[#22397A] shadow-md">
                        
                        <div v-else class="size-32 rounded-full bg-gray-200 flex items-center justify-center border-4 border-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-16 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>

                        <label class="absolute bottom-0 right-0 bg-[#22397A] p-2 rounded-full cursor-pointer text-white hover:scale-110 transition shadow-lg">
                            <input type="file" class="hidden" @change="handlePhotoChange" accept="image/*">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            </svg>
                        </label>
                    </div>
                    <p class="text-xs font-bold text-gray-400 mt-2">Haz clic en el icono para cambiar tu foto</p>
                    <small v-if="errors?.profile_photo" class="text-red-500 font-bold">{{ errors.profile_photo }}</small>
                </div>

                <div class="space-y-2 min-h-16">
                    <label for="name" class="block text-[#404040] font-semibold ml-1">Nombre</label>
                    <input type="text" id="name" placeholder="Ingresa tu Nombre"
                        class="w-full h-10 px-5 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                        :class="errors?.name ? 'border-[#DC2626] bg-[#FEE2E2]' : 'border-[#E5E5E5] focus:border-[#22397A]'"
                        v-model="form.name">
                </div>

                <div class="space-y-2 min-h-16">
                    <label for="lastname" class="block text-[#404040] font-semibold ml-1">Apellido</label>
                    <input type="text" id="lastname" placeholder="Ingresa tu Apellido"
                        class="w-full h-10 px-5 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                        :class="errors?.lastname ? 'border-[#DC2626] bg-[#FEE2E2]' : 'border-[#E5E5E5] focus:border-[#22397A]'"
                        v-model="form.lastname">
                </div>

                <div class="space-y-2 min-h-16">
                    <label for="email" class="block text-[#404040] font-semibold ml-1">Correo</label>
                    <input type="email" id="email" placeholder="Ingresa tu correo UNET"
                        class="w-full h-10 px-5 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                        :class="errors?.email ? 'border-[#DC2626] bg-[#FEE2E2]' : 'border-[#E5E5E5] focus:border-[#22397A]'"
                        v-model="form.email">
                    <small v-if="errors?.email" class="text-red-500 font-bold ml-4">{{ errors.email }}</small>
                </div>

                <div class="space-y-2 min-h-16">
                    <label for="card_code" class="block text-[#404040] font-semibold ml-1">Código Carnet</label>
                    <input type="text" id="card_code" placeholder="Carnet de 5 dígitos UNET"
                        class="w-full h-10 px-5 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                        :class="errors?.card_code ? 'border-[#DC2626] bg-[#FEE2E2]' : 'border-[#E5E5E5] focus:border-[#22397A]'"
                        v-model="form.card_code">
                    <small v-if="errors?.card_code" class="text-red-500 font-bold ml-4">{{ errors.card_code }}</small>
                </div>

                <div class="space-y-2 min-h-16">
                    <label for="career" class="block text-[#404040] font-semibold ml-1">Carrera</label>
                    <input type="text" id="career" placeholder="Ingresa tu carrera"
                        class="w-full h-10 px-5 rounded-full bg-gray-100 border-2 border-[#E5E5E5] focus:border-[#22397A] focus:bg-white focus:outline-none text-[#404040] transition duration-200 placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                        v-model="form.career">
                </div>

                <div class="pt-4 flex justify-center">
                    <button 
                        type="submit"
                        :disabled="form.processing"
                        class="w-1/2 py-3 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>

            </form>
        </section>

        <ModalComponent 
            :show="confirmingDeletion" 
            text="¡Cambios Guardados Correctamente!"
            url="/profile"
            title-button="Volver al perfil"
            @close="closeModal">
        </ModalComponent>
    </div>
</template>