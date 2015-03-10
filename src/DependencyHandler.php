<?php

namespace DbMockLibrary;

use SimpleArrayLibrary\SimpleArrayLibrary;
use DbMockLibrary\Exceptions\InvalidDependencyException;
use DbMockLibrary\Exceptions\AlreadyInitializedException;
use UnexpectedValueException;

class DependencyHandler extends DataContainer
{
    /**
     * @var array $dependencies
     */
    protected $dependencies;

    /**
     * @param array $initialData
     *
     * @param array $dependencies
     *
     * @return MockLibrary
     * @throws AlreadyInitializedException
     * @throws InvalidDependencyException
     */
    public static function init(array $initialData, array $dependencies = [])
    {
        parent::init($initialData);
        if ($dependencies) {
            static::$instance->validateDependencies($dependencies);
            static::$instance->dependencies = $dependencies;
        }
    }

    /**
     * @param array $wanted
     * @param array $extracted
     *
     * @return array
     */
    protected function extractDependencies(array $wanted, $extracted = [])
    {
        $extracted = empty($extracted) ? [$wanted] : $extracted;
        foreach ($wanted as $dependentCollection => $dependentIds) {
            foreach ($this->dependencies as $dependency) {
                $toAdd = [];
                $onCollection = reset(array_keys($dependency[ON]));
                $onField = reset($dependency[ON]);
                $dependentField = reset($dependency[DEPENDENT]);
                // if dependency exists for the wanted collection
                if (!empty($dependency[DEPENDENT][$dependentCollection])) {
                    $addedFor = [];
                    foreach ($this->data[$onCollection] as $onId => $onRow) {
                        foreach ($dependentIds as $dependentId) {
                            if ($onRow[$onField] == $this->data[$dependentCollection][$dependentId][$dependentField]) {
                                $toAdd[$onId] = true;
                                $addedFor[] = $dependentId;
                            }
                        }
                    }
                    if (!SimpleArrayLibrary::hasAllValues($addedFor, $dependentIds)) {
                        throw new UnexpectedValueException('Dependency missing. Expected: ' . implode(', ', $dependentIds)
                            . ', found: ' . implode(', ', $addedFor)
                            . 'in ' . $dependentCollection . ' on ' . $onCollection . ' dependency'
                        );
                    }
                    $newWanted = [$onCollection => array_keys($toAdd)];
                    $extracted[] = $newWanted;
                    $extracted = $this->extractDependencies($newWanted, $extracted);
                }
            }
        }

        return $extracted;
    }

    /**
     * @param array $extracted
     *
     * @return array
     */
    protected function repackDependencies(array $extracted)
    {
        $return = [];
        for ($i = 0; $i < count($extracted); $i++) {
            // each element has just one key, collection, pointing to the array of ids
            $collection = key($extracted[$i]);
            $return[$collection] = (empty($return[$collection]) ?
                $extracted[$i][$collection] :
                array_merge($return[$collection], $extracted[$i][$collection]));
            $return[$collection]['i'] = empty($return[$collection]['i']) ?
                $i
                : ($return[$collection]['i'] > $i ?
                    $return[$collection]['i'] :
                    $i);
        }

        return $return;
    }

    /**
     * @param array $repacked
     *
     * @return array
     */
    protected function compactDependencies(array $repacked)
    {
        $return = [];
        foreach ($repacked as $collection => $data) {
            $i = $data['i'];
            unset($data['i']);
            $return[$i] = [$collection => array_unique($data)];
        }
        ksort($return);
        $return = array_values($return);
        $return = array_reverse($return);

        return $return;
    }

    /**
     * @param array $wanted
     *
     * @return array
     */
    public function prepareDependencies(array $wanted)
    {
        $return = $this->extractDependencies($wanted);
        $return = $this->repackDependencies($return);
        return $this->compactDependencies($return);
    }

    /**
     * @param array $dependencies
     *
     * @return void
     * @throws InvalidDependencyException
     */
    protected function validateDependencies(array $dependencies)
    {
        foreach ($dependencies as $dependency) {
            $dependentCollection = reset(array_keys($dependency[DEPENDENT]));
            $dependentColumn = $dependency[DEPENDENT][$dependentCollection];
            if (!(isset($this->data[$dependentCollection]) || array_key_exists($dependentCollection, $this->data))) {
                throw new InvalidDependencyException('Collection "' . $dependentCollection . ' does not exist');
            }
            foreach ($this->data[$dependentCollection] as $row) {
                if (!(isset($row[$dependentColumn]) || array_key_exists($dependentColumn, $row))) {
                    throw new InvalidDependencyException('Column "' . $dependentColumn . ' does not exist in a row in a collection "' . $dependentCollection . '"');
                }
            }

            $onCollection = reset(array_keys($dependency[ON]));
            $onColumn = $dependency[ON][$onCollection];
            if (!(isset($this->data[$onCollection]) || array_key_exists($onCollection, $this->data))) {
                throw new InvalidDependencyException('Collection "' . $onCollection . ' does not exist');
            }
            foreach ($this->data[$onCollection] as $row) {
                if (!(isset($row[$onColumn]) || array_key_exists($onColumn, $row))) {
                    throw new InvalidDependencyException('Column "' . $onColumn . ' does not exist in a row in a collection "' . $onCollection . '"');
                }
            }
        }
        foreach ($this->data as $collection => $whatever) {
            $levels = [[$collection]];
            for ($i = 0; $i < count($levels); $i++) {
                $newDependencies = [];
                foreach ($levels[$i] as $collectionToCheck) {
                    foreach ($dependencies as $dependency) {
                        if ($collectionToCheck == ($dependentCollection = reset(array_keys($dependency[DEPENDENT])))) {
                            if ($i != 0 && ($onCollection = reset(array_keys($dependency[ON]))) == $collection) {
                                throw new InvalidDependencyException('Collection: ' . $collection . ' depends on itself via ' . $dependentCollection);
                            } else {
                                $newDependencies[] = $onCollection = reset(array_keys($dependency[ON]));
                            }
                        }
                    }
                }
                if (!empty($newDependencies)) {
                    $levels[$i + 1] = $newDependencies;
                }
            }
        }
    }
}