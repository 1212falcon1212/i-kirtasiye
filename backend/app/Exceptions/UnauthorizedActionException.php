<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedActionException extends Exception
{
    protected string $action;
    protected ?string $resource;

    public function __construct(
        string $action,
        ?string $resource = null,
        string $message = ''
    ) {
        $this->action = $action;
        $this->resource = $resource;

        if (empty($message)) {
            $message = $resource
                ? "Bu islemi ({$action}) {$resource} uzerinde yapma yetkiniz yok."
                : "Bu islemi ({$action}) yapma yetkiniz yok.";
        }

        parent::__construct($message, 403);
    }

    /**
     * Get the action that was attempted.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the resource on which the action was attempted.
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'unauthorized_action',
            'details' => [
                'action' => $this->action,
                'resource' => $this->resource,
            ],
        ], 403);
    }
}
