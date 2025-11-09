<?php

namespace App\Rules;

use App\Services\FilterService;
use Illuminate\Contracts\Validation\Rule;

class ValidDueDateFilter implements Rule
{
    protected $filterService;
    protected $validValues;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->filterService = app(FilterService::class);
        $this->validValues = $this->filterService->getValidDueDateFilterValues();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true;
        }

        return $this->filterService->isValidDueDateFilter($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected due date filter is invalid. Valid options are: ' . implode(', ', $this->validValues);
    }
}
