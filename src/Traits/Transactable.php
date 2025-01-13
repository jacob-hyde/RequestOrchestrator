<?php

namespace JacobHyde\RequestOrchestrator\Traits;

use Illuminate\Support\Facades\DB;

trait Transactable
{
    protected bool $useTransactions = true;

    public function beginTransaction(): void
    {
        if ($this->useTransactions) {
            DB::beginTransaction();
        }
    }

    public function commitTransaction(): void
    {
        if ($this->useTransactions) {
            DB::commit();
        }
    }

    public function rollbackTransaction(): void
    {
        if ($this->useTransactions) {
            DB::rollBack();
        }
    }
}