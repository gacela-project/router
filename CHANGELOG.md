# Changelog

### 0.10.0

##### 2023-04-27

- Add Infection
- Create RouterInterface
- Group Configure classes in the same dir
- Add adapter RouterGacelaConfig

### 0.9.0

##### 2023-04-27

- Allow adding routes in different steps
- Unify Router use
- Refactor Router Static Methods

### 0.8.0

##### 2023-04-24

- Require response to be a string or implements Stringable
- Simplify Route::requestMatches implementation

### 0.7.0

##### 2023-04-22

- Create JsonResponse
- Allow headers in Response

### 0.6.0

##### 2023-04-21

- Move and fix 404 tests
- Error handler
- Introspective handling
- Support class handlers
- Add named constructor for UnsupportedHttpMethodException
- Improve readme example

### 0.5.0

##### 2023-04-18

- Trigger 404 if no Route was found

### 0.4.0

##### 2023-04-17

- Inject Request in Controller's constructor
- Match route

### 0.3.0

##### 2023-04-16

- Refactoring structure

### 0.2.0

##### 2023-04-16

- Create RoutingConfigurator
- Refactor extract class RouteParams
- UnsupportedHttpMethodException if HTTP verb is invalid
- Support all routes with "any"
- Automatically resolve controller dependencies
- Allow redirect routes

### 0.1.0

##### 2023-04-10

- Initial release