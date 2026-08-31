# SCC Web Design flagship acceptance criteria

The flagship homepage is not production-ready until every gate below has explicit evidence.

## 1. Visual acceptance
- Capture and review 375, 390, 430, 768, 1024, 1440 and 1920 px viewports.
- No horizontal overflow.
- No clipped display type, hidden controls or accidental overlap.
- Mobile is art-directed independently rather than treated as compressed desktop.
- Hero communicates web design in the first viewport.
- Portfolio proof shows actual SCC websites, not generic stock imagery.

## 2. Interaction acceptance
- Navigation works with keyboard and touch.
- Interactive project canvases load progressively and never block core content.
- Motion respects `prefers-reduced-motion`.
- All primary calls to action remain usable without JavaScript.
- External project previews degrade gracefully if framing is blocked.

## 3. Accessibility acceptance
- One H1 and logical heading hierarchy.
- Keyboard-visible focus states.
- Sufficient text/background contrast.
- Meaningful iframe titles and link text.
- Skip link works.
- No information is available only through animation, colour or hover.

## 4. Performance acceptance
- Record reproducible Lighthouse mobile and desktop runs against the review URL.
- No fabricated performance scores on the public site.
- Lazy-load non-critical live canvases.
- No unnecessary third-party libraries or framework runtime.
- Review total transfer size and main-thread impact before release.

## 5. SEO and publishing acceptance
- Unique title and description.
- Canonical, Open Graph and structured-data review before production.
- Sitemap and robots remain valid.
- Existing consent-aware analytics remains functional.
- Search Console and Bing foundations remain intact.

## 6. Provenance acceptance
- Each project is labelled `live`, `in progress` or `pre-launch` accurately.
- Portfolio links resolve to the named project.
- Before/after claims are only used where both states are retained and traceable.
- Metrics shown publicly must carry a date and reproducible source.

## 7. Release acceptance
- Review evidence attached to the release/PR.
- Production branch is not updated merely because code compiles.
- Final visual review must include a real Android-width capture and a desktop capture.
- Rollback remains possible through Git history and Hostinger backup.

## Design rule
**Show exceptional websites first. Explain SCC second. Reveal the engineering third.**
