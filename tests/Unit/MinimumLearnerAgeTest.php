<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\StoreMinorRegistrationRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Validator;

class MinimumLearnerAgeTest extends TestCase
{
    public function test_personal_and_guardian_request_validation_share_the_age_ten_minimum(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 15));
        foreach (['2016-11-16' => false, '2016-11-15' => true, '2009-11-15' => true, '2008-11-15' => true, '1920-11-15' => true] as $birthdate => $eligible) {
            foreach ([RegisterRequest::class, StoreMinorRegistrationRequest::class] as $class) {
                $request = new $class;
                $request->setUserResolver(fn () => null);
                $request->merge(['first_name' => 'Test', 'last_name' => 'Learner', 'birthdate' => $birthdate, 'guardian_email' => 'guardian@example.com']);
                $validator = Validator::make($request->all(), $request->rules(), $request->messages());
                if ($request instanceof RegisterRequest) {
                    $request->withValidator($validator);
                }
                $this->assertSame($eligible, $validator->passes(), $class.': '.$birthdate.' '.json_encode($validator->errors()->toArray()));
            }
        }
    }
}
