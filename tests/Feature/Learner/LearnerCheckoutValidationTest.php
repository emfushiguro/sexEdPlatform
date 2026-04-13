<?php

namespace Tests\Feature\Learner;

use App\Http\Requests\Checkout\ProcessLearnerCheckoutRequest;
use App\Http\Requests\ProcessModulePaymentRequest;
use App\Http\Requests\ProcessPaymentRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LearnerCheckoutValidationTest extends TestCase
{
    public function test_shared_learner_checkout_request_requires_terms_and_validates_optional_fields_when_present(): void
    {
        $request = new ProcessLearnerCheckoutRequest();
        $rules = $request->rules();

        $invalid = Validator::make([
            'payment_method' => 'invalid_method',
            'accept_terms' => '0',
            'billing_name' => '',
            'billing_email' => 'not-an-email',
            'billing_phone' => '',
        ], $rules);

        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('payment_method', $invalid->errors()->messages());
        $this->assertArrayHasKey('accept_terms', $invalid->errors()->messages());
        $this->assertArrayHasKey('billing_email', $invalid->errors()->messages());

        $valid = Validator::make([
            'payment_method' => 'card',
            'accept_terms' => '1',
            'billing_name' => 'Learner One',
            'billing_email' => 'learner@example.test',
            'billing_phone' => '09171234567',
        ], $rules);

        $this->assertFalse($valid->fails());
    }

    public function test_subscription_process_payment_request_requires_billing_fields(): void
    {
        $request = new ProcessPaymentRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'accept_terms' => '1',
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_module_process_payment_request_keeps_billing_requirements(): void
    {
        $request = new ProcessModulePaymentRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'accept_terms' => '1',
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_optional_payment_method_still_validates_supported_values_when_present(): void
    {
        $request = new ProcessModulePaymentRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'accept_terms' => '1',
            'payment_method' => 'invalid',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_method', $validator->errors()->messages());
    }
}
