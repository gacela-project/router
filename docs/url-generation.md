# Named routes and URL generation

Give a route a name and build its URL later, instead of hard-coding paths.

## Naming a route

`name()` chains off any route registration, like `middleware()`:

```php
$routes->get('/', HomeController::class)->name('home');
$routes->get('users/{id}', UserController::class, 'show')->name('user.show');
$routes->get('archive/{year?}', ArchiveController::class)->name('archive');

$routes->get('admin', AdminController::class)
    ->middleware('auth')
    ->name('admin.index');
```

Names are optional. An unnamed route works exactly as before, it just cannot be
looked up.

## Generating a URL

`UrlGenerator` is bound automatically, so a controller can type-hint it and the
container injects it:

```php
use Gacela\Router\UrlGenerator;

final class LinkController
{
    public function __construct(
        private UrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(): array
    {
        return [
            'user' => $this->urlGenerator->generate('user.show', ['id' => 7]), // /users/7
            'home' => $this->urlGenerator->generate('home'),                   // /
        ];
    }
}
```

Params fill the `{placeholders}` by name:

```php
$urlGenerator->generate('user.show', ['id' => 7]);          // /users/7
$urlGenerator->generate('archive', ['year' => 2026]);       // /archive/2026
$urlGenerator->generate('archive');                         // /archive
```

- **Mandatory params** must be supplied, otherwise `UrlGenerationException` is thrown.
- **Optional params** are omitted when absent. Since optionals are always trailing,
  generation stops at the first one missing: for `a/{one?}/{two?}` passing only `two`
  yields `/a`, rather than placing it where `one` would be read back.
- **Extra params** that match no placeholder are ignored; they are not appended as a
  query string.
- Values may be scalars or **backed enums**, which contribute their `value`. `bool` is
  rejected, since neither `1` nor `true` is an obviously right URL segment.

## Errors

Everything throws `UrlGenerationException`, so one handler covers the lot:

| Situation | Message |
|---|---|
| Name never registered | `No route is named 'x'.` |
| Two routes share a name | `Route name 'x' is already taken.` |
| Mandatory param missing | `Missing param 'id' to generate the url for route 'x'.` |
| Param is an array/object/bool | `Unsupported type 'array' for param 'id' generating the url for route 'x'.` |

Names are indexed the first time a URL is generated, not at registration: a route is
named *after* `Routes` hands it back, so the map is only complete once the configure
closure has finished. A duplicate name therefore surfaces on first use rather than at
registration time.
