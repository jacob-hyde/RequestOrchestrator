<?php

namespace JacobHyde\RequestOrchestrator\Actions;

abstract class FormAction
{
    protected array $requiredFields = [];

    public function shouldRun(array $data): bool
    {
        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                return false;
            }
        }

        return true;
    }

    public function rollback($previousOutput)
    {
        // Default no-op rollback
    }

    public function rules(): array
    {
        return [];
    }

    public function validate(array $data)
    {
        $validator = validator($data, $this->rules());
        $validator->validate();
    }

    abstract public function handle(array $data, $previousOutput = null);
}

