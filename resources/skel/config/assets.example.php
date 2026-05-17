<?php
/**
 * Global/Default Assets Configuration Example
 * 
 * This shows how to configure CSS and JavaScript files that are automatically
 * included in all view/layout renders.
 * 
 * Place this configuration in bootstrap/app.php after View::init()
 * 
 * Best practices:
 * 1. CSS Reset first (reset.css / normalize.css)
 * 2. Base typography styles
 * 3. Theme/utility CSS
 * 4. Common utility JavaScript
 * 5. Page-specific assets are registered via View::render() and override these defaults
 */

// ============================================================================
// MODULAR CSS LAYERING PATTERN
// ============================================================================
// This follows modern CSS architecture principles:
// - Layer 1: Browser resets (normalize browser defaults)
// - Layer 2: Typography (base fonts, sizes, line-height)
// - Layer 3: Theme (colors, spacing, components)
// - Layer 4: Utilities (helpers, responsive grid)
// - Page-specific: Override and extend via per-render assets

View::registerDefaults(
    // ========== GLOBAL CSS FILES ==========
    [
        // Layer 1: CSS Reset/Normalize
        'assets/css/reset.css',
        
        // Layer 2: Base Typography
        'assets/css/typography.css',
        
        // Layer 3: Site Theme
        'assets/css/theme.css',
        
        // Layer 4: Utilities
        'assets/css/utilities.css',
        
        // Layout CSS (header, footer, sidebar)
        'assets/css/layout.css',
    ],
    
    // ========== GLOBAL JAVASCRIPT FILES ==========
    [
        // Common utility functions (defer = true by default)
        'assets/js/common.js',
        
        // Site-wide interactions
        'assets/js/interactions.js',
        
        // Analytics (no defer, loads immediately)
        ['path' => 'assets/js/analytics.js', 'defer' => false],
        
        // Tracking pixel
        ['path' => 'assets/js/tracking.js', 'defer' => false],
    ]
);

// ============================================================================
// EXAMPLE: Per-Page Asset Registration (in controller)
// ============================================================================
// Page-specific assets are added via View::render()
// These are combined with global assets automatically

// Example in a controller action:
// View::render('dashboard', [
//     'title' => 'Dashboard',
//     'items' => $items
// ], 
// [
//     'assets/css/dashboard.css',  // Page-specific CSS (loaded after global)
// ],
// [
//     'assets/js/dashboard.js',    // Page-specific JS
//     ['path' => 'assets/js/charts.js', 'defer' => true]
// ]);

// Final HTML will render assets in this order:
// 1. Global CSS (reset → typography → theme → utilities → layout)
// 2. Per-render CSS (dashboard.css)
// 3. Global JS
// 4. Per-render JS
