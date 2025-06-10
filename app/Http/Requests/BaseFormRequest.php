<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Exceptions\ValidationException;
use App\Http\Dto\Request\BaseDto;
use App\Services\RequestLogger;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        $validationException = new \Exception(
            'Form Validation Failed in '.static::class.': '.implode('; ', $validator->errors()->all())
        );

        RequestLogger::addException($validationException);

        RequestLogger::addEvent('[validation] validation_failed', [
            'request_class' => static::class,
            'failed_fields' => array_keys($validator->errors()->toArray()),
            'error_count' => $validator->errors()->count(),
            'first_error' => $validator->errors()->first(),
            'failed_rules' => $this->getFailedRules($validator),
        ]);

        throw new ValidationException($validator->errors()->all());
    }

    private function getFailedRules(Validator $validator): array
    {
        $failedRules = [];

        foreach ($validator->errors()->toArray() as $field => $messages) {
            $failedRules[$field] = [
                'messages' => $messages,
                'value' => $this->input($field),
                'rules' => $this->rules()[$field] ?? 'unknown',
            ];
        }

        return $failedRules;
    }

    public function validatedDTO(string $dto): BaseDto
    {
        return new $dto($this->validated());
    }
}
