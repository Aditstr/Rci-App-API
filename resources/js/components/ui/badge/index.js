import { cva } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-[#fc5000] text-white shadow hover:bg-[#fc5000]/80',
                secondary: 'border-transparent bg-[#f5f28e] text-[#070607] hover:bg-[#eae77f]',
                destructive: 'border-transparent bg-red-500 text-white shadow hover:bg-red-600',
                outline: 'border-[#070607]/20 text-[#070607] bg-white/50',
                success: 'border-transparent bg-emerald-500 text-white shadow hover:bg-emerald-600',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);
