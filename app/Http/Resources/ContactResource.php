<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Contact
 */
class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'prefix' => $this->prefix,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'suffix' => $this->suffix,
            'nickname' => $this->nickname,
            'maiden_name' => $this->maiden_name,
            'gender' => $this->gender?->name,
            'pronoun' => $this->pronoun?->name,
            'company' => $this->company?->name,
            'job_position' => $this->job_position,
            'contact_information' => $this->contactInformations->map(fn ($contactInformation) => [
                'type' => $contactInformation->contactInformationType?->name,
                'data' => $contactInformation->data,
            ]),
            'addresses' => $this->addresses->map(fn ($address) => [
                'type' => $address->addressType?->name,
                'line_1' => $address->line_1,
                'line_2' => $address->line_2,
                'city' => $address->city,
                'province' => $address->province,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
            ]),
            'important_dates' => $this->importantDates->map(fn ($importantDate) => [
                'label' => $importantDate->label,
                'day' => $importantDate->day,
                'month' => $importantDate->month,
                'year' => $importantDate->year,
            ]),
            'labels' => $this->labels->pluck('name'),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
