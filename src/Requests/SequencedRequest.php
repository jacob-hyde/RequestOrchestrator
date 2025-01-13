<?php

namespace JacobHyde\RequestOrchestrator\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

abstract class SequencedRequest extends FormRequest
{
    protected array $actions = [];


    public function handle()
    {
        // Attempt validation and log errors instead of halting the process
        try {
            $this->validateResolved(); // Validate input data
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning("Validation failed: " . $e->getMessage());
        }

        $data = $this->safe()->all(); // Use safe() to access all validated and non-validated data
        $previousOutputs = [];
        $executedActions = [];

        if (isset($this->useTransactions) && $this->useTransactions) {
            DB::beginTransaction();
        }

        try {
            foreach ($this->actions as $action) {
                if (!class_exists($action)) {
                    Log::error("Action class {$action} not found.");
                    continue;
                }

                /** @var FormAction $actionInstance */
                $actionInstance = app($action);

                if (!$actionInstance->shouldRun($data)) {
                    continue;
                }

                Event::dispatch("action.starting", [$action]);

                $previousOutputs[] = $actionInstance->handle($data, end($previousOutputs));
                $executedActions[] = $actionInstance;

                Event::dispatch("action.completed", [$action, end($previousOutputs)]);
            }

            if (isset($this->useTransactions) && $this->useTransactions) {
                DB::commit();
            }
        } catch (\Exception $e) {
            if (isset($this->useTransactions) && $this->useTransactions) {
                DB::rollBack();
            }

            foreach (array_reverse($executedActions) as $action) {
                $action->rollback(array_pop($previousOutputs));
            }
            Log::error("Error during action handling: " . $e->getMessage());
            throw $e; // Propagate the exception to trigger rollback in tests
        }

        return end($previousOutputs);
    }
}
