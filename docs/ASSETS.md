# Asset sources

Folkscript's MIT license covers the project source. Third-party materials retain the licenses below; the software license does not relicense authors' stories or third-party assets.

## Brand

The Folkscript identity combines an authored geometric SVG symbol with an outlined lowercase Lexend wordmark at weight 600. The symbol depicts two open book pages; the gold right page forms a speech-bubble tail. Lettering was outlined from the bundled Lexend font, distributed under the [SIL Open Font License](../public/fonts/lexend-LICENSE.txt).

Transparent light, dark, and monochrome lockups, standalone marks, and the navy-backed app icon live in `public/images`. The favicon, device icons, and default social image use the same identity. PNG device icons, `public/og-default.png`, and `docs/brand/logo-preview.png` were rasterized from their vector sources, without image generation. See [Brand assets and usage](brand/README.md) for the file inventory and sizing rules. The previous supplied identity is preserved in Git history.

No affiliation with external AI services is implied by displaying their marks.

## Fonts

- **Lexend Variable:** self-hosted WOFF2 files from `@fontsource-variable/lexend`, with normal-style Latin, Latin Extended, and Vietnamese subsets covering weights 100–900. [Bundled SIL Open Font License](../public/fonts/lexend-LICENSE.txt).

The exact package version is pinned in `package-lock.json`. Lexend supplies page text, headings, controls, and the outlined SVG wordmark; code uses the platform monospace stack. Lexend has no true italic face, so browsers may synthesize oblique emphasis. Synthetic weight is disabled. Retired font assets and their notices remain in earlier Git history.

## Icons and service marks

Interface icons come from the MIT-licensed [Lucide](https://github.com/lucide-icons/lucide) package. AI-reader service SVGs come from Lobe Icons; their [source/version inventory](../public/images/ai-readers/README.md) and [MIT notice](../public/images/ai-readers/LICENSE) are bundled. Service names and marks belong to their respective owners.

## Editorial photography

The sample photographs below were sourced from Unsplash and remain under the [Unsplash License](https://unsplash.com/license), not MIT. That license allows their use in this application; it does not permit selling the unmodified images or compiling them into a competing image service.


- story-attention.jpg: https://images.unsplash.com/photo-1470770841072-f978cf4d019e
- story-city.jpg: https://images.unsplash.com/photo-1519501025264-65ba15a82390
- story-design.jpg: https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85
- story-nature.jpg: https://images.unsplash.com/photo-1441974231531-c6227db76b6e
- story-studio.jpg: https://images.unsplash.com/photo-1497366754035-f200968a6e72
- story-book.jpg: https://images.unsplash.com/photo-1507842217343-583bb7270b66

Photographs accompany original demonstration stories; people and stories are sample editorial data, not platform endorsements.

`apple-touch-icon.png`, `icon-192.png`, and `icon-512.png` are direct rasterizations of `public/images/folkscript-icon-mark.svg` for device compatibility.
