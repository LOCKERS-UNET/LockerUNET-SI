<script setup lang="ts">
    import { router } from '@inertiajs/vue3';
    import { reactive,ref } from 'vue';
    import ModalComponent from '../Components/ModalComponent.vue';
    import LayoutAdmin from '../Layouts/LayoutAdmin.vue';

    
    defineOptions({layout: LayoutAdmin})

    const confirmingDeletion = ref(false);

    //* Funciones para abrir y cerrar Modal*/

    const openModal = () => {
        confirmingDeletion.value = true;
    };

    
    const closeModal = () => {
        confirmingDeletion.value = false;
    };

    //*Fin de las funciones */

    interface User {

        id:number;
        name: string;
        lastname: string;
        card_code: string;

    }

    const props= defineProps<{
        user: User,
        errors: any
    }>();

    const form = reactive({
        user_id: props.user.id,
        amount: null,
        reason: null
    })

    //**Ejemplo al momento de subir formulario, 
    // debemos abrir el modal en caso de que todo este bien. */
    const submit = () => {
        router.post('/admin/fines', form, {
            onSuccess: () => {
                openModal();
            }
        });
    }

</script>

<template>

    <div class="flex flex-col items-center justify-center min-h-screen py-5">


        <section class=" w-full max-w-3xl flex flex-col items-center p-5">

            <h1 class=" text-4xl text-center font-bold mb-5">Añadir Multa</h1>

            <h1 class=" text-2xl text-center font-semibold mb-5">
                Multa para el usuario 
                <span class="text-[#2E7AC0]">{{ user.name }} {{ user.lastname }}</span>
            </h1>

            <form @submit.prevent="submit" class="flex flex-col gap-5 w-full">

                <div class="space-y-2 min-h-16">
                    <label for="monto" class="block text-[#404040] font-semibold ml-1">
                        Monto
                    </label>
                    <input 
                        type="number"
                        step="1"
                        min="1"
                        name="monto" 
                        id="monto"
                        placeholder="Ej. BS.1000"
                        class="w-full h-10 px-5 py-3 rounded-full bg-gray-100  border-3  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                            placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                        :class="{
                            'border-[#DC2626]  bg-[#FEE2E2]': errors?.amount, 
                            'border-[#A3A3A3] focus:border-[#22397A]': !errors?.amount
                        }"
                        v-model="form.amount"
                    >
                    <small v-if="errors?.amount" class="text-red-500">{{ errors.amount }}</small>
                </div>

                <div class="space-y-2 min-h-16">
                    <label for="descripcion" class="block text-[#404040] font-semibold ml-1">
                        Descripcion de la Multa
                    </label>

                    <textarea 
                        name="descripcion" 
                        id="descripcion"
                        placeholder="Descripcion de la multa"
                        class="w-full h-32 resize-none px-5 py-3 rounded-xl bg-gray-100  border-3  focus:bg-white focus:outline-none text-[#404040] transition duration-200
                            placeholder:font-bold placeholder:text-[#A3A3A3] placeholder:text-sm"
                        :class="{
                            'border-[#DC2626]  bg-[#FEE2E2]': errors?.reason, 
                            'border-[#A3A3A3] focus:border-[#22397A]': !errors?.reason
                        }"
                        v-model="form.reason"
                    ></textarea>
                    <small v-if="errors?.reason" class="text-red-500">{{ errors.reason }}</small>
                </div>


                <div class="pt-4 flex justify-center">
                    <button 
                        type="submit"
                        class="w-1/2 py-3 bg-[#213779] hover:bg-[#1a2b5f] text-white font-bold rounded-xl transition duration-300 shadow-md active:scale-95"
                    >
                        Generar Multa
                    </button>
                </div>

            </form>


        </section>


        <ModalComponent 
            :show="confirmingDeletion" 
            text="¡Multa generada Correctamente!"
            url="/inicio-admin"
            title-button="Volver al Panel"
            @close="closeModal">
            
        </ModalComponent>

    </div>

</template>