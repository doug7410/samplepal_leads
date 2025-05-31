<?php

namespace App\Http\Requests;

use App\Models\CampaignContact;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignContactStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                'in:'.implode(',', [
                    CampaignContact::STATUS_PENDING,
                    CampaignContact::STATUS_PROCESSING,
                    CampaignContact::STATUS_SENT,
                    CampaignContact::STATUS_DELIVERED,
                    CampaignContact::STATUS_OPENED,
                    CampaignContact::STATUS_CLICKED,
                    CampaignContact::STATUS_RESPONDED,
                    CampaignContact::STATUS_BOUNCED,
                    CampaignContact::STATUS_FAILED,
                    CampaignContact::STATUS_UNSUBSCRIBED,
                    CampaignContact::STATUS_CANCELLED,
                    CampaignContact::STATUS_DEMO_SCHEDULED,
                ]),
            ],
        ];
    }
}
