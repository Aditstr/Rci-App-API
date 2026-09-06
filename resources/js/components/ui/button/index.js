import { cva } from 'class-variance-authority';

export { default as Button } from './Button.vue';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#fc5000] focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 active:scale-[0.98] cursor-pointer',
    {
        variants: {
            variant: {
                default: 'bg-[#fc5000] text-white hover:bg-[#e04700] shadow-sm',
                destructive: 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
                outline: 'border border-[#070607] bg-transparent text-[#070607] hover:bg-[#e2e2df]/60',
                secondary: 'bg-[#e2e2df] text-[#070607] hover:bg-[#d6d6d2]',
                ghost: 'hover:bg-[#e2e2df]/50 text-[#070607]',
                link: 'text-[#fc5000] underline-offset-4 hover:underline p-0 h-auto',
            },
            size: {
                default: 'h-10 px-5 py-2',
                sm: 'h-8 px-3.5 text-xs',
                lg: 'h-12 px-8 text-base',
                icon: 'h-10 w-10 rounded-full p-0 flex items-center justify-center',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);
