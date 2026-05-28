import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            },
            colors: {
                brand: {
                    50:  '#EEF4FF',
                    100: '#D9E8FF',
                    200: '#B3CFFF',
                    300: '#7AAFF5',
                    400: '#4B8FEF',
                    500: '#2563EB',
                    600: '#1D4ED8',
                    700: '#1E40AF',
                    800: '#1E3A8A',
                    900: '#1E3074',
                },
                gray: {
                    50:  '#EEF4FF',
                    100: '#D4E0F5',
                    200: '#9BB5D5',
                    300: '#7898BB',
                    400: '#557AA0',
                    500: '#3A5E80',
                    600: '#214263',
                    700: '#152E4F',
                    800: '#0E1F35',
                    900: '#071428',
                    950: '#040D1A',
                },
                'bkash-pink': '#E2136E',
            },
            boxShadow: {
                'brand-glow':    '0 0 24px rgba(37,99,235,0.45)',
                'brand-glow-lg': '0 0 48px rgba(37,99,235,0.35)',
                'card':          '0 4px 24px rgba(0,0,0,0.4)',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%':      { transform: 'translateY(-12px)' },
                },
                'pulse-slow': {
                    '0%, 100%': { opacity: '0.6' },
                    '50%':      { opacity: '1' },
                },
            },
            animation: {
                float:        'float 3s ease-in-out infinite',
                'pulse-slow': 'pulse-slow 3s ease-in-out infinite',
            },
        },
    },
};
