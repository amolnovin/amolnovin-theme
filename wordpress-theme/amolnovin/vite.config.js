import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import { wordpressPlugin } from '@roots/vite-plugin'

// laravel-vite-plugin needs an APP_URL; the value is irrelevant for production builds
if (!process.env.APP_URL) {
  process.env.APP_URL = 'http://example.test'
}

export default defineConfig({
  // Relative base: the built CSS references fonts/images relative to itself, so the theme keeps working
  // whatever the theme folder is called and even when WordPress lives in a sub-directory.
  base: './',
  build: {
    assetsInlineLimit: 0, // keep every image/font a separate, cacheable file (same as the static HTML template)
  },
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/editor.css',
        'resources/js/editor.js',
        'resources/css/admin.css',
        'resources/js/admin.js',
      ],
      refresh: true,
      assets: ['resources/images/**', 'resources/fonts/**'],
    }),

    // Externalises @wordpress/* imports of editor.js and emits editor.deps.json
    wordpressPlugin(),
  ],
  resolve: {
    alias: {
      '@scripts': '/resources/js',
      '@styles': '/resources/css',
      '@fonts': '/resources/fonts',
      '@images': '/resources/images',
    },
  },
})
