## Sass Functions

Sass Functions allow us to enforce consistency, validation, and theme-awareness across all components.. Functions are found under the `/src/scss/partials` directory.

| Function | Description | Example |
|----------|-------------|---------|
| color($key, $map-id: 'pairings') | Returns a CSS variable reference for a color key and logical map id. Automatically resolves the active theme and validates the color exists. | <code>.button { color: color('white', 'global'); }</code> |

