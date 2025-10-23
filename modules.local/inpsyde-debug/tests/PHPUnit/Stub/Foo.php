<?php
declare(strict_types=1);

namespace Inpsyde\Debug\Tests\Stub;

//phpcs:disable
class Foo
{

    private $state;

    public function __construct()
    {
        $this->state = uniqid();
    }

    public function withoutParams()
    {
        throw new VerySpecificException(sprintf('hi'));
    }

    public function withParamTypes(int $param1, array $param2): string
    {
        throw new VerySpecificException(sprintf('hi'));
    }

    public function withoutParamType($noType): array
    {
        throw new VerySpecificException(sprintf('hi'));
    }

    public function withNullable(?string $input = null)
    {
        throw new VerySpecificException(sprintf('hi'));
    }

    public function withVariadic(string ...$strings)
    {
        throw new VerySpecificException(sprintf('hi'));
    }

    public function withReturnValue(): string
    {
        return 'success';
    }

    public function withInternalState()
    {
        return $this->state;
    }

    private function hidden()
    {
    }
}