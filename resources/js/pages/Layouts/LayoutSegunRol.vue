<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Layout from './Layout.vue';
import LayoutAdmin from './LayoutAdmin.vue';

// Para las páginas que comparten estudiantes y admins (Notificaciones): cada uno ve su propio menú
const page = usePage<{ auth: { user: { is_admin: boolean | number } } }>();
const esAdmin = computed(() => Boolean(page.props.auth?.user?.is_admin));
</script>

<template>
    <component :is="esAdmin ? LayoutAdmin : Layout">
        <slot />
    </component>
</template>
