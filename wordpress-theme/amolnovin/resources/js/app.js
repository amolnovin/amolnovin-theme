/**
 * Amol Novin — front-end entry.
 * The design system (CSS) and behaviour (main.js) are the unchanged files of the static HTML template.
 */

// register every image/font in the Vite manifest so Blade can resolve them with Vite::asset()
import.meta.glob(['../images/**', '../fonts/**']);

import './main.js';
