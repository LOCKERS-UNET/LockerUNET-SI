import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// Fecha y hora del backend (ISO en UTC, p. ej. requested_at) → "22/10/2026, 01:37" en la hora local del navegador
export function formatFechaHora(valor: string | null | undefined): string {
    if (!valor) {
        return '—';
    }

    return new Date(valor).toLocaleString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Fecha sin hora ("2026-10-22", p. ej. start_date) → "22/10/2026", sin pasar por Date para no correr el día por la zona horaria
export function formatFecha(valor: string | null | undefined): string {
    if (!valor) {
        return '—';
    }

    const [anio, mes, dia] = valor.slice(0, 10).split('-');

    return `${dia}/${mes}/${anio}`;
}
