<?php
/**
 * User: Andrey Mistulov
 * Company: Aristos
 * Email: prowebcraft@gmail.com
 * Date: 16.10.2018 20:35
 */

namespace prowebcraft\yii2params;

use function Webmozart\Assert\Tests\StaticAnalysis\string;

trait Params
{

    /**
     * Override a model-level rule set, "Make Params Safe Again" ©
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        foreach ($rules as $k => $rule) {
            if (is_array($rule[0]) && (($params = array_search('params', $rule[0])) !== false)) {
                unset($rule[0][$params]);
                $rules[$k] = $rule;
            }
        }
        return array_merge($rules, [
            [['params'], 'safe'], // not use string
        ]);
    }

    /**
     * Local storage of params data
     * @var array
     */
    protected $paramsArray = null;

    function __set($name, $value)
    {
        if ($name === 'params') {
            return $this->setParams($value);
        }
        parent::__set($name, $value);
    }

    function __get($name)
    {
        if ($name === 'params') {
            return $this->getParams();
        }
        return parent::__get($name);
    }

    /**
     * Initialise params data
     */
    protected function initParams()
    {
        if ($this->paramsArray === null) {
            if (!empty($this->getAttribute('params'))) {
                $this->paramsArray = json_decode($this->getAttribute('params'), true) ?: [];
            } else {
                $this->paramsArray = [];
            }
        }
    }

    /**
     * Get Params JSON
     * @param bool $prettyPrint
     * @return null|string
     */
    public function getParamsAsJsonString($prettyPrint = false)
    {
        $this->initParams();
        $params = $this->paramsArray;
        $flags = $prettyPrint ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE;
        if (empty($params)) {
            return null;
        }

        return is_array($params) || is_object($params) ? json_encode($params, $flags) : (string)$params;
    }

    /**
     * Serialise Params Object
     * @param $insert
     * @return mixed
     */
    public function beforeSave($insert)
    {
        $this->setAttribute('params', $this->getParamsAsJsonString());
        return parent::beforeSave($insert);
    }

    /**
     * Init Object after find
     * @return void
     */
    public function afterFind()
    {
        $this->initParams();
        parent::afterFind();
    }

    /**
     * Check param key exist
     * @param $key
     * @return bool
     */
    public function hasParam($key): bool
    {
        return isset($this->paramsArray[$key]);
    }

    /**
     * Get Parameter by key
     * @param string $key
     * Key to retrieve. You can use dot access like "car.info.age"
     * @return mixed|null
     * Default value
     */
    public function getParam($key, $default = null): mixed
    {
        $this->initParams();
        $array = $this->paramsArray;
        if (isset($this->paramsArray[$key])) {
            return $this->paramsArray[$key];
        }
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (isset($array[$k])) {
                $array = $array[$k];
            } else {
                return $default;
            }
        }
        return $array;
    }

    /**
     * Store a parameter by key
     * @param array|string $key
     * Key to store. You can use dot access like "car.info.age"
     * @param int|float|string|array $value
     * Value to store.
     * @param $merge bool
     * Merge array values
     * @param $recursive bool
     * Merge array value recursively
     * @return $this
     */
    public function setParam(array|string $key, mixed $value, bool $merge = false, bool $recursive = false)
    {
        $this->initParams();
        $target = &$this->paramsArray;
        if (is_string($key)) {
            // Iterate path
            $keys = explode('.', $key);
            foreach ($keys as $k) {
                if (!isset($target[$k]) || !is_array($target[$k])) {
                    $target[$k] = [];
                }
                $target = &$target[$k];
            }
            // Set value to path
        } elseif (is_array($key)) {
            // Iterate array of paths and values
            foreach ($key as $k => $v) {
                $this->setParam($k, $v, $merge, $recursive);
            }
        }
        if ($merge && is_array($value)) {
            $function = $recursive ? 'array_replace_recursive' : 'array_replace';
            $target = $function($target, $value);
        } else {
            $target = $value;
        }
        $this->setAttribute('params', $this->getParamsAsJsonString());
        return $this;
    }

    /**
     * Get params object
     * @return array
     */
    public function getParams()
    {
        $this->initParams();
        return $this->paramsArray;
    }

    /**
     * Set params object
     * @param array $params
     * Params object
     * @param bool $merge
     * Merge array values
     * @param bool $recursive
     * Merge array value recursively
     * @return $this
     */
    public function setParams($params, $merge = true, $recursive = false)
    {
        $this->initParams();
        $function = $recursive ? 'array_replace_recursive' : 'array_replace';
        if (is_array($params)) {
            $this->paramsArray = $merge ? $function($this->paramsArray, $params) : $params;
        }
        $this->setAttribute('params', $this->getParamsAsJsonString());
        return $this;
    }

    /**
     * Unset param by key
     * @param string $key
     * Key to unset. You can use dot access like "car.info.age"
     * @return $this
     */
    public function unsetParam($key)
    {
        if (isset($this->paramsArray[$key])) {
            unset($this->paramsArray[$key]);
            $this->setAttribute('params', $this->getParamsAsJsonString());
        }
        return $this;
    }

    /**
     * Unset params
     * @param $keys array|string
     * Null, Array or comma-separated string of keys to unset. If null - unset all keys.
     * @return $this
     * @throws Exception
     */
    public function unsetParams($keys = null)
    {
        if (empty($keys)) {
            $this->paramsArray = [];
        } else {
            if (is_string($keys)) {
                $keys = explode(',', $keys);
                $keys = array_map(function ($v) {
                    return trim($v);
                }, $keys);
            }
            foreach ($keys as $key) {
                if (isset($this->paramsArray[$key])) {
                    unset($this->paramsArray[$key]);
                }
            }
        }
        $this->setAttribute('params', $this->getParamsAsJsonString());
        return $this;
    }


    /**
     * Add param to array by key
     * @param string $key
     * Key to store. You can use dot access like "car.info.age"
     * @param mixed $value
     * @return $this
     */
    public function addParam($key, $value)
    {
        $this->initParams();
        $array = &$this->paramsArray;
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (!isset($array[$k]) || !is_array($array[$k])) {
                $array[$k] = [];
            }
            $array = &$array[$k];
        }
        // Add value to path
        $array[] = $value;

        $this->setAttribute('params', $this->getParamsAsJsonString());
        return $this;
    }

}
