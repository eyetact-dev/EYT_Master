<?php

namespace App\Generators\Views;

use App\Generators\GeneratorUtils;
use App\Models\Attribute;
use App\Models\Crud;
use App\Models\Module;
use App\Models\Multi;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Support\Facades\File;


class IndexViewGenerator
{
    /**
     * Generate an index view.
     *
     * @param array $request
     * @return void
     */
    public function generate(array $request)
    {
        $model = GeneratorUtils::setModelName($request['name'], 'default');
        $path = GeneratorUtils::getModelLocation($request['name']);

        $request['code'] = str()->snake(str_replace(['.', '/', '\\', '-', ' ', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '+', '=', '<', '>', ',', '{', '}', '[', ']', ':', ';', '"', '\''], '', str($request['code'])->lower()));
        // dd($request['code']);
        $code = GeneratorUtils::setModelName($request['code'], 'default');
        $modelName = GeneratorUtils::pluralKebabCase($code);




        $modelNamePluralUcWords = GeneratorUtils::cleanPluralUcWords($model);
        $modelNamePluralKebabCase = GeneratorUtils::pluralKebabCase($code);
        $modelNamePluralLowerCase = GeneratorUtils::cleanPluralLowerCase($model);
        $modelNameSingularLowercase = GeneratorUtils::cleanSingularLowerCase($model);

        $thColums = '';
        $tdColumns = '';
        $totalFields = !empty($request['fields']) ? count($request['fields']) : 0;

        if (!empty($request['fields'][0])) {
            foreach ($request['fields'] as $i => $field) {
                if ($request['input_types'][$i] != 'password') {
                    /**
                     * will generate something like:
                     * <th>{{ __('Price') }}</th>
                     */
                    if ($request['column_types'][$i] != 'foreignId') {
                        $thColums .= "<th>{{ __('" . GeneratorUtils::cleanUcWords($field) . "') }}</th>";
                    }

                    if ($request['input_types'][$i] == 'image') {
                        /**
                         * will generate something like:
                         * {
                         *    data: 'photo',
                         *    name: 'photo',
                         *    orderable: false,
                         *    searchable: false,
                         *    render: function(data, type, full, meta) {
                         *        return `<div class="avatar">
                         *            <img src="${data}" alt="Photo">
                         *        </div>`;
                         *    }
                         * },
                         */

                        $tdColumns .= "{
                    data: \"" . str()->snake($field) . "\",
                    name: \"" . str()->snake($field) . "\",
                    orderable: false,
                    searchable: false,
                    render: function(data, type, full, meta) {
                        return `<div class=\"avatar\">
                            <img src=\"" . '$' . "{data}\" alt=\"" . GeneratorUtils::cleanSingularUcWords($field) . "\">
                        </div>`;
                        }
                    },";
                    } else if ($request['input_types'][$i] == 'file') {
                        /**
                         * will generate something like:
                         * {
                         *    data: 'photo',
                         *    name: 'photo',
                         *    orderable: false,
                         *    searchable: false,
                         *    render: function(data, type, full, meta) {
                         *        return `<div class="avatar">
                         *            <img src="${data}" alt="Photo">
                         *        </div>`;
                         *    }
                         * },
                         */

                        $tdColumns .= "{
                    data: \"" . str()->snake($field) . "\",
                    name: \"" . str()->snake($field) . "\",
                    orderable: false,
                    searchable: false,
                    render: function(data, type, full, meta) {
                        return `<div class=\"avatar\">
                            {data}
                        </div>`;
                        }
                    },";
                    } elseif ($request['column_types'][$i] == 'foreignId') {
                        // remove '/' or sub folders
                        $constrainModel = GeneratorUtils::setModelName($request['constrains'][$i], 'default');

                        $thColums .= "<th>{{ __('" . GeneratorUtils::cleanSingularUcWords($constrainModel) . "') }}</th>";

                        /**
                         * will generate something like:
                         * {
                         *    data: 'user',
                         *    name: 'user.name'
                         * }
                         */
                        $tdColumns .= "{
                    data: \"" . GeneratorUtils::singularSnakeCase($constrainModel) . "\",
                    name: \"" . GeneratorUtils::singularSnakeCase($constrainModel) . "." . GeneratorUtils::getColumnAfterId($constrainModel) . "\"
                },";
                    } else {
                        /**
                         * will generate something like:
                         * {
                         *    data: 'price',
                         *    name: 'price'
                         * }
                         */
                        $tdColumns .= "{
                    data: \"" . str()->snake($field) . "\",
                    name: \"" . str()->snake($field) . "\",
                },";
                    }

                    if ($i + 1 != $totalFields) {
                        // add new line and tab
                        $thColums .= "\n\t\t\t\t\t\t\t\t\t\t\t";
                        $tdColumns .= "\n\t\t\t\t";
                    }
                }
            }
        }

        $modelNameSingularUcWords = GeneratorUtils::cleanSingularUcWords($code);


        $template = str_replace(
            [
                '{{modelNamePluralUcWords}}',
                '{{modelNamePluralKebabCase}}',
                '{{modelNameSingularLowercase}}',
                '{{modelNamePluralLowerCase}}',
                '{{thColumns}}',
                '{{tdColumns}}',
                '{{trHtml}}',
                '{{modelNameSingularUcWords}}',
                '{{code}}',
                '{{code2}}'

            ],
            [
                $modelNamePluralUcWords,
                $modelNamePluralKebabCase,
                $modelNameSingularLowercase,
                $modelNamePluralLowerCase,
                $thColums,
                $tdColumns,
                '',
                $modelNameSingularUcWords,
                $code,
                GeneratorUtils::singularPascalCase($code)

            ],
            GeneratorUtils::getTemplate('views/index')
        );

        switch ($path) {
            case '':
                GeneratorUtils::checkFolder(resource_path("/views/admin/$modelName/include"));

                GeneratorUtils::checkFolder(resource_path("/views/admin/$modelName"));
                file_put_contents(resource_path("/views/admin/$modelName/index.blade.php"), $template);
                file_put_contents(resource_path("/views/admin/$modelName/include/custom.blade.php"), "");
                break;
            default:
                $fullPath = resource_path("/views/admin/" . strtolower($path) . "/$modelName");
                GeneratorUtils::checkFolder($fullPath);
                file_put_contents($fullPath . "/index.blade.php", $template);
                break;
        }
    }

    public function reGenerate($id)
    {
        $module = Module::find($id);
        $model = GeneratorUtils::setModelName($module->name, 'default');
        $path = GeneratorUtils::getModelLocation($module->name);

        $code = GeneratorUtils::setModelName($module->code, 'default');
        $modelName = GeneratorUtils::pluralKebabCase($code);

        $modelNamePluralUcWords = GeneratorUtils::cleanPluralUcWords($model);
        $modelNamePluralKebabCase = GeneratorUtils::pluralKebabCase($code);
        $modelNamePluralLowerCase = GeneratorUtils::cleanPluralLowerCase($model);
        $modelNameSingularLowercase = GeneratorUtils::cleanSingularLowerCase($code);
        $modelNameSingularUcWords = GeneratorUtils::cleanSingularUcWords($code);

        $thColums = '';
        $tfColums = '';
        $tdColumns = '';
        $trhtml = '';
        $agg = '';
        $totalFields = count($module->fields()->where('is_enable', 1)->get());

        foreach ($module->fields()->where('is_enable', 1)->orderBy('sequence', 'asc')->get() as $i => $field) {
            $field->name = GeneratorUtils::singularSnakeCase($field->name);
            $field->code = !empty($field->code) ? GeneratorUtils::singularSnakeCase($field->code) : GeneratorUtils::singularSnakeCase($field->name);
            // dd($field->type);
            if ($field->input != 'password' && $field->type != 'assign') {
                /**
                 * will generate something like:
                 * <th>{{ __('Price') }}</th>
                 */
                if ($field->type != 'foreignId' && $field->type != 'condition'&& $field->type != 'informatic' && $field->type != 'doublefk' && $field->primary != 'lookup' && $field->type != 'fk') {
                    // $thColums .= "<th>{{ __('" . GeneratorUtils::cleanUcWords($field->name) . "') }}</th>";


                    if($field->type_of_calc == 'one')
                    {

                        if($field->first_multi_column == NULL){

                            $fieldNameParts = explode('_', $field->first_column);
                            $lastPart = end($fieldNameParts);

                            if ($lastPart === 'id') {
                                $fkAttr = Attribute::where('code', $field->first_column)
                                                   ->where('module',$id)
                                                    ->first();




                                $const = GeneratorUtils::setModelName((string)$fkAttr->constrain, 'default');


                                $agg .= "'" . GeneratorUtils::singularSnakeCase($const) . "_" . str()->snake($fkAttr->attribute) . "." . $fkAttr->attribute . "'" . ' => App\\Models\\Admin\\' . GeneratorUtils::singularPascalCase($code) . "::get()->" . $field->operation  . '(function ($' . GeneratorUtils::singularSnakeCase($const) . ') {
                                    return $' . GeneratorUtils::singularSnakeCase($const) . '->' . GeneratorUtils::singularSnakeCase($const) . "_" . str()->snake($fkAttr->attribute) . "?->" . str()->snake($fkAttr->attribute) . ';
                                }),';




                            }


                       else{

                        $agg .= "
                         '".$field->first_column ."' => App\\Models\\Admin\\" .GeneratorUtils::singularPascalCase($code) ."::" . $field->operation . "('" . $field->first_column . "'),

                  ";
                       }
                    }


                    else
                    {


                        $thColums .= "<th>{{ __('" . GeneratorUtils::cleanUcWords($field->name) . "') }}</th>";
                        $tfColums .= "<th></th>";
                    }

                    }

                    else{

                        if($field->first_multi_column == NULL){

                        $thColums .= "<th>{{ __('" . GeneratorUtils::cleanUcWords($field->name) . "') }}</th>";
                        $tfColums .= "<th></th>";

                        }

                    }
                }


                if ($field->type == 'multi') {
                    $trhtml .= "
                    $(document).on('change', '.select-cond', function() {

                        var constrain = $(this).data('constrain')
                        var id = $(this).find('option:selected').data('id')
                        $(this).closest('tr').find('.select-base').find('option').hide();
                        console.log('option[data-'+constrain+'='+id+']')
                        $(this).closest('tr').find('.select-base').find('option[data-'+constrain+'='+id+']').show();
                    });


                    $(document).on('click', '#add_new_tr_" . $field->id . "', function() {
                        let table = $('#tbl-field-" . $field->id . " tbody').first();
                        var list_" . $field->id . " = '';

                        let no = table.find('tr').length + 1\n";

                    foreach ($field->multis as $key => $value) {
                        switch ($value->type) {
                            case 'foreignId':
                                case 'condition':

                                $trhtml .= "var list_" . $field->id . $key . " = '<option selected disabled>-- select " . $value->constrain . " -- </option>'
                                \n";
                                $dataIds = '';
                                if (!empty($value->source)) {

                                    $current_model = Module::where(
                                        'code',
                                        GeneratorUtils::singularSnakeCase($value->constrain)
                                    )->orWhere('code', GeneratorUtils::pluralSnakeCase($value->constrain))
                                        ->orWhere('code', $value->constrain)->first();
                                    // dd($current_model);
                                    $lookatrrs = Attribute::where("module", $current_model->id)->where(function ($query) {
                                        $query->where('type', 'foreignId')
                                              ->orWhere('type', 'fk');

                                    })
                                    ->get();

                                    foreach ($lookatrrs as $sa) {
                                        // $dataIds .= "data-" . GeneratorUtils::singularSnakeCase($sa->constrain) . "={{ \$item2->" . $sa->code . "}}";

                                        $dataIds .= "data-" . GeneratorUtils::singularSnakeCase($sa->constrain) . "=\"{{ \$item2->" . $sa->code . " }}\"";
                                    }

                                }

                                $trhtml .= '@php
                                ';

                                $trhtml .= '$model = \App\Models\Module::where(\'code\', App\Generators\GeneratorUtils::singularSnakeCase(\'' . $value->constrain . '\'))->orWhere(\'code\', App\Generators\GeneratorUtils::pluralSnakeCase(\'' . $value->constrain . '\'))?->first();
                                ';
                                $trhtml .= 'if ($model) {
                                    ';
                                $trhtml .= '$for_attr = json_encode($model->fields()->select(\'code\', \'attribute\')->where(\'type\', \'foreignId\')->orWhere(\'type\', \'fk\')->get());
                                ';
                                $trhtml .= '$for_attr = str_replace(\'"\', \'\\\'\', $for_attr);
                                ';
                                $trhtml .= '}
                                ';

                                $trhtml .= '@endphp
                                ';

                                $trhtml .= 'var attr_' . $field->id . $key . ' = "{{ $for_attr }}"
                                ';


                                $trhtml .= '@foreach( \\App\\Models\\Admin\\' . GeneratorUtils::singularPascalCase($value->constrain) . '::all() as $item2 )
                                ';
                                $trhtml .= 'list_' . $field->id . $key . ' += \'<option ' . $dataIds . ' data-id="{{ $item2->id }}"   value="{{ $item2->' . $value->attribute . '}}" >{{ $item2->' . $value->attribute . '}}</option>\'
                                ';
                                $trhtml .= '@endforeach

                                console.log(list_' . $field->id . $key . ')
                                ';

                                break;


                                case 'doubleMulti':

                                    if($value->primary == 'lookup'){

                                        $trhtml .= "var list_" . $field->id . $key . " = '<option selected disabled>-- select " . $value->constrain . " -- </option>'
                                        \n";
                                        $dataIds = '';
                                        if (!empty($value->source)) {

                                            $current_model = Module::where(
                                                'code',
                                                GeneratorUtils::singularSnakeCase($value->constrain)
                                            )->orWhere('code', GeneratorUtils::pluralSnakeCase($value->constrain))
                                                ->orWhere('code', $value->constrain)->first();
                                            // dd($current_model);
                                            $lookatrrs = Attribute::where("module", $current_model->id)->where(function ($query) {
                                                $query->where('type', 'foreignId')
                                                      ->orWhere('type', 'fk');

                                            })
                                            ->get();

                                            foreach ($lookatrrs as $sa) {
                                                // $dataIds .= "data-" . GeneratorUtils::singularSnakeCase($sa->constrain) . "={{ \$item2->" . $sa->code . "}}";

                                                $dataIds .= "data-" . GeneratorUtils::singularSnakeCase($sa->constrain) . "=\"{{ \$item2->" . $sa->code . " }}\"";
                                            }

                                        }

                                        $trhtml .= '@php
                                        ';

                                        $trhtml .= '$model = \App\Models\Module::where(\'code\', App\Generators\GeneratorUtils::singularSnakeCase(\'' . $value->constrain . '\'))->orWhere(\'code\', App\Generators\GeneratorUtils::pluralSnakeCase(\'' . $value->constrain . '\'))?->first();
                                        ';
                                        $trhtml .= 'if ($model) {
                                            ';
                                        $trhtml .= '$for_attr = json_encode($model->fields()->select(\'code\', \'attribute\')->where(\'type\', \'foreignId\')->orWhere(\'type\', \'fk\')->get());
                                        ';
                                        $trhtml .= '$for_attr = str_replace(\'"\', \'\\\'\', $for_attr);
                                        ';
                                        $trhtml .= '}
                                        ';

                                        $trhtml .= '@endphp
                                        ';

                                        $trhtml .= 'var attr_' . $field->id . $key . ' = "{{ $for_attr }}"
                                        ';


                                        $trhtml .= '@foreach( \\App\\Models\\Admin\\' . GeneratorUtils::singularPascalCase($value->constrain) . '::all() as $item2 )
                                        ';
                                        $trhtml .= 'list_' . $field->id . $key . ' += \'<option ' . $dataIds . ' data-id="{{ $item2->id }}"   value="{{ $item2->' . $value->attribute . '}}" >{{ $item2->' . $value->attribute . '}}</option>\'
                                        ';
                                        $trhtml .= '@endforeach

                                        console.log(list_' . $field->id . $key . ')
                                        ';
                                    }
                                    break;
                        }
                    }

                    $trhtml .= "let tr = `";

                    $trhtml .= '<tr draggable="true" containment="tbody" ondragstart="dragStart()" ondragover="dragOver()" style="cursor: move;">';
                    foreach ($field->multis as $key => $value) {
                        switch ($value->type) {

                            case 'text':
                            case 'email':
                            case 'tel':
                            case 'url':
                            case 'search':
                            case 'file':
                            case 'number':
                            case 'date':
                            case 'time':
                                $trhtml .= ' <td>
                                        <div class="input-box">
                                            <input type="' . $value->type . '" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                class="form-control google-input"
                                                placeholder="' . $value->name . '" required>
                                        </div>
                                    </td>
                                    ';
                                break;

                                case 'calc':

                                    if($value->type_of_calc == 'two')
                                    {


                                        $trhtml .= ' <td>
                                        <div class="input-box">
                                            <input type="number" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                class="form-control google-input" data-first="'. $value->first_column .'"  data-second="'. $value->second_column .'" data-operation="'. $value->operation .'"
                                                placeholder="' . $value->name . '" required readonly>
                                        </div>
                                    </td>
                                    ';

                                    }


                                    break;
                                case 'doubleMulti':




                                    if($value->primary == 'lookup')
                                    {

                                        $constrainModel = GeneratorUtils::setModelName($value->constrain, 'default');


                                        if($value->secondary == 'prefix'){

                                            $class = "select-base";


                                            if (empty($value->source) || $value->source == 'disabled') {
                                                $class = 'select-cond';
                                            }



                                            $trhtml .= '<td><div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                              <label class="input-group-text" for="inputGroupSelect01" > ' .  $value->fixed_value . '</label>
                                            </div>';
                                            $trhtml .= '<input type="hidden"  name="' . $field->code . '[${no}][id]" />';

                                            $trhtml .= ' <select  data-source="' . $value->source . '" data-attr="${attr_' . $field->id . $key . '}" data-constrain="' . GeneratorUtils::singularSnakeCase($value->constrain) . '" name="' . $field->code . '[${no}][' . $value->code . ']" class="custom-select select-cond multi-type " required="">';
                                            $trhtml .= '${list_' . $field->id . $key . '}';
                                            $trhtml .= '</select>';
                                            $trhtml .= '</div></td>';

                                        }

                                        if($value->secondary == 'suffix'){



                                            $class = "select-base";


                                            if (empty($value->source) || $value->source == 'disabled') {
                                                $class = 'select-cond';
                                            }



                                            $trhtml .= '<td><div class="input-group mb-3">';
                                            $trhtml .= '<input type="hidden"  name="' . $field->code . '[${no}][id]" />';

                                            $trhtml .= ' <select data-source="' . $value->source . '" data-attr="${attr_' . $field->id . $key . '}" data-constrain="' . GeneratorUtils::singularSnakeCase($value->constrain) . '" name="' . $field->code . '[${no}][' . $value->code . ']" class="custom-select ' . $class . '  multi-type " required="">';
                                            $trhtml .= '${list_' . $field->id . $key . '}';
                                            $trhtml .= '</select>    <div class="input-group-prepend">
                                            <label class="input-group-text" for="inputGroupSelect01"> ' .  $value->fixed_value . '</label>
                                          </div>';
                                            $trhtml .= '</div></td>';


                                        }

                                        if($value->secondary == 'lookprefix'){



                                            $class = "select-base";


                                            if (empty($value->source) || $value->source == 'disabled') {
                                                $class = 'select-cond';
                                            }



                                            $trhtml .= '<td><div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                              <label class="input-group-text inform-multi" data-attr2="' . $value->attribute2  .  '" data-source="' .   $constrainModel .  '" for="inputGroupSelect01"></label>
                                            </div>';
                                            $trhtml .= '<input type="hidden"  name="' . $field->code . '[${no}][id]" />';

                                            $trhtml .= ' <select data-fixmulti="true" data-source="' . $value->source . '" data-attr="${attr_' . $field->id . $key . '}" data-constrain="' . GeneratorUtils::singularSnakeCase($value->constrain) . '" name="' . $field->code . '[${no}][' . $value->code . ']" class="custom-select select-cond multi-type fix-multi " required="">';
                                            $trhtml .= '${list_' . $field->id . $key . '}';
                                            $trhtml .= '</select>';
                                            $trhtml .= '</div></td>';


                                        }

                                        if($value->secondary == 'looksuffix'){



                                            $class = "select-base";


                                            if (empty($value->source) || $value->source == 'disabled') {
                                                $class = 'select-cond';
                                            }



                                            $trhtml .= '<td><div class="input-group mb-3">';
                                            $trhtml .= '<input type="hidden"  name="' . $field->code . '[${no}][id]" />';

                                            $trhtml .= ' <select data-fixmulti="true" data-source="' . $value->source . '" data-attr="${attr_' . $field->id . $key . '}" data-constrain="' . GeneratorUtils::singularSnakeCase($value->constrain) . '" name="' . $field->code . '[${no}][' . $value->code . ']" class="custom-select select-cond multi-type fix-multi " required="">';
                                            $trhtml .= '${list_' . $field->id . $key . '}';
                                            $trhtml .= '</select>  <div class="input-group-prepend">
                                            <label class="input-group-text inform-multi" data-attr2="' . $value->attribute2  .  '" data-source="' .   $constrainModel .  '" for="inputGroupSelect01"></label>
                                          </div>';
                                            $trhtml .= '</div></td>';


                                        }

                                    }

                                    if($value->primary == 'text')
                                    {
                                        if($value->secondary == 'prefix'){

                                            $trhtml .= ' <td>
                                            <label class="input-label" > '. $value->name .' </label>
                                            <div class="input-group mb-3">
                                                 <span class="input-group-text"
                                                    id="inputGroup-sizing-default">
                                                   '. $value->fixed_value . '
                                                </span>
                                                <input type="text" class="form-control" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default"  required>

                                                     </div>
                                        </td>
                                        ';

                                        }

                                        else{

                                            $trhtml .= ' <td>
                                            <label class="input-label" > '. $value->name .' </label>
                                            <div class="input-group mb-3">

                                                <input type="text" class="form-control" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default"  required>

                                                    <span class="input-group-text"
                                                    id="inputGroup-sizing-default">
                                                   '. $value->fixed_value . '
                                                </span>
                                                     </div>
                                        </td>
                                        ';


                                        }
                                    }

                                    if($value->primary == 'integer')
                                    {
                                        if($value->secondary == 'prefix'){

                                            $trhtml .= ' <td>
                                            <label class="input-label" > '. $value->name .' </label>
                                            <div class="input-group mb-3">
                                                 <span class="input-group-text"
                                                    id="inputGroup-sizing-default">
                                                   '. $value->fixed_value . '
                                                </span>
                                                <input type="number" class="form-control" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default"  required>

                                                     </div>
                                        </td>
                                        ';

                                        }

                                        else{

                                            $trhtml .= ' <td>
                                            <label class="input-label" > '. $value->name .' </label>
                                            <div class="input-group mb-3">

                                                <input type="number" class="form-control" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default"  required>

                                                    <span class="input-group-text"
                                                    id="inputGroup-sizing-default">
                                                   '. $value->fixed_value . '
                                                </span>
                                                     </div>
                                        </td>
                                        ';


                                        }
                                    }

                                    if($value->primary == 'decimal')
                                    {
                                        if($value->secondary == 'prefix'){

                                            $trhtml .= ' <td>
                                            <label class="input-label" > '. $value->name .' </label>
                                            <div class="input-group mb-3">
                                                 <span class="input-group-text"
                                                    id="inputGroup-sizing-default">
                                                   '. $value->fixed_value . '
                                                </span>
                                                <input type="number" step="0.000000000000000001" class="form-control" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default"  required>

                                                     </div>
                                        </td>
                                        ';

                                        }

                                        else{

                                            $trhtml .= ' <td>
                                            <label class="input-label" > '. $value->name .' </label>
                                            <div class="input-group mb-3">

                                                <input type="number" step="0.000000000000000001" class="form-control" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default"  required>

                                                    <span class="input-group-text"
                                                    id="inputGroup-sizing-default">
                                                   '. $value->fixed_value . '
                                                </span>
                                                     </div>
                                        </td>
                                        ';


                                        }
                                    }


                                    if($value->primary == 'select')
                                    {
                                        if($value->secondary == 'prefix'){



                                            $arrOption = explode('|', $value->select_options);

                                            $totalOptions = count($arrOption);
                                            $trhtml .= '<td><div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                              <label class="input-group-text" for="inputGroupSelect01"> ' .  $value->fixed_value . '</label>
                                            </div>';
                                            $trhtml .= ' <select name="' . $field->code . '[${no}][' . $value->code . ']" class="custom-select multi-type" required="">';
                                            $trhtml .= '<option selected disabled > -- select --</option>';
                                            foreach ($arrOption as $arrOptionIndex => $value) {
                                                $trhtml .= '<option value="' . $value . '" >' . $value . '</option>';

                                            }
                                            $trhtml .= '</select>';
                                            $trhtml .= '</div></td>';


                                        }

                                        else{



                                            $arrOption = explode('|', $value->select_options);

                                            $totalOptions = count($arrOption);
                                            $trhtml .= '<td><div class="input-group mb-3">';
                                            $trhtml .= ' <select name="' . $field->code . '[${no}][' . $value->code . ']" class="custom-select multi-type" required="">';
                                            $trhtml .= '<option selected disabled > -- select --</option>';
                                            foreach ($arrOption as $arrOptionIndex => $svalue) {
                                                $trhtml .= '<option value="' . $svalue . '" >' . $svalue . '</option>';

                                            }
                                            $trhtml .= '</select>  <div class="input-group-prepend">
                                            <label class="input-group-text" for="inputGroupSelect01"> ' .  $value->fixed_value . '</label>
                                          </div>';
                                            $trhtml .= '</div></td>';


                                        }
                                    }





                                    break;
                            case 'decimal':
                                $trhtml .= ' <td>
                                            <div class="input-box">
                                                <input type="number" step="0.000000000000000001" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    class="form-control google-input"
                                                    placeholder="' . $value->name . '" required>
                                            </div>
                                        </td>
                                        ';
                                break;

                            case 'image':
                                $trhtml .= ' <td>
                                            <div class="input-box">
                                                <input type="file" name="' . $field->code . '[${no}][' . $value->code . ']"
                                                    class="form-control google-input"
                                                    placeholder="' . $value->name . '" required>
                                            </div>
                                        </td>
                                        ';
                                break;

                            case 'textarea':
                                $trhtml .= ' <td>
                                            <div class="input-box">

                                            <textarea name="' . $field->code . '[${no}][' . $value->code . ']"  class="google-input"  placeholder="' . $value->name . '"></textarea>

                                            </div>
                                        </td>
                                        ';
                                break;

                            case 'texteditor':
                                $trhtml .= ' <td>
                                                <div class="input-box">

                                                <textarea name="' . $field->code . '[${no}][' . $value->code . ']"  class="content"  placeholder="' . $value->name . '"></textarea>

                                                </div>
                                            </td>
                                            ';
                                break;

                            case 'range':
                                $trhtml .= '<td>
                                                    <div class="row">
                                                        <div class="col-md-11">
                                                            <div class="input-box">
                                                                <input onmousemove="' . $value->name . '1.value=value" type="range" name="' . $field->code . '[' . $value->code . ']" class="range " min="1" max="1000" >

                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">  <output id="' . $value->name . '1"></output></div>
                                                    </div>
                                                </td>
                                                    ';
                                break;
                            case 'radio':
                                $trhtml .= '<td>
                                    <div class="custom-controls-stacked">
                                    <label class="custom-control custom-radio" for="' . $value->name . '-1">
                                        <input class="custom-control-input" type="radio" name="' . $field->code . '[${no}][' . $value->code . ']" id="' . $value->name . '-1" value="1">
                                        <span class="custom-control-label">True</span>
                                    </label>

                                    <label class="custom-control custom-radio" for="' . $value->name . '-0">
                                        <input class="custom-control-input" type="radio" name="' . $field->code . '[${no}][' . $value->code . ']" id="' . $value->name . '-0" value="0">
                                        <span class="custom-control-label">False</span>
                                    </label>
                                </div>
                                                </td>
                                                            ';
                                break;
                            case 'select':

                                $arrOption = explode('|', $value->select_options);

                                $totalOptions = count($arrOption);
                                $trhtml .= '<td><div class="input-box">';

                                    if($value->unique == 1)
                                    {
                                        $trhtml .= ' <select name="' . $field->code . '[${no}][' . $value->code . ']" class="form-select unique  google-input multi-type" required="">';

                                    }
                                    else{

                                        $trhtml .= ' <select name="' . $field->code . '[${no}][' . $value->code . ']" class="form-select  google-input multi-type" required="">';

                                    }

                                $trhtml .= '<option selected disabled > -- select --</option>';
                                foreach ($arrOption as $arrOptionIndex => $value) {
                                    $trhtml .= '<option value="' . $value . '" >' . $value . '</option>';

                                }
                                $trhtml .= '</select>';
                                $trhtml .= '</div></td>';
                                break;

                            case 'foreignId':
                                case 'condition':
                                $class = "select-base";


                                if (empty($value->source) || $value->source == 'disabled') {
                                    $class = 'select-cond';
                                }



                                $trhtml .= '<td><div class="input-box">';
                                $trhtml .= '<input type="hidden"  name="' . $field->code . '[${no}][id]" />';

                                $trhtml .= ' <select data-source="' . $value->source . '" data-attr="${attr_' . $field->id . $key . '}" data-constrain="' . GeneratorUtils::singularSnakeCase($value->constrain) . '" name="' . $field->code . '[${no}][' . $value->code . ']" class="form-select ' . $class . '  google-input multi-type " required="">';
                                $trhtml .= '${list_' . $field->id . $key . '}';
                                $trhtml .= '</select>';
                                $trhtml .= '</div></td>';
                                break;

                            default:
                                # code...
                                break;
                        }

                    }
                    $trhtml .= '
                    <td>
                        <div class="input-box">

                            <button type="button"
                                class="btn btn-outline-danger btn-xs btn-delete">
                                x
                            </button>
                        </div>
                    </td>
                    </tr>';
                    $trhtml .= "`

                            table.append(tr)

                            table.find('.content:last').richText();
                            reindexTable(table);
                             applyCalcOperations();
                             dynamicCalculation();
                             updateUniqueOptions(table);

                        });\n";
                }


                $subMulti = "<script>" . $trhtml . "</script>";



                if ($module->parent_id > 0) {
                    $parentModule = Module::find($module->parent_id);
                    $parentCode = GeneratorUtils::setModelName($parentModule->code, 'default');
                    $parentModelName = GeneratorUtils::pluralKebabCase($parentCode);

                    File::append(resource_path("/views/admin/$parentModelName/include/multi.blade.php"), $subMulti);

                }



                if ($field->input == 'image') {
                    /**
                     * will generate something like:
                     * {
                     *    data: 'photo',
                     *    name: 'photo',
                     *    orderable: false,
                     *    searchable: false,
                     *    render: function(data, type, full, meta) {
                     *        return `<div class="avatar">
                     *            <img src="${data}" alt="Photo">
                     *        </div>`;
                     *    }
                     * },
                     */

                    $tdColumns .= "{
                    data: \"" . str()->snake($field->code) . "\",
                    name: \"" . str()->snake($field->name) . "\",
                    orderable: false,
                    searchable: false,
                    render: function(data, type, full, meta) {
                        return `<div class=\"avatar\">
                            <img src=\"" . '$' . "{data}\" alt=\"" . GeneratorUtils::cleanSingularUcWords($field->name) . "\">
                        </div>`;
                        }
                    },";
                } elseif ($field->type == 'foreignId'|| $field->type == 'condition' || $field->type == 'informatic' || $field->type == 'doublefk' || $field->primary == 'lookup' || $field->type == 'fk')  {
                    // remove '/' or sub folders
                    $constrainModel = GeneratorUtils::setModelName($field->constrain, 'default');

                    $thColums .= "<th>{{ __('" . GeneratorUtils::cleanSingularUcWords($constrainModel) . "') }}</th>";
                    $tfColums .= "<th></th>";

                    /**
                     * will generate something like:
                     * {
                     *    data: 'user',
                     *    name: 'user.name'
                     * }
                     */
                    $tdColumns .= "{
                    data: \"" . GeneratorUtils::singularSnakeCase($constrainModel). "_" .str()->snake($field->attribute)  . "\",
                    name: \"" . GeneratorUtils::singularSnakeCase($constrainModel). "_" .str()->snake($field->attribute)   . "." . $field->attribute . "\"
                },";


                } else {
                    /**
                     * will generate something like:
                     * {
                     *    data: 'price',
                     *    name: 'price'
                     * }
                     */

                     if($field->type_of_calc != 'one'){


                        if($field->first_multi_column == NULL){

                    $tdColumns .= "{
                    data: \"" . str()->snake($field->code) . "\",
                    name: \"" . str()->snake($field->name) . "\",
                },";

                        }



                     }


                     if($field->first_multi_column != NULL &&  $field->type_of_calc == 'one'){



                    $tdColumns .= "{
                    data: \"" . str()->snake($field->code) . "\",
                    name: \"" . str()->snake($field->name) . "\",
                },";



                     }
                }

                if ($i + 1 != $totalFields) {
                    // add new line and tab
                    $thColums .= "\n\t\t\t\t\t\t\t\t\t\t\t";
                    $tdColumns .= "\n\t\t\t\t";
                }
            }
        }

        // dd($trhtml);
        $template = str_replace(
            [
                '{{modelNamePluralUcWords}}',
                '{{modelNamePluralKebabCase}}',
                '{{modelNameSingularLowercase}}',
                '{{modelNamePluralLowerCase}}',
                '{{thColumns}}',
                '{{tfColumns}}',
                '{{tdColumns}}',
                '{{trHtml}}',
                '{{modelNameSingularUcWords}}',
                '{{code}}',
                '{{code2}}',
                '{{agg}}'

            ],
            [
                $modelNamePluralUcWords,
                $modelNamePluralKebabCase,
                $modelNameSingularLowercase,
                $modelNamePluralLowerCase,
                $thColums,
                $tfColums,
                $tdColumns,
                $trhtml,
                $modelNameSingularUcWords,
                $module->code,
                GeneratorUtils::singularPascalCase($code),
                '@php
                $aggregates = [' .$agg . '];
                @endphp
                '
            ],
            GeneratorUtils::getTemplate('views/index')
        );

        // $modelName = $module->code;

        switch ($path) {
            case '':
                GeneratorUtils::checkFolder(resource_path("/views/admin/$modelName"));
                file_put_contents(resource_path("/views/admin/$modelName/index.blade.php"), $template);
                if (!File::exists(resource_path("/views/admin/$modelName/include/custom.blade.php"))) {
                    GeneratorUtils::checkFolder(resource_path("/views/admin/$modelName/include"));

                    file_put_contents(resource_path("/views/admin/$modelName/include/custom.blade.php"), "");

                }
                break;
            default:
                $fullPath = resource_path("/views/admin/" . strtolower($path) . "/$modelName");
                GeneratorUtils::checkFolder($fullPath);
                file_put_contents($fullPath . "/index.blade.php", $template);
                break;
        }
    }

    /**
     * Method remove
     *
     * @param $id $id
     *
     * @return void
     */
    public function remove($id)
    {
        $crud = Module::find($id);
        $model = GeneratorUtils::setModelName($crud->name, 'default');
        $path = GeneratorUtils::getModelLocation($crud->name);

        $modelNamePluralKebabCase = GeneratorUtils::pluralKebabCase($model);

        switch ($path) {
            case '':
                $fullPath = resource_path("/views/admin/$modelNamePluralKebabCase");
                $this->removeDir($fullPath);
                break;
            default:
                $fullPath = resource_path("/views/admin/" . strtolower($path) . "/$modelNamePluralKebabCase");
                $this->removeDir($fullPath);
                break;
        }
    }

    /**
     * Method removeDir
     *
     * @param string $dir
     *
     * @return void
     */
    protected function removeDir(string $dir): void
    {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator(
            $it,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }
}
