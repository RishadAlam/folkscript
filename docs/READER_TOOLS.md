# Public reader tools

Updated September 25, 2026 using Impeccable Read/Operate guidance.

## Reader actions

- **Copy page** copies public Markdown with author credit, publication date, source links, and article structure. Clipboard errors retain selectable Markdown and a plain-text link; request failures provide retry guidance.
- **View as Markdown** opens a plain-text browser response. The `.md` endpoint serves `text/markdown` to clients by default.
- **Claude, ChatGPT, Perplexity, Copilot, Grok, and Google AI Mode** open native new-tab links with the same prompt: `Read from <absolute Markdown export URL> so I can ask questions about it.` The URL identifies the current story, author, collection, topic, or filtered listing. Listing query parameters remain intact.
- AI links are ready in server-rendered HTML. Opening the menu or selecting an AI provider does not fetch article contents, change the clipboard, or show a copied-prompt notification. Gemini, DeepSeek, the “Copy & paste instead” group, and all AI paste/recovery steps were removed at the user's request.
- **Google Search** remains separate and searches the current page title. AI Overviews are controlled by Google and cannot be forced by this link.

The deployed Markdown URL must be publicly reachable for an external assistant to read it. Localhost, private network addresses, authentication requirements, provider sign-in, and the provider's browsing support can prevent retrieval. The current flow deliberately sends only the URL prompt; it does not embed the article. No AI API key is required.

## Markdown reading format

Each export begins with a short quoted **Content index** block linking to the live `/llms.txt` index, followed by one page title and a quoted summary. Story metadata identifies the public source, the Markdown URL, the author, the ISO 8601 publication timestamp, and a separate canonical source when the author supplied one. The generic record-update timestamp is not presented as an editorial revision date because reading a story also updates that timestamp.

The full authored text follows, preserving headings, quotations, lists, code languages, tables, image descriptions, captions, and video links. A duplicate opening title is removed, and authored top-level sections nest beneath the export's title without changing code examples. Topic and further-reading links lead directly to other public Markdown exports. No generated synopsis or provider-specific instructions are added.

Discovery exports use `## Stories` or `## Writers` with `###` entries. Story summaries are explicitly identified as summaries, with links to the full text. The export's own URL and pagination preserve search, topic, sort, and page parameters while omitting the browser-only `view=plain` parameter. Copy page, View as Markdown, and AI URL prompts all use this same export.

Verification of this format: 5 focused PHP tests / 180 assertions passed in isolated SQLite, including public-access restrictions, heading and code preservation, absolute links, discovery-index limits, and filtered pagination. Browser inspection confirmed the live article, discovery export, and dynamic index. A native Chrome copy-and-paste into an unsent local field confirmed the complete 4,643-character article export with the new index and metadata; the field was cleared and nothing submitted. The automation clipboard-read API did not reflect the native clipboard consistently, so copy verification used the actual pasted text. PHP formatting and diff checks passed.

## Layout and access

The compact two-column picker retains its 20px provider logos and 44px targets. It anchors 8px from the split button, stays inside 22px viewport gutters, scrolls within the available space, and closes when its button leaves the viewport. Copy-page feedback lives inside the open menu or outside document flow when closed; AI actions have no launch message.

Article titles, covers, and prose share the same reading column. Reading menus appear on public stories, profiles, collections, topics, Explore, and Trending. Exports retain the current results page, filters, sort order, attribution, and navigation. Private stories, suspended authors, and private account data are excluded even for administrators. Exporting does not increment story views.

Copy page prepares Markdown in the background without touching the clipboard, retries on demand, times out after 15 seconds, and shares simultaneous requests. Prepared Markdown is written synchronously during the copy click; AI links do not depend on that request. It prefers `writeText`, with ClipboardItem as a fallback. Without JavaScript, View as Markdown remains available. Asset provenance and licenses are recorded in `public/images/ai-readers/README.md`.

## Verification of the simplified flow

- Browser inspection confirmed six native links with the exact URL-only prompt and no manual-provider elements or AI launch notice.
- A real Perplexity click opened the destination with that prompt and closed the Folkscript menu without a notification. Remote retrieval was not claimed: the tested export URL is on localhost.
- Desktop and 320px phone screenshots confirmed the shorter menu fits within viewport gutters without horizontal overflow.
- The focused Node suite passes 12 tests for Copy page success, clipboard recovery, request errors, deduplication, opening without another fetch, and menu positioning. The PHP reader suite passes 4 tests / 127 assertions, covering all six rendered URLs (including an encoded filtered-listing URL) and Markdown access/conversion.
- A real clipboard read verified the separate Copy page action returns raw Markdown after the change. Production build and Blade compilation passed. Automated tests use isolated SQLite, preserving local MySQL data.

## Historical checks of the retired full-content flow

These checks describe the previous implementation, which embedded full article text and offered manual paste tools. The user subsequently replaced that flow with URL-only prompts and removed manual-only providers. They are not verification of the current URL-based reading flow.

These historical checks used the public seeded article “The quiet art of paying attention.” No accounts were created or signed in.

After removing the Continue step, a single Grok click was rechecked: it opened the service with the entire prompt in its confirmation. The subsequent Google correction verified its native AI Mode query flow, then a single Folkscript click opened `https://www.google.com/search?udm=50&q=…` with the complete 4,481-character prompt and generated an attributed summary. Gemini's copy button was checked to create zero new tabs, copy the full prompt, and display its explicit Open action. Earlier Gemini paste testing confirmed that the clipboard contains the whole page; automatic consumer Gemini prompt transfer remains unsupported by this implementation.

| Assistant | Observed browser result |
| --- | --- |
| ChatGPT | Received the full article and generated an attributed summary. |
| Perplexity | Received the full article and generated a summary and analysis. |
| Grok | Displayed the full prompt in its “Send this message?” confirmation. Not submitted. |
| Gemini | Pasted the complete copied prompt into its composer, including instructions and the final article section. Not submitted. |
| Google AI Mode | A single Folkscript click delivered the entire article through the query URL and generated an attributed summary. No paste needed for the tested article. |
| Claude | Reached sign-in. Outgoing full-content query and clipboard fallback verified; behavior after sign-in remains unverified. |
| Copilot | Reached sign-in. Outgoing full-content query and clipboard fallback verified; behavior after sign-in remains unverified. |
| DeepSeek | Reached sign-in. Complete prompt prepared locally; paste after sign-in remains unverified. |

Prompt-query patterns are observed integrations, not a guaranteed cross-account contract. Sources: [Microsoft's analysis documenting the five provider URL patterns](https://www.microsoft.com/en-us/security/blog/2026/02/10/ai-recommendation-poisoning/) and [Mintlify contextual-menu documentation](https://www.mintlify.com/docs/ai/contextual-menu). Those observations do not guarantee that every service will retain its prompt query after sign-in.

Google AI Mode's `udm=50&q=…` pattern was verified from Google's native submitted-query URL and from the actual Folkscript button. [Google's AI Mode help](https://support.google.com/websearch/answer/16011537?hl=en) describes the experience. AI Overviews are a separate Search feature whose display Google controls; the Google Search link cannot force them.

No supported arbitrary-text consumer Gemini URL was verified. [Chromium's Gemini shortcut implementation](https://chromium.googlesource.com/chromium/src/+/4934ed06936964c9281a7af1cfc14a9e73639ea1/components/omnibox/browser/autocomplete_controller_unittest.cc) uses a custom `X-Omnibox-Gemini` navigation header, which a normal website link cannot attach. Google's [prompt gallery](https://gemini.google/explore/) uses registered prompt IDs rather than arbitrary article text. Do not substitute guessed query parameters or silently redirect the Gemini button to the different AI Studio product.

Provider entry points were checked against official sources: [ChatGPT](https://help.openai.com/en/articles/9125172-the-chatgpt-home-page), [Gemini](https://support.google.com/gemini/answer/13275745?hl=en), [Google AI Mode](https://support.google.com/websearch/answer/16011537?hl=en), [AI Overviews](https://support.google.com/websearch/answer/14901683?hl=en), [Perplexity](https://www.perplexity.ai/), [Copilot](https://copilot.microsoft.com/), [Grok](https://grok.com/), and [DeepSeek](https://chat.deepseek.com/).

## Public Markdown discovery index

`/llms.txt` is generated by the application, using named route `reader.index`. It begins with a Folkscript heading and a short project description, then supplies working absolute Markdown links to the paginated story archive, writers, trending stories, up to 24 categories with public stories, and the 12 latest published stories. Story entries include author, publication date, and a concise excerpt. The index clearly identifies the latest-story list as a selection and links to the complete paginated archive.

All entries use the existing published-content scope, excluding drafts, future or scheduled stories, archived stories, and suspended writers. Only public attribution and descriptions are included. The response is UTF-8 plain text with `nosniff` and `no-store, private`; it does not depend on authentication or expose private account fields. The former static `public/llms.txt` was removed so web servers cannot serve a stale file instead of the current public index.

The index preserves the distinction between free access and licensing: authors retain their writing rights, and the software's MIT license does not grant rights to every published story or third-party asset. RSS and sitemap links remain available as alternate public discovery formats. Markdown links become externally useful when the deployment has a publicly reachable base URL.
