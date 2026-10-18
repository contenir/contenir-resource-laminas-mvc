<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Integration;

use Contenir\Preview\Clock\SystemClock;
use Contenir\Preview\PreviewConfig;
use Contenir\Preview\PreviewRecord;
use Contenir\Preview\Token\TokenSigner;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowArticleResolver;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowPageResolver;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Preview\PreviewRecords;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow\ArticleControllerWorkflow;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcApplicationTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Application;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Stdlib\Parameters;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SensitiveParameter;

use function str_contains;

/**
 * CMS previews of resources through a real laminas-mvc Application with contenir-preview-laminas-mvc
 * loaded: pages at their workflow's controller and articles on their listing page's detail view,
 * each showing the editor's unsaved values, drafts and records not saved yet included, while an
 * ordinary visit sees the site as stored.
 */
#[Group('integration')]
final class PreviewApplicationTest extends TestCase
{
    use MvcApplicationTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    private const string SHARED = 'a-secret-of-at-least-thirty-two-bytes!!';

    #[Test]
    public function aDraftPageIsPreviewed(): void
    {
        $response = $this->preview(PreviewRecords::saved(3, ['title' => 'Spa rituals']));

        static::assertSame([200, true], [$response->getStatusCode(), $this->shows($response, '<h1>Spa rituals</h1>')]);
    }

    #[Test]
    public function anArticleIsPreviewedUnderItsUnsavedSlug(): void
    {
        $response = $this->preview(PreviewRecords::saved(
            7,
            [
                'slug'  => 'checkout-late',
                'title' => 'Late checkout, all summer',
            ],
            'library.articles.article',
        ));

        static::assertSame(
            [200, true],
            [$response->getStatusCode(), $this->shows($response, '<h1 class="article">Late checkout, all summer</h1>')],
        );
    }

    #[Test]
    public function anArticleNotSavedYetIsPreviewed(): void
    {
        $response = $this->preview(PreviewRecords::unsaved([
            'resource_type_id' => 'article',
            'slug'             => 'brand-new',
            'title'            => 'Brand new',
        ]));

        static::assertSame(
            [200, true],
            [$response->getStatusCode(), $this->shows($response, '<h1 class="article">Brand new</h1>')],
        );
    }

    #[Test]
    public function anOrdinaryVisitSeesTheSiteAsStored(): void
    {
        $response = $this->dispatch($this->application(), 'https://www.example.com/about');

        static::assertSame([200, true], [$response->getStatusCode(), $this->shows($response, '<h1>About</h1>')]);
    }

    #[Test]
    public function aPageIsPreviewedWithItsUnsavedValues(): void
    {
        $response = $this->preview(PreviewRecords::saved(1, ['title' => 'About us, unsaved']));

        static::assertSame(
            [200, true, true],
            [
                $response->getStatusCode(),
                $this->shows($response, '<h1>About us, unsaved</h1>'),
                $this->shows($response, 'data-contenir-preview'),
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->setUpDatabase();
        $this->insertResource(['resource_id' => 1, 'slug' => 'about', 'title' => 'About']);
        $this->insertResource(['resource_id' => 3, 'slug' => 'spa', 'title' => 'Spa (draft)', 'active' => 'pending']);
        $this->insertResource(['resource_id' => 5, 'slug' => 'news', 'title' => 'News', 'workflow' => 'news']);
        $this->insertResource([
            'resource_id'      => 7,
            'parent_id'        => 5,
            'resource_type_id' => 'article',
            'slug'             => 'late-checkout',
            'title'            => 'Late checkout',
        ]);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }

    /**
     * @mago-expect lint:no-literal-namespace-string Module names are namespaces, not classes.
     */
    private function application(): Application
    {
        return $this->bootstrapApplication(
            modules: ['Contenir\Preview\Laminas\Mvc'],
            config: [
                'workflow_manager' => [
                    'aliases'   => ['news' => ArticleControllerWorkflow::class],
                    'factories' => [ArticleControllerWorkflow::class => InvokableFactory::class],
                ],
                'contenir_preview' => [
                    'issuer'    => 'https://admin.example.com',
                    'audience'  => 'https://www.example.com',
                    'secrets'   => ['test' => self::SHARED],
                    'sign_with' => 'test',
                    'replay'    => ['directory' => $this->tmpDir],
                    'resolvers' => [
                        'site.page.home'           => WorkflowPageResolver::class,
                        'library.articles.article' => WorkflowArticleResolver::class . '.news',
                    ],
                ],
            ],
        );
    }

    private function post(#[SensitiveParameter] string $token): Response
    {
        $application = $this->application();
        $request     = $application->getRequest();
        static::assertInstanceOf(Request::class, $request);
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters(['contenir_preview' => $token]));

        return $this->dispatch($application, 'https://www.example.com/api/resource/preview');
    }

    private function preview(PreviewRecord $record): Response
    {
        $config = new PreviewConfig(
            'https://admin.example.com',
            'https://www.example.com',
            ['test' => self::SHARED],
            'test',
        );

        return $this->post((new TokenSigner($config, new SystemClock()))->sign($record, '12'));
    }

    private function shows(Response $response, string $html): bool
    {
        return str_contains($response->getContent(), $html);
    }
}
