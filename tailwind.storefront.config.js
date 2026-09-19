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

            /*
             | Every colour below resolves to a CSS custom property declared in
             | resources/css/storefront.css. The variables hold bare RGB
             | channels so Tailwind's opacity modifiers (bg-surface-1/60) keep
             | working, which is what lets the whole palette live in one place
             | instead of being re-typed across the views.
             */
            colors: {
                surface: {
                    0: 'rgb(var(--surface-0) / <alpha-value>)',
                    1: 'rgb(var(--surface-1) / <alpha-value>)',
                    2: 'rgb(var(--surface-2) / <alpha-value>)',
                    3: 'rgb(var(--surface-3) / <alpha-value>)',
                },
                ink: {
                    hi:  'rgb(var(--text-hi) / <alpha-value>)',
                    mid: 'rgb(var(--text-mid) / <alpha-value>)',
                    low: 'rgb(var(--text-low) / <alpha-value>)',
                },
                accent: {
                    DEFAULT: 'rgb(var(--accent) / <alpha-value>)',
                    hover:   'rgb(var(--accent-hover) / <alpha-value>)',
                },
                success: 'rgb(var(--success) / <alpha-value>)',
                warning: 'rgb(var(--warning) / <alpha-value>)',
                danger:  'rgb(var(--danger) / <alpha-value>)',

                /* Payment and chat brand marks. Not part of the design system —
                   these are other companies' colours and may not be re-tinted. */
                'bkash-pink': '#E2136E',
                whatsapp:     '#25D366',
                messenger:    '#0099FF',
            },

            /* 30 / 22 / 17 / 15 / 13 / 11 — redesign spec section 2.2. Named
               rather than numbered so a size can never drift from its role. */
            fontSize: {
                display: ['30px', { lineHeight: '1.15', letterSpacing: '-0.02em' }],
                title:   ['22px', { lineHeight: '1.25', letterSpacing: '-0.015em' }],
                lede:    ['17px', { lineHeight: '1.45' }],
                body:    ['15px', { lineHeight: '1.6' }],
                caption: ['13px', { lineHeight: '1.5' }],
                meta:    ['11px', { lineHeight: '1.45', letterSpacing: '0.01em' }],
            },

            borderRadius: {
                card:    '10px',
                control: '8px',
                chip:    '6px',
            },

            /* Exactly two, both neutral black. No coloured glows: the old
               theme's glow was what made every surface read as a light source. */
            boxShadow: {
                card:  '0 1px 2px rgba(0,0,0,0.30)',
                hover: '0 8px 24px rgba(0,0,0,0.45)',
            },

            /* Section rhythm: 36px mobile, 56px desktop. */
            spacing: {
                section:    '36px',
                'section-lg': '56px',
            },

            maxWidth: {
                shell: '1280px',
            },
        },
    },
};
