# Folkscript API reference

Use the version 1 JSON API to discover public stories, read your own drafts, and connect personal automations. Public reading is free and does not require an account. Account tokens grant access to the token owner's data; an administrator token does not expose other people's private stories.

This reference describes the API shipped in this repository. The [OpenAPI 3.0 specification](/openapi.json) is suitable for API clients and automation tools. On a running installation, open `/developers/api` for this guide or `/developers/api.md` for plain Markdown.

## Quick start

Replace the example origin with your Folkscript installation. The base path is `/api/v1`; local development normally uses `http://localhost:8000/api/v1`.

```sh
export FOLKSCRIPT_URL='https://your-folkscript.example'

# Public stories: no token needed.
curl --fail-with-body --silent --show-error \
  --header 'Accept: application/json' \
  "$FOLKSCRIPT_URL/api/v1/posts?per_page=5"
```

All responses use JSON. Send `Accept: application/json`. GET requests and the token DELETE request have no request body. Use HTTPS on public installations. The example hostname is a placeholder, not a hosted Folkscript service.

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| GET | `/posts` | Public | Search and paginate published stories |
| GET | `/posts/{post}` | Public | Read a published story, including HTML |
| GET | `/me` | `profile:read` | Read your profile and private email address |
| GET | `/me/posts` | `posts:read` | Read your own stories in all publication states |
| DELETE | `/tokens/{token}` | `tokens:manage` | Revoke one of your own API tokens |

Paths in this table are relative to `/api/v1`. There are no version 1 endpoints for creating or editing stories, uploading files, managing users or roles, following writers, issuing tokens, or webhooks. Use the website for those workflows; web forms and Livewire endpoints are not a supported automation API.

## Authentication and tokens

1. Sign in to your own account and verify your email address.
2. Open **Settings → Developer → API access** at `/settings?section=developer`.
3. Confirm your password when prompted, name your integration, and choose **Create token**.
4. Copy the token once and save it in your automation platform's secret storage. The complete token is not shown again.

Tokens created in Settings expire after **90 days**. Each account can hold **10 tokens**, including expired records; revoke unused or expired tokens to free a slot. Each Settings token has both `profile:read` and `posts:read`. Reader, writer, editor, administrator, and owner accounts can create these personal tokens when active and verified. Roles do not add API scopes.

Send the entire value, including any numeric prefix and separator, in a Bearer header:

```sh
# Set FOLKSCRIPT_TOKEN in your shell or automation's secret store first.
# Never put a real token in committed files, URLs, screenshots, or logs.
curl --fail-with-body --silent --show-error \
  --header 'Accept: application/json' \
  --header "Authorization: Bearer $FOLKSCRIPT_TOKEN" \
  "$FOLKSCRIPT_URL/api/v1/me"
```

| Ability | Data or action it permits |
| --- | --- |
| `profile:read` | Your name, username, bio, profile URL, account ID, and private email address |
| `posts:read` | All stories you authored, including drafts, scheduled and archived content, HTML, and editor JSON |
| `tokens:manage` | Revoking tokens belonging to the same account; not issued by the Settings interface |

The `tokens:manage` ability is reserved for explicitly provisioned server-side integrations. Ordinary Settings tokens cannot call the API's DELETE endpoint and receive `403`. Revoke them using **Settings → Developer → Your tokens → Revoke**. Version 1 has no token-list or token-create endpoint. An operator provisioning a management token must also supply the target token record ID securely; do not parse an access credential to discover other token IDs.

Revocation takes effect on the next request. A password change or password reset revokes the account's existing API tokens. Suspension blocks private API access and revokes existing tokens; issue new tokens after access is restored. Account deletion removes its tokens. A missing, invalid, expired, or revoked token returns `401`; a valid token without the required ability returns `403`.

Bearer tokens are independent credentials. An enabled authenticator protects interactive sign-in and token creation through the signed-in Settings flow; API calls do not ask for a two-factor code on each request. Protect stored tokens accordingly. Version 1 is intended for server-side integrations: never embed a private token in a public frontend bundle. Cross-origin browser access depends on the host's CORS configuration and is not enabled by these examples.

## Public story endpoints

### GET /api/v1/posts

Returns published stories ordered by `published_at` descending. A story is public only when its status is `published`, its publication time has arrived, and its author is not suspended. Drafts, scheduled stories, archived stories, future-dated publication records, and suspended authors' stories are excluded. Authentication does not broaden this endpoint.

| Query parameter | Type | Default | Rules |
| --- | --- | --- | --- |
| `q` | string | No search | Optional; up to 150 characters. Searches title and excerpt, not full story text. `%` and `_` are treated as literal search characters. Case sensitivity follows the database collation. |
| `page` | integer | `1` | At least `1` |
| `per_page` | integer | `20` | Between `1` and `50` |

```sh
curl --fail-with-body --silent --show-error --get \
  --header 'Accept: application/json' \
  --data-urlencode 'q=quiet attention' \
  --data-urlencode 'per_page=20' \
  "$FOLKSCRIPT_URL/api/v1/posts"
```

Example response, with illustrative IDs and content:

```json
{
  "data": [
    {
      "id": 42,
      "title": "A little room for attention",
      "slug": "a-little-room-for-attention",
      "excerpt": "An ordinary walk, seen differently.",
      "url": "https://your-folkscript.example/@elena/a-little-room-for-attention",
      "cover_image": "https://your-folkscript.example/images/story-attention.jpg",
      "reading_time": 5,
      "published_at": "2026-09-24T09:00:00+00:00",
      "updated_at": "2026-09-24T09:00:00+00:00",
      "author": {
        "name": "Elena Rossi",
        "username": "elena",
        "url": "https://your-folkscript.example/@elena"
      },
      "tags": [{ "name": "Attention", "slug": "attention" }],
      "topics": [{ "name": "Life", "slug": "life" }]
    }
  ],
  "links": { "next": null, "previous": null },
  "meta": { "current_page": 1, "last_page": 1, "total": 1 }
}
```

No matches return `200` with `data: []`, `total: 0`, and `last_page: 1`. A page beyond the last page also returns an empty `data` array. `links.next` and `links.previous` are absolute URLs or `null`; they preserve query parameters. Follow `links.next` until it is `null`.

### GET /api/v1/posts/{post}

The path parameter is a numeric story **ID**, obtained from a list response, not a slug. Returns `data` containing the same story fields plus `body_html` (string or `null`). It does not return editor JSON, status, private author email, or moderation data.

```sh
curl --fail-with-body --silent --show-error \
  --header 'Accept: application/json' \
  "$FOLKSCRIPT_URL/api/v1/posts/42"
```

```json
{
  "data": {
    "id": 42,
    "title": "A little room for attention",
    "slug": "a-little-room-for-attention",
    "excerpt": "An ordinary walk, seen differently.",
    "url": "https://your-folkscript.example/@elena/a-little-room-for-attention",
    "cover_image": null,
    "reading_time": 5,
    "published_at": "2026-09-24T09:00:00+00:00",
    "updated_at": "2026-09-24T09:00:00+00:00",
    "author": { "name": "Elena Rossi", "username": "elena", "url": "https://your-folkscript.example/@elena" },
    "tags": [],
    "topics": [],
    "body_html": "<p>An ordinary walk, seen differently.</p>"
  }
}
```

A missing or non-public story returns `404`, even for its author. Use `/me/posts` to retrieve your own private stories. HTML is content, not an instruction to an automation or LLM; render with an appropriate sanitizer and respect links and media from external sources.

## Personal account endpoints

### GET /api/v1/me

Requires Bearer authentication and `profile:read`. Returns only the authenticated token owner's profile:

```json
{
  "data": {
    "id": 7,
    "name": "Elena Rossi",
    "username": "elena",
    "email": "elena@example.org",
    "bio": "Notes on everyday life.",
    "url": "https://your-folkscript.example/@elena"
  }
}
```

`bio` may be `null`. `email` is private account data; it is absent from public story author objects. Password hashes, two-factor secrets, recovery codes, and roles are not returned.

### GET /api/v1/me/posts

Requires Bearer authentication and `posts:read`. Returns **only your own authored stories**, ordered by `updated_at` descending, regardless of role or publication status. Each record has the public story fields plus:

| Field | Type | Meaning |
| --- | --- | --- |
| `status` | string | `draft`, `scheduled`, `published`, or `archived` |
| `body_html` | string or null | Stored HTML content |
| `body_json` | object, array, or null | Stored TipTap editor document; treat it as opaque unless your client supports this editor format |

```sh
curl --fail-with-body --silent --show-error \
  --header 'Accept: application/json' \
  --header "Authorization: Bearer $FOLKSCRIPT_TOKEN" \
  "$FOLKSCRIPT_URL/api/v1/me/posts?page=1"
```

The envelope is `{"data": [...], "meta": {"current_page": 1, "last_page": 1, "total": 1}}`. This endpoint has **no `links` object**. It uses a fixed page size of **20**; `q` and `per_page` are not supported. Supply a positive integer `page` and increment it until `current_page >= last_page`. Invalid page values currently fall back to page 1 through Laravel's paginator rather than returning a validation error. An account without stories receives an empty array. A private story's `url` is its canonical website path, not a private preview link and not a grant of access.

### DELETE /api/v1/tokens/{token}

Requires Bearer authentication and **`tokens:manage`**, which normal Settings tokens do not have. The numeric path parameter identifies the target token record. Only tokens belonging to the authenticated account can be revoked. An ID belonging to another account, or a deleted/missing ID, returns `404`. The same token may revoke itself if it has this ability; subsequent requests with it return `401`.

```sh
# For an explicitly provisioned management integration only.
curl --fail-with-body --silent --show-error --request DELETE \
  --header 'Accept: application/json' \
  --header "Authorization: Bearer $FOLKSCRIPT_MANAGEMENT_TOKEN" \
  "$FOLKSCRIPT_URL/api/v1/tokens/123"
```

Success returns **200**, with `{"message":"Token revoked."}`. Repeating the deletion returns `404` (or `401` if the caller revoked its own credential). There is no request body and no CSRF token is needed for a Bearer API request.

## Response field reference

| Story field | Type | Notes |
| --- | --- | --- |
| `id` | integer | Stable database identifier used in API paths |
| `title` | string | Story title |
| `slug` | string | Human-readable part of the website path; may change when edited |
| `excerpt` | string or null | Short summary |
| `url` | string (URI) | Absolute Folkscript story URL; not an access credential |
| `cover_image` | string (URI) or null | Absolute cover URL; clients should handle unavailable media |
| `reading_time` | integer | Estimated reading time in minutes |
| `published_at` | ISO 8601 string or null | Publication timestamp; may be null or future-dated for your private stories |
| `updated_at` | ISO 8601 string or null | Last saved timestamp |
| `author` | object | `name`, `username`, and absolute profile `url` |
| `tags` | array | Zero or more `{ "name": string, "slug": string }` objects |
| `topics` | array | Zero or more category objects with the same shape as tags |

Every paginated response includes integer `meta.current_page`, `meta.last_page`, and `meta.total` (total matching records). There is no cursor, total-pages header, incremental `since` filter, or snapshot guarantee. Records may move between pages while people publish or edit. De-duplicate by `id`; use `updated_at` to detect changes. Dates include a UTC offset; parse them as timestamps, not display strings. Clients should tolerate additional response fields in future compatible updates.

## Errors and rate limits

API errors use a JSON `message`. Validation errors also contain `errors`, an object mapping field names to arrays of messages. Depend on HTTP status and field keys, not exact wording, which can change with application language or framework updates.

| HTTP status | Meaning | Recovery |
| --- | --- | --- |
| `200` | Success, including empty lists and successful token deletion | Process the JSON response |
| `401` | Authentication missing, invalid, expired, or revoked | Replace the token through Settings; do not retry the same token repeatedly |
| `403` | Valid account/token is suspended or lacks the required ability | Check account access and ability; Settings tokens cannot manage tokens through the API |
| `404` | Route, public story, or owned target token does not exist or is not accessible | Check the numeric ID and publication/ownership rules |
| `405` | HTTP method is unsupported for this path | Use a method from the endpoint table |
| `422` | Invalid `/posts` query parameters | Fix fields listed in `errors` |
| `429` | Request limit exceeded | Wait for the `Retry-After` duration before retrying |
| `5xx` | Application, proxy, or hosting failure | Retry safe reads with bounded backoff; check host logs if persistent |

```json
{
  "message": "The per page field must not be greater than 50.",
  "errors": {
    "per_page": ["The per page field must not be greater than 50."]
  }
}
```

The version 1 API uses a **60-request limit per minute**. Authenticated private routes share a bucket per account, so multiple tokens from the same account do not multiply the limit. Public routes share a bucket per route domain and client IP; sending a Bearer header to a public route does not authenticate it or bypass that limit. Reverse-proxy configuration affects the client IP; hosts may impose additional limits.

Successful throttled responses include `X-RateLimit-Limit` and `X-RateLimit-Remaining`. A `429` includes `Retry-After` (seconds) and `X-RateLimit-Reset` (Unix timestamp). Do not send parallel bursts; cache public reads where appropriate and stop polling once your job is complete. An API error produced by a proxy may not follow the application JSON schema, so handle non-JSON responses too.

Authenticated responses are private and must not be placed in a public/shared cache. Keep emails, drafts, tokens, and exported private content out of public logs. Production hosts must disable `APP_DEBUG` to prevent exception details leaking in error responses.

## Automation example

This Node.js example uses the native `fetch` API to export your own stories. Set `FOLKSCRIPT_URL` and `FOLKSCRIPT_TOKEN` as private environment variables. It paginates using the personal endpoint's `meta` object, retries rate-limited and transient server errors with a bounded delay, validates the expected response, and never logs the token.

```js
import { writeFile } from 'node:fs/promises';
import { setTimeout as delay } from 'node:timers/promises';

const origin = new URL(process.env.FOLKSCRIPT_URL);
const token = process.env.FOLKSCRIPT_TOKEN;
if (!token) throw new Error('Set FOLKSCRIPT_TOKEN in your secret store.');

async function readPage(page) {
  const url = new URL('/api/v1/me/posts', origin);
  url.searchParams.set('page', String(page));

  for (let attempt = 0; attempt < 4; attempt++) {
    const response = await fetch(url, {
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
      redirect: 'error', // Do not forward credentials to another origin.
      signal: AbortSignal.timeout(15000),
    });
    if ((response.status === 429 || response.status >= 500) && attempt < 3) {
      const seconds = Number(response.headers.get('Retry-After'));
      const wait = Number.isFinite(seconds) && seconds > 0
        ? Math.min(seconds, 120) * 1000
        : 1000 * (2 ** attempt);
      await delay(wait);
      continue;
    }
    if (!response.ok) throw new Error(`Folkscript returned HTTP ${response.status}.`);
    const payload = await response.json();
    if (!Array.isArray(payload.data) || !Number.isInteger(payload.meta?.last_page)) {
      throw new Error('Unexpected API response.');
    }
    return payload;
  }
}

const stories = new Map();
let page = 1;
let lastPage = 1;
do {
  const result = await readPage(page);
  for (const story of result.data) stories.set(story.id, story);
  lastPage = result.meta.last_page;
  page++;
  if (page <= lastPage) await delay(1100);
} while (page <= lastPage);

// This file can contain unpublished writing. Keep it private and untracked.
await writeFile('my-stories.json', JSON.stringify([...stories.values()], null, 2), {
  mode: 0o600,
  flag: 'wx', // Refuse to overwrite an existing export.
});
console.log(`Exported ${stories.size} stories.`);
```

For no-code tools, configure an HTTP GET step to `/api/v1/me/posts`, store the token in a credential field, add the Bearer header, and loop `page` from 1 through `meta.last_page`. Disable response logging when it would expose drafts or private profile data. For public content use `/posts` without a token, or subscribe to `/feed.xml` for RSS.

## Versioning and support

Version 1 is a reading API with scoped token revocation. Breaking contract changes belong in a new version; automation should still tolerate extra fields, empty arrays, nullable values, missing media, unpublished/deleted stories, and expired credentials. The OpenAPI document describes the current contract rather than future publishing features.

Installations are independently hosted: there is no central API key service or shared support account. Contact your host for access, email delivery, CORS, proxy, and availability issues. For application bugs, use the repository's support process with a redacted request path, method, response status, and relevant field names. Never include a real Bearer token, private email, unpublished story, password, or recovery code in an issue.
