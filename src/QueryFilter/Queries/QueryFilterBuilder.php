<?php

namespace eloquentFilter\QueryFilter\Queries;

use eloquentFilter\QueryFilter\HelperFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class QueryFilterBuilder.
 */
class QueryFilterBuilder
{
    use HelperFilter;
    /**
     * @var
     */
    protected $builder;

    /**
     * QueryBuilder constructor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param       $field
     * @param array $params
     */
    public function whereBetween($field, array $params)
    {
        $start = $params[0]['start'];
        $end = $params[0]['end'];
        $jdate = $this->convertJdateToG($params[0]);
        if ($jdate) {
            $start = $jdate['start'];
            $end = $jdate['end'];
        }
        $this->builder->whereBetween($field, [$start, $end]);
    }

    /**
     * @param $field
     * @param $value
     */
    public function where($field, $value)
    {
        $this->builder->where("$field", $value);
    }

    /**
     * @param $field
     * @param $params
     */
    public function whereByOpt($field, $params)
    {
        $opt = $params[0]['operator'];
        $value = $params[0]['value'];
        $this->builder->where("$field", "$opt", $value);
    }

    /**
     * @param       $field
     * @param array $params
     */
    public function whereIn($field, array $params)
    {
        foreach ($params as $key => $value) {
            if (is_null($value) || $value == '') {
                unset($params[$key]);
            }
        }
        if (!empty($params)) {
            $this->builder->whereIn("$field", $params);
        }
    }

    /**
     * @param       $field
     * @param array $params
     */
    public function like($field, array $params)
    {
        foreach ($params as $key => $value) {
            if (is_null($value) || $value == '') {
                unset($params[$key]);
            }
        }
    
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $this->builder->where("$field", 'like', '%' . $value['like'] . '%');
            }
        }
    }

    /**
     * @param $limit
     */
    public function limit(int $limit)
    {
        $this->builder->limit($limit);
    }

    /**
     * @param $field
     * @param $type
     */
    public function orderBy(string $field, string $type)
    {
        $this->builder->orderBy($field, $type);
    }

    /**
     * @param string $table
     * @param string $field
     * @param array $params
     */
    public function withRelationship(string $table, string $field, array $params)
    {
        $this->builder->with([$table])
        ->whereHas($table, function($q) use (&$field, &$params) {
            if (!empty($params[0]['start']) && !empty($params[0]['end'])) {
                $start = $params[0]['start'];
                $end = $params[0]['end'];
                $jdate = $this->convertJdateToG($params[0]);
                if ($jdate) {
                    $start = $jdate['start'];
                    $end = $jdate['end'];
                }
                $q->whereBetween($field, [$start, $end]);
            } elseif ($field == 'f_params') {
                foreach ($params as $key => $param) {
                    if (!in_array($key, $this->reserve_param['f_params'])) {
                        throw new \Exception("$key is not in f_params array.");
                    }
                    if (is_array($param)) {
                        $q->$key($param['field'], $param['type']);
                    } else {
                        $q->$key($param);
                    }
                }
            } elseif (!empty($params[0]['operator']) && !empty($params[0]['value'])) {
                $opt = $params[0]['operator'];
                $value = $params[0]['value'];
                $q->where("$field", "$opt", $value);
            } elseif (!empty($params[0]['like'])) {
                foreach ($params as $key => $value) {
                    if (is_null($value) || $value == '') {
                        unset($params[$key]);
                    }
                }
            
                if (!empty($params)) {
                    foreach ($params as $key => $value) {
                        $q->where("$field", 'like', '%' . $value['like'] . '%');
                    }
                }
            } elseif (is_array($params[0])) {
                foreach ($params as $key => $value) {
                    if (is_null($value) || $value == '') {
                        unset($params[$key]);
                    }
                }
                if (!empty($params)) {
                    $q->whereIn("$field", $params);
                }
            } elseif (!empty($params[0])) {
                $q->where("$field", $params[0]);
            }
        });
    }
}
