<?php

namespace App\Http\Resources;

use App\Models\Admin\Compolist;
use App\Models\Admin\Component;
use App\Models\Admin\ComponentsSet;
use App\Models\Admin\Machine;
use App\Models\Admin\Machinecompo;
use App\Models\Admin\MainPart;
use App\Models\Admin\Product;
use App\Models\Admin\Software;
use App\Models\Admin\SupplyEngine;
use App\Models\SoftMixture;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */




    public function toArray(Request $request): array
    {



        $blend = $this->blend ? json_decode($this->blend, true) : [];
        $machine = Machine::find($this->machine_machine_model_id);
$main = MainPart::find($machine->main_part_main_code_id);
$supplys = collect(json_decode($main->supply_engine));
$compos = collect(json_decode($machine->machine_component));
$componentIds = $compos->pluck("id");

$response = [];
$index = 1; // Initialize index

foreach ($blend as $mcomponent) {
    $componentId = $mcomponent['id'];
    $componentIndex = null;
    $isExist = false; // Initialize isExist

    // Find the component index
    foreach ($compos as $i => $component) { // Use $i to find the index
        if ($component->id == $componentId) {
            $componentIndex = $i;
            break;
        }
    }

    if ($componentIndex !== null && isset($supplys[$componentIndex])) {
        $supply = SupplyEngine::find($supplys[$componentIndex]->id);

        if ($supply !== null) {
            // Check if component exists
            if ($componentId == 19) {
                $isExist = true;
            } else {
                $compolists = Compolist::where('component_name_id', $componentId)->get();

                foreach ($compolists as $compolist) {
                    $machinecompo = Machinecompo::where('machine_serial_number_id', $request->machine_id)
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

            if (!$component) {
                return $this->returnError(__('Component not found!'));
            }

            // Determine component data
            if ($component->component_carrier == 1) {
                $componentData = [
                    'unit' => '%',
                    'minimum' => '0',
                    'maximum' => '100',
                    'default' => '0',
                    'mainValue' => '100',
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
                'index' => $componentIndex, // Add component index
                'componentId' => $componentId,
                'component' => $component->name, // Add component name
                'value' => $mcomponent['value'], // Add value
                'unit' => $mcomponent['unit'], // Add unit
                'volume' => $mcomponent['volume'], // Add volume
                'componentData' => $componentData,
                'isExist' => $isExist,
            ];
        }
    }
}

// Retrieve outlet information
$outlet = $machine ? json_decode($machine->outlet, true) : null;

return [

        'id' => $this->id,
        'name' => $this->name,
        'dose' => $this->dose,
        'unit' => $this->unit,
        'price' => $this->price,
        'target' => $this->target,
        'recommended_use' => $this->recommended_use,
        'components' => $response, // Return the response array
        'outlet' => $outlet,

];


    }
}

    //     $mixture = Product::find($this->id);
    //     $mixtureData = collect(json_decode($mixture->blend));
    //     $compoMixIds = $mixtureData->pluck("id");

    //     $machine = Machine::find($mixture->machine_machine_model_id);
    //     $main = MainPart::find($machine->main_part_main_code_id);
    //     $supplys = collect(json_decode($main->supply_engine));
    //     $compos = collect(json_decode($machine->machin_component));
    //     $componentIds = $compos->pluck("id");

    //     $index = 1;
    //     $response = [];
    //     $maxDelay = 0;

    //     foreach ($mixtureData as $mcomponent) {
    //         $componentId = $mcomponent->id;
    //         $componentIndex = null;
    //         $isExist = false; // Initialize isExist




    //         foreach ($compos as $index => $component) {
    //             if ($component->id == $componentId) {
    //                 $componentIndex = $index;
    //                 break;
    //             }
    //         }

    //         if ($componentIndex !== null && isset($supplys[$componentIndex])) {
    //             $supply = SupplyEngine::find($supplys[$componentIndex]->id);

    //             if ($supply !== null) {



    //                 if ($componentId == 6) {
    //                             $isExist = true;
    //                         } else {
    //                             $compolists = Compolist::where('component_compo_name_id', $componentId)->get();

    //                             foreach ($compolists as $compolist) {
    //                                 $machinecompo = Machinecompo::where('machine_machine_serial_id', $request->machine_id)
    //                                     ->where('machine_compo_code', $compolist->compo_code)
    //                                     ->first();

    //                                 if ($machinecompo) {
    //                                     $isExist = true;
    //                                     break;
    //                                 }
    //                             }

    //                         }



    //                 $flowRate = $supply->flow_rate;
    //                 $flowRotation = $supply->flow_rotation;
    //                 $minVoltage = $supply->min_voltage;
    //                 $speed = $supply->engine_speed;
    //                 $runningVoltage = $supply->engine_voltage;
    //                 $volume = $mcomponent->volume;
    //                 $unit = $mcomponent->unit;

    //                 $delay = ($volume / $flowRate) * 60000;

    //                 $componentData = [
    //                     'index' => $componentIndex,
    //                     'componentId' => $componentId,
    //                     'volume' => $volume,
    //                     'unit' => $unit,
    //                     'flowRate' => $flowRate,
    //                     'flowRotation' => $flowRotation,
    //                     'minVoltage' => $minVoltage,
    //                     'speed' => $speed,
    //                     'runningVoltage' => $runningVoltage,
    //                     'delay' => (integer)$delay,
    //                     'isExist' => $isExist,
    //                 ];

    //                 $response[] = $componentData;

    //                 if ($isExist && $delay > $maxDelay) {
    //                     $maxDelay = $delay;
    //                 }



    //         }
    //     }
    //  }

    //     if ($main->main_software == "Standard") {
    //         foreach ($response as &$component) {
    //             if ($component['isExist']) { // Calculate only if isExist is true
    //             $component['voltageVolume'] = number_format($component['runningVoltage'], 3);
    //             $component['timeVoltage'] = (integer)(($component['volume'] / $component['flowRate']) * 60000);
    //         }
    //     }
    // }elseif ($main->main_software == "PWM") {
    //         $delays = array_column(array_slice($response, 1), 'delay');
    //         $maxDelay = count($delays) > 0 ? max($delays) : 0;

    //         foreach ($response as $key => &$component) {
    //             if ($component['isExist']) { // Calculate only if isExist is true
    //             if ($key == 0) {
    //                 $firstDelay = $component['delay'];
    //                 $component['maxDelay'] = $maxDelay;

    //                 if ($firstDelay < $maxDelay + 1000) {
    //                     $requiredTime = $maxDelay + 1000;
    //                 } else {
    //                     $requiredTime = $component['delay'];
    //                 }
    //             } else {
    //                 $requiredTime = $maxDelay;
    //             }

    //             $component['requiredTime'] = (integer)$requiredTime;
    //             $rotationVolume = $component['volume'] / $component['flowRotation'];
    //             $voltageVolume = ($rotationVolume / $requiredTime) * 60000 * $component['runningVoltage'] / $component['speed'];

    //             if ($voltageVolume < $component['minVoltage']) {
    //                 $voltageVolume = $component['minVoltage'];
    //             } else {
    //                 if ($voltageVolume > $component['runningVoltage']) {
    //                     $voltageVolume = $component['runningVoltage'];
    //                 }
    //             }

    //             $component['voltageVolume'] = number_format($voltageVolume, 3);
    //             $component['rotationVolume'] = number_format($rotationVolume, 3);

    //             $timeVoltage = ($component['runningVoltage'] / $voltageVolume) * $rotationVolume / $component['speed'] * 60000;
    //             $component['timeVoltage'] = (integer)$timeVoltage;
    //         }
    //     }

    //         $timeVoltages = array_column(array_slice($response, 1), 'timeVoltage');
    //         $minTimeVoltage = count($timeVoltages) > 0 ? min($timeVoltages) : 0;

    //         if (count($response) > 0 && $response[0]['delay'] > $maxDelay + 1000 && $response[0]['timeVoltage'] < $minTimeVoltage) {
    //             $component['minTimeVoltage'] = $minTimeVoltage;
    //             $requiredTime = $minTimeVoltage;

    //             foreach ($response as $key => &$component) {
    //                 if ($component['isExist'] && $key != 0) {
    //                     $rotationVolume = $component['volume'] / $component['flowRotation'];
    //                     $voltageVolume = ($rotationVolume / $requiredTime) * 60000 * $component['runningVoltage'] / $component['speed'];

    //                     $component['requiredTime'] = (integer)$requiredTime;

    //                     if ($voltageVolume < $component['minVoltage']) {
    //                         $voltageVolume = $component['minVoltage'];
    //                     } else {
    //                         if ($voltageVolume > $component['runningVoltage']) {
    //                             $voltageVolume = $component['runningVoltage'];
    //                         }
    //                     }
    //                     $component['voltageVolume'] = number_format($voltageVolume, 3);

    //                     $timeVoltage = ($component['runningVoltage'] / $voltageVolume) * $rotationVolume / $component['speed'] * 60000;
    //                     $component['timeVoltage'] = (integer)$timeVoltage;
    //                 }
    //             }
    //         }
    //     }







