<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Integration\View;

use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcApplicationTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use DateTimeImmutable;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\View\Exception\InvalidArgumentException as ViewInvalidArgumentException;
use Laminas\View\Helper\Doctype;
use Laminas\View\Helper\HeadLink;
use Laminas\View\Helper\HeadMeta;
use Laminas\View\Helper\HeadTitle;
use Laminas\View\HelperPluginManager;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The resourceMeta helper from a real application's ViewHelperManager,
 * writing to the head helpers the layout renders.
 */
#[Group('integration')]
final class ResourceMetaHelperTest extends TestCase
{
    use MvcApplicationTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    private HelperPluginManager $helpers;

    #[Test]
    public function itBuildsTheMetadataOfAResourceForTheCurrentRequest(): void
    {
        $about                  = ResourceFactory::make();
        $about->metaDescription = 'Fish & chips';
        $about->created         = new DateTimeImmutable('2024-01-02 03:04:05+00:00');

        $this->helper()($about);

        static::assertSame(
            '<title>About</title>'
                . '<meta property="og&#x3A;type" content="website">'
                . "\n"
                . '<meta property="og&#x3A;url" content="https&#x3A;&#x2F;&#x2F;www.example.com&#x2F;about">'
                . "\n"
                . '<meta property="twitter&#x3A;url" content="https&#x3A;&#x2F;&#x2F;www.example.com&#x2F;about">'
                . "\n"
                . '<meta property="og&#x3A;title" content="About">'
                . "\n"
                . '<meta property="twitter&#x3A;title" content="About">'
                . "\n"
                . '<meta name="description" content="Fish&#x20;&amp;&#x20;chips">'
                . "\n"
                . '<meta property="og&#x3A;description" content="Fish&#x20;&amp;&#x20;chips">'
                . "\n"
                . '<meta property="twitter&#x3A;description" content="Fish&#x20;&amp;&#x20;chips">'
                . "\n"
                . '<meta property="og&#x3A;updated_time" content="2024-01-02T03&#x3A;04&#x3A;05&#x2B;00&#x3A;00">'
                . '<link href="https&#x3A;&#x2F;&#x2F;www.example.com&#x2F;about" rel="canonical">',
            $this->head(),
        );
    }

    #[Test]
    public function itDoesNothingWithoutMetadata(): void
    {
        $this->helper()();

        static::assertSame('<title></title>', $this->head());
    }

    #[Test]
    public function itKeepsTheLayoutTitleWhenThereIsNone(): void
    {
        $this->helpers->get(HeadTitle::class)->__invoke('Layout title');

        $this->helper()(new PageMetadata('https://s.test/'));

        static::assertStringStartsWith('<title>Layout title</title><meta', $this->head());
    }

    #[Test]
    public function itNeedsAnRdfaCapableDoctype(): void
    {
        $this->helpers->get(Doctype::class)->setDoctype(Doctype::HTML4_LOOSE);

        $this->expectException(ViewInvalidArgumentException::class);

        $this->helper()(new PageMetadata('https://s.test/'));
    }

    #[Test]
    public function itReplacesTheLayoutTitle(): void
    {
        $this->helpers->get(HeadTitle::class)->__invoke('Layout title');

        $this->helper()(new PageMetadata('https://s.test/', title: 'About "us"'));

        static::assertStringStartsWith('<title>About &quot;us&quot;</title>', $this->head());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->setUpDatabase();
        $application = $this->bootstrapApplication(['base_url' => 'https://www.example.com']);
        $request     = $application->getRequest();
        static::assertInstanceOf(Request::class, $request);
        $request->setUri('https://evil.test/about?x=1');

        $helpers = $application->getServiceManager()->get('ViewHelperManager');
        static::assertInstanceOf(HelperPluginManager::class, $helpers);
        $helpers->get(Doctype::class)->setDoctype(Doctype::HTML5);
        $this->helpers = $helpers;
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }

    private function head(): string
    {
        return (
            $this->helpers->get(HeadTitle::class)->toString()
                . $this->helpers->get(HeadMeta::class)->toString()
                . $this->helpers->get(HeadLink::class)->toString()
        );
    }

    private function helper(): callable
    {
        $helper = $this->helpers->get('resourceMeta');
        static::assertIsCallable($helper);

        return $helper;
    }
}
