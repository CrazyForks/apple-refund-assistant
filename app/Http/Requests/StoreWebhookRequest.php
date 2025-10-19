<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Validate that the request body is valid JSON
            // The actual content is validated by Apple's SDK
        ];
    }

    /**
     * Validate that the request has valid JSON content
     */
    protected function prepareForValidation(): void
    {
        $content = $this->getContent();
        
        if (empty($content)) {
            throw new \InvalidArgumentException('Request body cannot be empty');
        }

        // Validate JSON structure
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        // Validate required Apple notification fields
        if (!isset($data['notificationType']) || !isset($data['notificationUUID'])) {
            throw new \InvalidArgumentException('Missing required notification fields');
        }
    }
}

