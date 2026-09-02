<?php

namespace App\Services\Automation;

use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use RuntimeException;
use Throwable;

class NodeExecutor
{
    public function __construct(
        protected ExecutionLogger $logger,
        protected NodeRegistry $registry
    ) {}

    public function execute(
        AutomationExecution $execution,
        AutomationNode $node
    ): NodeResult {
        $input = $execution->context ?? [];

        if (!is_array($input)) {
            $input = [];
        }

        $log = $this->logger->start(
            $execution,
            $node,
            $input
        );

        try {
            $handler = $this->registry->resolve(
                $node->component
            );

            if (!method_exists($handler, 'handle')) {
                throw new RuntimeException(
                    "Automation handler '{$node->component}' does not have a handle() method."
                );
            }

            $result = $handler->handle(
                $execution,
                $node,
                $input
            );

            if (!$result instanceof NodeResult) {
                throw new RuntimeException(
                    "Automation component '{$node->component}' did not return a valid NodeResult."
                );
            }

            $output = is_array($result->output)
                ? $result->output
                : [];

            if ($output !== []) {
                $context = $execution->context ?? [];

                if (!is_array($context)) {
                    $context = [];
                }

                $execution->update([
                    'context' => array_merge(
                        $context,
                        $output
                    ),
                ]);
            }

            $this->logger->complete(
                $log,
                $output
            );

            return $result;

        } catch (Throwable $e) {
            $this->logger->fail(
                $log,
                $e
            );

            throw $e;
        }
    }
}