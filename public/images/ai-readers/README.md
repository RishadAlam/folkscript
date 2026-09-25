# AI reader service marks

These self-hosted SVG marks identify the external services in Folkscript’s reader menu. Their adjacent text labels remain the accessible names; render these images with empty `alt` text. The marks do not imply an affiliation or endorsement.

## Source

- Project: [Lobe Icons](https://github.com/lobehub/lobe-icons)
- Package: [`@lobehub/icons-static-svg` 1.95.1](https://www.npmjs.com/package/@lobehub/icons-static-svg/v/1.95.1)
- Upstream commit: [`49a2130df7bfa5eb1b088261bff20a37e2967789`](https://github.com/lobehub/lobe-icons/tree/49a2130df7bfa5eb1b088261bff20a37e2967789)
- Retrieved: September 25, 2026
- Package archive SHA-1: `7c6281bf457230b5cfc3b7c2cdd15d3b8d8ab479` (matches npm registry metadata)
- License: MIT, copyright © 2023 LobeHub; the exact upstream notice is retained in [LICENSE](LICENSE). Brand names and marks remain the property of their respective owners.

The selected SVG contents are copied unchanged; color-variant source names are shortened to the service names below. Every asset has a `0 0 24 24` viewBox and is rendered at a consistent size by CSS. OpenAI and Grok use `currentColor`, which resolves to black in an external SVG image; the menu inverts those two monochrome marks for dark surfaces. Other marks retain their original color fills and gradients. Google’s mark is reused for Google AI Mode and Google Search; OpenAI’s mark identifies ChatGPT.

| Local file | Upstream file | Bytes | Rendering |
| --- | --- | ---: | --- |
| `claude.svg` | [`claude-color.svg`](https://github.com/lobehub/lobe-icons/blob/49a2130df7bfa5eb1b088261bff20a37e2967789/packages/static-svg/icons/claude-color.svg) | 1,696 | Original brand colors |
| `openai.svg` | [`openai.svg`](https://github.com/lobehub/lobe-icons/blob/49a2130df7bfa5eb1b088261bff20a37e2967789/packages/static-svg/icons/openai.svg) | 1,687 | Monochrome; invert on dark surfaces |
| `perplexity.svg` | [`perplexity-color.svg`](https://github.com/lobehub/lobe-icons/blob/49a2130df7bfa5eb1b088261bff20a37e2967789/packages/static-svg/icons/perplexity-color.svg) | 603 | Original brand colors |
| `copilot.svg` | [`copilot-color.svg`](https://github.com/lobehub/lobe-icons/blob/49a2130df7bfa5eb1b088261bff20a37e2967789/packages/static-svg/icons/copilot-color.svg) | 3,569 | Original brand colors |
| `grok.svg` | [`grok.svg`](https://github.com/lobehub/lobe-icons/blob/49a2130df7bfa5eb1b088261bff20a37e2967789/packages/static-svg/icons/grok.svg) | 756 | Monochrome; invert on dark surfaces |
| `google.svg` | [`google-color.svg`](https://github.com/lobehub/lobe-icons/blob/49a2130df7bfa5eb1b088261bff20a37e2967789/packages/static-svg/icons/google-color.svg) | 920 | Original brand colors |

All six files were parsed as SVG and checked for scripts, event handlers, external references, embedded raster images, and foreign objects. None are present. Internal gradient references remain intact. There is no runtime icon package, third-party asset request, or additional JavaScript dependency.
