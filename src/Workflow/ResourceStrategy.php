<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Workflow;

use Contenir\Workflow\ResourceInterface;
use Contenir\Workflow\Strategy\AbstractResourceStrategy;
use Contenir\Workflow\Workflow\WorkflowInterface;
use Override;

use const DATE_ATOM;

/**
 * The workflow-mezzio strategy for Contenir resources.
 *
 * Each resource is routed by the workflow plugin named in its "workflow"
 * column ("page" when empty). Its navigation page takes the resource's
 * short title (or title) as label, its visibility, and its modified date as
 * the sitemap lastmod. Resources that are not WorkflowResources fall back to
 * the workflow-mezzio defaults.
 *
 * @psalm-import-type NavigationPage from AbstractResourceStrategy
 *
 * @api
 */
final class ResourceStrategy extends AbstractResourceStrategy
{
    /**
     * @return NavigationPage
     */
    #[Override]
    protected function getNavigationPage(WorkflowInterface $workflow): array
    {
        $page     = parent::getNavigationPage($workflow);
        $resource = $workflow->getResource();
        if (! $resource instanceof WorkflowResource) {
            return $page;
        }

        $entity = $resource->getEntity();

        return [
            ...$page,
            'label'   => $entity->getNavigationLabel() ?? $page['label'],
            'visible' => $entity->isVisible(),
            'lastmod' => $entity->getMetaModified()?->format(DATE_ATOM),
        ];
    }

    #[Override]
    protected function getWorkflowType(ResourceInterface $resource): string
    {
        return $resource instanceof WorkflowResource
            ? $resource->getEntity()->getWorkflowName()
            : parent::getWorkflowType($resource);
    }
}
