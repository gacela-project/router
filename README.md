# Gacela Router

A minimalistic, proof-of-concept, HTTP router.

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

