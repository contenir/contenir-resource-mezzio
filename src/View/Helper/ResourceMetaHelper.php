<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\View\Helper;

use Contenir\Resource\Core\Metadata\PageMetadata;
use Laminas\View\Exception\ExceptionInterface as ViewException;
use Laminas\View\Helper\HeadLink;
use Laminas\View\Helper\HeadMeta;
use Laminas\View\Helper\HeadTitle;
use Laminas\View\Helper\Placeholder\Container\AbstractContainer;

/**
 * laminas-view helper "resourceMeta": writes a page's metadata into the
 * headTitle (replacing it), headLink and headMeta helpers.
 *
 * Open Graph tags use the "property" attribute, which headMeta only accepts
 * with an HTML5 or RDFa doctype; set one in the layout.
 *
 * @api
 */
final readonly class ResourceMetaHelper
{
    public function __construct(
        private HeadTitle $headTitle,
        private HeadLink $headLink,
        private HeadMeta $headMeta,
    ) {}

    /**
     * @throws ViewException When the doctype does not allow a tag.
     */
    public function __invoke(?PageMetadata $metadata = null): void
    {
        if (null === $metadata) {
            return;
        }

        if (null !== $metadata->title) {
            $this->headTitle->__invoke($metadata->title, AbstractContainer::SET);
        }

        foreach ($metadata->getLinks() as $link) {
            $this->headLink->__invoke($link);
        }

        foreach ($metadata->getMetaTags() as $tag) {
            'name' === $tag['attribute']
                ? $this->headMeta->setName($tag['key'], $tag['content'])
                : $this->headMeta->setProperty($tag['key'], $tag['content']);
        }
    }
}
