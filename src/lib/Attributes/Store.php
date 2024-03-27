<?php
namespace CatPaw\Store\Attributes;

use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Service;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Store\Services\StoreService;
use ReflectionParameter;

#[Attribute]
#[Service]
class Store implements AttributeInterface {
    use CoreAttributeDefinition;
    private StoreService $storeService;
    
    public function __construct(private string $name) {
    }

    #[Entry] public function setup(
        StoreService $storeService
    ) {
        $this->storeService = $storeService;
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        $value = $this->storeService->of($this->name);
    }
}