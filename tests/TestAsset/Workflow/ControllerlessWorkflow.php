<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractPageWorkflow;

/**
 * A page workflow with no controller, as a misconfigured site might register one.
 */
final class ControllerlessWorkflow extends AbstractPageWorkflow {}
