# HTTP behavior checklist

Paths are relative to the repository root. This describes the current implementation; verify it when changing behavior. Select checks for the affected boundary.

| Boundary | Behavior to preserve or change explicitly | Evidence |
| --- | --- | --- |
| Matching | Methods are normalized at registration. Static lookup precedes dynamic matching; dynamic candidates keep registration order. Duplicate static registrations keep the first. | src/Router/Configure/Routes.php; tests/Unit/Router/Configure/RoutesMatchingTest.php |
| Methods | An explicit HEAD route wins; otherwise HEAD uses GET. HEAD runs the pipeline but emits no body, including errors. A known path with the wrong method yields 405 and Allow; an unknown path yields 404. | tests/Feature/Router/HeadRequestTest.php; tests/Feature/Router/MethodNotAllowedTest.php |
| Paths | Root registration stores an empty path; the static index uses '/'. Preserve '0' segments, optional trailing params and group prefix cleanup after exceptions. | tests/Unit/Router/Entities/RequestPathTest.php; tests/Feature/Router/RouteGroupTest.php; tests/Feature/Router/RouterParamTest.php |
| Invocation | Path placeholders supply action arguments; query and form values are read through Request. Scalar and backed-enum values are validated, not cast: a value that does not fit throws InvalidParamValueException (built-in 404), including int-backed zero. Signature caches are keyed by controller class and action. | src/Router/Entities/RouteParams.php; tests/Feature/Router/EnumParamTest.php; tests/Feature/Router/InvalidParamValueTest.php |
| Middleware | Global middleware precedes route middleware. One Route is shared by every method in match()/any(), so chained middleware covers all those methods. Groups resolve through Middlewares. | src/Router/Router.php; tests/Feature/Router/MiddlewareTest.php; tests/Feature/Router/MiddlewareGroupTest.php |
| Output | Actions return string, Stringable or array; arrays become JsonResponse. Headers may be emitted while responses are converted to strings. | src/Router/Entities/Route.php; tests/Feature/Router/ArrayResponseTest.php; tests/Feature/Router/RouterResponseTest.php |
| Errors | The closest registered handler wins: exact class, parent classes, interfaces, then Throwable. An Error must not reach an Exception-only handler. | src/Router/Router.php; tests/Feature/Router/ErrorHandlingTest.php |
| URLs | Named routes are indexed lazily. Check missing/duplicate names, required/optional values, backed enums and groups. Test generation against matching where the change touches both. | src/Router/UrlGenerator.php; tests/Feature/Router/NamedRouteTest.php |
| Framework | RouterGacelaConfig binds Router and RouterInterface without making framework bootstrap necessary for standalone routing. | tests/Feature/Config/RouterConfigTest.php |
