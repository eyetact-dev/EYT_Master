<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MyComponentsResource;
use App\Models\Admin\Compolist;
use App\Models\Admin\Component;
use App\Models\Admin\ComponentsSet;
use App\Models\Admin\Element;
use App\Models\Admin\Machine;
use App\Models\Admin\Machinecompo;
use App\Models\Admin\MainPart;
use App\Models\Admin\Software;
use Illuminate\Http\Request;
use App\Repositories\Repository;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ComponentResource;
use App\Http\Resources\ElementResource;
use App\Http\Resources\ComponentDataResource;


class ComponentController extends ApiController
{
    public function __construct()
    {
        $this->resource = ComponentResource::class;
        $this->model = app(Component::class);
        $this->repositry = new Repository($this->model);
    }

    public function save(Request $request)
    {

        $machine = Software::find($request->machine_id);

        // dd($machine->main_part_id);

        $model = new Component();
        $model->compo_name = $request->name;
        $model->element_id = $request->element_id;
        $model->compo_concentration = $request->concentration;
        // $model->unit_id = $request->unit_id;
        $unit_id = Element::find($request->element_id)->unit_id;
        $model->unit_id = $unit_id;
        $model->compo_carrier = $request->compo_carrier;
        $model->main_part_id = $machine->main_part_id;



        // $decodedCategories = is_array($request->compo_category) ? $request->compo_category : json_decode($request->compo_category, true);
        // if (!is_array($decodedCategories)) {
        //     return $this->returnError(__('Invalid compo_category data.'));
        // }

        $setCategories = [];
        $index = 1;
        foreach ($request->compo_category as $item) {
            if (!isset($item['name'])) {
                return $this->returnError(__('Invalid category name.'));
            }

            $setCategories[$index] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'minimum' => $item['minimum'],
                'maximum' => $item['maximum'],
                'default' => $item['default'],
            ];
            $index++;
        }



        $model->compo_category = $setCategories;
        $model->user_id = auth()->user()->id;
        $model->save();


        if ($model) {


            $component_set = ComponentsSet::where('main_part_id', $machine->main_part_id)
                ->where('user_id', auth()->user()->id)
                ->latest()
                ->first();

            if ($component_set) {
                $existingComponents = json_decode($component_set->set_component, true);

                if (!is_array($existingComponents)) {
                    $existingComponents = [];
                }

                $newComponent = [
                    'id' => $model->id,
                    'name' => $model->compo_name,
                ];

                if (!is_array($newComponent) || empty($newComponent['name'])) {
                    return $this->returnError(__('Invalid component data.'));
                }

                $maxNumericKey = max(array_keys($existingComponents));

                $newNumericKey = $maxNumericKey + 1;


                $existingComponents[$newNumericKey] = $newComponent;


                // $encodedJson = json_encode($existingComponents, JSON_UNESCAPED_UNICODE);

                $component_set->set_component = $existingComponents;
                $component_set->save();


            }

            if (!$component_set) {

                $set = new ComponentsSet();
                $set->compo_set_name = "user selection";
                $set->main_part_id = $machine->main_part_id;



                $setComponents = [];

                $index = 1;
                $setComponents[$index] = [

                    'id' => $model->id,
                    'name' => $model->compo_name,
                ];

                $set->set_component = $setComponents;
                $set->user_id = auth()->user()->id;
                $set->save();

                $machine->components_set_id = $set->id;
                $machine->save();


            }





            return $this->returnData('data', new ComponentDataResource($model), __('Succesfully'));
        }

        return $this->returnError(__('Sorry! Failed to create !'));

    }

    public function edit($id, Request $request)
    {



        $model = Component::find($id);
        $machine = Software::find($request->machine_id);


        if ($model) {
            // dd($machine->main_part_id);

            $model->compo_name = $request->name;
            $model->element_id = $request->element_id;
            $model->compo_concentration = $request->concentration;
            $model->main_part_id = $machine->main_part_id;


            // $model->unit_id = $request->unit_id;
            $unit_id = Element::find($request->element_id)->unit_id;
            $model->unit_id = $unit_id;
            $model->compo_carrier = $request->compo_carrier;


            if (isset($request->compo_category)) {

                $setCategories = [];
                $index = 1;
                foreach ($request->compo_category as $item) {
                    if (!isset($item['name'])) {
                        return $this->returnError(__('Invalid category name.'));
                    }

                    $setCategories[$index] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'minimum' => $item['minimum'],
                        'maximum' => $item['maximum'],
                        'default' => $item['default'],
                    ];
                    $index++;
                }



                $model->compo_category = $setCategories;

            }



            if (isset($request['name'])) {

                $component_set = ComponentsSet::where('main_part_id', $model->main_part_id)
                    ->where('user_id', auth()->user()->id)
                    ->latest()
                    ->first();

                if ($component_set) {
                    $existingComponents = json_decode($component_set->set_component, true);

                    if (!is_array($existingComponents)) {
                        $existingComponents = [];
                    }

                    $collection = collect($existingComponents);


                    $collection = $collection->map(function ($component) use ($request, $model) {
                        if ($component['id'] == $model->id) {


                            $component['name'] = $request->name;


                        }
                        return $component;
                    });

                    // $encodedJson = $collection->toJson();

                    $component_set->set_component = $collection;
                    $component_set->save();
                }
            }

            $model->save();

            return $this->returnData('data', new ComponentDataResource($model), __('Succesfully'));
        }


        return $this->returnError(__('Sorry! Component not found !'));

    }

    public function components($id)
    {
        $machine = Software::find($id);
        $components = Component::where('main_part_id', $machine->main_part_id)
            ->where(function ($query) use ($machine) {
                $query->where('customer_id', auth()->user()->id)
                    ->orWhere('global', 1)
                    ->orWhere('user_id', auth()->user()->id)
                    ->orWhere('assign_id', auth()->user()->id);

                if ($machine->customer_group_id !== null) {
                    $query->orWhere('customer_group_id', $machine->customer_group_id);
                }
            })
            ->get();



        return $this->returnData('data', ComponentResource::collection($components), __('Get successfully'));

    }

    public function elements()
    {

        $elements = Element::all();



        return $this->returnData('data', ElementResource::collection($elements), __('Get successfully'));

    }

    public function view($id)
    {
        $model = $this->repositry->getByID($id);

        if ($model) {
            return $this->returnData('data', new ComponentDataResource($model), __('Get  succesfully'));
        }

        return $this->returnError(__('Sorry! Failed to get !'));
    }


    public function getComponentsByCategory(Request $request)
    {

        $machine = Software::find($request->machine_id);

        $componentSetId = $machine->components_set_id;
        $catId = $request->category_id;
        $components = collect([]);

        if ($componentSetId) {
            $components_set = ComponentsSet::find($componentSetId);

            if ($components_set) {
                $set_component = json_decode($components_set->set_component);
                $componentsId = collect($set_component)->pluck('id');

                foreach ($componentsId as $componentId) {
                    $component = Component::find($componentId);
                    $compo_category = json_decode($component->compo_category, true);
                    $compo_category_collection = collect($compo_category)->pluck('id');

                    if ($compo_category_collection->contains($catId)) {
                        // if ($compo_category_collection->contains($catId) && !$components->contains('id', $componentId)) {
                        $components->push($component);
                    }
                }
                return $this->returnData('data', ComponentResource::collection($components), __('Get successfully'));

            }
        }

    }



    public function show(Request $request)
    {
        $machine = Software::find($request->machine_id);
        $components_set = ComponentsSet::find($machine->components_set_id);

        $descriptions = [];
        $results = [];
        $delays = [];
        $components = collect([]);
        $matchedComponent = null;

        if ($components_set) {
            $set_component = json_decode($components_set->set_component);
            $componentIds = collect($set_component)->pluck("id");
            $main_pumps = json_decode($machine->main_part->main_pump);

            $main_pumps_array = get_object_vars($main_pumps);

            $indexMap = [];

            foreach ($componentIds as $index => $componentId) {
                $component = Component::find($componentId);
                if ($component) {

                    $description = collect($set_component)->firstWhere('id', $componentId)->describtion;
                    $descriptions[$componentId] = $description;


                    $indexMap[$componentId] = $index + 1;

                    $flowRate = $main_pumps_array[$index + 1]->pump_flow;


                    if ($machine->main_part->main_type != 'Mixing With Carrier') {
                        $result = 1000 / $component->compo_concentration * $component->compo_value;
                    } else {

                        if ($index == 0) {
                            $result = null;
                        } else {
                            $result = 1000 / $component->compo_concentration * $component->compo_value;
                        }
                    }
                    $results[$componentId] = $result;


                    if ($result !== null) {
                        $delay = ($result * 60 / $flowRate) * 1000;
                        $delays[$componentId] = $delay;
                    }

                    $components->push($component);


                    if ($componentId == $request->id) {
                        $matchedComponent = $component;
                    }
                }
            }


            if ($machine->main_part->main_type == 'Mixing With Carrier' && $components->isNotEmpty()) {
                $sumOfOtherResults = collect($results)->filter()->sum();
                $firstComponentId = $components->first()->id;
                $results[$firstComponentId] = 1000 - $sumOfOtherResults;


                $flowRate = $main_pumps_array[1]->pump_flow;
                $delays[$firstComponentId] = ($results[$firstComponentId] * 60 / $flowRate) * 1000;

                if ($firstComponentId == $request->id) {
                    $matchedComponent = $components->first();
                }
            }
        }

        if ($matchedComponent) {
            $matchedComponent->description = $descriptions[$matchedComponent->id] ?? null;
            $matchedComponent->json_index = $indexMap[$matchedComponent->id] ?? null;
            $matchedComponent->result = isset($results[$matchedComponent->id]) ? number_format($results[$matchedComponent->id], 3) : null; // Format to 3 decimal places
            $matchedComponent->delay = isset($delays[$matchedComponent->id]) ? number_format($delays[$matchedComponent->id], 3) : null; // Format to 3 decimal places
        }

        return $this->returnData('data', new MyComponentsResource($matchedComponent), __('Get successfully'));
    }




    public function getMinMaxUnit($componentId)
    {

        $component = Component::find($componentId);


        if (!$component) {
            return $this->returnError(__('Component not found!'));
        }

        if(  $component->compo_carrier == 1){




            $componentData = [
                'unit' => '%',
                'minimum' => '0',
                'maximum' => '100',
                'default' => '0',
                'mainValue' => '100' ,

            ];


        }

        else{

        if($component->combined_component == 1){

            $componentData = [
                'unit' => '%',
                'minimum' => '0',
                'maximum' => '50',
                'default' => '0',
                'mainValue' => '100' ,

            ];



        }

        if($component->combined_component == 0){


            $compo_element = json_decode($component->compo_element, true);

            $compo_element = array_combine(
                array_map('intval', array_keys($compo_element)),
                array_values($compo_element)
            );

            foreach ($compo_element as $item) {

                $componentData = [
                    'unit' => $item['unit'],
                    'minimum' => '0',
                    'maximum' =>(string)($item['value'] * 0.5),
                    'default' => '0',
                    'mainValue' => $item['value'] ,

                ];

                break;
            }


    }

    }
    // dd($componentData);

    return $this->returnSuccessMessage($componentData);

    }





    public function getComponents(Request $request) {
        $machine = Machine::find($request->machine_id);
        $components = collect([]);

        if ($machine) {
            $compos = json_decode($machine->machine_component);
            $componentIds = collect($compos)->pluck("id");

            foreach ($compos as $index => $compo) {
                $component = Component::find($compo->id);


                if ($component) {
                    // $compolists = Compolist::where('component_name_id', $componentId)->get();
                    // $isExist = false;

                    // foreach ($compolists as $compolist) {
                    //     $machinecompo = Machinecompo::where('machine_serial_number_id', $machine->id)
                    //         ->where('machine_compo_code', $compolist->compo_code)
                    //         ->first();

                    //     if ($machinecompo) {
                    //         $isExist = true;
                    //         break;
                    //     }
                    // }

                    // $component->isExist = $isExist;

                    // if ($component->id == 1) {
                    //     $component->isExist = true;
                    // }

                    // if ($component->isExist) {
                    //     $components->push($component);
                    // }

                    $component->index = $index; // Add index to the component

                              $components->push($component);
                }
            }
        }

        return $this->returnData('data', ComponentResource::collection($components), __('Get successfully'));
    }




    public function checkComponent(Request $request)
    {
        $machine = Machine::find($request->machine_id);

        if (!$machine) {
            return $this->returnError(__('Machine not found.'));
        }

        $code = Compolist::where('compo_code', $request->code)->first();

        if (!$code) {
            return $this->returnError(__('This component is not recognized in the system.'));
        }

        $existingMachineCompo = Machinecompo::where('machine_compo_code', 'like', $request->code)->first();

        if ($existingMachineCompo) {
            // Check if the component is associated with another machine
            if ($existingMachineCompo->machine_serial_number_id != $request->machine_id) {
                return $this->returnError(__('This component is already in use in another machine.'));
            }

            // Check if the pin status is 1 for the same pin number in the current machine
            if (
                $existingMachineCompo->machine_serial_number_id == $request->machine_id &&
                $existingMachineCompo->pin_number == $request->pin_number &&
                $existingMachineCompo->pin_status == $request->pin_number && $request->pin_number == 1
            ) {
                return $this->returnError(__('Sorry, this pin is already defined and active for this machine.'));
            }

            // If pin status is 0, update the fields
            if (
                $existingMachineCompo->machine_serial_number_id == $request->machine_id &&
                $existingMachineCompo->pin_number == $request->pin_number &&
                $existingMachineCompo->pin_status == $request->pin_number && $request->pin_number == 0
            ) {
                $existingMachineCompo->update([
                    'machine_compo_code' => null,
                    'main_volume' => 0,
                    'remaining_volume' => 0,
                    'pin_status' => 0,
                ]);
            }
        }

        $component = Component::find($code->component_name_id);
        $compos = json_decode($machine->machine_component);
        $componentIds = collect($compos)->pluck("id");



        // Fetch the machine component entry
        $machineCompo = Machinecompo::where('machine_serial_number_id', $request->machine_id)
            ->where('pin_number', $request->pin_number)
            ->first();

        if ($machineCompo) {
            // If `pin_status` is 0, update fields
            if ($request->pin_status == 0) {
                $machineCompo->update([
                    'machine_compo_code' => null,
                    'main_volume' => null,
                    'remaining_volume' => null,
                    'pin_status' => 0 ,
                ]);
            }
            else{


                 // If no component found, create or update with the requested data
                 $machineCompo->update(
                [
                    'machine_serial_number_id' => $request->machine_id,
                    'pin_number' => $request->pin_number,
                    'machine_compo_code' => $request->code,
                    'pin_status' => 1,
                     'main_volume' => $code->volume,
                     'remaining_volume' => $code->volume,
                ]
            );

            }

        }

        return $this->returnSuccess(__('Component processed successfully.'));
    }


}
