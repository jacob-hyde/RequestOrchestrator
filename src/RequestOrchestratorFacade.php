<?php

namespace JacobHyde\RequestOrchestrator;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JacobHyde\RequestOrchestrator\Skeleton\SkeletonClass
 */
class RequestOrchestratorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'requestorchestrator';
    }
}
