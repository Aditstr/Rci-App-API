import { cva } from 'class-variance-authority';

export { default as Alert } from './Alert.vue';
export { default as AlertTitle } from './AlertTitle.vue';
export { default as AlertDescription } from './AlertDescription.vue';

export const alertVariants = cva(
    'relative w-full rounded-2xl border p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:text-foreground',
    {
        variants: {
            variant: {
                default: 'bg-[#f7f6f2] text-[#070607] border-black/10',
                destructive:
                    'border-red-500/50 text-red-600 bg-red-50 [&>svg]:text-red-600',
                success:
                    'border-emerald-500/50 text-emerald-800 bg-emerald-50 [&>svg]:text-emerald-600',
                ember:
                    'border-[#fc5000]/30 text-[#fc5000] bg-[#fc5000]/10 [&>svg]:text-[#fc5000]',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);
