<?php

namespace Tests\Feature\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiValidationEnvelopeTest extends TestCase
{
    public function test_api_form_request_returns_standard_validation_envelope(): void
    {
        Route::post('/api/v1/test/validation-envelope', function (DummyValidationRequest $request) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => $request->validated(),
                'errors' => null,
            ]);
        });

        $response = $this->postJson('/api/v1/test/validation-envelope', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('data', null)
            ->assertJsonStructure([
                'errors' => ['required_field'],
            ]);
    }
}

class DummyValidationRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'required_field' => ['required', 'string'],
        ];
    }
}
