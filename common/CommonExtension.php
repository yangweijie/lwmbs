<?php

class CommonExtension
{
    use SourceTrait;

    static private ?array $allExtensionDescs = null;

    static public function getAllExtensionDescs(): array
    {
        if (!static::$allExtensionDescs) {
            static::$allExtensionDescs = BuiltinExtensionDesc::getAll();
            static::$allExtensionDescs += ExternExtensionDesc::getAll();
        }
        return static::$allExtensionDescs;
    }

    protected ExtensionType $type;
    protected ExtensionDesc $desc;
    public function __construct(
        protected string $name,
        protected Config $config,
    ) {
        $this->desc = static::getAllExtensionDescs()[$name];
        foreach ($this->desc->getLibDeps() as $name => $optional) {
            $this->addLibraryDependency($name, $optional);
        }
        foreach ($this->desc->getExtDeps() as $name) {
            $this->addExtensionDependency($name);
        }
    }
    public function getType(): ExtensionType
    {
        return $this->type;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function addExtensionDependency(string $name): void
    {
        $depExt = $this->config->getExt($name);
        if (!$depExt) {
            throw new Exception("{$this->name} requires extension $name");
        } else {
            $this->dependencies[] = $depExt;
        }
    }
    public function addLibraryDependency(string $name, bool $optional = false)
    {
        $depLib = $this->config->getLib($name);
        if (!$depLib) {
            if (!$optional) {
                throw new Exception("{$this->name} requires library $name");
            } else {
                Log::i("enabling {$this->name} without $name");
            }
        } else {
            $this->dependencies[] = $depLib;
        }
    }
    public function getLibraryDependencies(bool $recursive = false): array
    {
        $ret = array_filter($this->dependencies, fn($x)=> $x instanceof Library);
        if (!$recursive){
            return $ret;
        }
    
        $added = 1;
        while($added !==0) {
            $added = 0;
            foreach($ret as $dep) {
                foreach ($dep->getDependencies(true) as $depdep){
                    if (!in_array($depdep, $ret, true)) {
                        array_push($ret, $depdep);
                        $added++;
                    }
                }
            }
        }

        return $ret;
    }
}