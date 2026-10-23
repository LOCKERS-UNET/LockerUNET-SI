<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import LayoutSegunRol from '../Layouts/LayoutSegunRol.vue';

// La usan estudiantes y admins. El perfil no se edita desde la app (nombre, carrera, foto, correo y
// carnet los cambia el Decanato): aquí solo se cambia la contraseña. El aviso de éxito lo muestra el layout.
defineOptions({ layout: LayoutSegunRol });

const formClave = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const campos = [
    { id: 'current_password', label: 'Contraseña actual', auto: 'current-password' },
    { id: 'password', label: 'Nueva contraseña (mínimo 8 caracteres)', auto: 'new-password' },
    { id: 'password_confirmation', label: 'Confirmar nueva contraseña', auto: 'new-password' },
] as const;

const cambiarClave = () => {
    formClave.post('/edit-profile/password', {
        preserveScroll: true,
        onSuccess: () => formClave.reset(),
        onError: () => formClave.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Cambiar contraseña" />
    <div class="flex flex-col items-center justify-center min-h-screen py-5">
        <section class="w-full max-w-3xl flex flex-col items-center p-5">
            <h1 class="text-4xl text-center font-bold mb-3">Cambiar contraseña</h1>
            <p class="text-gray-500 text-sm text-center mb-8">
                Para cambiar tu nombre, carrera, correo o carnet, dirígete al Decanato de Desarrollo Estudiantil.
            </p>

            <form @submit.prevent="cambiarClave" class="flex flex-col gap-5 w-full">
                <div v-for="campo in campos" :key="campo.id" class="space-y-2">
                    <label :for="campo.id" class="block text-[#404040] font-semibold ml-1">{{ campo.label }}</label>
                    <input type="password" :id="campo.id" :autocomplete="campo.auto"
                        class="w-full h-10 px-5 rounded-full bg-gray-100 border-2 focus:bg-white focus:outline-none text-[#404040] transition duration-200"
                        :class="formClave.errors[campo.id] ? 'border-[#DC2626] bg-[#FEE2E2]' : 'border-[#E5E5E5] focus:border-[#22397A]'"
                        v-model="formClave[campo.id]">
                    <small v-if="formClave.errors[campo.id]" class="text-red-500 font-bold ml-4">{{ formClave.errors[campo.id] }}</small>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row gap-3 justify-center">
                    <Link href="/profile"
                        class="sm:w-1/3 min-h-[44px] flex items-center justify-center border-2 border-gray-300 text-gray-700 font-bold rounded-xl transition hover:bg-gray-50">
                        Volver al perfil
                    </Link>
                    <button type="submit" :disabled="formClave.processing"
                        class="sm:w-1/3 min-h-[44px] bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95 disabled:opacity-50">
                        {{ formClave.processing ? 'Cambiando…' : 'Cambiar contraseña' }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</template>
