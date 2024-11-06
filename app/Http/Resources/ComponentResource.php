<?php

namespace App\Http\Resources;

use App\Models\Admin\Compolist;
use App\Models\Admin\Component;
use App\Models\Admin\Machinecompo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     *
     *
     */



     public function toArray(Request $request): array
{
    $compoElement = json_decode($this->compo_element, true);
    $compoCategory = json_decode($this->compo_category, true);



    $response = [];
    $index = 1; // Initialize index



        $componentId = $this->id;
        $componentIndex = null;
        $isExist = false; // Initialize isExist





        // Check if component exists
        if ($componentId == 1) {
            $isExist = true;
        } else {
            $compolists = Compolist::where('component_name_id', $componentId)->get();

            foreach ($compolists as $compolist) {
                $machinecompo = Machinecompo::where('machine_serial_number_id', $this->machine_machine_model_id)
                    ->where('machine_compo_code', $compolist->compo_code)
                    ->first();

                if ($machinecompo) {
                    $isExist = true;
                    break;
                }
            }
        }



        // Fetch the component
        $component = Component::find($componentId);





        if ($component->component_carrier == 1) {
            $componentData = [
                'unit' => 'ml',
                'minimum' => '0',
                'maximum' => '1000',
                'default' => '0',
                'mainValue' => '1000',

            ];
        } else {
            if ($component->combined_component == 1) {
                $componentData = [
                    'unit' => '%',
                    'minimum' => '0',
                    'maximum' => '50',
                    'default' => '0',
                    'mainValue' => '100',

                ];
            } else {
                $compo_element = json_decode($component->compo_element, true);

                $compo_element = array_combine(
                    array_map('intval', array_keys($compo_element)),
                    array_values($compo_element)
                );

                foreach ($compo_element as $item) {
                    $componentData = [
                        'unit' => $item['unit'],
                        'minimum' => '0',
                        'maximum' => (string)($item['value'] * 0.5),
                        'default' => '0',
                        'mainValue' => $item['value'],

                    ];
                    break;
                }
            }
        }

        // Add component data to response
        $response[] = [

            'componentId' => $componentId,
            'component' => $component->name, // Add component name
            'value' => $componentData['mainValue'], // Add main value
            'unit' => $componentData['unit'], // Add unit
            'componentData' => $componentData,
            'isExist' => $isExist,
        ];


    return [
        'id' => $this->id,
        'name' => $this->name,
        'combined_component' => $this->combined_component,
        'element' => $compoElement,
        'form' => $this->form,
        'category' => $compoCategory,
        'carrier' => $this->carrier,
        'parent' => $this->classification_parent?->parent,
        'index' => $this->index, // Return the index
        'componentData' => $response, // Return the response array

    ];
}


    // public function toArray(Request $request): array
    // {
    //     return [

    //         'id' => $this->id,
    //         'name' => $this->name,
    //         'combined_component' => $this->combined_component,
    //         'element'=>json_decode($this->compo_element, true),
    //         'form' => $this->form,
    //         'category'=>json_decode($this->compo_category, true),
    //         'carrier'=>$this->carrier,
    //         'parent'=>$this->classification_parent?->parent,

    //     ];
    // }
}
