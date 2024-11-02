<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'name' => $this->name,
            'combined_component' => $this->combined_component,
            'element'=>json_decode($this->compo_element, true),
            'form' => $this->form,
            'category'=>json_decode($this->compo_category, true),
            'carrier'=>$this->carrier,
            'parent'=>$this->classification_parent?->parent,

        ];
    }
}
