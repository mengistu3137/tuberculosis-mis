/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
      "./index.php",
          "./login.php",
              "./partials/**/*.php",
                  "./modules/**/*.php",
                      "./assets/**/*.js",
                        ],
                          theme: {
                              extend: {
                                    colors: {

                                            primary: {
                                                      50: '#f0fdfa',
                                                      100: '#ccfbf1',

                                                      200: '#99f6e4',
                                                      300: '#5eead4',
                                                                                              400: '#2dd4bf',
                                                                                                        500: '#14b8a6',
                                                                                                                  600: '#0d9488',
                                                                                                                            700: '#0F766E',
                                                                                                                                      800: '#115e59',
                                                                                                                                                900: '#134e4a',
                                                                                                                                                          950: '#042f2e',
                                                                                                                                                                  },
                                                                                                        secondary: {
                                                                                                                                                                                    50: '#eff6ff',
                                                                                                                                        100: '#dbeafe',
                                                                                                                                                                                                        200: '#bfdbfe',
                                                                                                                                                                                                                  300: '#93c5fd',
                                                                                                                                                                                                                            400: '#60a5fa',
                                                                                                                                                                                                                                      500: '#3b82f6',
                                                                                                                                                                                                                                                600: '#2563EB',
                                                                                                                                                                                                                                                          700: '#1d4ed8',
                                                                                                                                                                                                                                                                    800: '#1e40af',
                                                                                                                                                                                                                                                                              900: '#1e3a8a',
                                                                                                                                                                                                                                                                                      },
                                                                                                                                                                                                                                                                                            },
                                                                                                                                                                                                                                                                                                  fontFamily: {
                                                                                                                                                                                                                                                                                                          sans: ['Sora', 'Inter', 'system-ui', 'sans-serif'],
                                                                                                                                                                                                                                                                                                                },
                                                                                                                                                                                                                                                                                                                    },
                                                                                                                                                                                                                                                                                                                      },
                                                                                                                                                                                                                                                                                                                        plugins: [],
                                                                                                                                                                                                                                                                                                                        } 