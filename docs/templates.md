# Metadata, templates and links

Everything here works with any `mezzio/mezzio-template` renderer (Twig, Plates, laminas-view).

## Page head

`ResourceAttribute::metadata($request)` gives the page's `PageMetadata`. Either render it with `MetaTagRenderer`
(escaped HTML; output it unescaped in `<head>`), or pass the `PageMetadata` to the template and iterate
`getLinks()` and `getMetaTags()` yourself, escaping each value. With laminas-view, the `resourceMeta` helper writes
it into the head helpers instead.

## Summaries

`Contenir\Resource\Core\Content\ResourceSummary` gives a plain-text teaser of a resource: its description, or else
its rendered section content. To render sections, configure:

```php
'contenir_resource' => [
    'section_template' => 'app::component/section',
    'section_renderer' => \Contenir\Resource\Mezzio\Content\TemplateSectionRenderer::class,
],
```

`TemplateSectionRenderer` renders `section_template` through the `TemplateRendererInterface` service with the
variables `section` (from `SectionAwareInterface::getSection()`) and `resource`. Only entities implementing
`SectionAwareInterface` have sections.

## Links

`ResourceUrlGenerator` (see [routing](routing.md#generating-urls)) gives resource and external links. External links
are normalised by `ExternalUrl`: only http, https, mailto and tel are allowed, a bare host gets `https://`, and
anything else gives no URL. Escape the URL and target when writing them into HTML.
