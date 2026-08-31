# SCC Web Design — 2026 Flagship Rebuild

This branch contains the first complete flagship homepage pass for SCC Web Design.

## Design direction

- Strong editorial typography and asymmetrical layouts rather than generic agency card grids.
- Motion used for hierarchy and feedback, with `prefers-reduced-motion` support.
- Real project status labels: live, pre-launch and in-progress are not conflated.
- Static-first, dependency-light implementation for performance and maintainability.
- Mobile navigation and touch-first behaviour retained.
- SCC assurance language integrated: Validation, Verification, Integrity and Provenance.

## New public experience

- Kinetic hero statement.
- Scrolling capabilities rail.
- Evidence/proof panel.
- Real transformation-led project storytelling.
- SCC Studio Lab section linking to the working Website Builder and project evidence.
- Assurance section covering accessibility, search, performance and auditable delivery.

## Technical scope

Only the public presentation layer is changed here. Existing forms, deployment workflow, legal pages, builder endpoint and underlying site machinery are deliberately preserved.

Files added/changed:

- `index.html`
- `flagship-2026.css`
- `flagship-2026.js`
- `REDESIGN_2026.md`

The older `studio-2026.css` / `studio-2026.js` remain in place as the base visual layer, with the new flagship files acting as a clean override and enhancement layer. This avoids destabilising other pages that currently rely on the shared styling.
