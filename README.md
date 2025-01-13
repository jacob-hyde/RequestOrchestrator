# Request Orchestrator

The **Request Orchestrator** is a Laravel package designed to simplify handling complex form submissions that require executing multiple sequential actions. It allows you to define actions, handle validation, and optionally manage database transactions, ensuring a clean and structured approach to multi-step processes.

---

## Features

- **Sequential Action Execution**: Define a list of actions to be executed in order.
- **Conditional Execution**: Skip actions based on dynamic conditions.
- **Integrated Validation**: Leverages Laravel's validation system.
- **Optional Transactions**: Enable or disable database transactions per request.
- **Error Handling and Rollbacks**: Rollback executed actions if an error occurs.

---

## Installation

Install the package via Composer:
```bash
composer require jacob-hyde/request-orchestrator
```

---

## Usage

### 1. Create a Custom Request Class
Extend the `SequencedRequest` class to define the actions and validation rules for your request.

```php
namespace App\Http\Requests;

use JacobHyde\RequestOrchestrator\Requests\SequencedRequest;

class MyCustomRequest extends SequencedRequest
{
    protected array $actions = [
        \App\Actions\ActionOne::class,
        \App\Actions\ActionTwo::class,
    ];

    public function rules(): array
    {
        return [
            'field1' => 'required|string',
            'field2' => 'required|integer',
        ];
    }
}
```

### 2. Define Actions
Actions should extend the `FormAction` class and implement the `handle` and `rollback` methods.

```php
namespace App\Actions;

use JacobHyde\RequestOrchestrator\Actions\FormAction;

class ActionOne extends FormAction
{
    protected array $requiredFields = ['field1'];

    public function handle(array $data, $previousOutput = null)
    {
        // Process action logic
        return 'ActionOneOutput';
    }

    public function rollback($previousOutput)
    {
        // Handle rollback logic
    }
}
```

### 3. Use the Request in a Controller

```php
namespace App\Http\Controllers;

use App\Http\Requests\MyCustomRequest;

class MyController extends Controller
{
    public function store(MyCustomRequest $request)
    {
        $result = $request->handle();
        return response()->json(['result' => $result]);
    }
}
```

---

## Configuration

### Optional Transactions
By default, `SequencedRequest` uses transactions. To disable transactions for a specific request, override the `$useTransactions` property:

```php
protected bool $useTransactions = false;
```

---

## Testing

### Run Tests
Run the package's test suite using PHPUnit:
```bash
vendor/bin/phpunit
```

### Example Test Case
```php
/** @test */
public function it_executes_actions_in_sequence()
{
    $request = new MyCustomRequest([
        'field1' => 'value1',
        'field2' => 123,
    ]);

    $result = $request->handle();

    $this->assertEquals('ExpectedOutput', $result);
}
```

---

## Contributing

Contributions are welcome! Feel free to submit a pull request or open an issue on GitHub.

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

