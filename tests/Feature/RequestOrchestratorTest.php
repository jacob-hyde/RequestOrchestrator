<?php

namespace JacobHyde\RequestOrchestrator\Tests\Feature;

use JacobHyde\RequestOrchestrator\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use JacobHyde\RequestOrchestrator\Requests\SequencedRequest;
use JacobHyde\RequestOrchestrator\Actions\FormAction;
use JacobHyde\RequestOrchestrator\Traits\Transactable;

class RequestOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Bind test actions to the service container
        $this->app->bind(TestActionOne::class, fn() => new TestActionOne());
        $this->app->bind(TestActionTwo::class, fn() => new TestActionTwo());
        $this->app->bind(FailingAction::class, fn() => new FailingAction());
    }

    /** @test */
    public function it_executes_actions_in_sequence()
    {
        Event::fake();

        $requestClass = new class extends SequencedRequest {
            protected array $actions = [
                TestActionOne::class,
                TestActionTwo::class,
            ];

            public function rules(): array
            {
                return ['field1' => 'required|string', 'field2' => 'required|string'];
            }
        };

        $request = $this->createRequestWithData($requestClass, [
            'field1' => 'value1',
            'field2' => 'value2',
        ]);

        $result = $request->handle();

        $this->assertEquals('ActionTwoOutput', $result);

        Event::assertDispatched('action.starting', 2);
        Event::assertDispatched('action.completed', 2);
    }

    /** @test */
    public function it_rolls_back_transactions_on_failure()
    {
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        $requestClass = new class extends SequencedRequest {

            use Transactable;
            protected array $actions = [
                TestActionOne::class,
                FailingAction::class,
            ];

            public function rules(): array
            {
                return ['field1' => 'required|string'];
            }
        };

        $request = $this->createRequestWithData($requestClass, [
            'field1' => 'value1',
        ]);

        try {
            $request->handle();
        } catch (\Exception $e) {
            $this->assertEquals('Intentional failure', $e->getMessage());
        }
    }

    /** @test */
    public function it_skips_actions_if_required_fields_are_missing()
    {
        $requestClass = new class extends SequencedRequest {
            protected array $actions = [
                TestActionOne::class,
                TestActionTwo::class,
            ];

            public function rules(): array
            {
                return ['field1' => 'required|string', 'field2' => 'required|string'];
            }
        };

        $request = $this->createRequestWithData($requestClass, [
            'field1' => 'value1', // 'field2' is missing
        ]);

        $result = $request->handle();

        // Assert that the output is the result of the last successful action (ActionOne)
        $this->assertEquals('ActionOneOutput', $result);
    }


    private function createRequestWithData($requestClass, array $data)
    {
        $request = new $requestClass();

        // Set the container and request instance
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));

        // Prevent automatic validation during instantiation
        $request->validateAutomatically = false;

        // Populate request data
        $request->merge($data);

        return $request;
    }

}

/**
 * Mock Actions for Testing
 */
class TestActionOne extends FormAction
{
    protected array $requiredFields = ['field1'];

    public function handle(array $data, $previousOutput = null)
    {
        return 'ActionOneOutput';
    }

    public function rollback($previousOutput)
    {
        Log::info('Rollback TestActionOne');
    }
}

class TestActionTwo extends FormAction
{
    protected array $requiredFields = ['field2'];

    public function handle(array $data, $previousOutput = null)
    {
        return 'ActionTwoOutput';
    }

    public function rollback($previousOutput)
    {
        Log::info('Rollback TestActionTwo');
    }
}

class FailingAction extends FormAction
{
    protected array $requiredFields = ['field1'];

    public function handle(array $data, $previousOutput = null)
    {
        throw new \Exception('Intentional failure');
    }
}
