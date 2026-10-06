# The resource middleware

`Middleware\ResourceMiddleware` turns a matched workflow route into a resolved resource.

```php
$app->pipe(RouteMiddleware::class);
$app->pipe(ResourceMiddleware::class);
$app->pipe(DispatchMiddleware::class);
```

For each request:

1. If the `id` route parameter is not an array with a `resourceId` key, the request is not a resource route and passes
   through untouched. A path placeholder named `id` is always a string, so it never qualifies.
2. The resource is looked up with `ResourceManagerInterface::findActive()`. Only positive integer ids (or digit
   strings) are queried.
3. A missing or non-active resource is answered by the not-found handler (`contenir_resource.not_found_handler`,
   default `Mezzio\Handler\NotFoundHandler`). This covers pages unpublished after the route cache was built.
4. Otherwise the request carries the resource under `AbstractResourceEntity::class` and its `PageMetadata` (built
   from the request URL and `base_url`) under `PageMetadata::class`, and goes on to the handler.

Read them with `ResourceAttribute`:

```php
ResourceAttribute::resource($request); // ?AbstractResourceEntity
ResourceAttribute::require($request);  // AbstractResourceEntity, or MissingResourceException
ResourceAttribute::metadata($request); // ?PageMetadata
```

`require()` failing means the handler is reached by a route that is not a resource workflow route, or the
middleware is not piped; it is a wiring error, so it throws rather than returning a 404.
