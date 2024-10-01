<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MixtureDataResource;
use App\Models\Admin\Component;
use App\Models\Admin\ComponentsSet;
use App\Models\Admin\Machine;
use App\Models\Admin\MainPart;
use App\Models\Admin\Mix;
use App\Models\Admin\Mixture;
use App\Models\Admin\Software;
use App\Models\Admin\SupplyEngine;
use App\Models\Order;
use App\Models\SoftMixture;
use Illuminate\Http\Request;
use App\Repositories\Repository;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Controllers\ApiController;
use App\Http\Resources\MixtureResource;

class MixtureController extends ApiController
{
    public function __construct()
    {
        $this->resource = MixtureResource::class;
        $this->model = app(Mixture::class);
        $this->repositry = new Repository($this->model);
    }

    public function save(Request $request)
    {

        $machine = Software::find($request->machine_id);
        $model = new Mixture();
        $model->components_set_id = $machine->components_set_id;
        $model->mix_name = $request->name;
        $model->category_id = $request->category_id ?? NULL;

        $mixComponenets = [];
        $index = 1;
        foreach ($request->mix_component as $item) {
            if (!isset ($item['name'])) {
                return $this->returnError(__('Invalid component name.'));
            }

            $mixComponenets[$index] = [
                'id'=> $item['id'],
                'name' => $item['name'],
                'value' => $item['value'],

            ];
            $index++;
        }


        $model->mix_component = $mixComponenets;
        $model->user_id = auth()->user()->id;
        $model->save();

        if ($model) {


            $softMix= new SoftMixture();
            $softMix->software_id =  $machine->id;
            $softMix->mixture_id = $model->id;
            $softMix->save();



            return $this->returnData('data', new MixtureDataResource($model), __('Succesfully'));
        }

        return $this->returnError(__('Sorry! Failed to create !'));




    }

    public function edit($id, Request $request)
    {


        $machine = Software::find($request->machine_id);
        $model = Mixture::find($id);
        $model->components_set_id = $machine->components_set_id;
        $model->mix_name = $request->name;
        $model->category_id = $request->category_id ?? NULL;


        if (isset ($request->mix_component)) {

            $mixComponenets = [];
            $index = 1;
            foreach ($request->mix_component as $item) {
                if (!isset ($item['name'])) {
                    return $this->returnError(__('Invalid component name.'));
                }

                $mixComponenets[$index] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'value' => $item['value'],

                ];
                $index++;
            }



            $model->mix_component = $mixComponenets;

        }


        $model->user_id = auth()->user()->id;
        $model->save();

        if ($model) {
            return $this->returnData('data', new MixtureDataResource($model), __('Succesfully'));
        }

        return $this->returnError(__('Sorry! Failed to create !'));


    }

    public function mixtures($id)
    {
        $machine = Software::find($id);
        $component_id = ComponentsSet::where('main_part_id', $machine->main_part_id)
            ->first()->id;
        // dd($component_id);


        $mixtures = Mixture::where('components_set_id', $component_id)
            ->where(function ($query) use ($machine) {
                $query->where('customer_id', auth()->user()->id)
                    ->orWhere('user_id', auth()->user()->id)
                    ->orWhere('assign_id', auth()->user()->id);

                if ($machine->customer_group_id !== NULL) {
                    $query->orWhere('customer_group_id', $machine->customer_group_id);
                }
            })
            ->get();


        // dd($mixtures);




        return $this->returnData('data', MixtureDataResource::collection($mixtures), __('Get successfully'));

    }


    public function view($id)
    {
        $model = $this->repositry->getByID($id);

        if ($model) {
            return $this->returnData('data', new MixtureDataResource( $model ), __('Get  succesfully'));
        }

        return $this->returnError(__('Sorry! Failed to get !'));
    }



    public function getMixByCategory(Request $request)
    {


        $machine = Software::find($request->machine_id);




        $mixtures = $machine->mixtures()->where('components_set_id', $machine->components_set_id)
                                      ->where('category_id',$request->category_id)
                                      ->get();
                                    //   ->unique();





        return $this->returnData('data', MixtureDataResource::collection($mixtures), __('Get successfully'));

    }

    public function makeOrder(Request $request)
    {


       $order= new Order();
       $order->software_id = $request->machine_id;
       $order->mixture_id = $request->mixture_id;
       $order->save();


       return $this->returnSuccessMessage('Done');

    }




    public function getResults($mixId)
    {
        // Find the mixture by ID
        $mixture = Mix::find($mixId);
        $mixtureData = collect(json_decode($mixture->machine_blend));
        $compoMixIds =$mixtureData->pluck("id");



        // Find the machine
        $machine = Machine::find($mixture->machine_machine_model_id);

        // Get the main part and its supplies
        $main = MainPart::find($machine->main_part_main_code_id);
        $supplys = collect(json_decode($main->supply_engine));
        $compos = collect(json_decode($machine->machin_component));
        $componentIds = $compos->pluck("id");

        // dd($compos);
        $index = 1;
        $response = [];
        $maxDelay = 0;

        foreach ($mixtureData as $mcomponent) {
            $componentId = $mcomponent->id;

            $componentIndex = null;
            foreach ($compos as $index => $component) {
                if ($component->id == $componentId) {
                    $componentIndex = $index;
                    break;
                }
            }

            if ($componentIndex !== null && isset($supplys[$componentIndex])) {
                $supply = SupplyEngine::find($supplys[$componentIndex]->id);

                if ($supply !== null) {
                    $flowRate = $supply->flow_rate;
                    $flowRotation = $supply->flow_rotation;
                    $minVoltage = $supply->min_voltage;
                    $speed = $supply->engine_speed;
                    $runningVoltage = $supply->engine_voltage;
                    $volume = $mcomponent->volume;

                    $delay = ($volume / $flowRate) * 60000;

                    $response[] = [
                        'index' => $componentIndex,
                        'componentId' => $componentId,
                        'volume' => $volume,
                        'flowRate' => $flowRate,
                        'flowRotation' => $flowRotation,
                        'minVoltage' => $minVoltage,
                        'speed' => $speed,
                        'runningVoltage' => $runningVoltage,
                        'delay' => (integer)$delay,
                    ];

                    if ($delay > $maxDelay ) {
                        $maxDelay = $delay;
                    }
                }
            }
        }

    // dd($response);

 if ($main->main_software == "Standard") {
    foreach ($response as &$component) {
        $component['voltageVolume'] = number_format($component['runningVoltage'], 3);
        $component['timeVoltage'] = (integer)(($component['volume'] / $component['flowRate']) * 60000);
    }
} elseif ($main->main_software == "PWM") {
    $delays = array_column(array_slice($response, 1), 'delay');
    $maxDelay = count($delays) > 0 ? max($delays) : 0;



    foreach ($response as $key => &$component) {
        if ($key == 0) {
            $firstDelay = $component['delay'];

            $component['maxDelay'] = $maxDelay;

            if ($firstDelay < $maxDelay + 1000) {
                $requiredTime = $maxDelay + 1000;



            } else {
                $requiredTime = $component['delay'];

            }
        } else {
            $requiredTime = $maxDelay;

        }

        $component['requiredTime'] = (integer)$requiredTime;

        $rotationVolume = $component['volume'] / $component['flowRotation'];
        $voltageVolume = ($rotationVolume / $requiredTime) * 60000 * $component['runningVoltage'] / $component['speed'];

        if ($voltageVolume < $component['minVoltage']) {
            $voltageVolume = $component['minVoltage'];
        } else{
            if ($voltageVolume > $component['runningVoltage']) {
            $voltageVolume = $component['runningVoltage'];
        }
    }

        $component['voltageVolume'] = number_format($voltageVolume,3);



        $component['rotationVolume'] = number_format($rotationVolume,3);

        $timeVoltage = ($component['runningVoltage'] / $voltageVolume) *   $rotationVolume / $component['speed'] * 60000;
        $component['timeVoltage'] = (integer)$timeVoltage;
    }

    $timeVoltages = array_column(array_slice($response, 1), 'timeVoltage');
    $minTimeVoltage = count($timeVoltages) > 0 ? min($timeVoltages) : 0;



    if (count($response) > 0 &&  $response[0]['delay'] > $maxDelay + 1000 && $response[0]['timeVoltage'] < $minTimeVoltage) {

        $component['minTimeVoltage'] = $minTimeVoltage;

        $requiredTime = $minTimeVoltage;
        foreach ($response as $key => &$component) {
            if ($key != 0) {
                $rotationVolume = $component['volume'] / $component['flowRotation'];
                $voltageVolume = ($rotationVolume / $requiredTime) * 60000 * $component['runningVoltage'] / $component['speed'];


                $component['requiredTime'] = (integer)$requiredTime;

                if ($voltageVolume < $component['minVoltage']) {
                    $voltageVolume = $component['minVoltage'];
                } else
                {if ($voltageVolume > $component['runningVoltage']) {
                    $voltageVolume = $component['runningVoltage'];
                }

            }
                $component['voltageVolume'] = number_format($voltageVolume,3);

                $timeVoltage = ($component['runningVoltage'] / $voltageVolume) *   $rotationVolume / $component['speed'] * 60000;
                $component['timeVoltage'] = (integer)$timeVoltage;
            }
        }
    }
}

return response()->json($response);
}

}
