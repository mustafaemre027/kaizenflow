<?php

namespace App\Exceptions\Workflow;

use Exception;

class InvalidApprovalWorkflowConfiguration extends Exception
{
    public static function noDefault(): self
    {
        return new self('No default active published workflow could be found.');
    }

    public static function multipleDefaults(): self
    {
        return new self('Multiple default active published workflows were found. This is a configuration error.');
    }

    public static function noActiveStages(): self
    {
        return new self('The workflow has no active stages configured.');
    }

    public static function invalidFinalStage(): self
    {
        return new self('The workflow does not have exactly one final stage correctly placed at the end.');
    }

    public static function publishedMutation(): self
    {
        return new self('Cannot mutate definition of a published workflow. Create a new version instead.');
    }

    public static function crossWorkflowStage(): self
    {
        return new self("Cross-workflow stage injection detected. The stage does not belong to the instance's workflow.");
    }
}
