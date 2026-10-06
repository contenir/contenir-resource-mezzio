<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Integration\View;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Mezzio\Container\ViewHelperFactory;
use Contenir\Resource\Mezzio\Tests\Trait\ApplicationContainerTrait;
use Contenir\Resource\Mezzio\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Mezzio\Tests\Trait\TemporaryDirectoryTrait;
use Contenir\Resource\Mezzio\View\Helper\ResourceContentHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceMetaHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceUrlHelper;
use DateTimeImmutable;
use Laminas\View\Exception\InvalidArgumentException as ViewInvalidArgumentException;
use Laminas\View\Helper\Doctype;
use Laminas\View\Helper\HeadLink;
use Laminas\View\Helper\HeadMeta;
use Laminas\View\Helper\HeadTitle;
use Laminas\View\HelperPluginManager;
use Laminas\View\Renderer\PhpRenderer;
use Mezzio\Application;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function html_entity_decode;

#[Group('integration')]
final class ViewHelpersTest extends TestCase
{
    use ApplicationContainerTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    private HelperPluginManager $helpers;

    #[Test]
    public function resourceMetaDoesNothingWithoutMetadata(): void
    {
        $this->helpers->get('resourceMeta')();

        static::assertSame("<title></title>\n\n", $this->head());
    }

    #[Test]
    public function resourceMetaKeepsTheLayoutTitleWhenThereIsNone(): void
    {
        $this->helpers->get(HeadTitle::class)->__invoke('Layout title');
        $this->helpers->get('resourceMeta')(new PageMetadata('https://s.test/'));

        static::assertStringStartsWith('<title>Layout title</title>', $this->head());
    }

    #[Test]
    public function resourceMetaNeedsAnRdfaCapableDoctype(): void
    {
        $this->helpers->get(Doctype::class)->setDoctype(Doctype::HTML4_LOOSE);

        $this->expectException(ViewInvalidArgumentException::class);

        $this->helpers->get('resourceMeta')(new PageMetadata('https://s.test/'));
    }

    #[Test]
    public function resourceMetaWritesTheHeadHelpers(): void
    {
        $this->helpers->get(HeadTitle::class)->__invoke('Layout title');
        $this->helpers->get('resourceMeta')(new PageMetadata(
            url: 'https://s.test/about',
            title: 'About "us"',
            description: 'Fish & chips',
            image: 'https://s.test/i.jpg',
            updated: new DateTimeImmutable('2024-01-02 03:04:05+00:00'),
        ));

        static::assertSame(
            "<title>About \"us\"</title>\n"
                . "<link href=\"https://s.test/about\" rel=\"canonical\">\n"
                . "<meta property=\"og:type\" content=\"website\">\n"
                . "<meta property=\"og:url\" content=\"https://s.test/about\">\n"
                . "<meta property=\"twitter:url\" content=\"https://s.test/about\">\n"
                . "<meta property=\"og:title\" content=\"About \"us\"\">\n"
                . "<meta property=\"twitter:title\" content=\"About \"us\"\">\n"
                . "<meta name=\"description\" content=\"Fish & chips\">\n"
                . "<meta property=\"og:description\" content=\"Fish & chips\">\n"
                . "<meta property=\"twitter:description\" content=\"Fish & chips\">\n"
                . "<meta property=\"og:image\" content=\"https://s.test/i.jpg\">\n"
                . "<meta property=\"twitter:image\" content=\"https://s.test/i.jpg\">\n"
                . '<meta property="og:updated_time" content="2024-01-02T03:04:05+00:00">',
            html_entity_decode($this->head()),
        );
    }

    #[Test]
    public function theFactoryRejectsAnUnknownHelper(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('No view helper "Other" is built by this factory');

        (new ViewHelperFactory())($this->container, requestedName: 'Other');
    }

    #[Test]
    public function theHelpersResolveUnderTheirLaminasMvcNames(): void
    {
        static::assertSame(
            [ResourceHelper::class, ResourceContentHelper::class, ResourceMetaHelper::class, ResourceUrlHelper::class],
            [
                $this->helpers->get('Resource')::class,
                $this->helpers->get('resourceContent')::class,
                $this->helpers->get('ResourceMeta')::class,
                $this->helpers->get('resourceUrl')::class,
            ],
        );
    }

    #[Test]
    public function theOtherHelpersWorkAgainstTheDatabaseAndRoutes(): void
    {
        $this->container->get(Application::class);

        static::assertSame(
            [1, 'About us', ['/about', null]],
            [
                $this->helpers->get('resource')(1)?->resourceId,
                $this->helpers->get('resourceContent')($this->helpers->get('resource')->findBySlug('about')),
                $this->helpers->get('resourceUrl')(1),
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->setUpDatabase();
        $this->insertResource([
            'resource_id' => 1,
            'slug'        => 'about',
            'title'       => 'About',
            'workflow'    => 'handler',
            'description' => '<p>About us</p>',
        ]);
        $this->setUpApplicationContainer();

        $config        = $this->container->get('config');
        $this->helpers = new HelperPluginManager($this->container, $config['view_helpers']);
        $this->container->setService(HelperPluginManager::class, $this->helpers);
        $renderer = new PhpRenderer();
        $renderer->setHelperPluginManager($this->helpers);
        $this->helpers->get(Doctype::class)->setDoctype(Doctype::HTML5);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }

    private function head(): string
    {
        $title = $this->helpers->get(HeadTitle::class)->toString();
        $links = $this->helpers->get(HeadLink::class)->toString();
        $metas = $this->helpers->get(HeadMeta::class)->toString();

        return "{$title}
{$links}
{$metas}";
    }
}
