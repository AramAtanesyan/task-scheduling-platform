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

mix.ts('resources/js/main.js', 'public/js')
    .vue({ version: 2 })
    .postCss('resources/css/app.css', 'public/css', [
        //
    ])
    .webpackConfig({
        resolve: {
            alias: {
                '@': __dirname + '/resources/js'
            },
            extensions: ['.js', '.ts', '.vue', '.json']
        },
        module: {
            rules: [
                {
                    test: /\.tsx?$/,
                    loader: 'ts-loader',
                    options: {
                        appendTsSuffixTo: [/\.vue$/],
                        transpileOnly: true
                    },
                    exclude: /node_modules/
                }
            ]
        }
    })
    .sourceMaps();
