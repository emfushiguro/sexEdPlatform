<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeInstructorPlanRequest extends FormRequest
{
	public function authorize(): bool
	{
		$user = $this->user();

		return $user !== null
			&& (
				$user->hasRole('instructor')
				|| $user->can('access instructor panel')
			);
	}

	public function rules(): array
	{
		return [
			'plan_id' => [
				'required',
				'integer',
				Rule::exists('subscription_plans', 'id')->where(function ($query) {
					$query->where('plan_audience', 'instructor')
						->where('is_active', true)
						->whereNull('archived_at');
				}),
			],
		];
	}

	public function messages(): array
	{
		return [
			'plan_id.exists' => 'The selected instructor plan is unavailable for checkout.',
		];
	}
}
