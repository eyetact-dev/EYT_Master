<?php

namespace App\Http\Resources;


use App\Models\Admin\Machine;
use App\Models\Admin\Software;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id'        => $this->id,
            'username'      => $this->username,
            'email'     => $this->email,
            'machine_id' => Machine::where('customer_id', auth()->user()->id)->first()->id,


        ];
    }
}
