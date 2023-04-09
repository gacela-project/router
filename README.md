# Gacela Router

A minimalistic HTTP router ideal for your proof-of-concept projects.

## Why?

There are many other routers out there. Eg: using Symfony Framework, Laravel, etc... however, these are really rich in features which means they add a lot of accidental complexity and dependencies to your vendor, that you might want to avoid. At least for your proof-of-concept project.

Gacela Router doesn't aim to be the best router that can do everything, but a light router to have the bare minimum code, ideal for your simple ideas to emerge.

For a POC, we simply value simplicity over a rich-feature library.

## Example

Start the example local server:
```bash
php -S localhost:8081 example/example.php
```

You can access the example routes:
```php
# localhost:8081/custom/123
Router::get('custom/{number}', $controller, 'customAction');

# localhost:8081/custom
Router::get('custom', $controller);

# localhost:8081?number=456
Router::get('/', $controller);
```

