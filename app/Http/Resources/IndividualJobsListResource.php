<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndividualJobsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'applicant_id' => $this->id,
            'full_name'    => $this->full_name,
            'email'        => $this->email,
            'phone'        => $this->cell_number,
            'resume'       => $this->resume ? asset($this->resume): null,
            'applied_at'   => $this->created_at?->diffForHumans(),
            'employee_image' => $this->employee ? asset($this->employee->image_url) : null,
        ];
    }
}
