# Story collections

Authors manage collections at `/series`. Each collection has a stable slug, an introduction, and an ordered list of the collection owner's stories. Verified authors create collections; their owner and verified editors/administrators can manage them. Laravel discovers `SeriesPolicy` automatically.

Routes are declared in `routes/series.php`, loaded before `routes/publishing.php`. Public collections use `/@{username}/series/{slug}` and emit CollectionPage/ItemList schema through the shared SEO component. Only posts belonging to the collection owner that pass `Post::published()` appear publicly; drafts, scheduled posts, archived posts, and suspended authors' stories remain hidden. Public lists are paginated. Premium articles retain their normal paywall.

The manager supports create, rename, introduction edits, adding/removing stories, numeric reading order, and deletion. Submitted post IDs must belong to the collection owner. Reordering requires the current exact set of attached IDs, rejecting stale or tampered submissions. Deleting a collection preserves all posts and redirects its former public URL to the author profile. Username changes preserve collection URLs through redirects.

Navigation integration: add a **Collections** link to `/series` in the writing dashboard and account menu. Public author profiles may link their published collections using `route('series.show', ['username' => $author->username, 'slug' => $series->slug])`. Management paths `series*` should use noindex metadata.
