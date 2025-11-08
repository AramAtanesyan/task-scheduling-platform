const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

// Compile page-specific entry files
mix.js('resources/js/pages/login.js', 'public/js/pages')
    .js('resources/js/pages/dashboard.js', 'public/js/pages')
    .js('resources/js/pages/statuses.js', 'public/js/pages')
    .postCss('resources/css/app.css', 'public/css', [
        //
    ])
    .webpackConfig({
        resolve: {
            alias: {
                '@': __dirname + '/resources/js',
                // Use the full build of Vue with template compiler (CommonJS version)
                'vue$': 'vue/dist/vue.common.js'
            },
            extensions: ['.js', '.ts', '.json']
        },
        module: {
            rules: [
                {
                    test: /\.tsx?$/,
                    loader: 'ts-loader',
                    options: {
                        transpileOnly: true
                    },
                    exclude: /node_modules/
                }
            ]
        }
    })
    .sourceMaps();
