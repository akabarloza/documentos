<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\CallOperation;
use App\Http\Controllers\Api\DeleteOperation;
use App\Http\Controllers\Api\IndexOperation;
use App\Http\Controllers\Api\StoreOperation;
use App\Http\Controllers\Api\UpdateOperation;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    const PER_PAGE = 500;

    /**
     * /api/users?page=2&filter[]=where,username,=,"david"&fields=username,firstname&include=roles,phone&sort=username
     * 
     * /api/users/create?factory=inactive,admin
     *
     * Factory se lo utiliza para aplicar uno o varios estados de un factory
     * para crear un nuevo modelo.
     *
     * Note que el valor del filtro debe estar en codificacion json.
     *
     */
    public function index(Request $request, ...$route)
    {
        $perPage = empty($request['per_page']) ?
            static::PER_PAGE : $request['per_page'];
        $collection = $this->doSelect(
            null, $route, $request['fields'], $request['include'], $perPage,
            $request['sort'], $request['filter'], $request['raw'],
            $request['factory'] ? explode(',', $request['factory']) : []
        );
        $minutes = 0.1;
        $response = response()->json(
            [
            'data' => $collection
            ], 200,
            [
            'Cache-Control' => 'max-age='.($minutes * 60).', public',
            ]
        );
        $response->setLastModified(new \DateTime('now'));
        $response->setExpires(\Carbon\Carbon::now()->addMinutes($minutes));
        return $response;
    }

    private function doSelect($modelBase, $route, $fields, $include, $perPage, $sort,
                              $filter, $raw=false, array $factoryStates = [])
    {
        $operation = new IndexOperation($route, $modelBase, $factoryStates);
        $result = $operation->index($sort, $filter, $perPage);
        if ($raw) {
            return $result;
        }
        $type = $this->getType($operation->model);
        return $this->packResponse($result, $type, $fields, $include);
    }

    protected function packResponse($result, $type, $requiredFields,
                                    $requiredIncludes, $sparseFields = true)
    {
        if ($result instanceof Model) {
            /* @var $a Model */
            $collection = [
                'type'          => $type,
                'id'            => $result->id,
                'attributes'    => $sparseFields ?
                    $this->sparseFields($requiredFields, $result->toArray()) :
                    $result->toArray(),
                'relationships' => $this->sparseRelationships($requiredFields,
                                                              $requiredIncludes,
                                                              $result),
            ];
        } elseif ($result === null) {
            $collection = null;
        } else {
            $collection = [];
            foreach ($result as $row) {
                $sparcedFields = $sparseFields ?
                        $this->sparseFields($requiredFields, $row instanceof Model ? $row->toArray() : $row) :
                        ($row instanceof Model ? $row->toArray() : $row);
                $collection[] = [
                    'type'          => $type,
                    'id'            => $row->id,
                    'attributes'    => $sparcedFields,
                    'relationships' => $this->sparseRelationships($requiredFields,
                                                                  $requiredIncludes,
                                                                  $row),
                ];
            }
        }
        return $collection;
    }

    public function store(Request $request, ...$route)
    {
        $route0 = $route;
        $data = $request->json("data");
        $call = $request->json("call");
        if ($data) {
            $operation = new StoreOperation($route);
            $result = $operation->store($data);
            if (is_array($result)) {
                $response = $result;
            } else {
                $response = [
                    'data' => [
                        'type'       => $this->getType($result),
                        'id'         => $result->id,
                        'attributes' => $result
                    ]
                ];
            }
        } elseif ($call) {
            $operation = new CallOperation($route);
            $result = $operation->callMethod($call);
            $response = [
                "success"  => true,
                "response" => $result,
            ];
        } else {
            throw new \App\Exceptions\InvalidApiCall("Expected data or call property.");
        }
        return response()->json($response);
    }

    public function update(Request $request, ...$route)
    {
        $data = $request->json("data");
        $operation = new UpdateOperation($route);
        $result = $operation->update($data);
        $response = [
            'data' => [
                'type'       => $this->getType($result),
                'id'         => $result->id,
                'attributes' => $result
            ]
        ];
        return response()->json($response);
    }

    public function delete(...$route)
    {
        $operation = new DeleteOperation($route);
        $operation->delete();
        $response = [];
        return response()->json($response);
    }

    protected function getType($model)
    {
        \Illuminate\Support\Facades\Log::info(is_object($model) ? get_class($model) : gettype($model));
        if (is_array($model)) return isset($model[0]) ? $this->getType ($model[0]) : '';
        $class = is_string($model) ? $model : ($model instanceof Model ? get_class($model)
                        : ($model instanceof \Illuminate\Database\Eloquent\Relations\Relation ? get_class($model->getRelated()):'') );
        if (substr($class, 0, 1) != '\\') $class = '\\'.$class;
        return str_replace('\\', '.', substr($class, 1));
    }

    protected function resolve($routesArray, $method, $data = null,
                               $model = null)
    {
        $routes = $routesArray;
        while ($routes) {
            $route = array_shift($routes);
            if ($route === '' || !is_string($route)) {
                throw new Exception('Invalid route component ('.json_encode($route).') in '.json_encode($routesArray));
            }
            $isZero = $route == '0' || $route === 'create';
            $isNumeric = !$isZero && is_numeric($route);
            $isString = !$isZero && !$isNumeric;
            if ($model === null && $isString) {
                $model = "\App\Models\\".ucfirst($route)."\\".ucfirst(camel_case(str_singular(array_shift($routes))));
            } elseif (is_string($model) && $isZero) {
                $model = new $model();
            } elseif (is_string($model) && $isNumeric) {
                $model = $model::whereId($route)->first();
            } elseif ($model instanceof Model && $isString) {
                $model = $model->$route();
            } elseif ($model instanceof BelongsTo && $isString) {
                $model = $model->$route();
            } elseif ($model instanceof HasOne && $isString) {
                $model = $model->$route();
            } elseif ($model instanceof HasMany && $isZero) {
                $model = $model->newInstance();
            } elseif ($model instanceof HasMany && $isNumeric) {
                $model = $model->whereId($route)->first();
            } elseif ($model instanceof BelongsToMany && $isZero) {
                $model = $model->newInstance();
            } elseif ($model instanceof BelongsToMany && $isNumeric) {
                $model = $model->whereId($route)->first();
            } else {
                throw new Exception('Invalid route component ('.json_encode($route).') in '.json_encode($routesArray));
            }
        }
        if ($data !== null) {
            $res = $method($model, $data);
            if (isset($data['relationships'])) {
                foreach ($data['relationships'] as $rel => $json) {
                    $route1 = $routesArray;
                    $route1[] = $rel;
                    $this->resolve($route1, $method, $json['data'], $res);
                }
            }
            return $res;
        }
        return $method($model);
    }

    /**
     *
     * @param Request $request
     * @param array $row
     * @return array
     */
    protected function sparseFields($requiredFields, $row)
    {
        if (empty($requiredFields)) {
            return $row;
        }
        $fields = explode(",", $requiredFields);
        return array_intersect_key($row, array_flip($fields));
    }

    /**
     * Load the required relationships.
     *
     * @param array $requiredFields
     * @param array $requiredIncludes
     * @param array $row
     *
     * @return array
     */
    protected function sparseRelationships($requiredFields, $requiredIncludes,
                                           $row)
    {
        $relationships = [];
        if (empty($requiredFields) && empty($requiredIncludes)) {
            return [];
        }
        $fields = explode(",", $requiredFields.','.$requiredIncludes);
        foreach ($fields as $field) {
            if ($field && is_callable([$row, $field])) {
                $relationships[$field] = $this->doSelect($row, [$field], '', '', static::PER_PAGE, '', '');
            }
        }
        return $relationships;
    }
}
