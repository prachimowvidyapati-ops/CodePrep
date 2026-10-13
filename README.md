# SmartPick — API-based discovery website

## Requirements
PHP 8.1+ with cURL and mbstring extensions, Apache or PHP built-in server; MySQL optional for search logs.

## Run locally
1. Put the `smartpick` folder inside XAMPP's `htdocs` folder.
2. Start Apache. Open `http://localhost/smartpick/`.
3. Book search should work when outbound internet is available. It fetches Google Books live, so no large local catalogue is needed.
4. For movies, obtain your own TMDB API key from https://www.themoviedb.org/settings/api and enter it in `api/config.php`. Respect TMDB attribution and license conditions.
5. Optionally start MySQL and import `sql/database.sql` in phpMyAdmin. Change MySQL credentials in `api/config.php`. Search log recording is optional and silently skipped if no database is configured.

## Limitations
- No Google-wide scraping and no invented customer reviews. Google Books metadata may include an average rating and count, but not always individual reviews. The `Read reviews` link opens the provider page.
- TMDB's aggregate ratings are shown; the review link opens TMDB's review page.
- Matches are search results, not objectively verified 'best' rankings. Add ranking heuristics only after testing relevance and data coverage.
- For launch, add rate limiting, caching, privacy policy, error logging, stronger monitoring, and secure environment-based secrets. Do not use XAMPP's default DB credentials on production.
- Product categories beyond books/movies require separate licensed APIs and may charge usage fees.
- TMDB requires attribution and special licensing for commercial projects. See https://developer.themoviedb.org/docs/faq
