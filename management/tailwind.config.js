const colors = require('tailwindcss/colors');

module.exports = {
    mode: 'jit',
    purge: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    darkMode: 'class', // or 'media' or 'class'
    theme: {
        colors: {
            transparent: 'transparent',
            white: colors.white,
            black: colors.black,
            gray: colors.trueGray,
            red: colors.red,
            amber: colors.amber,
            amber: colors.amber,
            yellow: {
                light: '#fdef74',
                DEFAULT: '#ffe505',
                dark: '#bfab00',
            },
            green: colors.green,
            blue: colors.blue,
            cyan: colors.cyan,
            purple: colors.purple,
        },
        extend: {
            colors: {
                gray: {
                    350: 'rgba(185,185,185,var(--tw-text-opacity))',
                    // 800: 'rgba(38, 38, 38, var(--tw-bg-opacity))',
                    850: 'rgba(30, 30, 30, var(--tw-bg-opacity))',
                    // 900: 'rgba(23, 23, 23, var(--tw-bg-opacity))',
                    950: 'rgba(17, 17, 17, var(--tw-bg-opacity))',
                },
            },
            fontSize: {
                '2xs': '.6rem',
            },
            borderRadius: {
                '1/4': '25%',
                '1/2': '50%',
            },
            boxShadow: {
                'sm-dark': '0 1px 2px 0 rgba(0, 0, 0, 0.5)',
                // First component is a large, faint, blurry shadow as from direct light
                // Second component is tighter and darker, like the shadow underneath an object
                'dark': '0 1px 3px 0 rgba(0, 0, 0, 0.2), 0 1px 3px 0 rgba(0, 0, 0, 0.4)',
                'md-dark': '1px 4px 9px -1px rgba(0, 0, 0, 0.35), 0 1px 6px -1px rgba(0, 0, 0, 0.3)',
                'lg-dark': '1px 4px 17px 0px rgba(0, 0, 0, 0.38), 0 1px 11px -2px rgba(0, 0, 0, 0.3)',
                'xl-dark': '2px 6px 34px -2px rgba(0, 0, 0, 0.35), 0 1px 13px -1px rgba(0, 0, 0, 0.3)',
                '2xl-dark': '3px 7px 56px -6px rgba(0, 0, 0, 0.55)',
                'inset-dark': 'inset 0px 1px 4px 0 rgba(0, 0, 0, 0.35)',
            },
            dropShadow: {
                // Create some very bold, visible shadows. The tw default ones are almost invisible.
                'sm-dark': '0px 1px 1px rgba(0,0,0,0.4)',
                'dark':    '0px 1px 2px rgba(0,0,0,0.5)',
                'md-dark': '1px 2px 4px rgba(0,0,0,0.48)',
                'lg-dark': '1px 4px 8px rgba(0,0,0,0.45)',
                'xl-dark': '1px 5px 13px rgba(0,0,0,0.4)',
                '2xl-dark':'2px 6px 25px rgba(0,0,0,0.4)',
            },
        },
    },
    variants: {
        extend: {
            // Unfortunately background opacity gets overridden by dark theme, so I need this for translucency.
            backgroundOpacity: ['dark'],
            backgroundColor: ['dark', 'checked', 'hover', 'active', 'focus', 'odd', 'even'],
            textColor: ['hover', 'checked', 'active', 'dark'],
            transform: ['hover', 'active', 'focus'],
            opacity: ['disabled'],
            borderColor: ['checked'],
            borderWidth: ['checked'],
        },
    },
    plugins: [
        require('@tailwindcss/forms')
    ],
}

