# Interface localization

Folkscript ships an English interface. The Blade controls for navigation, authentication, account and platform settings, administration, collections, discovery, and publishing use Laravel translation helpers. No additional language or automatic content translation is enabled.

`lang/en.json` is the English source catalog for interface copy. `lang/en/ui.php` holds plural forms and fixed status and role labels. Use `__('Source text')` for prose and `trans_choice('ui.story_count', $count)` for counts. Keep complete phrases together and pass variable values as named placeholders:

```blade
{{ __('Edit :title', ['title' => $post->title]) }}
{{ trans_choice('ui.search_story_count', $posts->total(), ['query' => $query]) }}
```

Blade escapes translated output. Do not render translations as raw HTML or concatenate unescaped translations into JavaScript. Use `Illuminate\Support\Js::from()` or Blade's `@js` when passing translations to Alpine. User-written titles, descriptions, stories, taxonomy names, comments, and stored administrative references are content; do not pass them through `__()`.

To add a language, create `lang/<locale>.json` and `lang/<locale>/ui.php` from the English catalogs, then translate their values while preserving keys, placeholders, and plural interval syntax. Set `APP_LOCALE` to that locale and keep `APP_FALLBACK_LOCALE=en`. Clear and rebuild configuration and views during deployment. The document language follows the configured locale. There is no per-user language picker yet.

This is an interface foundation, not a claim of a fully translated product. JavaScript editor prompts, server-side notifications and flash messages, legal/editorial pages, pagination and framework validation messages still need a language pass when introducing another locale. Date and number formatting, right-to-left layout, text expansion, emails, and accessibility announcements should be checked with the actual target language. Keep editorial content in its original language unless the author supplies a translation.

After editing language files or translated templates, run `php artisan view:cache` to compile Blade. Use the project's required `rtk` prefix when running shell commands through an agent.
