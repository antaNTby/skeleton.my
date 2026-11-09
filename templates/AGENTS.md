# AGENTS — Views (Smarty)

Read root [AGENTS.md](../../AGENTS.md) first. Security: [SECURITY.md](../../SECURITY.md).

## Purpose

HTML (and fragments) only. **Smarty** under `templates/`. No PHP view files for new UI.

## Nuances (easy to get wrong)

1. **Engine:** Smarty is registered in `services.php`. Controllers call `$app->renderSmarty('name', $data)` which maps to Smarty (`.tpl.html` optional in the name).
2. **Layout:** extend `layout.tpl.html` for full pages:

   ```.smarty
   {extends 'layout.tpl.html'}
   {block name="title"}…{/block}
   {block name="body"}…{/block}
   ```

3. **Globals available:** `csp_nonce`, `app_env`, `base_url` (from services). Prefer these over hardcoding hosts.
4. **Escaping:** Smarty escapes by default. **Do not** use `|raw` unless content is trusted and you accept XSS risk (see SECURITY.md).
5. **No business logic:** no DB calls, no `$_ENV`, no Flight facades in templates. Pass data from the controller.
6. **Links:** `{*base_url}posts` — Smarty global is always normalized with a trailing slash (`Config::baseUrl()`), so `/` or `/myapp` both join correctly.
7. **CSP:** if you add inline `<script>` or `<style>`, attach `nonce="{$csp_nonce}"` and keep `SecurityHeadersMiddleware` in mind.
8. **Cache:** production uses `app/cache/smarty`; debug disables cache. Do not commit compiled cache artifacts.
9. **Partials:** use `{include}` / `{capture}`; keep names clear (`posts/_row.tpl.html`).
10. **JSON APIs** do not use Smarty — controllers return `$app->json(...)`.

## Do not

- Add Latte, Blade, or raw PHP templates as a second system.
- Put secrets or env dumps in templates for “debugging” on shared environments.
- Disable autoescape globally.
