<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Preview;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Preview\InvalidPreview;
use Contenir\Preview\PreviewRecord;
use Contenir\Preview\Resolver\PreviewResolverInterface;
use Contenir\Preview\Resolver\PreviewTarget;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Middleware\ResourceMiddleware;
use Contenir\Workflow\Workflow\WorkflowInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Override;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

use function is_string;

/**
 * Previews a page at the middleware its workflow (the CMS's Template field) names, with the
 * page's id as its route would carry it, for ResourceMiddleware to load. The page is dispatched
 * without routing, so a changed slug or template shows as it will once saved. A preview that
 * carries no template, as a shared preview carries no values at all, is shown by the stored
 * page's workflow. Name it as the resolver for each CMS section that edits the site's pages:
 *
 *     'contenir_preview' => ['resolvers' => ['site.page.home' => WorkflowPageResolver::class]],
 *
 * @api
 */
final readonly class WorkflowPageResolver implements PreviewResolverInterface
{
    /**
     * @param ResourceManagerInterface $resources Finds the stored page, whatever its status
     */
    public function __construct(
        private WorkflowPluginManager $workflows,
        private ResourceManagerInterface $resources,
    ) {}

    /**
     * @throws InvalidPreview When the page has not been saved, or uses a template the site lacks
     * @throws DbModelException When the stored page cannot be read
     */
    #[Override]
    public function resolve(PreviewRecord $record): PreviewTarget
    {
        $id = $record->keys['resource_id'] ?? throw InvalidPreview::notPreviewable(
            'This page can be previewed once it has been saved.',
        );
        $workflow = $record->value('workflow');

        return PreviewTarget::handler(
            $this->middleware(
                is_string($workflow) && '' !== $workflow
                    ? $workflow
                    : $this->resources->find($id)?->getWorkflowName() ?? 'page',
            ),
            [ResourceMiddleware::ROUTE_PARAMETER => [AbstractResourceEntity::PRIMARY_KEY => $id]],
        );
    }

    /**
     * @throws InvalidPreview When the site has no such workflow, or it names no middleware
     */
    private function middleware(string $workflow): string
    {
        try {
            /** @var WorkflowInterface $instance The plugin manager serves workflows only. */
            $instance = $this->workflows->get($workflow);

            return $instance->getRouteMiddleware();
        } catch (ContainerExceptionInterface|RuntimeException) {
            throw InvalidPreview::notPreviewable('This page uses a template the site does not have.');
        }
    }
}
