<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\RequestLogger;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $this->logExceptionToMongo($e);
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        // Handle ValidationException specifically for API requests
        if ($e instanceof ValidationException && $request->expectsJson()) {
            return $this->handleValidationException($e, $request);
        }

        // Handle other exceptions with request tracking
        if ($request->expectsJson()) {
            return $this->handleApiException($e, $request);
        }

        return parent::render($request, $e);
    }

    /**
     * Log exception details to MongoDB through RequestLogger
     */
    private function logExceptionToMongo(Throwable $e): void
    {
        try {
            // Add exception to current request tracking
            RequestLogger::addException($e);

            // Add specific contextual events based on exception type
            $this->addContextualExceptionEvents($e);

        } catch (Exception $mongoException) {
            // Fallback to standard Laravel logging if MongoDB fails
            logger()->error('Failed to log exception to MongoDB', [
                'original_exception' => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                'mongo_exception' => $mongoException->getMessage(),
            ]);
        }
    }

    /**
     * Add contextual events based on specific exception types
     */
    private function addContextualExceptionEvents(Throwable $e): void
    {
        if ($e instanceof ValidationException) {
            RequestLogger::addEvent('validation_exception_details', [
                'failed_rules' => $this->getFailedValidationRules($e),
                'input_count' => count(request()->all()),
                'error_count' => $e->validator->errors()->count(),
            ]);
        }

        if ($e instanceof QueryException) {
            RequestLogger::addEvent('database_exception_details', [
                'error_code' => $e->getCode(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
                'driver_code' => $e->errorInfo[1] ?? 'unknown',
            ]);
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            RequestLogger::addEvent('authentication_exception_details', [
                'guards' => $e->guards(),
                'intended_url' => request()->fullUrl(),
            ]);
        }

        if ($e instanceof NotFoundHttpException) {
            RequestLogger::addEvent('not_found_exception_details', [
                'requested_path' => request()->path(),
                'method' => request()->method(),
                'referrer' => request()->header('referer'),
            ]);
        }
    }

    /**
     * Handle ValidationException for API requests
     */
    private function handleValidationException(ValidationException $e, Request $request): JsonResponse
    {
        $requestId = RequestLogger::getRequestId();

        // Log validation failure details
        RequestLogger::addEvent('validation_response_sent', [
            'error_fields' => array_keys($e->validator->errors()->toArray()),
            'total_errors' => $e->validator->errors()->count(),
        ]);

        return response()->json([
            'data' => [],
            'errors' => array_values($e->validator->errors()->all()),
            'request_id' => $requestId,
        ], 422)->withHeaders([
            'X-Request-ID' => $requestId,
            'X-Error-Type' => 'validation',
        ]);
    }

    /**
     * Handle other exceptions for API requests
     */
    private function handleApiException(Throwable $e, Request $request): JsonResponse
    {
        $requestId = RequestLogger::getRequestId();
        $statusCode = $this->getStatusCodeFromException($e);

        // Log API error response details
        RequestLogger::addEvent('api_error_response_sent', [
            'exception_class' => get_class($e),
            'status_code' => $statusCode,
            'user_message' => $this->getUserFriendlyMessage($e),
        ]);

        // Different response format based on environment
        $responseData = [
            'data' => [],
            'error' => $this->getUserFriendlyMessage($e),
            'request_id' => $requestId,
        ];

        // Add debug information in non-production environments
        if (! app()->environment('production')) {
            $responseData['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(3)->toArray(),
            ];
        }

        return response()->json($responseData, $statusCode)->withHeaders([
            'X-Request-ID' => $requestId,
            'X-Error-Type' => 'exception',
            'X-Exception-Class' => get_class($e),
        ]);
    }

    /**
     * Get failed validation rules from ValidationException
     */
    private function getFailedValidationRules(ValidationException $e): array
    {
        $failedRules = [];

        foreach ($e->validator->errors()->toArray() as $field => $messages) {
            $failedRules[$field] = count($messages);
        }

        return $failedRules;
    }

    /**
     * Get appropriate status code from exception
     */
    private function getStatusCodeFromException(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ModelNotFoundException) {
            return 404;
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        return 500;
    }

    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyMessage(Throwable $e): string
    {
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 'Authentication required.';
        }

        if ($e instanceof AuthorizationException) {
            return 'You are not authorized to perform this action.';
        }

        if ($e instanceof ModelNotFoundException) {
            return 'The requested resource was not found.';
        }

        if ($e instanceof NotFoundHttpException) {
            return 'The requested endpoint was not found.';
        }

        if ($e instanceof QueryException) {
            return 'A database error occurred. Please try again later.';
        }

        // In production, don't expose internal error messages
        if (app()->environment('production')) {
            return 'An unexpected error occurred. Please try again later.';
        }

        return $e->getMessage();
    }

    /**
     * Report or log an exception (override parent method)
     */
    public function report(Throwable $e): void
    {
        // Let parent handle standard Laravel logging
        parent::report($e);

        // Add our custom MongoDB logging
        // $this->logExceptionToMongo($e);
    }
}
