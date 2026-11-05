<?php

namespace App\Services;

use App\Models\LogError;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ErrorLogService
{
    /**
     * Guard to prevent infinite recursion if database error occurs during error logging.
     */
    protected static bool $isLogging = false;

    /**
     * List of exceptions that shouldn't be captured into the error log table.
     */
    protected static array $ignoreExceptions = [
        ValidationException::class,
        AuthenticationException::class,
    ];

    /**
     * Fields to mask in request payload.
     */
    protected static array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        '_token',
    ];

    /**
     * Capture and record an exception into log_errores.
     */
    public static function capture(Throwable $e, ?string $nivel = null, array $extraContext = []): ?LogError
    {
        if (self::$isLogging) {
            return null;
        }

        // Check if exception type is ignored
        foreach (self::$ignoreExceptions as $ignored) {
            if ($e instanceof $ignored) {
                return null;
            }
        }

        self::$isLogging = true;

        try {
            $request = request();
            $user = Auth::user();

            $clase = get_class($e);
            $nivel = $nivel ?? self::resolveSeverity($e);

            // Compute hash for identifying recurring errors
            $hash = md5($e->getFile() . ':' . $e->getLine() . ':' . $clase);

            // Extract sanitized request data
            $requestData = null;
            $headers = null;

            if ($request) {
                $rawInput = $request->except(self::$sensitiveFields);
                $requestData = self::sanitize($rawInput);

                $headers = [
                    'host' => $request->header('host'),
                    'user-agent' => $request->header('user-agent'),
                    'accept' => $request->header('accept'),
                    'content-type' => $request->header('content-type'),
                    'referer' => $request->header('referer'),
                ];
            }

            if (!empty($extraContext)) {
                $requestData = array_merge($requestData ?? [], ['_extra_context' => $extraContext]);
            }

            return LogError::create([
                'nivel' => strtoupper($nivel),
                'clase_error' => $clase,
                'mensaje' => $e->getMessage() ?: ('Excepción ' . class_basename($e)),
                'codigo' => (string) $e->getCode(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request?->fullUrl(),
                'metodo_http' => $request?->method() ?? 'CLI',
                'ip_address' => $request?->ip() ?? '127.0.0.1',
                'user_agent' => $request?->userAgent() ?? 'Sistema / CLI',
                'request_data' => $requestData,
                'headers' => $headers,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Invitado / CLI',
                'user_email' => $user?->email,
                'estado' => 'pendiente',
                'hash' => $hash,
            ]);
        } catch (Throwable $loggingError) {
            Log::error("ErrorLogService falló al registrar excepción en BD: " . $loggingError->getMessage());
            return null;
        } finally {
            self::$isLogging = false;
        }
    }

    /**
     * Determine severity level based on exception class or code.
     */
    protected static function resolveSeverity(Throwable $e): string
    {
        if ($e instanceof \Illuminate\Database\QueryException || $e instanceof \PDOException) {
            return 'CRITICAL';
        }

        if ($e instanceof \Error || $e instanceof \ParseError || $e instanceof \TypeError) {
            return 'CRITICAL';
        }

        if ($e instanceof NotFoundHttpException) {
            return 'WARNING';
        }

        return 'ERROR';
    }

    /**
     * Sanitize array values recursively.
     */
    protected static function sanitize(array $data): array
    {
        foreach ($data as $key => $val) {
            if (in_array(strtolower($key), self::$sensitiveFields, true)) {
                $data[$key] = '********';
            } elseif (is_array($val)) {
                $data[$key] = self::sanitize($val);
            }
        }

        return $data;
    }
}
