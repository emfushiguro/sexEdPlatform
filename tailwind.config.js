import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        'bg-orange-500',
        'bg-orange-600',
        'hover:bg-orange-600',
        // Dynamic sidebar/content margin classes (used via Alpine :class binding)
        'xl:ml-[290px]',
        'xl:ml-[90px]',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    25:  '#f2f7ff',
                    50:  '#ecf3ff',
                    100: '#ddeaff',
                    200: '#c2d6ff',
                    300: '#9db8ff',
                    400: '#7592ff',
                    500: '#465fff',
                    600: '#3450f5',
                    700: '#2b3fe0',
                    800: '#2636b5',
                    900: '#25348f',
                    950: '#161e54',
                },
                success: {
                    50:  '#ecfdf3',
                    100: '#d1fadf',
                    500: '#12b76a',
                    600: '#039855',
                    700: '#027a48',
                },
                error: {
                    50:  '#fef3f2',
                    100: '#fee4e2',
                    500: '#f04438',
                    600: '#d92d20',
                    700: '#b42318',
                },
                warning: {
                    50:  '#fffaeb',
                    100: '#fef0c7',
                    500: '#f79009',
                    600: '#dc6803',
                    700: '#b54708',
                },
            },
            boxShadow: {
                'theme-xs': '0px 1px 2px 0px rgba(16,24,40,0.05)',
                'theme-sm': '0px 1px 3px 0px rgba(16,24,40,0.1),0px 1px 2px 0px rgba(16,24,40,0.06)',
                'theme-md': '0px 4px 8px -2px rgba(16,24,40,0.1),0px 2px 4px -2px rgba(16,24,40,0.06)',
                'theme-lg': '0px 12px 16px -4px rgba(16,24,40,0.08),0px 4px 6px -2px rgba(16,24,40,0.03)',
                'focus-ring': '0px 0px 0px 4px rgba(70,95,255,0.12)',
            },
            zIndex: {
                1: '1', 9: '9', 99: '99', 999: '999',
                9999: '9999', 99999: '99999', 999999: '999999',
            },
        },
    },

    plugins: [forms],
};
