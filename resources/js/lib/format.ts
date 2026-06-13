export function money(amount: number, currency = 'EUR'): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(amount);
}

export function date(value?: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
}
